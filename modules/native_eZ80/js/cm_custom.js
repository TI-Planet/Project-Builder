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
    widgets = lineWidgetsAsm = [];
    asmBeingShown = false;

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

    /* Adapted from https://gist.github.com/anaran/9198993 */
    const showKeybindings = () =>
    {
        let i;
        let keymap = window.CodeMirror.keyMap[window.CodeMirror.defaults.keyMap];

        const newBindingsKeys = Object.keys(editor.state.keyMaps[0]);
        for (i=0; i<newBindingsKeys.length; i++) {
            keymap[newBindingsKeys[i]] = editor.state.keyMaps[0][newBindingsKeys[i]].name;
        }
        delete keymap.fallthrough;

        const orderedKM = {};
        Object.keys(keymap).sort().forEach( (key) => { orderedKM[key] = keymap[key]; });

        const modal = $("#keybindingsModal");
        modal.find("div.modal-body").html("<pre style='max-height:300px'>" + JSON.stringify(orderedKM, null, 2) + "</pre>");
        modal.modal();
    };
    $("#customExtraSBButton").html('<span class="glyphicon glyphicon-question-sign"></span>')
                             .attr("title", "Editor key bindings")
                             .on("click", showKeybindings)
                             .show();

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
        while (lineNum--) {
            const line = doc.getLine(lineNum);
            if (/^(;|\/\/|\/\*)/.test(line))
            {
                lines.unshift(line);
            } else {
                break;
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
                case 'label':
                case 'function':
                    lblClass = 'primary';
                    break;
                case 'prototype':
                    lblClass = 'warning';
                    break;
                case 'enumerator':
                case 'member':
                    lblClass = 'danger';
                    break;
                case 'variable':
                    lblClass = 'info';
                    break;
                case 'define':
                case 'macro':
                case 'typedef':
                case 'enum':
                case 'struct':
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

    dispSrc = (targetEditor, callback) => {
        let i;

        if (typeof(targetEditor) === "undefined") {
            targetEditor = editor;
        }

        if (asmBeingShown === true) {
            asmBeingShown = false;
            for (i = 0; i < lineWidgetsAsm.length; i++) {
                targetEditor.removeLineWidget(lineWidgetsAsm[i]);
            }
            lineWidgetsAsm.length = 0;
            targetEditor.refresh();
            targetEditor.focus();

            $("#asmToggleButton").css('background-color', 'white').parent().attr('title', 'Click to show ASM').tooltip('fixTitle').tooltip('show');

            if (typeof callback === "function") {
                callback();
            }
            return;
        }
        ajax("ActionHandler.php", `id=${proj.pid}&file=${proj.currFile}&action=getCurrentSrc`, data => {
            if (data === null)
            {
                asmBeingShown = false;
                $("#asmToggleButton").css('background-color', 'white').parent().attr('title', 'Click to show ASM').tooltip('fixTitle').tooltip('show');
                showNotification("warning", "There is no ASM file for this source file", "Have you built the project yet?", null, 10000);
                if (typeof callback === "function") {
                    callback();
                }
            } else {
                asmBeingShown = true;
                $("#asmToggleButton").css('background-color', '#CACBC7').parent().attr('title', 'Click to hide ASM').tooltip('fixTitle').tooltip('show');

                const allSrcLines = data.replace("\\r", "").split("\n");

                const linesForC = { '0':[] }; // format: key = C line (start). value = [ asm lines... ].
                let currKey = '0';

                for (i=0; i<allSrcLines.length; i++)
                {
                    const line = allSrcLines[i];
                    const matchesNewCLine = line.match(/^;\s+(\d+)\t/);
                    if (matchesNewCLine && matchesNewCLine.length >= 1)
                    {
                        // New C line found. Let's process the previous range
                        linesForC[currKey].shift(); // Remove first line (which is the C one)
                        if (!linesForC[currKey].length) {
                            delete linesForC[currKey];
                        }
                        // Prepare for insertions
                        currKey = matchesNewCLine[1];
                        linesForC[currKey] = [];
                    }
                    // Insert
                    if (line.trim().length > 0) {
                        linesForC[currKey].push(line);
                    }
                }

                for (i = 0; i < lineWidgetsAsm.length; i++) {
                    targetEditor.removeLineWidget(lineWidgetsAsm[i]);
                }
                lineWidgetsAsm.length = 0;

                let key;
                for (key in linesForC)
                {
                    if (!linesForC.hasOwnProperty(key) || key === '0') continue;

                    const lines = linesForC[key];

                    let valueChunk = "<pre style='padding:4px;line-height:.65em;'><code>";
                    let asmLineIdx;
                    for (asmLineIdx in lines)
                    {
                        if (!lines.hasOwnProperty(asmLineIdx) || lines[asmLineIdx][0] === ";") continue;

                        const trimmedLine = lines[asmLineIdx].trim();
                        if (trimmedLine.indexOf("XREF") === 0 || trimmedLine.indexOf("XDEF") === 0 || trimmedLine.indexOf("END") === 0) {
                            continue;
                        }
                        valueChunk += `${lines[asmLineIdx].replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;")}<br/>`;
                    }
                    valueChunk = valueChunk.slice(0, -1); // remove extra newline at the end
                    valueChunk += "</code></pre>";
                    const msg = document.createElement("div");
                    msg.innerHTML = valueChunk;
                    msg.className = "inline-asm";

                    lineWidgetsAsm.push(targetEditor.addLineWidget(parseInt(key)-1, msg, {coverGutter: false, noHScroll: true}));
                }

                targetEditor.refresh();
                targetEditor.focus();

                if (typeof callback === "function") {
                    callback();
                }
            }
        });
    };

    addIconToFileTab = (filename, errtype) => {
        $(`div.filelist span.filename:contains('${filename}')`).each((idx, el) => {
            $(el).next().html(`<span class="glyphicon glyphicon-${errtype == 'error' ? 'exclamation-sign' : 'alert'}"></span>`);
        });
    };

    updateHints = silent => {
        editor.operation(() => {
            let i;
            for (i = 0; i < widgets.length; ++i)
            {
                editor.removeLineWidget(widgets[i]);
            }
            widgets.length = 0;

            const combined_logs = build_output.concat(build_check);

            if (combined_logs.length)
            {
                $(".fileTabIconContainer").empty();
            }
            const linesProcessed = [];
            let errOnOtherFiles = false;
            for (i = 0; i < combined_logs.length; ++i)
            {
                const err = combined_logs[i];
                if (!err || linesProcessed.includes(err.line))
                    continue;

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
                const tmp = document.createElement("span");
                tmp.style['margin-left'] = '12px';
                tmp.innerHTML = `<pre class='inline-lint-msg'>${(" ").repeat(Math.max(0, err.col - 2))}</pre>${err.col > 0 ? "<b>↑</b> " : ""}${err.text}`;
                msg.appendChild(tmp);
                msg.className = "lint-error";
                widgets.push(editor.addLineWidget(err.line - 1, msg, {coverGutter: true, noHScroll: true}));
                linesProcessed.push(err.line);
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

    editor.on("keyup", debounce(function (cm, event)
    {
        // disable esc, enter, shift, ctrl, alt, windows/cmd, select/cmd, and arrows
        const toFilter = [ 13, 27, 16, 17, 18, 91, 93, 37, 38, 39, 40 ];
        if (!cm.state.completionActive &&     /* Enables keyboard navigation in autocomplete list */
            toFilter.indexOf(event.keyCode) < 0)
        {
            CodeMirror.commands.autocomplete(cm, null, {completeSingle: false});
        }
    }, 500));

    editor.on("mousedown", (cm, e) => {
        if (e.ctrlKey || e.metaKey)
        {
            const isEditorASM = editor.getMode().name === 'z80';

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

            if (isEditorASM)
            {
                const anchorPrevLetterFrom = {ch: wordRange.anchor.ch - 1, line: wordRange.anchor.line, sticky: null};
                const anchorPrevLetterTo   = {ch: wordRange.anchor.ch,     line: wordRange.anchor.line, sticky: null};
                const prevLetter = editor.getRange(anchorPrevLetterFrom, anchorPrevLetterTo).trim();
                if (prevLetter === '$') {
                    wholeWord = '$' + word;
                    wordRange.anchor = anchorPrevLetterFrom;
                }
            }

            if (isNumeric(wholeWord))
            {
                const number = parseInt(word, 10);
                const rawHexValue = (number.toString(16)).toUpperCase();
                const hexValue = (editor.getMode().name === 'z80') ? ((number === 0 ? '' : '0') + rawHexValue + 'h')
                                                                   : ('0x' + rawHexValue);
                editor.replaceRange(hexValue, wordRange.anchor, wordRange.head);
            } else if (isHexNum(wholeWord)) {
                const decValue = (parseInt(word, 16).toString(10)).toUpperCase();
                if (decValue !== 'NAN')
                {
                    editor.replaceRange(decValue, wordRange.anchor, wordRange.head);
                }
            } else if (e.target.classList.contains("cm-variable") || e.target.classList.contains("cm-asm-variable")) {
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
                        // Only file ctags makes sense for asm, so if nothing was found, abort.
                        if (isEditorASM) {
                            // Todo: handle ti84pce.inc ctags here, and for asm only
                            return;
                        }

                        // Then try from sdk ctags
                        if (word.length >= 4)
                        {
                            let ctag_from_sdk = window.sdk_ctags.filter( (val) => val.n === wholeWord );
                            if (ctag_from_sdk.length)
                            {
                                ctag_from_sdk = ctag_from_sdk[0];
                                const line = parseInt(ctag_from_sdk.l);
                                const isFromLibs = [ 'graphx.h', 'fileioc.h', 'keypadc.h', 'graphx.h' ].indexOf(ctag_from_sdk.file) > -1;
                                const isFromCE   = [ 'debug.h', 'decompress.h', 'intce.h', 'tice.h', 'usb.h' ].indexOf(ctag_from_sdk.file) > -1;
                                if (isFromLibs) {
                                    window.open(`https://github.com/CE-Programming/toolchain/blob/v7.2/src/${ctag_from_sdk.file.slice(0,-2)}/${ctag_from_sdk.file}#L${line}`, '_blank');
                                } else if (isFromCE) {
                                    window.open(`https://github.com/CE-Programming/toolchain/blob/v7.2/src/ce/${ctag_from_sdk.file}#L${line}`, '_blank');
                                } else {
                                    window.open(`https://github.com/CE-Programming/toolchain/blob/v7.2/src/std/${ctag_from_sdk.file}#L${line}`, '_blank');
                                }
                                clearTooltip();
                                return;
                            }
                        }

                        // Otherwise try from any word in the file
                        lineNumOfFirstDef = editor.posFromIndex(editor.getValue().search(new RegExp(`\\b${escapeRegExp(wholeWord)}\\b`)));
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
            const isEditorASM = editor.getMode().name === 'z80';
            const target = evt.target;
            const targetText = target.innerText.trim();
            if (target.innerText !== "asm" && (target.classList.contains("cm-variable") || target.classList.contains("cm-asm-variable")))
            {
                editor.currentHighlightedWord = target;
                target.style.textDecoration = "underline";
                target.style.backgroundColor = "lightcyan";
                target.style.cursor = "pointer";
                target.addEventListener("mouseleave", highlightedWordMouseLeaveHandler);
                const hoverPos = editor.coordsChar({left: evt.pageX, top: evt.pageY});
                const wordRange = editor.findWordAt(hoverPos);
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
                        // Only file ctags makes sense for asm, so if nothing was found, abort.
                        if (isEditorASM) {
                            // Todo: handle ti84pce.inc ctags here, and for asm only
                            return;
                        }

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
                                const comment = (isEditorASM ? "; " : "// ") + `${kind} from ${val.file}, line ${val.l}`;
                                return comment + `\n${retType}${name}${args}`;
                            });

                            if (defFromSDK.length) {
                                makeTempTooltip(defFromSDK[0], target.getBoundingClientRect(), true);
                                return;
                            }
                        }

                        // Otherwise try from any word in the file
                        lineNumOfFirstDef = editor.posFromIndex(editor.getValue().search(wordRegexp));
                    }

                    if (lineNumOfFirstDef && lineNumOfFirstDef.line >= 0 && lineNumOfFirstDef.line !== wordRange.head.line)
                    {
                        let whatToShow = editor.getLine(lineNumOfFirstDef.line).trim();
                        let commentsAbove = getCommentsAboveLine(lineNumOfFirstDef.line);
                        if (commentsAbove.length) {
                            whatToShow = commentsAbove.join("\n") + "\n" + whatToShow;
                        }
                        if (whatToShow.length) {
                            makeTempTooltip(whatToShow, target.getBoundingClientRect(), true);
                        }
                    }
                }
            } else if (target.className.includes("number"))
            {
                editor.currentHighlightedWord = target;
                target.addEventListener("mouseleave", highlightedWordMouseLeaveHandler);
                const clickPos = editor.coordsChar({left: evt.clientX, top: evt.clientY});
                const wordRange = editor.findWordAt(clickPos);
                const number = editor.getRange(wordRange.anchor, wordRange.head).trim();
                let wholeWord = number;
                if (isEditorASM)
                {
                    const anchorPrevLetterFrom = {ch: wordRange.anchor.ch - 1, line: wordRange.anchor.line, sticky: null};
                    const anchorPrevLetterTo   = {ch: wordRange.anchor.ch,     line: wordRange.anchor.line, sticky: null};
                    const prevLetter = editor.getRange(anchorPrevLetterFrom, anchorPrevLetterTo).trim();
                    if (prevLetter === '$') {
                        wholeWord = '$' + number;
                        wordRange.anchor = anchorPrevLetterFrom;
                    }
                }
                if (isNumeric(wholeWord))
                {
                    wholeWord = parseInt(number, 10);
                    target.style.textDecoration = "underline";
                    target.style.backgroundColor = "lightgreen";
                    const rawHexValue = (wholeWord.toString(16).toUpperCase());
                    const hexValue = (editor.getMode().name === 'z80') ? ((wholeWord === 0 ? '' : '0') + rawHexValue + 'h')
                                                                       : ('0x' + rawHexValue);
                    makeTempTooltip(`${wholeWord} == ${hexValue}`, target.getBoundingClientRect(), true);
                } else if (isHexNum(wholeWord)) {
                    const decNum = parseInt(number, 16);
                    target.style.textDecoration = "underline";
                    target.style.backgroundColor = "lightgreen";
                    const numStr = (decNum.toString(10)).toUpperCase();
                    if (numStr !== 'NAN')
                    {
                        makeTempTooltip(`${wholeWord} == ${numStr}`, target.getBoundingClientRect(), true);
                    }
                }
            }
        }
    };
    editor.getWrapperElement().addEventListener("mousemove", myMouseOverHandler);

    document.addEventListener("keydown", evt => {
        evt = evt || window.event;
        if (evt.keyCode == 27)
        { // Esc.
            editor.state.currentTooltip && highlightedWordMouseLeaveHandler();
        }
    });


    editor.on("change", c => {
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
                mode: (editor.getMode().name === 'z80') ? 'text/x-ez80' : 'text/x-csrc',
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