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
    widgets = [];

    editor.addKeyMap({
        "Tab": function (cm)
        {
            if (cm.somethingSelected())
            {
                var sel = editor.getSelection("\n");
                // Indent only if there are multiple lines selected, or if the selection spans a full line
                if (sel.length > 0 && (sel.indexOf("\n") > -1 || sel.length === cm.getLine(cm.getCursor().line).length))
                {
                    cm.indentSelection("add");
                    return;
                }
            }
            cm.execCommand(cm.options.indentWithTabs ? "insertTab" : "insertSoftTab");
        },
        "Shift-Tab": function (cm)
        {
            cm.indentSelection("subtract");
        }
    });

    updateHints = function(silent)
    {
        editor.operation(function ()
        {
            var i;
            for (i = 0; i < widgets.length; ++i)
            {
                editor.removeLineWidget(widgets[i]);
            }
            widgets.length = 0;

            var combined_logs = build_output.concat(build_check);

            var linesProcessed = [];
            var errOnOtherFiles = false;
            for (i = 0; i < combined_logs.length; ++i)
            {
                var err = combined_logs[i];
                if (!err || linesProcessed.indexOf(err.line) > -1)
                    continue;
                if (err.file.toLowerCase() != proj.currFile.toLowerCase())
                {
                    errOnOtherFiles = true;
                    continue;
                }
                var msg = document.createElement("div");
                var icon = msg.appendChild(document.createElement("span"));
                icon.innerHTML = (err.type === "error") ? "!!" : "?";
                icon.className = (err.type === "error") ? "lint-error-icon" : "lint-warning-icon";
                var tmp = document.createElement("span");
                tmp.style['margin-left'] = '12px';
                tmp.innerHTML = "<pre class='inline-lint-msg'>" + (" ").repeat(Math.max(0, err.col - 2)) + "</pre>" + (err.col > 0 ? "<b>↑</b> " : "") + err.text;
                msg.appendChild(tmp);
                msg.className = "lint-error";
                widgets.push(editor.addLineWidget(err.line - 1, msg, {coverGutter: true, noHScroll: true}));
                linesProcessed.push(err.line);
            }
            editor.refresh();
            editor.focus();
            if (errOnOtherFiles && silent === false)
            {
                alert("Warnings/Errors have been found in other files, please check them.");
            }
        });
        var info = editor.getScrollInfo();
        var after = editor.charCoords({
            line: editor.getCursor().line + 1,
            ch: 0
        }, "local").top;
        if (info.top + info.clientHeight < after)
        {
            editor.scrollTo(null, after - info.clientHeight + 3);
        }
    };

    editor.on("mousedown", function (cm, e)
    {
        if (e.ctrlKey || e.metaKey)
        {
            e.preventDefault(); // Don't move the cursor there
            var clickPos = editor.coordsChar({left: e.clientX, top: e.clientY});
            var wordRange = editor.findWordAt(clickPos);
            var word = editor.getRange(wordRange.anchor, wordRange.head);
            if (isNumeric(word))
            {
                var hexValue = "0x" + (parseInt(word).toString(16)).toUpperCase();
                editor.replaceRange(hexValue, wordRange.anchor, wordRange.head);
            } else
            {
                var firstSeenIdx = editor.getValue().search(new RegExp(' ' + word + '[^\\w]'));
                if (firstSeenIdx > 0)
                {
                    var firstSeenPos = editor.posFromIndex(firstSeenIdx);
                    if (firstSeenPos.line != clickPos.line)
                    {
                        editor.setCursor(firstSeenPos);
                        clearTooltip();
                    }
                }
            }
        }
    });


    highlightedWordMouseLeaveHandler = function (evt)
    {
        editor.currentHighlightedWord.style.textDecoration = "initial";
        editor.currentHighlightedWord.style.backgroundColor = "initial";
        editor.currentHighlightedWord.style.cursor = "initial";
        clearTooltip();
    };

    myMouseOverHandler = function (evt)
    {
        if (evt.ctrlKey || evt.metaKey)
        {
            var target = evt.target;
            if (target.innerText != "asm" && target.className.indexOf("cm-variable") >= 0)
            {
                editor.currentHighlightedWord = target;
                target.style.textDecoration = "underline";
                target.style.backgroundColor = "lightcyan";
                target.style.cursor = "pointer";
                target.addEventListener("mouseleave", highlightedWordMouseLeaveHandler);
                var clickPos = editor.coordsChar({left: evt.clientX, top: evt.clientY});
                var wordRange = editor.findWordAt(clickPos);
                var word = editor.getRange(wordRange.anchor, wordRange.head);
                if (word.length > 1)
                {
                    var lineNumOfFirstDef = editor.posFromIndex(editor.getValue().search(new RegExp(' ' + word + '[^\\w]'))).line;
                    if (lineNumOfFirstDef > 0)
                    {
                        var lineOfFirstDef = editor.getLine(lineNumOfFirstDef);
                        makeTempTooltip(lineOfFirstDef.trim(), target.getBoundingClientRect(), true);
                    }
                }
            } else if (target.className.indexOf("cm-number") >= 0)
            {
                editor.currentHighlightedWord = target;
                target.addEventListener("mouseleave", highlightedWordMouseLeaveHandler);
                var clickPos = editor.coordsChar({left: evt.clientX, top: evt.clientY});
                var wordRange = editor.findWordAt(clickPos);
                var number = editor.getRange(wordRange.anchor, wordRange.head);
                if (isNumeric(number))
                {
                    number = parseInt(number);
                    target.style.textDecoration = "underline";
                    target.style.backgroundColor = "lightgreen";
                    makeTempTooltip(number + " == 0x" + (number.toString(16)).toUpperCase(), target.getBoundingClientRect(), true);
                }
            }
        }
    };
    editor.getWrapperElement().addEventListener("mouseover", myMouseOverHandler);

    document.addEventListener("keydown", function (evt)
    {
        evt = evt || window.event;
        if (evt.keyCode == 27)
        { // Esc.
            editor.state.currentTooltip && highlightedWordMouseLeaveHandler();
        }
    });


    editor.on("change", function (c)
    {
        savedSinceLastChange = false;
        var saveButton = document.getElementById('saveButton');
        if (saveButton) saveButton.disabled = false;
    });

    // Tooltips (inspired from Tern)

    clearTooltip = function()
    {
        if (!editor.state.currentTooltip || !editor.state.currentTooltip.parentNode)
            return;
        editor.off('blur', clearTooltip);
        editor.off('scroll', clearTooltip);
        remove(editor.state.currentTooltip);
        editor.state.currentTooltip = null;
    }

    makeTempTooltip = function(content, where, highlight)
    {
        if (editor.state.currentTooltip)
            remove(editor.state.currentTooltip);
        editor.state.currentTooltip = makeTooltip(where.left, where.top - where.height - 8, content);
        if (highlight)
        {
            editor.state.currentTooltip.innerHTML = '';
            CodeMirror(editor.state.currentTooltip, {
                value: content,
                mode: 'text/x-csrc',
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