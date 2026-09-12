/* Part of TI-Planet's Project Builder. GPL-3.0-or-later. */

function createBBCodePreviewSync(editor, pane, content, checkbox, storageKey)
{
    let renderedSource = null;
    let sourceIsCurrent = false;
    let dirty = true;
    let geometryDirty = true;
    let matches = [];
    let anchors = [];
    let lineStarts = [];
    let previewPoints = [];
    let linkedElement = null;
    let expectedPreviewScroll = null;
    let expectedEditorScroll = null;
    let frame = null;
    let revealCursor = false;
    let alignCursorOnNextLink = false;

    // Return the first item whose key is >= the requested value.
    const lowerBound = (items, value, key, end = items.length) => {
        let low = 0, high = end;
        while (low < high) {
            const middle = (low + high) >>> 1;
            if (key(items[middle]) < value) { low = middle + 1; } else { high = middle; }
        }
        return low;
    };

    try { checkbox.checked = localStorage.getItem(storageKey) !== 'false'; } catch (_) { /* optional preference */ }

    const clearHighlight = () => {
        if (linkedElement) { linkedElement.classList.remove('bbcode-preview-linked'); }
        linkedElement = null;
    };
    const withoutHighlight = (value) => (value || '').replace(/\bbbcode-preview-linked\b/g, '').replace(/\s+/g, ' ').trim();
    const observer = new MutationObserver((mutations) => {
        if (mutations.some((mutation) => mutation.attributeName !== 'class' ||
            withoutHighlight(mutation.oldValue) !== withoutHighlight(mutation.target.getAttribute('class')))) {
            dirty = true;
        }
    });
    // Syntax highlighting and MathJax can replace text nodes after a render.
    observer.observe(content, {
        childList: true, subtree: true, characterData: true,
        attributes: true, attributeOldValue: true, attributeFilter: ['class', 'style', 'hidden', 'open']
    });
    const resizeObserver = new ResizeObserver(() => { geometryDirty = true; });
    resizeObserver.observe(content);
    resizeObserver.observe(pane);
    // Image loads need not mutate the DOM; their dimensions still move anchors.
    const onLoad = () => { geometryDirty = true; };
    content.addEventListener('load', onLoad, true);

    const rebuild = () => {
        clearHighlight();
        content.querySelectorAll('.bbcode-preview-linked').forEach((element) => element.classList.remove('bbcode-preview-linked'));
        matches = [];
        previewPoints = [];
        lineStarts = [0];
        for (let i = 0; i < renderedSource.length; i++) {
            if (renderedSource[i] === '\n') { lineStarts.push(i + 1); }
        }
        const text = [];
        const visit = (node) => {
            if (node.nodeType === Node.TEXT_NODE) {
                for (let i = 0; i < node.data.length; i++) {
                    if (!/\s/.test(node.data[i])) {
                        text.push(node.data[i]);
                        previewPoints.push({ node, offset: i });
                    }
                }
            } else if (node.nodeType === Node.ELEMENT_NODE) {
                if (node.matches('script, style, .codebox > dt, .MathJax_Preview, .MathJax, .MathJax_Display, .MathJax_SVG, .MathJax_MathML') ||
                    getComputedStyle(node).display === 'none' || getComputedStyle(node).visibility === 'hidden') {
                    return;
                }
                node.childNodes.forEach(visit);
            }
        };
        visit(content);
        // Ignore whitespace on both sides: <br>, wrapping and inline BBCode
        // boundaries need not produce the same text-node layout as the source.
        const previewText = text.join('');
        let previewOffset = 0;
        const addFragment = (start, end) => {
            let query = '';
            let offsets = [];
            const flush = () => {
                if (query.length >= 4) {
                    const found = previewText.indexOf(query, previewOffset);
                    if (found !== -1) {
                        matches.push({ offsets, previewStart: found });
                        // Matching in document order distinguishes repeated paragraphs.
                        previewOffset = found + query.length;
                    }
                }
                query = '';
                offsets = [];
            };
            for (let i = start; i < end; i++) {
                if (!/\s/.test(renderedSource[i])) {
                    query += renderedSource[i];
                    offsets.push(i);
                    if (query.length === 48) { flush(); }
                }
            }
            flush();
        };
        const tokenRe = /\[(\/)?([a-zA-Z*]+)(?:=[^\]\n]*)?(\s*\/)?\]|\n/g;
        let inCode = false;
        let start = 0;
        let token;
        while ((token = tokenRe.exec(renderedSource)) !== null) {
            const name = (token[2] || '').toLowerCase();
            if (inCode && token[0] !== '\n' && !(name === 'code' && token[1])) { continue; }
            addFragment(start, token.index);
            if (name === 'code') { inCode = !token[1]; }
            start = tokenRe.lastIndex;
        }
        addFragment(start, renderedSource.length);
        dirty = false;
        geometryDirty = true;
    };

    const ready = () => {
        // A pending preview must never map newly edited text onto an old render.
        if (!content.isConnected || !sourceIsCurrent) {
            clearHighlight();
            return false;
        }
        if (dirty) { rebuild(); }
        return true;
    };
    const rangeAt = (index) => {
        const point = previewPoints[index];
        const range = document.createRange();
        range.setStart(point.node, point.offset);
        range.setEnd(point.node, point.offset + 1);
        return range;
    };
    const previewY = (index) => rangeAt(index).getBoundingClientRect().top -
        pane.getBoundingClientRect().top - pane.clientTop + pane.scrollTop;
    const sourcePosition = (index) => {
        const line = Math.max(0, lowerBound(lineStarts, index + 1, (start) => start) - 1);
        return { line, ch: index - lineStarts[line] };
    };
    const editorY = (position) => {
        // charCoords() renders/measures offscreen lines. The height tree can
        // locate them without disturbing CodeMirror's virtualized viewport.
        const top = editor.heightAtLine(position.line, 'local');
        if (!position.ch) { return top; }
        const height = editor.heightAtLine(position.line + 1, 'local') - top;
        const length = (editor.getLine(position.line) || '').length;
        const wrappedHeight = Math.max(0, height - editor.defaultTextHeight());
        return top + wrappedHeight * position.ch / Math.max(1, length);
    };
    const measureAnchors = () => {
        anchors = [];
        const origin = pane.getBoundingClientRect().top + pane.clientTop - pane.scrollTop;
        matches.forEach((match) => {
            [0, match.offsets.length - 1].forEach((i) => {
                const range = rangeAt(match.previewStart + i);
                const rect = range.getBoundingClientRect();
                const top = rect.top - origin;
                const last = anchors[anchors.length - 1];
                // Keep one anchor per rendered row and ignore reordered floats.
                if (rect.height && (!last || top > last.previewTop)) {
                    anchors.push({ position: sourcePosition(match.offsets[i]), previewTop: top });
                }
            });
        });
        geometryDirty = false;
    };

    const scrollPreview = (top) => {
        const target = Math.max(0, Math.min(top, pane.scrollHeight - pane.clientHeight));
        if (Math.abs(pane.scrollTop - target) < 1) { return; }
        pane.scrollTop = target;
        expectedPreviewScroll = pane.scrollTop;
    };
    const scrollEditor = (top) => {
        const info = editor.getScrollInfo();
        const target = Math.max(0, Math.min(top, info.height - info.clientHeight));
        if (Math.abs(info.top - target) < 1) { return; }
        editor.scrollTo(null, target);
        expectedEditorScroll = editor.getScrollInfo().top;
    };

    const linkSelection = (scroll) => {
        if (!ready()) { return false; }
        const position = editor.getCursor();
        const cursor = lineStarts[position.line] + position.ch;
        let nearest = null;
        let distance = Infinity;
        const matchIndex = lowerBound(matches, cursor, (match) => match.offsets[match.offsets.length - 1]);
        // Only the surrounding fragments can contain the closest source offset.
        matches.slice(Math.max(0, matchIndex - 1), matchIndex + 1).forEach((match) => {
            match.offsets.forEach((offset, i) => {
                const candidateDistance = Math.abs(cursor - offset);
                if (candidateDistance < distance) {
                    distance = candidateDistance;
                    nearest = match.previewStart + i;
                }
            });
        });
        if (nearest === null) { clearHighlight(); alignCursorOnNextLink = false; return false; }
        const range = rangeAt(nearest);
        const element = range.startContainer.parentElement;
        // Plain phpBB text may be a direct child of the preview root. Avoid
        // outlining the entire article; scrolling still targets the text range.
        if (element !== linkedElement) {
            clearHighlight();
        }
        if (element !== content && element !== linkedElement) {
            linkedElement = element;
            linkedElement.classList.add('bbcode-preview-linked');
        }
        if (scroll && checkbox.checked) {
            const rect = range.getBoundingClientRect();
            const top = previewY(nearest);
            if (alignCursorOnNextLink) {
                // One cursor measurement when re-enabling sync, never a scan of
                // offscreen lines during scrolling. Keep its relative pane position.
                const info = editor.getScrollInfo();
                const caretTop = editor.charCoords(position, 'local').top - info.top;
                const fraction = caretTop >= 0 && caretTop < info.clientHeight ? caretTop / info.clientHeight : 0.35;
                const inset = Math.max(16, Math.min(pane.clientHeight - rect.height - 16, fraction * pane.clientHeight));
                scrollPreview(top - inset);
                alignCursorOnNextLink = false;
            } else if (top < pane.scrollTop + 16) { scrollPreview(top - 16); }
            else if (top + rect.height > pane.scrollTop + pane.clientHeight - 16) {
                scrollPreview(top + rect.height - pane.clientHeight + 16);
            }
        }
        return true;
    };

    const sync = (fromEditor) => {
        if (!checkbox.checked || !ready()) { return; }
        const info = editor.getScrollInfo();
        const editorMax = Math.max(0, info.height - info.clientHeight);
        const previewMax = Math.max(0, pane.scrollHeight - pane.clientHeight);
        if (!editorMax || !previewMax) { return; }
        const fromMax = fromEditor ? editorMax : previewMax;
        const toMax = fromEditor ? previewMax : editorMax;
        const position = Math.max(0, Math.min(fromMax, fromEditor ? info.top : pane.scrollTop));
        if (geometryDirty) { measureAnchors(); }
        const coordinates = (anchor) => {
            const sourceTop = editorY(anchor.position);
            return fromEditor ? { from: sourceTop, to: anchor.previewTop } : { from: anchor.previewTop, to: sourceTop };
        };
        // O(log n) line-height lookups, with no per-scroll preview measurement.
        const usableCount = Math.min(
            lowerBound(anchors, editorMax, (anchor) => editorY(anchor.position)),
            lowerBound(anchors, previewMax, (anchor) => anchor.previewTop)
        );
        const rightIndex = lowerBound(anchors, position, (anchor) => fromEditor ? editorY(anchor.position) : anchor.previewTop, usableCount);
        let left = rightIndex > 0 ? coordinates(anchors[rightIndex - 1]) : { from: 0, to: 0 };
        const right = rightIndex < usableCount ? coordinates(anchors[rightIndex]) : { from: fromMax, to: toMax };
        // Preserve exact start/end alignment even when the panes differ in height.
        if (left.from <= 0 || left.to <= 0) { left = { from: 0, to: 0 }; }
        if (left.from >= right.from || left.to > right.to) { left = { from: 0, to: 0 }; }
        const ratio = Math.max(0, Math.min(1, (position - left.from) / Math.max(1, right.from - left.from)));
        const target = left.to + ratio * (right.to - left.to);
        if (fromEditor) { scrollPreview(target); } else { scrollEditor(target); }
    };
    const queueSync = (fromEditor) => {
        if (frame !== null) { cancelAnimationFrame(frame); }
        frame = requestAnimationFrame(() => {
            frame = null;
            sync(fromEditor);
            // CodeMirror can scroll after cursorActivity. Reveal the caret after
            // that scroll, without pulling it back into view during wheel scrolling.
            if (revealCursor && fromEditor) { linkSelection(true); }
            revealCursor = false;
        });
    };
    const scheduleSync = (fromEditor) => {
        if (!checkbox.checked) { return; }
        const expected = fromEditor ? expectedEditorScroll : expectedPreviewScroll;
        const actual = fromEditor ? editor.getScrollInfo().top : pane.scrollTop;
        if (fromEditor) { expectedEditorScroll = null; } else { expectedPreviewScroll = null; }
        if (expected !== null && Math.abs(actual - expected) < 1) { return; }
        queueSync(fromEditor);
    };
    const onEditorScroll = () => scheduleSync(true);
    const onPreviewScroll = () => scheduleSync(false);
    const onCursor = () => {
        linkSelection(false);
        if (checkbox.checked) { revealCursor = true; queueSync(true); }
    };
    const onChange = () => { sourceIsCurrent = false; clearHighlight(); };
    const onToggle = () => {
        try { localStorage.setItem(storageKey, String(checkbox.checked)); } catch (_) { /* optional preference */ }
        if (frame !== null) { cancelAnimationFrame(frame); frame = null; }
        revealCursor = false;
        alignCursorOnNextLink = checkbox.checked;
        if (checkbox.checked && !linkSelection(true)) { sync(true); }
        // Discard scroll events still queued from independently moving either pane.
        expectedEditorScroll = editor.getScrollInfo().top;
        expectedPreviewScroll = pane.scrollTop;
    };
    editor.on('scroll', onEditorScroll);
    editor.on('cursorActivity', onCursor);
    editor.on('change', onChange);
    pane.addEventListener('scroll', onPreviewScroll);
    checkbox.addEventListener('change', onToggle);

    return {
        refresh(source) {
            renderedSource = source;
            sourceIsCurrent = editor.getValue() === source;
            dirty = true;
            clearHighlight();
        },
        invalidate() { dirty = true; },
        linkSelection,
        syncFromEditor() { sync(true); },
        destroy() {
            observer.disconnect();
            resizeObserver.disconnect();
            content.removeEventListener('load', onLoad, true);
            if (frame !== null) { cancelAnimationFrame(frame); }
            editor.off('scroll', onEditorScroll);
            editor.off('cursorActivity', onCursor);
            editor.off('change', onChange);
            pane.removeEventListener('scroll', onPreviewScroll);
            checkbox.removeEventListener('change', onToggle);
            clearHighlight();
        }
    };
}
