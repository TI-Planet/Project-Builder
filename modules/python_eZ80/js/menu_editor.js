/* Project Builder Python menu editor.
 * This widget owns no project state: the caller saves onChange(text) like source code.
 */
(function (root) {
    'use strict';
    let nextFieldId = 0;

    function create(container, options = {}) {
        const menu = root.PBPythonMenu;
        if (!menu) throw new Error('Python menu parser is not loaded');
        const doc = container.ownerDocument;
        let source = String(options.value || '');
        let parsed = menu.parseMenu(source);
        let groups = parsed.groups;
        let graphical = parsed.stats.errors === 0;
        let readOnly = !!options.readOnly;
        let destroyed = false;
        let view = graphical || !source ? 'graphical' : 'raw';
        let groupIndex = 0, pageIndex = 0, itemIndex = 0, itemKind = 'item';
        let focusedText = null;
        let rawCodeMirror = null;
        let updatingRaw = false;

        function el(tag, className, text) {
            const node = doc.createElement(tag);
            if (className) node.className = className;
            if (text !== undefined) node.textContent = text;
            return node;
        }
        function button(text, handler, mutates = false, title = '') {
            const node = el('button', 'btn btn-default btn-sm', text);
            node.type = 'button';
            if (title) node.title = title;
            if (mutates) node.dataset.mutates = 'true';
            node.addEventListener('click', () => {
                if (!destroyed && (!mutates || !readOnly)) {
                    const hadFocus = widget.contains(doc.activeElement);
                    handler();
                    // Structural edits replace their own button. Keep focus in the
                    // widget so the host's save/undo shortcuts still receive keys.
                    if (hadFocus && !destroyed && widget.isConnected && !widget.contains(doc.activeElement)) {
                        widget.focus({ preventScroll: true });
                    }
                }
            });
            return node;
        }
        function label(text, control) {
            const node = el('label', 'pb-menu-field');
            const caption = el('span', 'pb-menu-label', text);
            caption.id = `pb-python-menu-field-${++nextFieldId}`;
            control.setAttribute('aria-labelledby', caption.id);
            node.append(caption, control);
            return node;
        }
        function input(value, onInput, config = {}) {
            const node = el(config.multiline ? 'textarea' : 'input', 'form-control input-sm');
            if (!config.multiline) node.type = config.type || 'text';
            else node.rows = 2;
            node.value = value;
            node.dataset.mutates = 'true';
            node.spellcheck = false;
            if (config.max !== undefined) { node.min = '0'; node.max = String(config.max); }
            node.addEventListener('input', () => { if (!readOnly) onInput(node.value); });
            if (node.type !== 'number') {
                node.addEventListener('focus', () => { focusedText = node; });
            }
            return node;
        }
        function select(entries, value, onChange) {
            const node = el('select', 'form-control input-sm');
            entries.forEach(([key, text]) => {
                const option = el('option', '', text);
                option.value = String(key);
                node.append(option);
            });
            node.value = String(value);
            node.addEventListener('change', () => onChange(node.value));
            return node;
        }
        function heading(title, actions = []) {
            const row = el('div', 'pb-menu-section-heading');
            const toolbar = el('div', 'pb-menu-actions');
            toolbar.append(...actions);
            row.append(el('strong', '', title), toolbar);
            return row;
        }
        function appendTokens(node, raw) {
            for (const token of menu.tokenizeText(raw)) {
                node.append(el('span', `pb-menu-token pb-menu-color-${token.color}`, token.text));
            }
        }
        function group() { return groups[groupIndex]; }
        function page() { return group()?.pages[pageIndex]; }
        function itemList() { return itemKind === 'import' ? group()?.imports : page()?.items; }
        function item() { return itemList()?.[itemIndex]; }
        function clamp() {
            groupIndex = Math.max(0, Math.min(groupIndex, groups.length - 1));
            pageIndex = Math.max(0, Math.min(pageIndex, (group()?.pages.length || 1) - 1));
            if (itemKind === 'import' && !group()?.imports.length) itemKind = 'item';
            itemIndex = Math.max(0, Math.min(itemIndex, (itemList()?.length || 1) - 1));
        }
        function newItem(directive = '#MENUITEM') {
            const name = options.moduleName || 'example';
            const inserted = directive === '#MENUFROM' ? `from ${name} import *`
                : directive === '#MENUIMPORT' ? `import ${name}` : 'function()';
            return { directive, displayRaw: inserted, annotationRaw: '', insertionRaw: inserted,
                cursor: directive === '#MENUITEM' ? 1 : 0, assistant: 0, helpRaw: '' };
        }
        function newGroup() {
            return { labelRaw: (options.moduleName || 'example') + '<%ELLIPSIS%>', imports: [],
                pages: [{ titleRaw: 'Commands', items: [newItem()] }] };
        }
        function emit(origin) {
            const detail = { origin, valid: parsed.stats.errors === 0, diagnostics: parsed.diagnostics };
            if (typeof options.onChange === 'function') options.onChange(source, detail);
        }
        function changed(rebuild = false) {
            if (readOnly || destroyed) return;
            source = menu.serializeMenu(groups);
            parsed = menu.parseMenu(source);
            // Keep the form model while editing incomplete fields. Re-parsing it here
            // would discard fields and make it impossible to correct a temporary error.
            graphical = true;
            syncRawValue(source);
            if (rebuild) renderForm();
            renderPreview();
            renderDiagnostics();
            emit('graphical');
        }
        function move(list, index, delta) {
            const target = index + delta;
            if (target < 0 || target >= list.length) return index;
            [list[index], list[target]] = [list[target], list[index]];
            return target;
        }
        function reorderButtons(list, index, assign, noun) {
            const previous = button('↑', () => { assign(move(list, index, -1)); changed(true); }, true, `Move ${noun} up`);
            const next = button('↓', () => { assign(move(list, index, 1)); changed(true); }, true, `Move ${noun} down`);
            previous.setAttribute('aria-label', `Move ${noun} up`);
            next.setAttribute('aria-label', `Move ${noun} down`);
            previous.dataset.unavailable = String(index === 0);
            next.dataset.unavailable = String(index === list.length - 1);
            return [previous, next];
        }

        const widget = el('section', 'pb-python-menu');
        widget.tabIndex = -1;
        widget.setAttribute('aria-label', 'Python module menu editor');
        const header = el('div', 'pb-menu-header');
        const switches = el('div', 'btn-group');
        const graphicalButton = button('Menu editor', () => setView('graphical'));
        const rawButton = button('Raw .menu', () => setView('raw'));
        const previewButton = button('Preview', () => setView('preview'));
        switches.append(graphicalButton, rawButton, previewButton);
        const status = el('span', 'pb-menu-status');
        header.append(switches, status);
        const layout = el('div', 'pb-menu-layout');
        const editing = el('div', 'pb-menu-editing');
        const form = el('div', 'pb-menu-form');
        const rawPanel = el('div', 'pb-menu-raw-panel');
        const raw = el('textarea', 'form-control pb-menu-raw');
        raw.setAttribute('aria-label', 'Menu definition source');
        raw.spellcheck = false;
        raw.wrap = 'off';
        raw.value = source;
        raw.dataset.mutates = 'true';
        raw.addEventListener('focus', () => { focusedText = raw; });
        function rawChanged(value) {
            if (readOnly) return;
            source = value;
            parsed = menu.parseMenu(source);
            groups = parsed.groups;
            graphical = parsed.stats.errors === 0;
            renderPreview();
            renderDiagnostics();
            emit('raw');
        }
        raw.addEventListener('input', () => rawChanged(raw.value));
        const rawHelp = el('p', 'pb-menu-help', 'Raw text is preserved on load and save. Editing with the graphical controls writes a canonical menu definition.');
        rawPanel.append(raw, rawHelp);
        editing.append(form, rawPanel);

        const preview = el('aside', 'pb-menu-preview');
        const previewHeading = heading('Live preview');
        const previewGroup = select([], 0, value => {
            groupIndex = Number(value); pageIndex = itemIndex = 0; itemKind = 'item'; renderForm(); renderPreview();
        });
        previewGroup.setAttribute('aria-label', 'Preview menu group');
        const screen = el('div', 'pb-menu-screen');
        screen.tabIndex = 0;
        screen.setAttribute('aria-label', 'Interactive calculator menu preview');
        const screenTitle = el('div', 'pb-menu-screen-title', 'PYTHON EDITOR');
        const tabs = el('div', 'pb-menu-screen-tabs');
        const rows = el('div', 'pb-menu-screen-items');
        const softkeys = el('div', 'pb-menu-screen-softkeys');
        softkeys.append(el('span', '', 'ESC'), el('span', '', 'MODULE'));
        screen.append(screenTitle, tabs, rows, softkeys);
        const paging = el('div', 'pb-menu-preview-paging');
        const insertTitle = heading('Insertion');
        const insertion = el('div', 'pb-menu-insertion');
        insertion.setAttribute('aria-live', 'polite');
        const assistantNote = el('p', 'pb-menu-help');
        const scopeNote = el('p', 'pb-menu-help', 'Preview and checks follow the Evo menu editor. CE uses the same tags; firmware limits can differ.');
        preview.append(previewHeading, previewGroup, screen, paging, insertTitle, insertion, assistantNote, scopeNote);
        layout.append(editing, preview);
        const diagnostics = el('div', 'pb-menu-diagnostics');
        diagnostics.setAttribute('aria-live', 'polite');
        widget.append(header, layout, diagnostics);
        container.append(widget);

        function syncRawValue(value) {
            raw.value = value;
            if (!rawCodeMirror || rawCodeMirror.getValue() === value) return;
            const previous = rawCodeMirror.getValue();
            let start = 0, end = 0;
            while (start < previous.length && start < value.length && previous[start] === value[start]) start++;
            while (end < previous.length - start && end < value.length - start
                && previous[previous.length - end - 1] === value[value.length - end - 1]) end++;
            updatingRaw = true;
            try {
                // Unlike setValue(), a small replacement maps the selection through
                // external edits and retains the raw editor's cursor and undo history.
                rawCodeMirror.replaceRange(value.slice(start, value.length - end), rawCodeMirror.posFromIndex(start),
                    rawCodeMirror.posFromIndex(previous.length - end), 'pb-menu-sync');
            } finally {
                updatingRaw = false;
            }
        }
        function setupRawCodeMirror() {
            const CodeMirror = root.CodeMirror;
            if (!CodeMirror?.fromTextArea) return;
            const modeName = 'pb-python-menu';
            if (!CodeMirror.modes[modeName]) CodeMirror.defineMode(modeName, () => ({
                startState: () => ({ tokens: [], index: 0 }),
                token(stream, state) {
                    if (stream.sol()) { state.tokens = menu.tokenizeSource(stream.string); state.index = 0; }
                    while (state.index < state.tokens.length && state.tokens[state.index].end <= stream.pos) state.index++;
                    const token = state.tokens[state.index];
                    if (!token) { stream.skipToEnd(); return null; }
                    stream.pos = Math.max(stream.pos + 1, token.end);
                    return token.type === 'text' ? null : `pb-menu-${token.type}`;
                }
            }));
            const hint = force => cm => {
                if (cm.somethingSelected()) return null;
                const result = menu.getCompletions(cm.getValue(), cm.indexFromPos(cm.getCursor()), force);
                if (!result?.items.length) return null;
                return {
                    from: cm.posFromIndex(result.from), to: cm.posFromIndex(result.to),
                    list: result.items.map(item => ({ text: item.insertText, displayText: item.label,
                        className: `pb-menu-completion-${item.kind}` }))
                };
            };
            const complete = force => {
                if (!readOnly && rawCodeMirror?.showHint) rawCodeMirror.showHint({
                    hint: hint(force), completeSingle: false, container: widget
                });
            };
            rawCodeMirror = CodeMirror.fromTextArea(raw, {
                mode: modeName, theme: 'pb-menu', lineNumbers: true, lineWrapping: false,
                readOnly, indentUnit: 0, tabSize: 4,
                extraKeys: { 'Ctrl-Space': () => complete(true), 'Cmd-Space': () => complete(true) }
            });
            rawCodeMirror.getInputField().setAttribute('aria-label', 'Menu definition source');
            rawCodeMirror.on('change', cm => {
                if (!updatingRaw && !destroyed) rawChanged(cm.getValue());
            });
            rawCodeMirror.on('inputRead', () => complete(false));
            rawCodeMirror.on('focus', () => { focusedText = null; });
            rawHelp.textContent = 'Ctrl/Cmd-Space completes directives, colors, and symbols. Graphical edits write a canonical menu definition; raw edits retain unsupported directives.';
        }

        function setView(next) {
            view = next;
            renderForm();
            renderViews();
        }
        function renderViews() {
            editing.hidden = view === 'preview';
            layout.dataset.view = view;
            form.hidden = view !== 'graphical';
            rawPanel.hidden = view !== 'raw';
            graphicalButton.classList.toggle('active', view === 'graphical');
            rawButton.classList.toggle('active', view === 'raw');
            previewButton.classList.toggle('active', view === 'preview');
            graphicalButton.setAttribute('aria-pressed', String(view === 'graphical'));
            rawButton.setAttribute('aria-pressed', String(view === 'raw'));
            previewButton.setAttribute('aria-pressed', String(view === 'preview'));
            if (view === 'raw') rawCodeMirror?.refresh();
        }
        function applyReadOnly() {
            widget.querySelectorAll('[data-mutates]').forEach(node => {
                const disabled = readOnly || node.dataset.unavailable === 'true';
                if (node.tagName === 'INPUT' || node.tagName === 'TEXTAREA') node.readOnly = disabled;
                else node.disabled = disabled;
            });
            rawCodeMirror?.setOption('readOnly', readOnly);
            if (readOnly) rawCodeMirror?.closeHint?.();
            widget.classList.toggle('pb-menu-readonly', readOnly);
        }
        function renderForm() {
            clamp();
            focusedText = null;
            form.replaceChildren();
            if (!graphical && source) {
                form.append(el('p', 'alert alert-warning', 'This definition contains errors or unsupported directives. Its original text is preserved. Correct it in Raw .menu to use the graphical editor.'),
                    button('Edit raw definition', () => setView('raw')));
                applyReadOnly(); return;
            }
            const addGroup = button('Add group', () => {
                groups.push(newGroup()); groupIndex = groups.length - 1; pageIndex = itemIndex = 0;
                itemKind = 'item'; changed(true);
            }, true);
            form.append(heading('Menu groups', [addGroup]));
            if (!groups.length) {
                form.append(el('p', 'pb-menu-help', 'Add a group to create the module menu, then add its pages and commands.'));
                applyReadOnly(); return;
            }
            const groupSelect = select(groups.map((entry, index) => [index, menu.plainText(entry.labelRaw) || '(untitled group)']), groupIndex, value => {
                groupIndex = Number(value); pageIndex = itemIndex = 0; itemKind = 'item'; renderForm(); renderPreview();
            });
            form.append(label('Group', groupSelect));
            const groupActions = el('div', 'pb-menu-actions pb-menu-order');
            groupActions.append(...reorderButtons(groups, groupIndex, value => { groupIndex = value; }, 'group'),
                button('Remove group', () => { groups.splice(groupIndex, 1); changed(true); }, true));
            form.append(groupActions, label('Modules menu label', input(group().labelRaw, value => { group().labelRaw = value; changed(); })));

            const addPage = button('Add page', () => {
                group().pages.push({ titleRaw: 'Commands', items: [newItem()] });
                pageIndex = group().pages.length - 1; itemIndex = 0; itemKind = 'item'; changed(true);
            }, true);
            addPage.dataset.unavailable = String(group().pages.length >= 5);
            form.append(heading('Pages', [addPage]));
            if (group().pages.length) {
                const pageSelect = select(group().pages.map((entry, index) => [index, menu.plainText(entry.titleRaw) || '(untitled page)']), pageIndex, value => {
                    pageIndex = Number(value); itemIndex = 0; itemKind = 'item'; renderForm(); renderPreview();
                });
                form.append(label('Page', pageSelect));
                const actions = el('div', 'pb-menu-actions pb-menu-order');
                actions.append(...reorderButtons(group().pages, pageIndex, value => { pageIndex = value; }, 'page'),
                    button('Remove page', () => { group().pages.splice(pageIndex, 1); changed(true); }, true));
                form.append(actions, label('Page title', input(page().titleRaw, value => { page().titleRaw = value; changed(); })));
            }

            const addImport = (directive, title) => {
                const node = button(title, () => { group().imports.push(newItem(directive)); itemKind = 'import'; itemIndex = group().imports.length - 1; changed(true); }, true);
                node.dataset.unavailable = String(group().imports.some(entry => entry.directive === directive));
                return node;
            };
            form.append(heading('Imports', [addImport('#MENUFROM', 'Add from import'), addImport('#MENUIMPORT', 'Add import')]));
            appendItemList(group().imports, 'import');
            const addItem = button('Add item', () => {
                page().items.push(newItem()); itemKind = 'item'; itemIndex = page().items.length - 1; changed(true);
            }, true);
            addItem.dataset.unavailable = String(!page());
            form.append(heading('Page items', [addItem]));
            appendItemList(page()?.items || [], 'item');
            if (item()) renderItemFields();
            applyReadOnly();
        }
        function appendItemList(entries, kind) {
            const list = el('div', 'pb-menu-item-list');
            entries.forEach((entry, index) => {
                const node = button(menu.plainText(entry.displayRaw) || '(untitled item)', () => {
                    itemKind = kind; itemIndex = index; renderForm(); renderPreview();
                });
                node.className = 'pb-menu-item-choice' + (itemKind === kind && itemIndex === index ? ' active' : '');
                node.setAttribute('aria-pressed', String(itemKind === kind && itemIndex === index));
                list.append(node);
            });
            if (!entries.length) list.append(el('p', 'pb-menu-help', kind === 'import' ? 'No import shortcuts.' : 'No items on this page.'));
            form.append(list);
        }
        function renderItemFields() {
            const current = item();
            const list = itemList();
            const controls = [...reorderButtons(list, itemIndex, value => { itemIndex = value; }, 'item'),
                button('Remove item', () => { list.splice(itemIndex, 1); changed(true); }, true)];
            form.append(heading('Selected item', controls));
            const types = [['#MENUITEM', 'Menu item'], ['#MENUFROM', 'From import shortcut'], ['#MENUIMPORT', 'Import shortcut']];
            const type = select(types, current.directive, value => {
                if (readOnly) return;
                const newKind = value === '#MENUITEM' ? 'item' : 'import';
                if (newKind !== itemKind) {
                    list.splice(itemIndex, 1);
                    itemKind = newKind;
                    const destination = itemList();
                    destination.push(current); itemIndex = destination.length - 1;
                }
                current.directive = value;
                if (newKind === 'import') { current.assistant = 0; current.helpRaw = ''; }
                changed(true);
            });
            type.dataset.mutates = 'true';
            Array.from(type.options).forEach(option => {
                option.disabled = (option.value === '#MENUITEM' && !page())
                    || (option.value !== '#MENUITEM' && group().imports.some(entry => entry !== current && entry.directive === option.value));
            });
            form.append(label('Type', type));
            if (current.directive === '#MENUITEM') {
                const behavior = select([['insert', 'Insert text'], ['info', 'Informational (no insertion)']],
                    current.insertionRaw || current.cursor || current.assistant || current.helpRaw ? 'insert' : 'info', value => {
                        if (readOnly) return;
                        current.insertionRaw = value === 'insert' ? menu.plainText(current.displayRaw) : '';
                        current.cursor = 0; current.assistant = 0; current.helpRaw = '';
                        changed(true);
                    });
                behavior.dataset.mutates = 'true';
                form.append(label('Behavior', behavior));
            }
            const fields = el('div', 'pb-menu-detail-fields');
            for (const [key, title] of [['displayRaw', 'Display text'], ['annotationRaw', 'Right annotation'], ['insertionRaw', 'Text to insert']]) {
                fields.append(label(title, input(current[key], value => { current[key] = value; changed(); }, { multiline: key === 'insertionRaw' })));
            }
            fields.append(label('Cursor offset from end', input(current.cursor, value => { current.cursor = value; changed(); }, { type: 'number', max: 255 })));
            if (current.directive === '#MENUITEM') {
                const assistants = [[0, 'None (0)'], ...Array.from(menu.KNOWN_ASSISTANTS).filter(value => value !== 0).map(value => [value, `Assistant ${value}`])];
                if (!menu.KNOWN_ASSISTANTS.has(Number(current.assistant))) assistants.push([current.assistant, `Custom assistant ${current.assistant}`]);
                const assistant = select(assistants, current.assistant, value => { if (!readOnly) { current.assistant = Number(value); changed(); } });
                assistant.dataset.mutates = 'true';
                fields.append(label('Argument assistant', assistant), label('Help text', input(current.helpRaw, value => { current.helpRaw = value; changed(); })));
            }
            form.append(fields, el('p', 'pb-menu-help', 'Use <%NL%> for a new line and <%TAB%> for two inserted spaces. Cursor offset counts back from the end. An item without insertion text is informational.'));
            const palette = el('div', 'pb-menu-symbols');
            const choices = [['', 'Insert a color or symbol…'], ...Object.keys(menu.COLORS).map(name => [`<@${name}@>`, `Color: ${name.toLowerCase()}`]),
                ...menu.MACRO_NAMES.map(name => [`<%${name}%>`, `${menu.plainText(`<%${name}%>`).replace(/\n/g, '↵')} · ${name}`])];
            const symbols = select(choices, '', value => {
                if (!value || readOnly) return;
                const target = focusedText;
                if (target && widget.contains(target) && !target.readOnly) {
                    const start = target.selectionStart ?? target.value.length;
                    const end = target.selectionEnd ?? start;
                    target.setRangeText(value, start, end, 'end');
                    target.dispatchEvent(new Event('input', { bubbles: true }));
                    target.focus();
                }
                symbols.value = '';
            });
            symbols.dataset.mutates = 'true';
            palette.append(label('Symbols (insert into the last text field)', symbols));
            form.append(palette);
        }
        function renderPreview() {
            clamp();
            previewGroup.replaceChildren();
            groups.forEach((entry, index) => {
                const option = el('option', '', menu.plainText(entry.labelRaw));
                option.value = String(index); previewGroup.append(option);
            });
            previewGroup.value = String(groupIndex);
            previewGroup.hidden = groups.length < 2;
            tabs.replaceChildren();
            (group()?.pages || []).forEach((entry, index) => {
                const tab = button(menu.plainText(entry.titleRaw), () => {
                    pageIndex = index; itemIndex = 0; itemKind = 'item'; renderForm(); renderPreview();
                });
                tab.className = 'pb-menu-screen-tab' + (index === pageIndex ? ' active' : '');
                tabs.append(tab);
            });
            if (!tabs.childNodes.length) tabs.append(el('span', 'pb-menu-screen-tab active', 'No menu'));
            rows.replaceChildren();
            const items = page()?.items || [];
            const selected = itemKind === 'item' ? itemIndex : 0;
            const start = Math.floor(selected / 10) * 10;
            items.slice(start, start + 10).forEach((entry, visibleIndex) => {
                const absoluteIndex = start + visibleIndex;
                const row = button('', () => { itemIndex = absoluteIndex; itemKind = 'item'; renderForm(); renderPreview(); });
                row.className = 'pb-menu-screen-item' + (itemKind === 'item' && absoluteIndex === itemIndex ? ' selected' : '');
                row.setAttribute('aria-label', `Preview item ${absoluteIndex + 1}: ${menu.plainText(entry.displayRaw)}`);
                const digit = (absoluteIndex + 1) % 10;
                row.append(el('span', 'pb-menu-item-index', `${digit}${digit === 0 && absoluteIndex < items.length - 1 ? '↓' : ':'}`));
                const main = el('span', 'pb-menu-item-main'); appendTokens(main, entry.displayRaw); row.append(main);
                if (entry.annotationRaw) {
                    const annotation = el('span', 'pb-menu-item-annotation'); appendTokens(annotation, entry.annotationRaw); row.append(annotation);
                }
                rows.append(row);
            });
            if (!items.length) rows.append(el('p', 'pb-menu-screen-empty', 'No menu items to preview'));
            paging.replaceChildren();
            if (items.length > 10) {
                const previous = button('Previous 10', () => { itemKind = 'item'; itemIndex = Math.max(0, start - 10); renderForm(); renderPreview(); });
                const next = button('Next 10', () => { itemKind = 'item'; itemIndex = Math.min(items.length - 1, start + 10); renderForm(); renderPreview(); });
                previous.disabled = start === 0; next.disabled = start + 10 >= items.length;
                paging.append(previous, el('span', '', `${start + 1}–${Math.min(start + 10, items.length)} / ${items.length}`), next);
            }
            insertion.replaceChildren();
            const selectedItem = item();
            if (selectedItem?.insertionRaw) {
                const text = menu.plainText(selectedItem.insertionRaw);
                const offset = Math.max(0, Math.min(Number(selectedItem.cursor) || 0, text.length));
                const at = text.length - offset;
                const caret = el('span', 'pb-menu-insertion-caret'); caret.setAttribute('aria-hidden', 'true');
                insertion.append(doc.createTextNode(text.slice(0, at)), caret, doc.createTextNode(text.slice(at)));
            } else insertion.textContent = selectedItem ? 'Informational item — no text is inserted' : 'Select a menu item';
            assistantNote.textContent = selectedItem?.assistant ? `Argument assistant ${selectedItem.assistant}` : '';
        }
        function renderDiagnostics() {
            const stats = parsed.stats;
            status.textContent = `${stats.bytes} bytes · ${stats.pages} pages · ${stats.errors} errors · ${stats.warnings} warnings${readOnly ? ' · Read-only' : ''}`;
            diagnostics.replaceChildren();
            if (!stats.errors && !stats.warnings) diagnostics.append(el('p', 'pb-menu-diagnostic-success', 'Menu definition is valid.'));
            parsed.diagnostics.forEach(entry => {
                const row = button(`Line ${entry.line}: ${entry.message}`, () => {
                    setView('raw');
                    let start = 0;
                    const lines = source.split(/\r\n|\r|\n/);
                    // Locate the original line endings without changing them.
                    const endings = /\r\n|\r|\n/g;
                    for (let line = 1; line < entry.line; line++) { const match = endings.exec(source); if (!match) break; start = match.index + match[0].length; }
                    start += Math.min(entry.column - 1, (lines[entry.line - 1] || '').length);
                    if (rawCodeMirror) {
                        const position = { line: Math.max(0, entry.line - 1), ch: Math.max(0, entry.column - 1) };
                        rawCodeMirror.focus(); rawCodeMirror.setCursor(position); rawCodeMirror.scrollIntoView(position, 35);
                    } else {
                        raw.focus(); raw.setSelectionRange(start, start);
                    }
                });
                row.className = `pb-menu-diagnostic pb-menu-diagnostic-${entry.severity}`;
                diagnostics.append(row);
            });
            if (typeof options.onDiagnostics === 'function') options.onDiagnostics(parsed.diagnostics);
        }
        screen.addEventListener('keydown', event => {
            const items = page()?.items || [];
            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                event.preventDefault(); itemKind = 'item';
                itemIndex = Math.max(0, Math.min(items.length - 1, itemIndex + (event.key === 'ArrowDown' ? 1 : -1)));
            } else if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
                event.preventDefault();
                pageIndex = Math.max(0, Math.min((group()?.pages.length || 1) - 1, pageIndex + (event.key === 'ArrowRight' ? 1 : -1)));
                itemIndex = 0; itemKind = 'item';
            } else return;
            renderForm(); renderPreview();
        });
        function setTheme(theme) { widget.dataset.theme = theme === 'dark' ? 'dark' : 'light'; }
        function syncTheme() {
            setTheme(options.theme || (typeof root.isDarkThemeEnabled === 'function' && root.isDarkThemeEnabled() ? 'dark' : 'light'));
        }
        const themeObserver = typeof MutationObserver !== 'undefined' ? new MutationObserver(syncTheme) : null;
        doc.querySelectorAll('link.darkThemeLink').forEach(link => themeObserver?.observe(link, { attributes: true, attributeFilter: ['href'] }));
        setupRawCodeMirror();
        syncTheme(); renderForm(); renderViews(); renderPreview(); renderDiagnostics(); applyReadOnly();

        return {
            setValue(text) {
                text = String(text);
                if (destroyed || text === source) return;
                source = text; syncRawValue(source);
                parsed = menu.parseMenu(source); groups = parsed.groups;
                graphical = parsed.stats.errors === 0;
                if (!graphical && source) view = 'raw';
                renderForm(); renderViews(); renderPreview(); renderDiagnostics(); applyReadOnly();
            },
            getValue() { return source; },
            getDiagnostics() { return parsed.diagnostics.slice(); },
            setReadOnly(value) { readOnly = !!value; applyReadOnly(); renderDiagnostics(); },
            setTheme,
            focus() { (view === 'preview' ? screen : view === 'raw' ? (rawCodeMirror || raw) : form.querySelector('input, button'))?.focus(); },
            destroy() {
                destroyed = true; themeObserver?.disconnect(); focusedText = null;
                rawCodeMirror?.closeHint?.(); rawCodeMirror?.toTextArea(); rawCodeMirror = null;
                widget.remove();
            },
        };
    }
    root.PBPythonMenuEditor = Object.freeze({ create });
})(typeof window !== 'undefined' ? window : globalThis);
