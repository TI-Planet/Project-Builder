(function (root, factory) {
    const api = factory();
    if (typeof module === "object" && module.exports) {
        module.exports = api;
    }
    root.PBPythonMenu = api;
})(typeof globalThis !== "undefined" ? globalThis : this, function () {
    "use strict";

    const OS_VERSION = "7.0.0.3996";
    const DIRECTIVE_NAMES = Object.freeze([
        "#MENULABEL",
        "#MENUTITLE",
        "#MENUTOP",
        "#MENUFROM",
        "#MENUIMPORT",
        "#MENUITEM",
    ]);
    const DIRECTIVES = new Set(DIRECTIVE_NAMES);
    const ACTION_DIRECTIVES = new Set(["#MENUFROM", "#MENUIMPORT", "#MENUITEM"]);
    const COLORS = Object.freeze({
        BLACK: { expandedSize: 12, css: "black" },
        BLUE: { expandedSize: 9, css: "blue" },
        GREEN: { expandedSize: 10, css: "green" },
        CYAN: { expandedSize: 9, css: "cyan" },
        RED: { expandedSize: 8, css: "red" },
        MAGENTA: { expandedSize: 12, css: "magenta" },
        YELLOW: { expandedSize: 11, css: "yellow" },
        GRAY: { expandedSize: 9, css: "gray" },
        WHITE: { expandedSize: 10, css: "white" },
    });
    const MACRO_NAMES = Object.freeze([
        "TAB", "NL", "ELLIPSIS", "LEFT_ARROW_HEAD", "RIGHT_ARROW_HEAD",
        "UP_ARROW_HEAD", "DOWN_ARROW_HEAD", "ANGLE", "EXCLAM_INVERTED", "CENT_SIGN",
        "POUND_SIGN", "CURRENCY_SIGN", "YEN_SIGN", "BROKEN_BAR", "SECTION_SIGN",
        "COPYRIGHT_SIGN", "FEMININE_ORDINAL", "LEFT_DOUBLE_ANGLE", "NOT_SIGN",
        "REGISTERED_SIGN", "DEGREE_SIGN", "PLUS_MINUS", "SUPERSCRIPT_TWO",
        "SUPERSCRIPT_THREE", "MICRO_SIGN", "PILCROW_SIGN", "MIDDLE_DOT",
        "SUPERSCRIPT_ONE", "MASCULINE_ORDINAL", "RIGHT_DOUBLE_ANGLE",
        "FRACTION_ONE_QUARTER", "FRACTION_ONE_HALF", "FRACTION_THREE_QUARTERS",
        "INVERTED_QUESTION_MARK", "CAP_A_GRAVE", "CAP_A_ACUTE", "CAP_A_CIRCUMFLEX",
        "CAP_A_TILDE", "CAP_A_DIAERESIS", "CAP_A_RING", "CAP_AE", "CAP_C_CEDILLA",
        "CAP_E_GRAVE", "CAP_E_ACUTE", "CAP_E_CIRCUMFLEX", "CAP_E_DIAERESIS",
        "CAP_I_GRAVE", "CAP_I_ACUTE", "CAP_I_CIRCUMFLEX", "CAP_I_DIAERESIS",
        "CAP_ETH", "CAP_N_TILDE", "CAP_O_GRAVE", "CAP_O_ACUTE", "CAP_O_CIRCUMFLEX",
        "CAP_O_TILDE", "CAP_O_DIAERESIS", "MULTIPLY", "CAP_O_STROKE", "CAP_U_GRAVE",
        "CAP_U_ACUTE", "CAP_U_CIRCUMFLEX", "CAP_U_DIAERESIS", "CAP_Y_ACUTE",
        "CAP_THORN", "SHARP_S", "A_GRAVE", "A_ACUTE", "A_CIRCUMFLEX", "A_TILDE",
        "A_DIAERESIS", "A_RING", "AE", "C_CEDILLA", "E_GRAVE", "E_ACUTE",
        "E_CIRCUMFLEX", "E_DIAERESIS", "I_GRAVE", "I_ACUTE", "I_CIRCUMFLEX",
        "I_DIAERESIS", "ETH", "N_TILDE", "O_GRAVE", "O_ACUTE", "O_CIRCUMFLEX",
        "O_TILDE", "O_DIAERESIS", "DIVIDE", "O_STROKE", "U_GRAVE", "U_ACUTE",
        "U_CIRCUMFLEX", "U_DIAERESIS", "Y_ACUTE", "THORN", "Y_DIAERESIS",
    ]);
    const MACROS = new Set(MACRO_NAMES);
    const KNOWN_ASSISTANTS = new Set([0, 10, 15, 34, 35, 47, 50, 53, 56, 57, 61, 63]);
    const MACRO_TEXT = Object.freeze({
        TAB: "  ", NL: "\n", ELLIPSIS: "…", LEFT_ARROW_HEAD: "◀",
        RIGHT_ARROW_HEAD: "▶", UP_ARROW_HEAD: "▲", DOWN_ARROW_HEAD: "▼", ANGLE: "∠",
        EXCLAM_INVERTED: "¡", CENT_SIGN: "¢", POUND_SIGN: "£", CURRENCY_SIGN: "¤",
        YEN_SIGN: "¥", BROKEN_BAR: "¦", SECTION_SIGN: "§", COPYRIGHT_SIGN: "©",
        FEMININE_ORDINAL: "ª", LEFT_DOUBLE_ANGLE: "«", NOT_SIGN: "¬",
        REGISTERED_SIGN: "®", DEGREE_SIGN: "°", PLUS_MINUS: "±", SUPERSCRIPT_TWO: "²",
        SUPERSCRIPT_THREE: "³", MICRO_SIGN: "µ", PILCROW_SIGN: "¶", MIDDLE_DOT: "·",
        SUPERSCRIPT_ONE: "¹", MASCULINE_ORDINAL: "º", RIGHT_DOUBLE_ANGLE: "»",
        FRACTION_ONE_QUARTER: "¼", FRACTION_ONE_HALF: "½", FRACTION_THREE_QUARTERS: "¾",
        INVERTED_QUESTION_MARK: "¿", CAP_A_GRAVE: "À", CAP_A_ACUTE: "Á",
        CAP_A_CIRCUMFLEX: "Â", CAP_A_TILDE: "Ã", CAP_A_DIAERESIS: "Ä", CAP_A_RING: "Å",
        CAP_AE: "Æ", CAP_C_CEDILLA: "Ç", CAP_E_GRAVE: "È", CAP_E_ACUTE: "É",
        CAP_E_CIRCUMFLEX: "Ê", CAP_E_DIAERESIS: "Ë", CAP_I_GRAVE: "Ì", CAP_I_ACUTE: "Í",
        CAP_I_CIRCUMFLEX: "Î", CAP_I_DIAERESIS: "Ä", CAP_ETH: "Ð", CAP_N_TILDE: "Ñ",
        CAP_O_GRAVE: "Ò", CAP_O_ACUTE: "Ó", CAP_O_CIRCUMFLEX: "Ô", CAP_O_TILDE: "Õ",
        CAP_O_DIAERESIS: "Ö", MULTIPLY: "×", CAP_O_STROKE: "Ø", CAP_U_GRAVE: "Ù",
        CAP_U_ACUTE: "Ú", CAP_U_CIRCUMFLEX: "Û", CAP_U_DIAERESIS: "Ü", CAP_Y_ACUTE: "Ý",
        CAP_THORN: "Þ", SHARP_S: "ß", A_GRAVE: "à", A_ACUTE: "á", A_CIRCUMFLEX: "â",
        A_TILDE: "ã", A_DIAERESIS: "ä", A_RING: "å", AE: "æ", C_CEDILLA: "ç",
        E_GRAVE: "è", E_ACUTE: "é", E_CIRCUMFLEX: "ê", E_DIAERESIS: "ë", I_GRAVE: "ì",
        I_ACUTE: "í", I_CIRCUMFLEX: "î", I_DIAERESIS: "ï", ETH: "ð", N_TILDE: "ñ",
        O_GRAVE: "ò", O_ACUTE: "ó", O_CIRCUMFLEX: "ô", O_TILDE: "õ", O_DIAERESIS: "ö",
        DIVIDE: "÷", O_STROKE: "ø", U_GRAVE: "ù", U_ACUTE: "ú", U_CIRCUMFLEX: "û",
        U_DIAERESIS: "ü", Y_ACUTE: "ý", THORN: "þ", Y_DIAERESIS: "ÿ",
    });

    function utf8Size(value) {
        if (typeof TextEncoder !== "undefined") {
            return new TextEncoder().encode(value).length;
        }
        return unescape(encodeURIComponent(value)).length;
    }

    function addDiagnostic(diagnostics, severity, line, column, code, message) {
        diagnostics.push({ severity, line, column, code, message });
    }

    function macroExpandedSize(name) {
        if (name === "NL") return 1;
        if (name === "TAB") return 2;
        if (name === "ELLIPSIS" || name.includes("ARROW_HEAD") || name === "ANGLE") return 3;
        return 2;
    }

    function inspectText(text, line, diagnostics) {
        let expandedSize = 0;
        let hasBlue = false;
        let hasGreen = false;
        let lastColor = null;

        for (let index = 0; index < text.length;) {
            const marker = text[index + 1];
            if (text[index] !== "<" || (marker !== "@" && marker !== "%")) {
                const codePoint = text.codePointAt(index);
                const character = String.fromCodePoint(codePoint);
                expandedSize += utf8Size(character);
                index += character.length;
                continue;
            }

            const end = text.indexOf(">", index + 2);
            if (end < 0) {
                addDiagnostic(diagnostics, "error", line, index + 1, "E_TAG_UNTERMINATED",
                    `unterminated <${marker}...${marker}> tag`);
                expandedSize += utf8Size(text.slice(index));
                break;
            }

            const tag = text.slice(index, end + 1);
            if (utf8Size(tag.slice(0, -1)) > 32) {
                addDiagnostic(diagnostics, "error", line, index + 1, "E_TAG_SIZE",
                    "menu tag is longer than the editor's 32-byte parser buffer");
            }
            if (end === index + 2 || text[end - 1] !== marker) {
                addDiagnostic(diagnostics, "error", line, index + 1, "E_TAG_DELIMITER",
                    `menu tag has mismatched <${marker}...${marker}> delimiters`);
                expandedSize += utf8Size(tag);
                index = end + 1;
                continue;
            }

            const name = text.slice(index + 2, end - 1);
            if (marker === "@") {
                const color = COLORS[name];
                if (!color) {
                    addDiagnostic(diagnostics, "error", line, index + 1, "E_COLOR_UNKNOWN",
                        `unknown Evo menu color tag: ${tag}`);
                } else {
                    expandedSize += color.expandedSize;
                    hasBlue = hasBlue || name === "BLUE";
                    hasGreen = hasGreen || name === "GREEN";
                    lastColor = name;
                }
            } else if (!MACROS.has(name)) {
                addDiagnostic(diagnostics, "error", line, index + 1, "E_MACRO_UNKNOWN",
                    `unknown Evo menu character macro: ${tag}`);
            } else {
                expandedSize += macroExpandedSize(name);
                if (name === "CAP_I_DIAERESIS") {
                    addDiagnostic(diagnostics, "warning", line, index + 1, "W_OS_GLYPH",
                        `<%CAP_I_DIAERESIS%> expands to A-diaeresis on OS ${OS_VERSION}`);
                }
            }
            index = end + 1;
        }

        if (expandedSize > 298) {
            addDiagnostic(diagnostics, "error", line, 1, "E_FIELD_SIZE",
                `expanded menu field is ${expandedSize} bytes; editor limit is 298`);
        }
        return { expandedSize, hasBlue, hasGreen, lastColor };
    }

    function annotationIndex(text) {
        const match = / {2}/.exec(text);
        return match ? match.index : -1;
    }

    function splitDisplay(text) {
        const index = annotationIndex(text);
        if (index < 0) return { left: text, right: "" };
        return {
            left: text.slice(0, index).trimEnd(),
            right: text.slice(index).trimStart(),
        };
    }

    function validateDisplay(text, line, diagnostics, displayLimit) {
        const index = annotationIndex(text);
        const left = index < 0 ? text : text.slice(0, index + 1);
        const leftInfo = inspectText(left, line, diagnostics);
        if (leftInfo.expandedSize > displayLimit) {
            addDiagnostic(diagnostics, "error", line, 1, "E_DISPLAY_SIZE",
                `expanded menu display is ${leftInfo.expandedSize} bytes; limit is ${displayLimit}`);
        }
        if (index < 0) return leftInfo;

        const annotation = text.slice(index).trimStart();
        const annotationInfo = inspectText(annotation, line, diagnostics);
        if (annotationInfo.expandedSize > 71) {
            addDiagnostic(diagnostics, "error", line, index + 1, "E_ANNOTATION_SIZE",
                `expanded right-side annotation is ${annotationInfo.expandedSize} bytes; limit is 71`);
        }
        if (annotationInfo.hasBlue && annotationInfo.lastColor !== "BLACK") {
            addDiagnostic(diagnostics, "error", line, index + 1, "E_ANNOTATION_RESET",
                "blue right-side annotation must end with <@BLACK@>");
        }
        return {
            expandedSize: leftInfo.expandedSize + annotationInfo.expandedSize,
            hasBlue: leftInfo.hasBlue || annotationInfo.hasBlue,
            hasGreen: leftInfo.hasGreen || annotationInfo.hasGreen,
            lastColor: annotationInfo.lastColor || leftInfo.lastColor,
        };
    }

    function parseDecimal(value, maximum, fieldName, line, diagnostics) {
        if (value.length > 5) {
            addDiagnostic(diagnostics, "error", line, 1, "E_NUMBER_SIZE",
                `${fieldName} has more than five decimal digits`);
            return null;
        }
        if (!/^\d+$/.test(value)) {
            addDiagnostic(diagnostics, "error", line, 1, "E_NUMBER_FORMAT",
                `${fieldName} must contain decimal digits only`);
            return null;
        }
        const number = Number(value);
        if (number > maximum) {
            addDiagnostic(diagnostics, "error", line, 1, "E_NUMBER_RANGE",
                `${fieldName} value ${number} exceeds the supported maximum ${maximum}`);
            return null;
        }
        return number;
    }

    function rejectRepeatedSpaces(value, fieldName, line, diagnostics) {
        if (/ {2}/.test(value)) {
            addDiagnostic(diagnostics, "error", line, 1, "E_FIELD_SPACES",
                `${fieldName} contains consecutive physical spaces; the Evo parser treats them as an annotation separator`);
        }
    }

    function actionFields(directive, payload, line, diagnostics) {
        const fields = payload.split("|");
        if (fields.length > 5) {
            addDiagnostic(diagnostics, "error", line, 1, "E_FIELD_COUNT",
                "too many pipe-separated menu fields");
        }
        fields.forEach((field, index) => {
            if (field.length === 0) {
                addDiagnostic(diagnostics, "error", line, 1, "E_FIELD_EMPTY",
                    `menu field ${index + 1} is empty`);
            }
        });

        const maximumFields = directive === "#MENUITEM" ? 5 : 3;
        if (fields.length > maximumFields) {
            addDiagnostic(diagnostics, "error", line, 1, "E_FIELD_COUNT",
                `directive accepts at most ${maximumFields} fields`);
        }

        let displayInfo = { hasGreen: false };
        if (fields[0]) displayInfo = validateDisplay(fields[0], line, diagnostics, 255);
        if (fields[1]) {
            rejectRepeatedSpaces(fields[1], "insertion template", line, diagnostics);
            const insertionInfo = inspectText(fields[1], line, diagnostics);
            if (insertionInfo.expandedSize > 63) {
                addDiagnostic(diagnostics, "error", line, 1, "E_INSERT_SIZE",
                    `expanded insertion template is ${insertionInfo.expandedSize} bytes; limit is 63`);
            }
        }

        let cursor = 0;
        if (fields[2]) {
            rejectRepeatedSpaces(fields[2], "cursor field", line, diagnostics);
            cursor = parseDecimal(fields[2], 255, "cursor", line, diagnostics);
        }

        let assistant = 0;
        if (fields[3]) {
            rejectRepeatedSpaces(fields[3], "assistant field", line, diagnostics);
            assistant = parseDecimal(fields[3], 63, "assistant", line, diagnostics);
            if (assistant !== null && !KNOWN_ASSISTANTS.has(assistant)) {
                addDiagnostic(diagnostics, "warning", line, 1, "W_ASSISTANT_UNKNOWN",
                    `assistant ID ${assistant} is not used by known OS ${OS_VERSION} module definitions`);
            }
            if (assistant !== null && assistant !== 0 && !displayInfo.hasGreen) {
                addDiagnostic(diagnostics, "warning", line, 1, "W_ASSISTANT_MARKER",
                    `assistant ID ${assistant} has no <@GREEN@> marker used by native wizard entries`);
            }
        }

        if (fields[4]) {
            const helpInfo = inspectText(fields[4], line, diagnostics);
            if (helpInfo.expandedSize > 63) {
                addDiagnostic(diagnostics, "error", line, 1, "E_HELP_SIZE",
                    `expanded help field is ${helpInfo.expandedSize} bytes; limit is 63`);
            }
        }
        return { fields, cursor: cursor === null ? 0 : cursor, assistant: assistant === null ? 0 : assistant };
    }

    function makeItem(directive, parsedFields, line) {
        const fields = parsedFields.fields;
        const display = splitDisplay(fields[0] || "");
        return {
            directive,
            line,
            displayRaw: display.left,
            annotationRaw: display.right,
            insertionRaw: fields[1] || "",
            cursor: parsedFields.cursor,
            assistant: parsedFields.assistant,
            helpRaw: fields[4] || "",
        };
    }

    function parseMenu(source) {
        const diagnostics = [];
        const groups = [];
        const lines = source.split(/\r\n|\r|\n/);
        const byteSize = utf8Size(source);

        if (source.includes("\0")) {
            addDiagnostic(diagnostics, "error", 1, 1, "E_FILE_NUL",
                "menu definitions contain an embedded NUL byte");
        }
        if (source.charCodeAt(0) === 0xfeff) {
            addDiagnostic(diagnostics, "error", 1, 1, "E_FILE_BOM",
                "UTF-8 BOM is not accepted by the Evo menu parser");
        }
        if (byteSize > 0xffff) {
            addDiagnostic(diagnostics, "error", 1, 1, "E_FILE_SIZE",
                `menu definition is ${byteSize} bytes; the Evo editor uses 16-bit offsets`);
        }

        let haveLabel = false;
        let haveTitle = false;
        let pageHasItem = false;
        let haveFrom = false;
        let haveImport = false;
        let haveRegularItem = false;
        let pageCount = 0;
        let currentGroup = null;
        let currentPage = null;

        lines.forEach((lineText, zeroBasedLine) => {
            const line = zeroBasedLine + 1;
            if (!lineText.length) return;
            if (lineText.includes("\t")) {
                addDiagnostic(diagnostics, "warning", line, lineText.indexOf("\t") + 1, "W_TAB_LITERAL",
                    "physical tab byte is copied literally; use <%TAB%> for TI's two-space macro");
            }
            if (lineText[0] !== "#") {
                addDiagnostic(diagnostics, "error", line, 1, "E_DIRECTIVE_START",
                    "nonblank menu line must begin with a directive");
                return;
            }

            const separator = lineText.indexOf(" ");
            if (separator < 0) {
                addDiagnostic(diagnostics, "error", line, 1, "E_DIRECTIVE_SPACE",
                    "directive must be followed by one ASCII space and a payload");
                return;
            }
            const directive = lineText.slice(0, separator);
            const payload = lineText.slice(separator + 1);
            if (!payload.length) {
                addDiagnostic(diagnostics, "error", line, separator + 2, "E_PAYLOAD_EMPTY",
                    "directive payload is empty");
                return;
            }
            if (payload[0] === " ") {
                addDiagnostic(diagnostics, "error", line, separator + 2, "E_DIRECTIVE_SPACING",
                    "use exactly one space after the directive name");
            }
            if (!DIRECTIVES.has(directive)) {
                addDiagnostic(diagnostics, "error", line, 1, "E_DIRECTIVE_UNKNOWN",
                    `unknown Evo menu directive: ${directive}`);
                return;
            }

            if (directive === "#MENULABEL") {
                if (haveLabel && (!haveTitle || !pageHasItem)) {
                    addDiagnostic(diagnostics, "error", line, 1, "E_LABEL_INCOMPLETE",
                        "previous menu label has an empty or incomplete page");
                }
                haveLabel = true;
                haveTitle = false;
                pageHasItem = false;
                haveFrom = false;
                haveImport = false;
                haveRegularItem = false;
                pageCount = 0;
                validateDisplay(payload, line, diagnostics, 255);
                currentGroup = { line, labelRaw: payload, pages: [], imports: [] };
                groups.push(currentGroup);
                currentPage = null;
                return;
            }

            if (directive === "#MENUTITLE") {
                if (!haveLabel) {
                    addDiagnostic(diagnostics, "error", line, 1, "E_TITLE_ORDER",
                        "#MENUTITLE must follow #MENULABEL");
                }
                if (haveTitle) {
                    addDiagnostic(diagnostics, "error", line, 1, "E_TITLE_REPEAT",
                        "use #MENUTOP for pages after the first title");
                }
                const titleInfo = inspectText(payload, line, diagnostics);
                if (titleInfo.expandedSize > 249) {
                    addDiagnostic(diagnostics, "error", line, 1, "E_TITLE_SIZE",
                        `expanded menu title is ${titleInfo.expandedSize} bytes; limit is 249`);
                }
                haveTitle = true;
                pageCount = 1;
                currentPage = { line, titleRaw: payload, items: [] };
                if (currentGroup) currentGroup.pages.push(currentPage);
                return;
            }

            if (directive === "#MENUTOP") {
                if (!haveTitle || !pageHasItem) {
                    addDiagnostic(diagnostics, "error", line, 1, "E_PAGE_ORDER",
                        "#MENUTOP requires a nonempty preceding page");
                }
                pageCount += 1;
                if (pageCount > 5) {
                    addDiagnostic(diagnostics, "error", line, 1, "E_PAGE_COUNT",
                        "menu has more than five pages");
                }
                const titleInfo = inspectText(payload, line, diagnostics);
                if (titleInfo.expandedSize > 249) {
                    addDiagnostic(diagnostics, "error", line, 1, "E_TITLE_SIZE",
                        `expanded menu title is ${titleInfo.expandedSize} bytes; limit is 249`);
                }
                pageHasItem = false;
                currentPage = { line, titleRaw: payload, items: [] };
                if (currentGroup) currentGroup.pages.push(currentPage);
                return;
            }

            if (ACTION_DIRECTIVES.has(directive)) {
                const parsedFields = actionFields(directive, payload, line, diagnostics);
                const item = makeItem(directive, parsedFields, line);
                if (directive === "#MENUFROM" || directive === "#MENUIMPORT") {
                    if (!haveLabel || pageCount > 1 || haveRegularItem) {
                        addDiagnostic(diagnostics, "error", line, 1, "E_IMPORT_ORDER",
                            "import directives must follow the label and precede items");
                    }
                    if ((directive === "#MENUFROM" && haveFrom) ||
                        (directive === "#MENUIMPORT" && haveImport)) {
                        addDiagnostic(diagnostics, "error", line, 1, "E_IMPORT_REPEAT",
                            "duplicate import directive");
                    }
                    if (directive === "#MENUFROM") haveFrom = true;
                    if (directive === "#MENUIMPORT") haveImport = true;
                    if (currentGroup) currentGroup.imports.push(item);
                    return;
                }

                if (!haveTitle) {
                    addDiagnostic(diagnostics, "error", line, 1, "E_ITEM_ORDER",
                        "#MENUITEM must follow #MENUTITLE or #MENUTOP");
                }
                pageHasItem = true;
                haveRegularItem = true;
                if (currentPage) currentPage.items.push(item);
            }
        });

        if (!haveLabel) {
            addDiagnostic(diagnostics, "error", 1, 1, "E_LABEL_MISSING",
                "menu definition has no #MENULABEL");
        }
        if (!haveTitle) {
            addDiagnostic(diagnostics, "error", lines.length, 1, "E_TITLE_MISSING",
                "final menu label has no #MENUTITLE");
        }
        if (!pageHasItem) {
            addDiagnostic(diagnostics, "error", lines.length, 1, "E_ITEM_MISSING",
                "final menu page has no #MENUITEM");
        }

        const severityOrder = { error: 0, warning: 1, info: 2 };
        diagnostics.sort((a, b) => severityOrder[a.severity] - severityOrder[b.severity] || a.line - b.line || a.column - b.column);
        const pageTotal = groups.reduce((total, group) => total + group.pages.length, 0);
        return {
            source,
            diagnostics,
            groups,
            stats: {
                bytes: byteSize,
                lines: lines.length,
                pages: pageTotal,
                errors: diagnostics.filter((item) => item.severity === "error").length,
                warnings: diagnostics.filter((item) => item.severity === "warning").length,
            },
        };
    }

    function tokenizeSource(source) {
        const ranges = [];
        let lineStart = 0;

        function add(start, end, type) {
            if (end > start) ranges.push({ start, end, type });
        }

        while (lineStart <= source.length) {
            const newline = source.indexOf("\n", lineStart);
            const lineEnd = newline < 0 ? source.length : newline;
            const line = source.slice(lineStart, lineEnd).replace(/\r$/, "");
            const directiveMatch = /^#[A-Za-z_]*/.exec(line);
            const directive = directiveMatch ? directiveMatch[0] : "";
            if (directiveMatch) {
                add(lineStart, lineStart + directive.length,
                    DIRECTIVES.has(directive) ? "directive" : "directive-unknown");
            }

            const tagPattern = /<([@%])[^>\r\n]*>/g;
            let tagMatch;
            while ((tagMatch = tagPattern.exec(line)) !== null) {
                const raw = tagMatch[0];
                const marker = tagMatch[1];
                const validDelimiter = raw.length >= 4 && raw[raw.length - 2] === marker;
                const name = validDelimiter ? raw.slice(2, -2) : "";
                let type = "tag-unknown";
                if (marker === "@" && validDelimiter && COLORS[name]) type = "color-tag";
                if (marker === "%" && validDelimiter && MACROS.has(name)) type = "macro-tag";
                add(lineStart + tagMatch.index, lineStart + tagMatch.index + raw.length, type);
            }

            for (let index = 0; index < line.length; ++index) {
                if (line[index] === "|") add(lineStart + index, lineStart + index + 1, "separator");
            }

            if (ACTION_DIRECTIVES.has(directive)) {
                const separator = line.indexOf(" ");
                if (separator >= 0) {
                    const payloadStart = separator + 1;
                    const fields = line.slice(payloadStart).split("|");
                    let fieldStart = payloadStart;
                    fields.forEach((field, index) => {
                        if ((index === 2 || index === 3) && /^\d+$/.test(field)) {
                            add(lineStart + fieldStart, lineStart + fieldStart + field.length,
                                index === 2 ? "cursor-number" : "assistant-number");
                        }
                        fieldStart += field.length + 1;
                    });
                }
            }

            if (newline < 0) break;
            lineStart = newline + 1;
            if (lineStart > source.length) break;
        }

        ranges.sort((a, b) => a.start - b.start || b.end - a.end);
        const tokens = [];
        let offset = 0;
        for (const range of ranges) {
            if (range.start < offset) continue;
            if (range.start > offset) {
                tokens.push({ type: "text", text: source.slice(offset, range.start), start: offset, end: range.start });
            }
            tokens.push({
                type: range.type,
                text: source.slice(range.start, range.end),
                start: range.start,
                end: range.end,
            });
            offset = range.end;
        }
        if (offset < source.length || !tokens.length) {
            tokens.push({ type: "text", text: source.slice(offset), start: offset, end: source.length });
        }
        return tokens;
    }

    function completionItems(kind, query) {
        const normalized = query.toUpperCase();
        if (kind === "directive") {
            return DIRECTIVE_NAMES
                .filter((name) => name.startsWith(normalized))
                .map((name) => ({
                    label: name,
                    insertText: `${name} `,
                    kind,
                    detail: "directive",
                }));
        }
        if (kind === "color") {
            return Object.keys(COLORS)
                .filter((name) => name.startsWith(normalized))
                .map((name) => ({
                    label: `<@${name}@>`,
                    insertText: `<@${name}@>`,
                    kind,
                    detail: "color tag",
                }));
        }
        return MACRO_NAMES
            .filter((name) => name.startsWith(normalized))
            .map((name) => ({
                label: `<%${name}%>`,
                insertText: `<%${name}%>`,
                kind: "macro",
                detail: "character macro",
            }));
    }

    function getCompletions(source, caret, force) {
        const safeCaret = Math.max(0, Math.min(Number.isFinite(caret) ? caret : 0, source.length));
        const lineStart = safeCaret === 0 ? 0 : source.lastIndexOf("\n", safeCaret - 1) + 1;
        const before = source.slice(lineStart, safeCaret).replace(/^\r/, "");
        const colorMatch = /<@([A-Za-z_]*)$/.exec(before);
        if (colorMatch) {
            const from = safeCaret - colorMatch[0].length;
            return { context: "color", from, to: safeCaret, items: completionItems("color", colorMatch[1]) };
        }
        const macroMatch = /<%([A-Za-z_]*)$/.exec(before);
        if (macroMatch) {
            const from = safeCaret - macroMatch[0].length;
            return { context: "macro", from, to: safeCaret, items: completionItems("macro", macroMatch[1]) };
        }
        if (/^#[A-Za-z_]*$/.test(before)) {
            return {
                context: "directive",
                from: lineStart,
                to: safeCaret,
                items: completionItems("directive", before),
            };
        }
        const lineEndIndex = source.indexOf("\n", safeCaret);
        const lineEnd = lineEndIndex < 0 ? source.length : lineEndIndex;
        if (force && source.slice(lineStart, lineEnd).trim() === "") {
            return {
                context: "directive",
                from: lineStart,
                to: safeCaret,
                items: completionItems("directive", ""),
            };
        }
        return null;
    }

    function tokenizeText(value) {
        const tokens = [];
        let color = "black";
        let index = 0;
        let buffer = "";

        function flush() {
            if (buffer) {
                tokens.push({ text: buffer, color });
                buffer = "";
            }
        }

        while (index < value.length) {
            const marker = value[index + 1];
            if (value[index] === "<" && (marker === "@" || marker === "%")) {
                const end = value.indexOf(">", index + 2);
                if (end >= 0 && value[end - 1] === marker) {
                    const name = value.slice(index + 2, end - 1);
                    if (marker === "@" && COLORS[name]) {
                        flush();
                        color = COLORS[name].css;
                        index = end + 1;
                        continue;
                    }
                    if (marker === "%" && MACROS.has(name)) {
                        buffer += MACRO_TEXT[name] || "?";
                        index = end + 1;
                        continue;
                    }
                }
            }
            const codePoint = value.codePointAt(index);
            const character = String.fromCodePoint(codePoint);
            buffer += character;
            index += character.length;
        }
        flush();
        return tokens;
    }

    function plainText(value) {
        return tokenizeText(value).map((token) => token.text).join("");
    }

    // Canonical authoring form. Do not use this to rewrite an imported/raw file:
    // unknown directives and invalid fields cannot be represented by the parsed model.
    function serializeMenu(groups) {
        function action(item) {
            const display = String(item.displayRaw || "")
                + (item.annotationRaw ? "  " + item.annotationRaw : "");
            const fields = [display];
            const cursor = item.cursor === undefined ? 0 : item.cursor;
            const assistant = item.assistant === undefined ? 0 : item.assistant;
            const regular = item.directive === "#MENUITEM";
            if (item.insertionRaw || cursor || (regular && (assistant || item.helpRaw))) {
                fields.push(String(item.insertionRaw || ""));
                if (cursor || (regular && (assistant || item.helpRaw))) fields.push(String(cursor));
                if (regular && (assistant || item.helpRaw)) fields.push(String(assistant));
                if (regular && item.helpRaw) fields.push(String(item.helpRaw));
            }
            return item.directive + " " + fields.join("|");
        }
        return groups.map((group) => {
            const lines = ["#MENULABEL " + group.labelRaw];
            for (const item of group.imports) lines.push(action(item));
            group.pages.forEach((page, index) => {
                lines.push((index ? "#MENUTOP " : "#MENUTITLE ") + page.titleRaw);
                for (const item of page.items) lines.push(action(item));
            });
            return lines.join("\n");
        }).join("\n\n") + (groups.length ? "\n" : "");
    }

    return Object.freeze({
        serializeMenu,
        OS_VERSION,
        DIRECTIVE_NAMES,
        COLORS,
        MACRO_NAMES,
        KNOWN_ASSISTANTS,
        parseMenu,
        tokenizeSource,
        getCompletions,
        tokenizeText,
        plainText,
        splitDisplay,
        utf8Size,
    });
});
