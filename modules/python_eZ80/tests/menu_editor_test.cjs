#!/usr/bin/env node
"use strict";

const assert = require("node:assert/strict");
const menu = require("../js/python_menu.js");

function diagnostics(source, severity) {
    return menu.parseMenu(source).diagnostics.filter((item) => !severity || item.severity === severity);
}

const valid = [
    "#MENULABEL demo<%ELLIPSIS%>",
    "#MENUFROM from demo import *|from demo import *<%NL%>|0",
    "#MENUTITLE Drive",
    "#MENUITEM left(<@BLUE@>angle<@BLACK@>)<@BLUE@>  degrees<@BLACK@>|left()|1",
    "#MENUITEM right(<@BLUE@>angle<@BLACK@>)  <@BLUE@>degrees<@BLACK@>|right()|1",
    "#MENUTOP Control",
    "#MENUITEM set_pen(<@BLUE@>size<@BLACK@>) <@GREEN@><%RIGHT_ARROW_HEAD%>|set_pen(\"\",\"\")|5|15",
    "",
].join("\n");

const parsed = menu.parseMenu(valid);
assert.equal(parsed.stats.errors, 0);
assert.equal(parsed.stats.warnings, 0);
assert.equal(parsed.stats.pages, 2);
assert.equal(parsed.groups.length, 1);
assert.equal(parsed.groups[0].imports.length, 1);
assert.equal(parsed.groups[0].pages[0].items.length, 2);
assert.equal(parsed.groups[0].pages[1].items[0].assistant, 15);

const badAnnotation = [
    "#MENULABEL demo",
    "#MENUTITLE Drive",
    "#MENUITEM left(<@BLUE@>angle<@BLACK@>)  <@BLUE@>degrees|left()|1",
].join("\n");
assert.ok(diagnostics(badAnnotation, "error").some((item) => item.code === "E_ANNOTATION_RESET"));

const unknownAssistant = [
    "#MENULABEL demo",
    "#MENUTITLE Commands",
    "#MENUITEM choose()|choose()|0|42",
].join("\n");
const assistantWarnings = diagnostics(unknownAssistant, "warning");
assert.equal(assistantWarnings.length, 2);
assert.ok(assistantWarnings.some((item) => item.code === "W_ASSISTANT_UNKNOWN"));
assert.ok(assistantWarnings.some((item) => item.code === "W_ASSISTANT_MARKER"));

const malformed = [
    "#MENULABEL demo",
    "#MENUTITLE Commands",
    "#MENUITEM Drive|from demo import *<%NL%>|Drive|",
].join("\n");
const malformedCodes = new Set(diagnostics(malformed, "error").map((item) => item.code));
assert.ok(malformedCodes.has("E_FIELD_EMPTY"));
assert.ok(malformedCodes.has("E_NUMBER_FORMAT"));

assert.equal(menu.plainText("wait<%ELLIPSIS%><@BLUE@>go<@BLACK@>"), "wait…go");
assert.equal(menu.plainText("a<%TAB%>b<%NL%>c"), "a  b\nc");
assert.deepEqual(menu.splitDisplay("turn()     <@BLUE@>degrees<@BLACK@>"), {
    left: "turn()",
    right: "<@BLUE@>degrees<@BLACK@>",
});

const syntaxSource = "#MENUITEM set_pen(<@BLUE@>size<@BLACK@>) <@GREEN@><%RIGHT_ARROW_HEAD%>|set_pen(\"\",\"\")|5|15";
const syntaxTokens = menu.tokenizeSource(syntaxSource).filter((token) => token.type !== "text");
assert.deepEqual(syntaxTokens.map((token) => [token.type, token.text]), [
    ["directive", "#MENUITEM"],
    ["color-tag", "<@BLUE@>"],
    ["color-tag", "<@BLACK@>"],
    ["color-tag", "<@GREEN@>"],
    ["macro-tag", "<%RIGHT_ARROW_HEAD%>"],
    ["separator", "|"],
    ["separator", "|"],
    ["cursor-number", "5"],
    ["separator", "|"],
    ["assistant-number", "15"],
]);
assert.equal(menu.tokenizeSource("#NOPE value")[0].type, "directive-unknown");
assert.equal(menu.tokenizeSource("#MENULABEL <@NOPE@>").find((token) => token.text === "<@NOPE@>").type, "tag-unknown");

const directiveCompletion = menu.getCompletions("#MENU", 5, false);
assert.deepEqual([directiveCompletion.context, directiveCompletion.from, directiveCompletion.to], ["directive", 0, 5]);
assert.ok(directiveCompletion.items.some((item) => item.label === "#MENUITEM" && item.insertText === "#MENUITEM "));

const colorInput = "#MENUITEM name(<@BL";
const colorCompletion = menu.getCompletions(colorInput, colorInput.length, false);
assert.deepEqual([colorCompletion.context, colorCompletion.from, colorCompletion.to],
    ["color", colorInput.indexOf("<@"), colorInput.length]);
assert.deepEqual(colorCompletion.items.map((item) => item.label), ["<@BLACK@>", "<@BLUE@>"]);

const macroInput = "#MENULABEL demo<%RIGHT_A";
const macroCompletion = menu.getCompletions(macroInput, macroInput.length, false);
assert.equal(macroCompletion.context, "macro");
assert.ok(macroCompletion.items.some((item) => item.label === "<%RIGHT_ARROW_HEAD%>"));
assert.equal(macroCompletion.items.every((item) => item.label.startsWith("<%RIGHT_A")), true);

const forcedCompletion = menu.getCompletions("#MENULABEL demo\n\n#MENUTITLE Demo", 16, true);
assert.deepEqual([forcedCompletion.context, forcedCompletion.from, forcedCompletion.to], ["directive", 16, 16]);
assert.equal(forcedCompletion.items.length, menu.DIRECTIVE_NAMES.length);
assert.deepEqual(menu.getCompletions("\n#MENULABEL demo", 0, true).from, 0);
assert.equal(menu.getCompletions("#MENULABEL demo", 15, false), null);

// Project Builder preserves parsed menu semantics when the user makes a GUI edit.
const semanticModel = groups => JSON.parse(JSON.stringify(groups, (key, value) => key === "line" ? undefined : value));
const canonical = menu.serializeMenu(parsed.groups);
const reparsed = menu.parseMenu(canonical);
assert.equal(reparsed.stats.errors, 0);
assert.deepEqual(semanticModel(reparsed.groups), semanticModel(parsed.groups));
assert.equal(menu.serializeMenu(reparsed.groups), canonical);
assert.ok(canonical.includes("<@BLUE@>"));
assert.ok(canonical.includes("<%NL%>"));
assert.ok(canonical.indexOf("#MENUFROM") < canonical.indexOf("#MENUTITLE"));

const additionalGroup = menu.parseMenu([
    "#MENULABEL second",
    "#MENUTITLE More",
    "#MENUIMPORT import second|import second<%NL%>|0",
    "#MENUITEM <@GRAY@>Information<@BLACK@>",
    "#MENUITEM command|command()|1|0|Some help",
].join("\n"));
assert.equal(additionalGroup.stats.errors, 0);
const combined = [...parsed.groups, ...additionalGroup.groups];
const roundTrip = menu.parseMenu(menu.serializeMenu(combined));
assert.equal(roundTrip.stats.errors, 0);
assert.deepEqual(semanticModel(roundTrip.groups), semanticModel(combined));
assert.equal(menu.serializeMenu([]), "");

const literal = '#MENULABEL <img src=x>\r\n#MENUTITLE Literal\r\n#MENUITEM a<b & c> d|print("<tag>")|0\r\n';
assert.equal(menu.parseMenu(literal).source, literal);
assert.equal(menu.parseMenu(literal).stats.errors, 0);
const unknown = '#UNKNOWN preserve exactly\r\n';
assert.equal(menu.parseMenu(unknown).source, unknown);
assert.ok(menu.parseMenu(unknown).diagnostics.some(d => d.code === 'E_DIRECTIVE_UNKNOWN'));

console.log("Python menu parser and serializer tests passed");
