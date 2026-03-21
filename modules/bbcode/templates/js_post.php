<?php
/* This content will be included and displayed. */
if (!isset($pm)) { die('Ahem ahem'); }
/** @var \ProjectBuilder\bbcodeProject $currProject */
?>
<script>
    let editor = null;
    let savedSinceLastChange = true;
    let lastChangeTS = 0;
    let textarea, fakeContainer;
    let bbcodeInlineStyleMarks = [];
    const bbcodeInlineTagNames = new Set(['b', 'i', 'u', 's']);
    let bbcodePreviewLinkedElement = null;
    let bbcodePreviewSyncingFromEditor = false;
    let bbcodePreviewSyncingFromPane = false;
    let bbcodeLastLinkedPreviewQuery = '';
    let bbcodeActivePairMarks = [];
    let bbcodePreviewLanguageMode = 'auto';
    const bbcodeSelfClosingTagNames = new Set(['*']);
    const bbcodePreviewLanguageStorageKey = `bbcode_preview_language_${(window.proj && proj.pid) ? proj.pid : 'default'}`;

    const getBBCodeTagStackAtIndex = (src, endIndex) => {
        const stack = [];
        const tagRe = /\[(\/)?([a-zA-Z*]+)(?:=[^\]\n]*)?(\s*\/)?\]/g;
        let inCodeDepth = 0;
        let m;

        while ((m = tagRe.exec(src)) !== null && m.index < endIndex) {
            const isClosing = !!m[1];
            const name = (m[2] || '').toLowerCase();
            const trailingSlash = !!(m[3] && m[3].trim().length);

            if (name === 'code' && !trailingSlash) {
                if (isClosing) {
                    inCodeDepth = Math.max(0, inCodeDepth - 1);
                    for (let i = stack.length - 1; i >= 0; i--) {
                        if (stack[i] === 'code') {
                            stack.splice(i, 1);
                            break;
                        }
                    }
                } else {
                    inCodeDepth++;
                    stack.push(name);
                }
                continue;
            }

            if (inCodeDepth > 0) {
                continue;
            }

            if (trailingSlash || bbcodeSelfClosingTagNames.has(name)) {
                continue;
            }

            if (isClosing) {
                for (let i = stack.length - 1; i >= 0; i--) {
                    if (stack[i] === name) {
                        stack.splice(i, 1);
                        break;
                    }
                }
            } else {
                stack.push(name);
            }
        }

        return stack;
    };

    const getAutoPreviewLanguageMode = () => {
        const candidates = [
            (document.documentElement && document.documentElement.lang) ? document.documentElement.lang : '',
            (window.navigator && Array.isArray(window.navigator.languages) && window.navigator.languages.length > 0) ? window.navigator.languages[0] : '',
            (window.navigator && window.navigator.language) ? window.navigator.language : ''
        ];
        const detected = (candidates.find((value) => typeof value === 'string' && value.trim().length > 0) || '').toLowerCase();
        return detected.startsWith('fr') ? 'fr' : 'en';
    };

    const getResolvedPreviewLanguageMode = () => {
        return bbcodePreviewLanguageMode === 'auto' ? getAutoPreviewLanguageMode() : bbcodePreviewLanguageMode;
    };

    const applyPreviewLanguageMode = () => {
        const previewContent = document.getElementById('bbcodePreviewContent');
        if (previewContent) {
            previewContent.classList.remove('pb-preview-language-fr', 'pb-preview-language-en');
            previewContent.classList.add(`pb-preview-language-${getResolvedPreviewLanguageMode()}`);
        }

        const toolbar = document.getElementById('bbcodePreviewLanguageToolbar');
        if (!toolbar) {
            return;
        }

        toolbar.querySelectorAll('.bbcode-preview-language-button').forEach((button) => {
            button.classList.toggle('active', button.getAttribute('data-preview-language-mode') === bbcodePreviewLanguageMode);
        });
    };

    const bindPreviewLanguageToolbar = () => {
        const toolbar = document.getElementById('bbcodePreviewLanguageToolbar');
        if (!toolbar || toolbar.dataset.bound === '1') {
            return;
        }

        toolbar.dataset.bound = '1';
        const savedMode = localStorage.getItem(bbcodePreviewLanguageStorageKey);
        if (savedMode === 'auto' || savedMode === 'fr' || savedMode === 'en') {
            bbcodePreviewLanguageMode = savedMode;
        }

        toolbar.addEventListener('click', (event) => {
            const button = event.target.closest('.bbcode-preview-language-button');
            if (!button) {
                return;
            }

            const nextMode = button.getAttribute('data-preview-language-mode');
            if (nextMode !== 'auto' && nextMode !== 'fr' && nextMode !== 'en') {
                return;
            }

            bbcodePreviewLanguageMode = nextMode;
            localStorage.setItem(bbcodePreviewLanguageStorageKey, nextMode);
            applyPreviewLanguageMode();
        });

        applyPreviewLanguageMode();
    };

    const getBBCodeTagPairs = (src) => {
        const pairs = [];
        const stack = [];
        const tagRe = /\[(\/)?([a-zA-Z*]+)(?:=[^\]\n]*)?(\s*\/)?\]/g;
        let m;

        while ((m = tagRe.exec(src)) !== null) {
            const isClosing = !!m[1];
            const name = (m[2] || '').toLowerCase();
            const trailingSlash = !!(m[3] && m[3].trim().length);
            const start = m.index;
            const end = tagRe.lastIndex;

            if (trailingSlash || bbcodeSelfClosingTagNames.has(name)) {
                continue;
            }

            if (!isClosing) {
                stack.push({ name, start, end });
                continue;
            }

            for (let i = stack.length - 1; i >= 0; i--) {
                if (stack[i].name === name) {
                    const opening = stack[i];
                    pairs.push({
                        name,
                        openStart: opening.start,
                        openEnd: opening.end,
                        closeStart: start,
                        closeEnd: end
                    });
                    stack.splice(i, 1);
                    break;
                }
            }
        }

        return pairs;
    };

    const clearBBCodeActivePairMarks = () => {
        bbcodeActivePairMarks.forEach((mark) => mark.clear());
        bbcodeActivePairMarks = [];
    };

    const updateBBCodePairHighlight = () => {
        clearBBCodeActivePairMarks();
        if (!editor) {
            return;
        }

        const src = editor.getValue();
        if (!src.length) {
            return;
        }

        const cursorIndex = editor.indexFromPos(editor.getCursor());
        const activePair = getBBCodeTagPairs(src)
            .filter((pair) => cursorIndex >= pair.openStart && cursorIndex <= pair.closeEnd)
            .sort((left, right) => (left.closeEnd - left.openStart) - (right.closeEnd - right.openStart))[0];

        if (!activePair) {
            return;
        }

        bbcodeActivePairMarks.push(editor.markText(
            editor.posFromIndex(activePair.openStart),
            editor.posFromIndex(activePair.openEnd),
            {
                className: 'cm-bbcode-active-tag cm-bbcode-active-tag-open',
                startStyle: 'cm-bbcode-active-tag-start',
                endStyle: 'cm-bbcode-active-tag-end'
            }
        ));
        bbcodeActivePairMarks.push(editor.markText(
            editor.posFromIndex(activePair.closeStart),
            editor.posFromIndex(activePair.closeEnd),
            {
                className: 'cm-bbcode-active-tag cm-bbcode-active-tag-close',
                startStyle: 'cm-bbcode-active-tag-start',
                endStyle: 'cm-bbcode-active-tag-end'
            }
        ));

        if (activePair.closeStart > activePair.openEnd) {
            bbcodeActivePairMarks.push(editor.markText(
                editor.posFromIndex(activePair.openEnd),
                editor.posFromIndex(activePair.closeStart),
                { className: 'cm-bbcode-active-region' }
            ));
        }
    };

    const bbcodeWrapDefinitions = {
        b: { name: 'b', open: '[b]', close: '[/b]', placeholder: 'bold text' },
        i: { name: 'i', open: '[i]', close: '[/i]', placeholder: 'italic text' },
        u: { name: 'u', open: '[u]', close: '[/u]', placeholder: 'underlined text' },
        code: { name: 'code', open: '[code]', close: '[/code]', placeholder: 'code goes here' },
        quote: { name: 'quote', open: '[quote]', close: '[/quote]', placeholder: 'quoted text' },
    };
    const bbcodeAutocompleteTagDefinitions = [
        { name: 'b', description: 'bold' },
        { name: 'i', description: 'italic' },
        { name: 'u', description: 'underline' },
        { name: 's', description: 'strikethrough' },
        { name: 'sub', description: 'subscript' },
        { name: 'sup', description: 'superscript' },
        { name: 'goto=', description: 'link to hash' },
        { name: 'hashtag=', description: 'anchor link' },
        { name: 'hr', description: 'horizontal line' },
        { name: 'center', description: 'centered text' },
        { name: 'floatright', description: 'float-right content' },
        { name: 'floatleft', description: 'float-left content' },
        { name: 'clearright', description: 'clear float-right' },
        { name: 'clearleft', description: 'clear float-left' },
        { name: 'clearfloat', description: 'clear both float' },
        { name: 'center', description: 'centered text' },
        { name: 'quote', description: 'quote block' },
        { name: 'spoiler', description: 'spoiler block' },
        { name: 'ispoiler', description: 'inline spoiler block' },
        { name: 'code', description: 'code block' },
        { name: 'icode', description: 'inline code block' },
        { name: 'url', description: 'link' },
        { name: 'img', description: 'image' },
        { name: 'imagewidth', description: 'image with width' },
        { name: 'album', description: 'gallery image' },
        { name: 'albumleft', description: 'float-left gallery image' },
        { name: 'albumright', description: 'float-right gallery image' },
        { name: 'img', description: 'image' },
        { name: 'color', description: 'color' },
        { name: 'size', description: 'size' },
        { name: 'list', description: 'unordered list' },
        { name: 'list=1', description: 'ordered list' },
        { name: '*', description: 'list item' },
        { name: 'button=style,url', description: 'bootstrap button' },
        { name: 'label', description: 'bootstrap label' },
        { name: 'label=style', description: 'bootstrap label' },
        { name: 'table', description: 'table' },
        { name: 'tr', description: 'table row' },
        { name: 'td', description: 'table cell' },
        { name: 'latex', description: 'latex expression' },
        { name: 'feat_img', description: 'SEO featured-image URL' },
        { name: 'feat_text', description: 'SEO featured-text' },
        { name: 'success', description: 'green container' },
        { name: 'info', description: 'blue container' },
        { name: 'warning', description: 'yellow container' },
        { name: 'error', description: 'red container' },
        { name: 'tweet', description: 'X/twitter embed' },
        { name: 'youtube', description: 'youtube embed' },
    ];
    const bbcodeSnippetDefinitions = [
        {
            key: 'url',
            displayText: '[url=...]...[/url]',
            apply: () => insertBBCodeSnippet('url')
        },
        {
            key: 'img',
            displayText: '[img]...[/img]',
            apply: () => insertBBCodeSnippet('img')
        },
        {
            key: 'quote',
            displayText: '[quote]...[/quote]',
            apply: () => wrapSelectionWithBBCode(bbcodeWrapDefinitions.quote)
        },
        {
            key: 'spoiler',
            displayText: '[spoiler]...[/spoiler]',
            apply: () => wrapSelectionWithBBCode(bbcodeWrapDefinitions.spoiler)
        },
        {
            key: 'code',
            displayText: '[code]...[/code]',
            apply: () => wrapSelectionWithBBCode(bbcodeWrapDefinitions.code)
        },
        {
            key: 'list',
            displayText: '[list] with [*] items',
            apply: () => insertBBCodeSnippet('list')
        },
        {
            key: 'table',
            displayText: '[table] layout snippet',
            apply: () => insertBBCodeSnippet('table')
        },
    ];

    const insertBBCodeTemplate = (template, selectionStartOffset, selectionEndOffset) => {
        if (!editor || editor.getOption('readOnly')) {
            return;
        }

        const doc = editor.getDoc();
        const from = doc.getCursor('from');
        const startIndex = doc.indexFromPos(from);
        doc.replaceSelection(template, 'around');

        if (Number.isFinite(selectionStartOffset) && Number.isFinite(selectionEndOffset)) {
            const selectionFrom = doc.posFromIndex(startIndex + selectionStartOffset);
            const selectionTo = doc.posFromIndex(startIndex + selectionEndOffset);
            doc.setSelection(selectionFrom, selectionTo);
        }

        editor.focus();
    };

    const getMatchingWrapPairForSelection = (tagName) => {
        if (!editor || !tagName) {
            return null;
        }

        const doc = editor.getDoc();
        const fromIndex = doc.indexFromPos(doc.getCursor('from'));
        const toIndex = doc.indexFromPos(doc.getCursor('to'));

        return getBBCodeTagPairs(editor.getValue())
            .filter((pair) =>
                pair.name === tagName
                && fromIndex >= pair.openEnd
                && toIndex <= pair.closeStart
            )
            .sort((left, right) => (left.closeEnd - left.openStart) - (right.closeEnd - right.openStart))[0] || null;
    };

    const refreshBBCodeEditorUIState = () => {
        updateBBCodeToolbarState();
        updateBBCodePairHighlight();
        linkEditorSelectionToPreview(false);
    };

    const wrapSelectionWithBBCode = (definition) => {
        if (!editor || editor.getOption('readOnly') || !definition) {
            return;
        }

        const doc = editor.getDoc();
        const matchingPair = getMatchingWrapPairForSelection(definition.name);
        if (matchingPair) {
            const fromIndex = doc.indexFromPos(doc.getCursor('from'));
            const toIndex = doc.indexFromPos(doc.getCursor('to'));
            const unwrappedText = editor.getValue().slice(matchingPair.openEnd, matchingPair.closeStart);
            doc.replaceRange(
                unwrappedText,
                editor.posFromIndex(matchingPair.openStart),
                editor.posFromIndex(matchingPair.closeEnd)
            );

            const selectionFrom = editor.posFromIndex(Math.max(matchingPair.openStart, fromIndex - definition.open.length));
            const selectionTo = editor.posFromIndex(Math.max(matchingPair.openStart, toIndex - definition.open.length));
            if (fromIndex === toIndex) {
                doc.setCursor(selectionFrom);
            } else {
                doc.setSelection(selectionFrom, selectionTo);
            }
            editor.focus();
            refreshBBCodeEditorUIState();
            return;
        }

        const selection = doc.getSelection();
        const from = doc.getCursor('from');
        const to = doc.getCursor('to');
        const startIndex = doc.indexFromPos(from);
        const endIndex = doc.indexFromPos(to);
        const content = selection.length ? selection : definition.placeholder;
        const replacement = `${definition.open}${content}${definition.close}`;
        doc.replaceSelection(replacement, 'around');

        if (content.length) {
            const selectionFrom = doc.posFromIndex(startIndex + definition.open.length);
            const selectionTo = doc.posFromIndex(
                startIndex + definition.open.length + (selection.length ? (endIndex - startIndex) : content.length)
            );
            if (!selection.length) {
                doc.setSelection(selectionFrom, selectionTo);
            } else {
                doc.setSelection(selectionFrom, selectionTo);
            }
        }

        editor.focus();
        refreshBBCodeEditorUIState();
    };

    const insertBBCodeSnippet = (name) => {
        if (!editor || editor.getOption('readOnly')) {
            return;
        }

        const selection = editor.getDoc().getSelection();
        switch (name) {
            case 'url':
                if (selection.length) {
                    wrapSelectionWithBBCode({ open: '[url]', close: '[/url]', placeholder: '' });
                } else {
                    const template = '[url=https://example.com]link text[/url]';
                    const urlStart = template.indexOf('https://example.com');
                    insertBBCodeTemplate(template, urlStart, urlStart + 'https://example.com'.length);
                }
                break;
            case 'img':
                if (selection.length) {
                    wrapSelectionWithBBCode({ open: '[img]', close: '[/img]', placeholder: '' });
                } else {
                    const template = '[img]https://example.com/image.png[/img]';
                    const urlStart = template.indexOf('https://example.com/image.png');
                    insertBBCodeTemplate(template, urlStart, urlStart + 'https://example.com/image.png'.length);
                }
                break;
            case 'list': {
                const lines = selection.length
                    ? selection.split(/\r?\n/).map((line) => line.trim()).filter((line) => line.length > 0)
                    : [];
                const items = (lines.length ? lines : ['First item', 'Second item']).map((line) => `[*]${line}`);
                const template = `[list]${items.join('\n')}[/list]`;
                const selectionStart = template.indexOf(items[0]);
                const selectionEnd = selectionStart + items.join('\n').length;
                insertBBCodeTemplate(template, selectionStart, selectionEnd);
                break;
            }
            case 'table': {
                const template = [
                    '[table]',
                    '[tr][td]Column 1[/td][td]Column 2[/td][/tr]',
                    '[tr][td]Value 1[/td][td]Value 2[/td][/tr]',
                    '[/table]'
                ].join('\n');
                const selectionStart = template.indexOf('Column 1');
                const selectionEnd = template.indexOf('[/table]') - 1;
                insertBBCodeTemplate(template, selectionStart, selectionEnd);
                break;
            }
            default:
                break;
        }
    };

    const bindBBCodeToolbar = () => {
        const toolbar = document.getElementById('bbcodeTagToolbar');
        if (!toolbar || toolbar.dataset.bound === '1') {
            return;
        }

        toolbar.dataset.bound = '1';
        toolbar.addEventListener('mousedown', (event) => {
            if (event.target.closest('.bbcode-tag-button')) {
                event.preventDefault();
            }
        });
        toolbar.addEventListener('click', (event) => {
            const button = event.target.closest('.bbcode-tag-button');
            if (!button || button.disabled) {
                return;
            }

            const action = button.getAttribute('data-bbcode-action');
            if (action === 'wrap') {
                wrapSelectionWithBBCode(bbcodeWrapDefinitions[button.getAttribute('data-bbcode-tag')]);
            } else if (action === 'snippet') {
                insertBBCodeSnippet(button.getAttribute('data-bbcode-snippet'));
                refreshBBCodeEditorUIState();
            }
        });
    };

    const createBBCodeRangeHint = (from, to, insertText, displayText) => ({
        text: insertText,
        displayText,
        hint: (cm, _data, completion) => {
            cm.replaceRange(completion.text, from, to);
        }
    });

    const createBBCodeActionHint = (from, to, displayText, apply) => ({
        text: displayText,
        displayText,
        hint: (cm) => {
            cm.replaceRange('', from, to);
            apply();
        }
    });

    const bbcodeHint = (cm) => {
        const cursor = cm.getCursor();
        const line = cm.getLine(cursor.line);
        const beforeCursor = line.slice(0, cursor.ch);
        const tagOpenIndex = beforeCursor.lastIndexOf('[');
        const tagCloseIndex = beforeCursor.lastIndexOf(']');
        const list = [];

        if (tagOpenIndex > tagCloseIndex) {
            const rawTagQuery = beforeCursor.slice(tagOpenIndex + 1);
            const normalizedQuery = rawTagQuery.toLowerCase();
            const from = CodeMirror.Pos(cursor.line, tagOpenIndex + 1);
            const to = CodeMirror.Pos(cursor.line, cursor.ch);

            if (normalizedQuery.startsWith('/')) {
                const openTags = getBBCodeTagStackAtIndex(cm.getValue(), cm.indexFromPos(cursor))
                    .slice()
                    .reverse()
                    .filter((tagName, index, arr) => arr.indexOf(tagName) === index);
                const closePrefix = normalizedQuery.slice(1);
                openTags
                    .filter((tagName) => tagName.startsWith(closePrefix))
                    .forEach((tagName) => {
                        list.push(createBBCodeRangeHint(from, to, `/${tagName}]`, `/${tagName}]`));
                    });
            } else if (/^[a-z*]*$/i.test(rawTagQuery)) {
                bbcodeAutocompleteTagDefinitions
                    .filter((entry) => entry.name.startsWith(normalizedQuery))
                    .forEach((entry) => {
                        list.push(createBBCodeRangeHint(from, to, `${entry.name}]`, `${entry.name}]  ${entry.description}`));
                    });
            }

            return list.length ? { list, from, to } : null;
        }

        const wordMatch = beforeCursor.match(/[a-z*]+$/i);
        const word = wordMatch ? wordMatch[0].toLowerCase() : '';
        const from = CodeMirror.Pos(cursor.line, cursor.ch - word.length);
        const to = CodeMirror.Pos(cursor.line, cursor.ch);
        bbcodeSnippetDefinitions
            .filter((entry) => !word.length || entry.key.startsWith(word))
            .forEach((entry) => {
                list.push(createBBCodeActionHint(from, to, entry.displayText, entry.apply));
            });

        return list.length ? { list, from, to } : null;
    };

    const maybeTriggerBBCodeAutocomplete = (cm, change) => {
        if (!change || !change.text || change.text.length !== 1 || cm.state.completionActive) {
            return;
        }

        const insertedText = change.text[0];
        if (!insertedText) {
            return;
        }

        const shouldTrigger = insertedText === '[' || insertedText === '/' || /^[a-z*]$/i.test(insertedText);
        if (!shouldTrigger) {
            return;
        }

        const cursor = cm.getCursor();
        const line = cm.getLine(cursor.line);
        const beforeCursor = line.slice(0, cursor.ch);
        const tagOpenIndex = beforeCursor.lastIndexOf('[');
        const tagCloseIndex = beforeCursor.lastIndexOf(']');
        if (tagOpenIndex <= tagCloseIndex) {
            return;
        }

        cm.showHint({ hint: bbcodeHint, completeSingle: false });
    };

    const normalizePreviewSearchText = (text) => (text || '')
        .replace(/\[(\/)?([a-zA-Z*]+)(?:=[^\]\n]*)?(\s*\/)?\]/g, ' ')
        .replace(/\s+/g, ' ')
        .trim()
        .toLowerCase();

    const clearLinkedPreviewElement = () => {
        if (bbcodePreviewLinkedElement) {
            bbcodePreviewLinkedElement.classList.remove('bbcode-preview-linked');
            bbcodePreviewLinkedElement = null;
        }
    };

    const findPreviewLinkQuery = () => {
        if (!editor) {
            return '';
        }

        const doc = editor.getDoc();
        const selectionText = normalizePreviewSearchText(doc.getSelection());
        if (selectionText.length >= 4) {
            return selectionText;
        }

        const lineText = normalizePreviewSearchText(doc.getLine(doc.getCursor().line));
        if (lineText.length >= 4) {
            return lineText;
        }

        return '';
    };

    const linkEditorSelectionToPreview = (scrollIntoViewIfFound) => {
        const previewContent = document.getElementById('bbcodePreviewContent');
        if (!previewContent) {
            return;
        }

        const query = findPreviewLinkQuery();
        if (!query.length) {
            bbcodeLastLinkedPreviewQuery = '';
            clearLinkedPreviewElement();
            return;
        }
        if (query === bbcodeLastLinkedPreviewQuery && bbcodePreviewLinkedElement) {
            return;
        }

        const candidates = Array.from(previewContent.querySelectorAll('p, li, blockquote, .codebox, td, dd, dt, div, span'))
            .filter((element) => {
                if (!element || element.children.length > 8) {
                    return false;
                }
                const normalizedText = normalizePreviewSearchText(element.textContent);
                return normalizedText.length >= query.length && normalizedText.indexOf(query) !== -1;
            })
            .sort((left, right) => normalizePreviewSearchText(left.textContent).length - normalizePreviewSearchText(right.textContent).length);

        clearLinkedPreviewElement();
        bbcodeLastLinkedPreviewQuery = query;

        if (!candidates.length) {
            return;
        }

        bbcodePreviewLinkedElement = candidates[0];
        bbcodePreviewLinkedElement.classList.add('bbcode-preview-linked');
        if (scrollIntoViewIfFound) {
            bbcodePreviewLinkedElement.scrollIntoView({ block: 'nearest', inline: 'nearest' });
        }
    };

    const syncEditorScrollToPreview = () => {
        const previewPane = document.getElementById('bbcodePreview');
        if (!editor || !previewPane || bbcodePreviewSyncingFromPane) {
            return;
        }

        const scroller = editor.getScrollerElement();
        const editorScrollable = scroller.scrollHeight - scroller.clientHeight;
        const previewScrollable = previewPane.scrollHeight - previewPane.clientHeight;
        if (editorScrollable <= 0 || previewScrollable <= 0) {
            return;
        }

        const ratio = scroller.scrollTop / editorScrollable;
        bbcodePreviewSyncingFromEditor = true;
        previewPane.scrollTop = ratio * previewScrollable;
        window.requestAnimationFrame(() => { bbcodePreviewSyncingFromEditor = false; });
    };

    const syncPreviewScrollToEditor = () => {
        const previewPane = document.getElementById('bbcodePreview');
        if (!editor || !previewPane || bbcodePreviewSyncingFromEditor) {
            return;
        }

        const scroller = editor.getScrollerElement();
        const editorScrollable = scroller.scrollHeight - scroller.clientHeight;
        const previewScrollable = previewPane.scrollHeight - previewPane.clientHeight;
        if (editorScrollable <= 0 || previewScrollable <= 0) {
            return;
        }

        const ratio = previewPane.scrollTop / previewScrollable;
        bbcodePreviewSyncingFromPane = true;
        scroller.scrollTop = ratio * editorScrollable;
        window.requestAnimationFrame(() => { bbcodePreviewSyncingFromPane = false; });
    };

    const setupBBCodePreviewSync = () => {
        const previewPane = document.getElementById('bbcodePreview');
        if (!editor || !previewPane || previewPane.dataset.syncBound === '1') {
            return;
        }

        previewPane.dataset.syncBound = '1';
        editor.on('scroll', syncEditorScrollToPreview);
        editor.on('cursorActivity', () => { linkEditorSelectionToPreview(false); });
        previewPane.addEventListener('scroll', syncPreviewScrollToEditor);
        syncEditorScrollToPreview();
    };

    const updateBBCodeToolbarState = () => {
        const toolbar = document.getElementById('bbcodeTagToolbar');
        if (!toolbar) {
            return;
        }

        const isReadOnly = !editor || editor.getOption('readOnly');
        let activeTags = [];
        if (editor) {
            const cursorIndex = editor.indexFromPos(editor.getCursor());
            activeTags = getBBCodeTagStackAtIndex(editor.getValue(), cursorIndex);
        }

        toolbar.querySelectorAll('.bbcode-tag-button').forEach((button) => {
            const tagName = button.getAttribute('data-bbcode-tag');
            button.disabled = isReadOnly;
            button.classList.toggle('active', !!tagName && activeTags.indexOf(tagName) !== -1);
        });
    };

    const clearBBCodeInlineStyleMarks = () => {
        bbcodeInlineStyleMarks.forEach((mark) => mark.clear());
        bbcodeInlineStyleMarks = [];
    };

    const getBBCodeInlineStyleClassName = (activeTags) => {
        if (activeTags.length === 0) {
            return '';
        }

        const tagSet = new Set(activeTags);
        const classNames = ['cm-bbcode-inline'];
        if (tagSet.has('b')) { classNames.push('cm-bbcode-inline-bold'); }
        if (tagSet.has('i')) { classNames.push('cm-bbcode-inline-italic'); }
        if (tagSet.has('u') && tagSet.has('s')) {
            classNames.push('cm-bbcode-inline-decoration-both');
        } else if (tagSet.has('u')) {
            classNames.push('cm-bbcode-inline-decoration-underline');
        } else if (tagSet.has('s')) {
            classNames.push('cm-bbcode-inline-decoration-strikethrough');
        }

        return classNames.join(' ');
    };

    const _updateBBCodeInlineStyleMarksImpl = () => {
        if (!editor) {
            return;
        }

        clearBBCodeInlineStyleMarks();

        const src = editor.getValue();
        if (!src.length) {
            return;
        }

        const tagRe = /\[(\/)?([a-zA-Z*]+)(?:=[^\]\n]*)?(\s*\/)?\]/g;
        const stack = [];
        let inCodeDepth = 0;
        let lastIndex = 0;
        let m;

        const addStyledRange = (fromIndex, toIndex) => {
            if (inCodeDepth > 0 || toIndex <= fromIndex) {
                return;
            }

            const className = getBBCodeInlineStyleClassName(stack);
            if (!className) {
                return;
            }

            bbcodeInlineStyleMarks.push(editor.markText(
                editor.posFromIndex(fromIndex),
                editor.posFromIndex(toIndex),
                { className }
            ));
        };

        while ((m = tagRe.exec(src)) !== null) {
            const idx = m.index;
            addStyledRange(lastIndex, idx);

            const isClosing = !!m[1];
            const name = (m[2] || '').toLowerCase();
            const trailingSlash = !!(m[3] && m[3].trim().length);

            if (name === 'code' && !trailingSlash) {
                if (isClosing) {
                    inCodeDepth = Math.max(0, inCodeDepth - 1);
                } else {
                    inCodeDepth++;
                }
            } else if (bbcodeInlineTagNames.has(name) && inCodeDepth === 0 && !trailingSlash) {
                if (isClosing) {
                    for (let i = stack.length - 1; i >= 0; i--) {
                        if (stack[i] === name) {
                            stack.splice(i, 1);
                            break;
                        }
                    }
                } else {
                    stack.push(name);
                }
            }

            lastIndex = tagRe.lastIndex;
        }

        addStyledRange(lastIndex, src.length);
    };

    const updateBBCodeInlineStyleMarks = debounce(_updateBBCodeInlineStyleMarksImpl, 100);

    function init_post_js_1()
    {
        textarea = document.getElementById('codearea');
        fakeContainer = document.getElementById('fakeContainer');

        /* CodeMirror init */
        CodeMirror.commands.autocomplete = function(cm) { cm.showHint({ hint: bbcodeHint, completeSingle: false }); };

        const toggleComment = () => { editor.execCommand('toggleComment') }
        editor = CodeMirror.fromTextArea(textarea, {
            lineNumbers: true,
            lineWrapping: true,
            styleActiveLine: true,
            matchBrackets: true,
            indentWithTabs: false,
            indentUnit: 4,
            tabSize: 4,
            foldGutter: true,
            showTrailingSpace: true,
            dragDrop: false,
            mode: 'bbcode',
            gutters: ["CodeMirror-linenumbers", "CodeMirror-foldgutter"],
            extraKeys: {"Ctrl-Space": "autocomplete", 'Ctrl-/': toggleComment, 'Cmd-/': toggleComment, 'Ctrl-B': () => wrapSelectionWithBBCode(bbcodeWrapDefinitions.b), 'Cmd-B': () => wrapSelectionWithBBCode(bbcodeWrapDefinitions.b), 'Ctrl-I': () => wrapSelectionWithBBCode(bbcodeWrapDefinitions.i), 'Cmd-I': () => wrapSelectionWithBBCode(bbcodeWrapDefinitions.i), 'Ctrl-U': () => wrapSelectionWithBBCode(bbcodeWrapDefinitions.u), 'Cmd-U': () => wrapSelectionWithBBCode(bbcodeWrapDefinitions.u) },
            highlightSelectionMatches: {showToken: /\w/},
            theme: 'xq-light',
            readOnly: <?= $currProject->canUserEditCurrentFile($currUser) ? 'false' : 'true' ?>
        });
        updateBBCodeInlineStyleMarks();
        bindBBCodeToolbar();
        bindPreviewLanguageToolbar();
        updateBBCodeToolbarState();
        updateBBCodePairHighlight();
        editor.on('inputRead', maybeTriggerBBCodeAutocomplete);
        savedSinceLastChange = true; lastChangeTS = (new Date).getTime();
    }

    // Basic BBCode analyzer: produces stats and errors for user feedback
    const analyzeBBCode = (src) => {
        const result = {
            totalTags: 0,
            opened: 0,
            closed: 0,
            selfClosed: 0,
            errors: [],
            warnings: [],
            lines: (src.match(/\n/g) || []).length + 1,
            length: src.length,
        };

        const selfClosing = new Set(['*']);
        const balancedLikely = new Set(['b','i','u','s','url','img','quote','code','list','color','size','center','left','right','table','tr','td','spoiler','indent','align']);
        const tagRe = /\[(\/)?([a-zA-Z*]+)(?:=([^\]\n]*))?(\s*\/)?\]/g; // [tag], [tag=...], [/tag], [tag /]

        const stack = [];
        let m;
        let inCode = false; // ignore parsing inside [code]...[/code]

        const posToLineCol = (idx) => {
            let line = 1, col = 1;
            for (let i = 0; i < idx && i < src.length; i++) {
                if (src.charCodeAt(i) === 10) { line++; col = 1; } else { col++; }
            }
            return { line, col };
        };

        while ((m = tagRe.exec(src)) !== null) {
            const isClosing = !!m[1];
            const rawName = m[2] || '';
            const trailingSlash = !!(m[4] && m[4].trim().length);
            const name = rawName.toLowerCase();
            const idx = m.index;

            // Count totals
            result.totalTags++;

            // Handle [code] sections: toggle when encountering open/close
            if (!isClosing && name === 'code' && !trailingSlash) {
                stack.push({ name, index: idx });
                result.opened++;
                inCode = true;
                continue;
            }
            if (isClosing && name === 'code') {
                // find last code in stack
                let foundAt = -1;
                for (let i = stack.length - 1; i >= 0; i--) { if (stack[i].name === 'code') { foundAt = i; break; } }
                if (foundAt === -1) {
                    const { line, col } = posToLineCol(idx);
                    result.errors.push({ type: 'orphanClosing', message: `Closing [/code] without an opening [code]`, line, col });
                } else {
                    stack.splice(foundAt, 1);
                    result.closed++;
                }
                inCode = false;
                continue;
            }

            if (inCode) { continue; }

            const isSelfClosing = selfClosing.has(name) || (!isClosing && trailingSlash);

            if (!isClosing) {
                if (isSelfClosing) {
                    result.selfClosed++;
                } else {
                    stack.push({ name, index: idx });
                    result.opened++;
                }
            } else {
                // closing tag
                if (stack.length === 0) {
                    const { line, col } = posToLineCol(idx);
                    result.errors.push({ type: 'orphanClosing', message: `Closing [/${name}] without an opening [${name}]`, line, col });
                } else {
                    const top = stack[stack.length - 1];
                    if (top.name === name) {
                        stack.pop();
                        result.closed++;
                    } else {
                        // Search for matching open deeper in the stack
                        let foundAt = -1;
                        for (let i = stack.length - 1; i >= 0; i--) {
                            if (stack[i].name === name) { foundAt = i; break; }
                        }
                        const { line, col } = posToLineCol(idx);
                        if (foundAt === -1) {
                            result.errors.push({ type: 'mismatch', message: `Closing [/${name}] does not match the last opened [${top.name}]`, line, col });
                        } else {
                            // Unclosed tags between foundAt and top
                            for (let i = stack.length - 1; i > foundAt; i--) {
                                const unclosed = stack[i];
                                const p = posToLineCol(unclosed.index);
                                result.errors.push({ type: 'prematureClose', message: `Tag [${unclosed.name}] was not closed before closing [/${name}]`, line: p.line, col: p.col });
                            }
                            stack.splice(foundAt, 1);
                            result.closed++;
                        }
                    }
                }
            }
        }

        // Remaining unclosed tags
        for (const t of stack) {
            if (!balancedLikely.has(t.name)) { continue; }
            const p = posToLineCol(t.index);
            result.errors.push({ type: 'unclosed', message: `Tag [${t.name}] opened here is not closed`, line: p.line, col: p.col });
        }

        // Prepare HTML
        const errorItems = result.errors.map(e => `<li><span style="color:#b00;">${e.message}</span> <span style="opacity:.7;">(line ${e.line}, col ${e.col})</span></li>`).join('');
        const warnItems = result.warnings.map(e => `<li>${e}</li>`).join('');
        const statusColor = result.errors.length ? '#b00' : '#2b7a0b';
        const statusText = result.errors.length ? `${result.errors.length} error${result.errors.length>1?'s':''} detected` : 'No BBCode structural issues detected';

        const html = `
            <div id="bbcodeAnalysis" style="font:13px/1.4 -apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Helvetica,Arial,sans-serif;">
                <div style="display:flex;gap:16px;flex-wrap:wrap;align-items:center;">
                    <strong>BBCode analysis</strong>
                    <span style="color:${statusColor}">${statusText}</span>
                    <span style="opacity:.8">Lines: ${result.lines}</span>
                    <span style="opacity:.8">Chars: ${result.length}</span>
                    <span style="opacity:.8">Tags: ${result.totalTags} (open: ${result.opened}, closed: ${result.closed}, self: ${result.selfClosed})</span>
                </div>
                ${warnItems ? `<ul style="margin:6px 0 0 18px;color:#a60;">${warnItems}</ul>` : ''}
                ${errorItems ? `<ul style="margin:6px 0 0 18px;">${result.errors.map(e => `
                    <li>
                        <span style="color:#b00;">${e.message}</span>
                        <a href="#" class="bbcode-jump" data-line="${e.line}" data-col="${e.col}" style="margin-left:6px; text-decoration: underline; color: #06c;">
                            (line ${e.line}, col ${e.col})
                        </a>
                    </li>`).join('')}</ul>` : ''}
            </div>`;

        return { ...result, html };
    };

    const _updatePreviewImpl = () => {
        const src = editor.getValue().trim();
        if (!src.length) { return; }
        ajaxAction('preview_bbcode', `source=${encodeURIComponent(src)}`, (resp) => {
            document.getElementById('bbcodePreviewContent').innerHTML = (resp && resp.html) ? resp.html : '';
            document.getElementById('bbcodeRenderTime').textContent = (resp && resp.renderTime) ? (resp.renderTime + 'ms') : '?';
            applyPreviewLanguageMode();
            window.do_mathJax && do_mathJax();
            window.do_highlight_codes && do_highlight_codes();

            // Run analysis locally and render it in a dedicated dock below the preview
            try {
                const analysis = analyzeBBCode(src);
                const dock = document.getElementById('bbcodeAnalysisDock');
                if (dock) {
                    dock.innerHTML = analysis.html;
                    dock.style.minHeight = dock.style.maxHeight = Math.min(300, Math.max(75, $("#bbcodeAnalysis").height())) + 'px';
                    // Wire click-to-jump handlers
                    dock.querySelectorAll('.bbcode-jump').forEach((a) => {
                        a.addEventListener('click', (ev) => {
                            ev.preventDefault();
                            const line = Math.max(0, parseInt(a.getAttribute('data-line') || '1', 10) - 1);
                            const ch = Math.max(0, parseInt(a.getAttribute('data-col') || '1', 10) - 1);
                            try {
                                editor.focus();
                                editor.setCursor({ line, ch });
                                editor.scrollIntoView({ line, ch }, 100);
                            } catch (_) { /* noop */ }
                        });
                    });
                }
            } catch(e) {
                // Best-effort: ignore analysis errors
            }
            syncEditorScrollToPreview();
            linkEditorSelectionToPreview(false);
        }, () => {}, null);
    };
    const updatePreview = debounce(_updatePreviewImpl, 400);

    globalSyncOK = true;

    function init_post_js_2()
    {
        const editorContainer = $('#editorContainer');
        const saveBtn = document.getElementById('saveButton');
        const editorRuntimeSession = beginEditorRuntimeSession();
        const isActiveRuntimeSession = () => isCurrentEditorRuntimeSession(editorRuntimeSession);

        <?php if ($pm->currentUserHasLiveCollabEditAccess()) { ?>

        window.Firebase?.INTERNAL?.forceWebSockets();
        firebaseRoot = new Firebase('https://glowing-torch-6891.firebaseio.com/pb_tip/');
        var collabBindings = null;
        var collabSessionBlockedByAuth = false;
        firebaseRoot.authWithCustomToken(user.firebase_token, (error, authData) => {
            if (!isActiveRuntimeSession()) {
                return;
            }
            if (error) { // possibly expired token, etc.
                collabSessionBlockedByAuth = true;
                collabBindings && collabBindings.setSessionBlocked(true);
                globalSyncOK = false;
                window.onunload = window.onbeforeunload = null;
                showNotification("danger", "Shared-project session expired or invalid - it will be regenerated now",
                    "You might want to backup any unsaved changes...", null, 999999);
                setTimeout( () => {
                    ajaxAction("refreshFirebaseToken", "",
                        (text) => {
                            showNotification("success", "Collaborative edition token refreshed", "Reload the page to continue.", null, 999999);
                        },
                        (text) => {
                            showNotification("danger", "Collaborative edition token refresh failed: ", (text || "") + "Reload the page to continue.", null, 999999);
                        });
                }, 1000);
            }
        });

        const firepadRef = firebaseRoot.child(`codes/${proj.pid}/${proj.currFile.replace('.', '~')}`);

        collabBindings = createCollaborativeFirepadBindings({
            firepadRef: firepadRef,
            editor: editor,
            user: user,
            userListElement: document.getElementById('userlist'),
            isActiveRuntimeSession: isActiveRuntimeSession,
            getServerSource: () => fakeContainer.value || fakeContainer.textContent || '',
            onHealthy: () => { globalSyncOK = true; },
            onStalled: (title, message) => {
                globalSyncOK = false;
                showNotification("danger", title, message, null, 999999);
            },
            onReady: ({ serverSource, liveSource, usedServerSource }) => {
                lastSavedSource = usedServerSource ? serverSource : liveSource;

                editorContainer.css('pointer-events', 'auto');

                proj.cursors[proj.currFile] && editor.setCursor(JSON.parse(proj.cursors[proj.currFile]));

                const hashMatches = window.location.hash.match(/#L(\d+)/);
                if (hashMatches && hashMatches.length > 1)
                {
                    const lineFromHash = hashMatches.pop();
                    if (lineFromHash) {
                        editor.setCursor((+lineFromHash)-1, 0);
                    }
                }

                savedSinceLastChange = true;
                lastChangeTS = (new Date).getTime();
                document.getElementById('saveButton').disabled = true;
            }
        });
        const createOrResetFirepad = registerCollaborativeFirepadGlobals({
            getBindings: () => collabBindings,
            isCollaborative: () => proj.is_multi,
            isSessionBlocked: () => collabSessionBlockedByAuth
        });

        createOrResetFirepad();

        <?php } else { ?>

        const initialText = fakeContainer.value || '';
        editor.setValue(initialText);
        lastSavedSource = initialText;
        if (saveBtn) { saveBtn.disabled = true; }

        editorContainer.css('pointer-events', 'auto');

        <?php } ?>

        editor.on('change', () => {
            savedSinceLastChange = false;
            if (saveBtn) { saveBtn.disabled = false; }
            updateBBCodeInlineStyleMarks();
            updateBBCodeToolbarState();
            updateBBCodePairHighlight();
            updatePreview();
        });
        editor.on('cursorActivity', () => {
            updateBBCodeToolbarState();
            updateBBCodePairHighlight();
        });
        setupBBCodePreviewSync();
        // Show first preview
        updateBBCodeInlineStyleMarks();
        updateBBCodeToolbarState();
        updateBBCodePairHighlight();
        updatePreview();
    }

    // Initialize immediately on template load
    init_post_js_1();
    init_post_js_2();

    <?php if ($pm->currentUserHasLiveCollabEditAccess() && $currProject->isChatEnabled()) { ?>

    function init_chat()
    {
        const chatRef = firebaseRoot.child(`chat/${proj.pid}`);
        let chat = null;

        chatRef.onAuth(authData => {
            if (authData)
            {
                chat = new FirechatUI(chatRef, document.getElementById('firechat-wrapper'));
                chat.setUser(user.id, user.name);
                setTimeout(() => {
                    chat._chat.getRoomList(rooms => {
                        let found = false;
                        let roomkey;
                        for (roomkey in rooms)
                        {
                            if (!rooms.hasOwnProperty(roomkey)) {
                                continue;
                            }
                            const room = rooms[roomkey];
                            if (room.name == proj.pid)
                            {
                                found = true;
                                chat._chat.enterRoom(room.id);
                                break;
                            }
                        }
                        if (!found)
                        {
                            chat._chat.createRoom(proj.pid, "public", (roomID) => {});
                        }
                    });
                }, 2000);
            }
        });
    }
    init_chat();
    <?php } ?>

    // Resizable vertical split between editor and preview
    (function(){
        const container = document.getElementById('bbcodeSplitContainer');
        const editorPane = document.getElementById('bbcodeEditorPane');
        const previewPane = document.getElementById('bbcodePreviewPane');
        const splitter = document.getElementById('bbcodeSplitter');
        if (!container || !editorPane || !previewPane || !splitter) { return; }

        const storageKey = `bbcode_split_${(window.proj && proj.pid) ? proj.pid : 'default'}`;
        const saved = localStorage.getItem(storageKey);
        if (saved) {
            const pct = Math.max(15, Math.min(85, parseFloat(saved)));
            editorPane.style.flex = `0 0 ${pct}%`;
            previewPane.style.flex = '1 1 auto';
        }

        let dragging = false;
        const applyAt = (clientX) => {
            const rect = container.getBoundingClientRect();
            const relX = clientX - rect.left;
            const pct = Math.max(15, Math.min(85, (relX / rect.width) * 100));
            editorPane.style.flex = `0 0 ${pct}%`;
            previewPane.style.flex = '1 1 auto';
            localStorage.setItem(storageKey, pct.toFixed(2));
        };

        const onMouseMove = (e) => { if (dragging) { e.preventDefault(); applyAt(e.clientX); } };
        const onTouchMove = (e) => { if (dragging && e.touches && e.touches[0]) { e.preventDefault(); applyAt(e.touches[0].clientX); } };
        const stopDrag = () => { dragging = false; document.body.style.cursor = ''; document.body.style.userSelect = ''; };

        splitter.addEventListener('mousedown', (e) => { dragging = true; document.body.style.cursor = 'col-resize'; document.body.style.userSelect = 'none'; e.preventDefault(); });
        splitter.addEventListener('touchstart', () => { dragging = true; document.body.style.userSelect = 'none'; });
        window.addEventListener('mouseup', stopDrag);
        window.addEventListener('touchend', stopDrag);
        window.addEventListener('mousemove', onMouseMove);
        window.addEventListener('touchmove', onTouchMove, { passive: false });
    })();
</script>
