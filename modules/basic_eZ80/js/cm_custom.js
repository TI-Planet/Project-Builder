/*
 * Part of TI-Planet's Project Builder
 * (C) Adrien "Adriweb" Bertrand
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

/* CodeMirror custom stuff, events... */

function do_cm_custom()
{
    let widgets = [];

    const clearWidgets = function()
    {
        widgets.forEach((widget) => {
            editor.removeLineWidget(widget);
        });
        widgets = [];
    };

    editor.removeKeyMap("Ctrl-D");

    const dupLine = (cm) => {
        const doc = cm.getDoc();
        const cursor = doc.getCursor();
        const line = doc.getLine(cursor.line);
        const pos = {
            line: cursor.line,
            ch: line.length
        };
        doc.replaceRange(`\n${line}`, pos);
    };

    const deleteLine  = (cm) => { cm.execCommand("deleteLine"); };
    const UnIndent    = (cm) => { cm.indentSelection("subtract"); };
    const TabOrIndent = (cm) => {
        if (cm.somethingSelected())
        {
            const sel = editor.getSelection("\n");
            // Indent only if there are multiple lines selected, or if the selection spans a full line
            if (sel.length > 0 && (sel.includes("\n") || sel.length === cm.getLine(cm.getCursor().line).length))
            {
                cm.indentSelection("add");
                return;
            }
        }
        cm.execCommand(cm.options.indentWithTabs ? "insertTab" : "insertSoftTab");
    };

    editor.addKeyMap({
        "Tab":TabOrIndent,
        "Shift-Tab":UnIndent,
        "Ctrl-D": dupLine, "Cmd-D": dupLine,
        "Shift-Ctrl-D":deleteLine, "Shift-Cmd-D":deleteLine,
        "Ctrl-H": showKeybindings,
    });

    const smartReplaceEditorContent = function(txt)
    {
        let i = 0;
        const lines = txt.split(/\r\n|\r|\n/);
        const newCount = lines.length, oldCount = editor.lineCount();
        if (newCount > oldCount)
        {
            const lastLineNum = editor.lastLine();
            const lastWithBreaks = editor.getLine(lastLineNum) + ("\n").repeat(newCount-oldCount);
            editor.replaceRange(lastWithBreaks, {line: lastLineNum, ch: 0}, {line: lastLineNum});
        }
        lines.forEach( (newLine) => {
            if (newLine !== editor.getLine(i))
            {
                editor.replaceRange(newLine, {line: i, ch: 0}, {line: i});
            }
            i++;
        });
    };

    smartGoToLine = (line) => {
        const lineNow = editor.getCursor().line;
        if (lineNow === line) { return; }
        editor.setCursor(line + (lineNow < line ? 10 : -10));
        editor.setCursor(line);
        editor.focus();
    };

    getCommentsAboveLine = (lineNum) => {
        const doc = editor.getDoc();
        let lines = [];
        let inMultiLineComment = false;
        while (lineNum--) {
            const line = doc.getLine(lineNum).trim();
            if (/^([#@])/.test(line.trim())) {
                lines.unshift(line);
            } else if (line.trim().startsWith('"""')) {
                inMultiLineComment = !inMultiLineComment;
                lines.unshift(line);
            } else {
                if (inMultiLineComment) {
                    lines.unshift(line);
                } else {
                    break;
                }
            }
        }
        return lines;
    };

    getCommentsBelowLine = (lineNum) => {
        const doc = editor.getDoc();
        let lines = [];
        let inMultiLineComment = false;
        while (++lineNum < doc.size) {
            const line = doc.getLine(lineNum);
            if (line.trim().startsWith('#')) {
                lines.push(line);
            } else if (line.trim().startsWith('"""')) {
                inMultiLineComment = !inMultiLineComment;
                lines.push(line);
            } else {
                if (inMultiLineComment) {
                    lines.push(line);
                } else {
                    break;
                }
            }
        }
        return lines;
    };

    $("#codeOutlineList").empty();
    dispCodeOutline = (list) => {
        if (!list || !list.forEach) { return; }
        let html = "";
        list.forEach( (tag) =>
        {
            let lblClass;
            switch (tag.k)
            {
                case 'local':
                    return; // don't show them in the outline.
                case 'label':
                case 'function':
                    lblClass = 'primary';
                    break;
                case 'prototype':
                case 'macro':
                    lblClass = 'warning';
                    break;
                case 'enumerator':
                case 'member':
                    lblClass = 'danger';
                    break;
                case 'externvar':
                case 'variable':
                case 'typedef':
                    lblClass = 'info';
                    break;
                case 'define':
                case 'enum':
                case 'struct':
                case 'class':
                    lblClass = 'success';
                    break;
                default:
                    lblClass = 'default';
            }
            const retType = tag.r ? `data-rettype="${tag.r}"` : "";
            const args    = tag.a    ? `data-args="${tag.a}"`       : "";
            const name    = tag.n.startsWith("__anon") ? '<i class="text-muted">(anon)</i>' : tag.n;
            const indent  = tag.s   ? (12 * (1 + (tag.s.match(/::/g) || []).length)) : 0;
            const offset  = indent > 0  ? ` style='margin-left:${indent}px'` : "";
            html += `<li${offset}>`;

            html += `<span title="${tag.k}" class="hasTooltip label label-${lblClass}">${tag.k.charAt(0).toUpperCase()}</span>`;
            html += `<span class="taglink" ${retType} ${args} onclick="smartGoToLine(${tag.l - 1})">${name}`;
            html += `</span>`;

            html += `</li>`;
        });
        $("#codeOutlineList").html(html).find(".hasTooltip").tooltip({container: 'body', placement: 'left'});
        recalcOutlineSize();
    };

    filterOutline = (name) =>
    {
        if (name === undefined) { name = ""; }
        $("#codeOutlineList").find("li").show().filter(`:not(:Contains('${String(name)}'))`).hide();
    };

    recalcOutlineSize = () => {
        const finalHeight = document.querySelector("div.firepad").offsetHeight;
        document.getElementById("codeOutline").style.height = finalHeight + "px";
        const finalListHeight = finalHeight - document.getElementById("codeOutlineFilter").offsetHeight - 2;
        document.getElementById("codeOutlineList").style.height = finalListHeight + "px";
    };

    refreshOutlineSize = () => {
        const codeOutline = document.getElementById("codeOutline");
        if (codeOutline)
        {
            codeOutline.style.display = "none";
            recalcOutlineSize();
            codeOutline.style.display = "block";
            recalcOutlineSize(); // because of the rendered height being first incorrect...
        }
    };

    toggleOutline = (show, auto) =>
    {
        if (auto === undefined) { auto = false; }
        if (typeof(getCtags) !== "function") {
            return;
        }
        if (!document.getElementById('codeOutline')) {
            $("div.firepad").eq(0).prepend('<div id="codeOutline" style="display:none">' +
                '<input id="codeOutlineFilter" type="text" placeholder="Quick filter...">' +
                '<ul id="codeOutlineList"></ul></div>');
            $("#codeOutlineFilter").keyup(debounce(() => { filterOutline($("#codeOutlineFilter").val()); }, 50));
        }
        recalcOutlineSize();

        const outline = $("#codeOutline");
        if (outline.is(":visible"))
        {
            if (typeof(show) === "boolean" && show) { return; }
            $("#codeOutlineToggleButton").css('background-color', 'white');
        } else {
            if (typeof(show) === "boolean" && !show) { return; }
            $("#codeOutlineToggleButton").css('background-color', '#CACBC7');
        }
        if ($("#codeOutlineList").is(":empty")) {
            getCtags(proj.currFile);
        }
        outline.toggle();
        $("div.CodeMirror").toggleClass("hasOutline");
        proj.show_code_outline = outline.is(":visible");
        if (!auto) { saveProjConfig(); }
    };

    addIconToFileTab = (filename, errtype) => {
        $(`div.filelist span.filename:contains('${filename}')`).each((idx, el) => {
            $(el).next().html(`<span class="glyphicon glyphicon-${errtype == 'error' ? 'exclamation-sign' : 'alert'}"></span>`);
        });
    };

    const applyFixIt = function(fixit)
    {
        editor.replaceRange(fixit.repl, { line: fixit.src_l-1, ch: fixit.src_c-1 }, { line: fixit.dest_l-1, ch: fixit.dest_c-1 });
        $('.tooltip').hide();
        editor.focus();
        saveFile();
    };

    updateHints = (silent) => {
        editor.operation(() => {
            let i;
            clearWidgets();

            const combined_logs = code_analysis

            if (code_analysis.length)
            {
                $(".fileTabIconContainer").empty();
            }
            let errAlreadyProcessed = {};
            let errOnOtherFiles = false;
            for (i = 0; i < combined_logs.length; ++i)
            {
                const err = combined_logs[i];
                if (!err)
                    continue;

                const errIdentifier = `${err.type}|${err.file}|${err.line}|${err.col}|${err.text.length}`;
                const existing = errAlreadyProcessed[errIdentifier];
                if (existing)
                {
                    if (err.fixit && !existing.fixit) {
                        editor.removeLineWidget(existing.widget);
                    } else {
                        continue;
                    }
                }

                addIconToFileTab(err.file.toLowerCase(), err.type);

                if (err.file.toLowerCase() != proj.currFile.toLowerCase())
                {
                    errOnOtherFiles = true;
                    continue;
                }

                const msg = document.createElement("div");
                const icon = msg.appendChild(document.createElement("span"));
                icon.innerHTML = (err.type === "error") ? "!!" : "?";
                icon.className = (err.type === "error") ? "lint-error-icon" : "lint-warning-icon";
                icon.style.position = (err.from === 'cppcheck') ? 'initial' : 'absolute'; // todo: check for pylint?
                icon.title = err.from;
                const tmp = document.createElement("span");
                tmp.className = 'lint-content-wrapper';
                tmp.innerHTML = `<pre class='inline-lint-msg' title="${err.from}">${(" ").repeat(Math.max(0, err.col - 2))}</pre>${err.col > 0 ? "<b>↑</b> " : ""}`;
                const actualText = document.createElement("span");
                actualText.innerText = err.text;
                actualText.title = err.category;
                msg.appendChild(tmp);
                if (err.fixit)
                {
                    const fixItInvite = document.createElement("span");
                    fixItInvite.style.float = "right";
                    fixItInvite.innerHTML = "<a href='#' onclick='return false'><span class='glyphicon glyphicon-flash' aria-hidden='true'></span></a>";
                    fixItInvite.onclick = () => { applyFixIt(err.fixit); clearWidgets(); };
                    fixItInvite.className = 'hasTooltip';
                    fixItInvite.title = `Fix-it: replace by '${err.fixit.repl}'`;
                    tmp.appendChild(fixItInvite);
                }
                tmp.appendChild(actualText);
                msg.className = "lint-error";
                const widget = editor.addLineWidget(err.line - 1, msg, {coverGutter: false, noHScroll: false});
                $(tmp).find(".hasTooltip").tooltip({container: 'body', placement: 'left'});
                widgets.push(widget);
                errAlreadyProcessed[errIdentifier] = { text: err.text, fixit: err.fixit, widget: widget };
            }
            editor.refresh();
            editor.focus();
            if (errOnOtherFiles && silent === false)
            {
                showNotification("warning", "Hmm...", "Warnings/Errors have been found in other files, check them too.", null, 3500);
            }
        });
        const info = editor.getScrollInfo();
        const after = editor.charCoords({
            line: editor.getCursor().line + 1,
            ch: 0
        }, "local").top;
        if (info.top + info.clientHeight < after)
        {
            editor.scrollTo(null, after - info.clientHeight + 3);
        }
    };

    stripTrailingSpaces = () => {
        let i = 0;
        const editorLine = editor.getCursor().line;
        editor.eachLine( (line) => {
            if (i !== editorLine && !line.text.includes('"') && /\s+$/.test(line.text))
            {
                editor.replaceRange(line.text.replace(/\s+$/, ""), {line: i, ch: 0}, {line: i});
            }
            i++;
        });
    };

    reindent = () => {
        if (!TIVarsLib) {
            alert('tivars_lib not ready?!');
            return;
        }

        // de-indented source
        const prgmSource = editor.getValue().replace(/^[ \t]+/gm, '');
        if (!prgmSource || !prgmSource.length) {
            return;
        }

        const prgm = TIVarsLib.TIVarFile.createNew(TIVarsLib.TIVarType.createFromName("Program"),
                                                   proj.prgmName,
                                                   TIVarsLib.TIModel.createFromName('84+CE'));

        prgm.setContentFromString(prgmSource);

        const options = new TIVarsLib.options_t();
        options.set("prettify", false);
        options.set("reindent", true);
        const reindented = prgm.getReadableContent(options);
        if (reindented && reindented.length) {
            editor.setValue(reindented);
        }
    };

    // Will get called as needed
    setupAutocompletionAutoDisplayDelay = () =>
    {
        editor.on("keyup", debounce(function (cm, event)
        {
            // disable esc, enter, shift, ctrl, alt, windows/cmd, select/cmd, and arrows
            const toFilter = [13, 27, 16, 17, 18, 91, 93, 37, 38, 39, 40];
            if (!cm.state.completionActive && /* Enables keyboard navigation in autocomplete list */
                toFilter.indexOf(event.keyCode) < 0)
            {
                CodeMirror.commands.autocomplete(cm, null, {completeSingle: false});
            }
        }, proj.autocomplete_delay));
    };
    setupAutocompletionAutoDisplayDelay();

    editor.on("mousedown", (cm, e) => {
        if (e.ctrlKey || e.metaKey)
        {
            e.preventDefault(); // Don't move the cursor there

            const target = e.target;
            const targetText = target.innerText.trim();

            let wordRange = editor.findWordAt(editor.coordsChar({left: e.pageX, top: e.pageY}));
            let word = editor.getRange(wordRange.anchor, wordRange.head).trim();
            if (word !== targetText)
            {
                const clickElementRect = e.target.getBoundingClientRect();
                wordRange = editor.findWordAt(editor.coordsChar({left: clickElementRect.left, top: clickElementRect.top}));
                word = editor.getRange(wordRange.anchor, wordRange.head).trim();
                if (word !== targetText)
                {
                    return;
                }
            }
            if (!word.length) {
                return;
            }

            let wholeWord = word;

            if (e.target.className.includes("cm-basicvar")) {
                if (wholeWord.length > 0)
                {
                    // Try from file ctags first
                    let lineNumOfFirstDef;
                    let lineDefFromCtags = window.ctags.filter( (val) => val.n === wholeWord ).map( (val) => val.l );
                    if (lineDefFromCtags.length)
                    {
                        lineNumOfFirstDef = { line: parseInt(lineDefFromCtags)-1 }; // cm format
                    }
                    else
                    {
                        // Then try from sdk ctags
                        if (word.length >= 4)
                        {
                            let ctag_from_sdk = window.sdk_ctags.filter( (val) => val.n === wholeWord );
                            if (ctag_from_sdk.length)
                            {
                                ctag_from_sdk = ctag_from_sdk[0];
                                const line = parseInt(ctag_from_sdk.l);
                                // todo: ti doc URL
                                clearTooltip();
                                return;
                            }
                        }

                        // Otherwise try from any word in the file
                        const searchRes = editor.getValue().search(new RegExp(`\\b${escapeRegExp(wholeWord)}\\b`));
                        if (searchRes > -1) {
                            lineNumOfFirstDef = editor.posFromIndex(searchRes);
                        }
                    }

                    if (lineNumOfFirstDef && lineNumOfFirstDef.line >= 0 && lineNumOfFirstDef.line !== wordRange.head.line)
                    {
                        smartGoToLine(lineNumOfFirstDef.line);
                        clearTooltip();
                    }
                }
            }
        }
    });


    highlightedWordMouseLeaveHandler = evt => {
        editor.currentHighlightedWord.style.textDecoration = "initial";
        editor.currentHighlightedWord.style.backgroundColor = "initial";
        editor.currentHighlightedWord.style.cursor = "initial";
        clearTooltip();
    };

    myMouseOverHandler = evt => {
        if (evt.ctrlKey || evt.metaKey)
        {
            const target = evt.target;
            const targetText = target.innerText.trim();
            if (target.innerText !== "asm" && target.className.includes("cm-basicvar"))
            {
                editor.currentHighlightedWord = target;
                target.style.textDecoration = "underline";
                target.style.backgroundColor = "lightcyan";
                target.style.cursor = "pointer";
                target.addEventListener("mouseleave", highlightedWordMouseLeaveHandler);
                const hoverPos = editor.coordsChar({left: evt.pageX, top: evt.pageY});
                const wordRange = editor.findWordAt(hoverPos);
                {
                    const LH = editor.getLineHandle(wordRange.head.line);
                    const LH_cb = function(cm, changeObj) {
                        const charPos = changeObj.from.ch;
                        if (charPos >= wordRange.anchor.ch && charPos <= wordRange.head.ch)
                        {
                            LH.off("change", LH_cb);
                            highlightedWordMouseLeaveHandler();
                        }
                    };
                    LH.on("change", LH_cb);
                }
                let word = editor.getRange(wordRange.anchor, wordRange.head).trim();
                if (word !== targetText)
                {
                    const clickElementRect = target.getBoundingClientRect();
                    const hoverPos = editor.coordsChar({left: clickElementRect.left, top: clickElementRect.top});
                    const wordRange = editor.findWordAt(hoverPos);
                    word = editor.getRange(wordRange.anchor, wordRange.head).trim();
                    if (word !== targetText)
                    {
                        return;
                    }
                }
                if (word.length > 0)
                {
                    const wordRegexp = new RegExp(`\\b${escapeRegExp(word)}\\b`);
                    let lineNumOfFirstDef;

                    // Try from file ctags first
                    let lineDefFromCtags = window.ctags.filter( (val) => val.n === word ).map( (val) => val.l );
                    if (lineDefFromCtags.length) {
                        lineNumOfFirstDef = { line: parseInt(lineDefFromCtags[0])-1 }; // cm format
                    }
                    else
                    {
                        // Then try from sdk ctags
                        if (word.length >= 4)
                        {
                            const defFromSDK = window.sdk_ctags.filter( (tag) => wordRegexp.test(tag.n) ).map( (val) => {
                                const retType = (val.r && !val.r.startsWith("__anon")) ? (val.r + ' ') : '';
                                const name    = val.n ? val.n : '';
                                const args    = val.a ? val.a : '';
                                const kind    =   (val.k === 'enumerator') ? 'enum value'
                                                : (val.k === 'prototype')  ? 'function'
                                                :  val.k;
                                const comment =  `# ${kind} from ${val.file}, line ${val.l}`;
                                return comment + `\n${retType}${name}${args}`;
                            });

                            if (defFromSDK.length) {
                                makeTempTooltip(defFromSDK[0], target.getBoundingClientRect(), true);
                                return;
                            }
                        }

                        // Otherwise try from any word in the file
                        const searchRes = editor.getValue().search(wordRegexp);
                        if (searchRes > -1) {
                            lineNumOfFirstDef = editor.posFromIndex(searchRes);
                        }
                    }

                    if (lineNumOfFirstDef && lineNumOfFirstDef.line >= 0 && lineNumOfFirstDef.line !== wordRange.head.line)
                    {
                        let whatToShow = editor.getLine(lineNumOfFirstDef.line).trim();
                        const commentsAbove = getCommentsAboveLine(lineNumOfFirstDef.line);
                        if (commentsAbove.length) {
                            whatToShow = commentsAbove.join("\n") + "\n" + whatToShow;
                        }
                        const commentsBelow = getCommentsBelowLine(lineNumOfFirstDef.line);
                        if (commentsBelow.length) {
                            whatToShow += "\n" + commentsBelow.join("\n");
                        }
                        if (whatToShow.length) {
                            makeTempTooltip(whatToShow, target.getBoundingClientRect(), true);
                        }
                    }
                }
            }
        }
    };
    editor.getWrapperElement().addEventListener("mousemove", myMouseOverHandler);

    document.addEventListener("keydown", (evt) => {
        evt = evt || window.event;
        if (evt.keyCode == 27)
        { // Esc.
            editor.state.currentTooltip && highlightedWordMouseLeaveHandler();
        }
    });


    editor.on("change", (c) => {
        savedSinceLastChange = false;
        lastChangeTS = (new Date).getTime();
        const saveButton = document.getElementById('saveButton');
        if (saveButton) saveButton.disabled = false;
    });

    // Tooltips (inspired from Tern)

    const clearTooltip = () => {
        if (!editor.state.currentTooltip || !editor.state.currentTooltip.parentNode)
            return;
        editor.off('blur', clearTooltip);
        editor.off('scroll', clearTooltip);
        remove(editor.state.currentTooltip);
        editor.state.currentTooltip = null;
    };

    const makeTempTooltip = (content, where, highlight) => {
        if (editor.state.currentTooltip)  {
            remove(editor.state.currentTooltip);
        }
        const lines = content.split(/\r\n|\r|\n/).length;
        const deltaY = (lines > 1) ? 14*lines : 8;
        editor.state.currentTooltip = makeTooltip(where.left, where.top - where.height - deltaY, content);
        if (highlight)
        {
            editor.state.currentTooltip.innerHTML = '';
            CodeMirror(editor.state.currentTooltip, {
                value: content,
                mode: 'text/x-tibasic',
                lineNumbers: false,
                readOnly: true,
                theme: 'xq-light'
            });
        }
        editor.on('blur', clearTooltip);
        editor.on('scroll', clearTooltip);
    }

}

do_cm_custom();