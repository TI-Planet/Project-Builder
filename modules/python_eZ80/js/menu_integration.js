/* Keep the visual menu editor on the same CodeMirror/Firepad document as PB saves. */
(() => {
    'use strict';
    let binding = null;

    window.isPythonMenuFile = filename => /\.menu$/i.test(filename || proj.currFile);

    window.destroyPythonMenuEditor = () => {
        binding?.destroy();
        binding = null;
    };

    window.initPythonMenuEditor = () => {
        window.destroyPythonMenuEditor();
        const container = document.getElementById('editorContainer');
        const isMenu = window.isPythonMenuFile();
        container.classList.toggle('python-menu-file', isMenu);
        if (!isMenu) return;

        const cm = editor;
        const wrapper = cm.getWrapperElement();
        const host = document.createElement('div');
        host.id = 'pythonMenuEditor';
        wrapper.parentNode.insertBefore(host, wrapper);
        wrapper.style.display = 'none';
        let fromVisualEditor = false;
        const widget = window.PBPythonMenuEditor.create(host, {
            value: cm.getValue(),
            moduleName: proj.currFile.replace(/\.menu$/i, ''),
            readOnly: !!cm.getOption('readOnly'),
            onChange: text => {
                if (cm.getOption('readOnly') || text === cm.getValue()) return;
                const old = cm.getValue();
                let start = 0;
                while (start < old.length && start < text.length && old[start] === text[start]) start++;
                let end = 0;
                while (end < old.length - start && end < text.length - start
                    && old[old.length - 1 - end] === text[text.length - 1 - end]) end++;
                fromVisualEditor = true;
                try {
                    // A minimal text operation preserves undo and Firepad collaboration.
                    cm.replaceRange(text.slice(start, text.length - end), cm.posFromIndex(start),
                        cm.posFromIndex(old.length - end), '+menu');
                } finally {
                    fromVisualEditor = false;
                }
            }
        });
        const onChange = () => {
            if (!fromVisualEditor) widget.setValue(cm.getValue());
        };
        const onOptionChange = (_, option) => {
            if (option === 'readOnly') widget.setReadOnly(!!cm.getOption('readOnly'));
        };
        const updateTheme = () => widget.setTheme(proj.use_dark ? 'dark' : 'light');
        const themeObserver = new MutationObserver(updateTheme);
        document.querySelectorAll('.darkThemeLink').forEach(link => themeObserver.observe(link, {attributes: true, attributeFilter: ['href']}));
        updateTheme();
        cm.on('change', onChange);
        cm.on('optionChange', onOptionChange);
        const onKeyDown = event => {
            const key = event.key.toLowerCase();
            if ((event.ctrlKey || event.metaKey) && ['s', 'z', 'y'].includes(key)) {
                event.preventDefault();
                event.stopPropagation();
                if (cm.getOption('readOnly')) return;
                if (key === 's') saveFile();
                else if (key === 'y' || event.shiftKey) cm.redo();
                else cm.undo();
            }
        };
        // Capture before the raw editor's own keymap: both menu views share the
        // primary document's undo manager (including Firepad's collaborative undo).
        host.addEventListener('keydown', onKeyDown, true);
        const downloadButton = document.getElementById('builddlButton');
        if (downloadButton) {
            downloadButton.innerHTML = '<span class="glyphicon glyphicon-download-alt" aria-hidden="true"></span> Download menu (.menu)';
            downloadButton.title = 'Download the current menu file';
            downloadButton.onclick = () => { downloadCurrentFile(proj.currFile); return false; };
        }
        container.querySelectorAll('a[onclick*="downloadCurrentFile"]').forEach(link => {
            link.textContent = 'Download current menu file (.menu)';
        });
        ['buildUsbButton', 'transferButton'].forEach(id => {
            const button = document.getElementById(id);
            if (button) {
                button.classList.add('disabled');
                button.setAttribute('disabled', 'true');
                button.setAttribute('aria-disabled', 'true');
            }
        });
        const outlineButton = document.getElementById('codeOutlineToggleButton');
        if (outlineButton) outlineButton.parentElement.style.display = 'none';
        binding = {destroy() {
            cm.off('change', onChange);
            cm.off('optionChange', onOptionChange);
            themeObserver.disconnect();
            host.removeEventListener('keydown', onKeyDown, true);
            widget.destroy();
            host.remove();
            wrapper.style.display = '';
        }};
    };

    window.getPythonBytecodeSources = async signal => {
        // Snapshot the active buffer before any network/compilation await. Other
        // files use the authenticated source endpoint, including read-only projects.
        const currentFile = proj.currFile;
        const currentSource = editor.getValue();
        const response = await new Promise((resolve, reject) => {
            let settled = false;
            const finish = (error, value) => {
                if (settled) return;
                settled = true;
                signal?.removeEventListener('abort', onAbort);
                if (error) reject(error);
                else resolve(value);
            };
            const onAbort = () => finish(new DOMException('Compilation cancelled.', 'AbortError'));
            if (signal?.aborted) { onAbort(); return; }
            signal?.addEventListener('abort', onAbort, {once: true});
            const params = `id=${encodeURIComponent(proj.pid)}&action=getAllSrcFilesContent&except=${encodeURIComponent(currentFile)}`;
            const fail = () => finish(new Error('Unable to read the matching Python/menu files. Please retry.'));
            // Use the shared authenticated transport without ajaxAction's global
            // error notifications: a late reply after Cancel must remain silent.
            const request = allowRefresh => fetchPOST('ActionHandler.php', params, 10000, 'text', true).then(result => {
                if (settled) return;
                if (result.status === 200) {
                    clearSessionRefreshGuard();
                    finish(null, result.body);
                } else if (result.status === 401 && allowRefresh) {
                    refreshCSRFToken(params, ok => {
                        if (settled) return;
                        if (ok) request(false);
                        else fail();
                    });
                } else fail();
            }).catch(error => finish(error));
            request(true);
        });
        if (!response || typeof response !== 'object' || (Array.isArray(response) && response.length)) {
            throw new Error('Unable to read the project sources.');
        }
        // PHP encodes an empty associative result as [].
        const files = {...response};
        files[currentFile] = currentSource;
        const basename = currentFile.replace(/\.(py|menu)$/i, '');
        const matches = extension => Object.keys(files).filter(name =>
            name.slice(0, -(extension.length + 1)) === basename && name.toLowerCase().endsWith(`.${extension}`));
        const sources = matches('py');
        const menus = matches('menu');
        if (sources.length !== 1) {
            throw new Error(`Bytecode export needs exactly one matching ${basename}.py file.`);
        }
        if (menus.length > 1) throw new Error(`More than one menu matches ${basename}.py. Rename the duplicate menu.`);
        const filename = sources[0];
        const menuText = menus.length ? files[menus[0]] : null;
        if (typeof files[filename] !== 'string' || (menus.length && typeof menuText !== 'string')) {
            throw new Error('A matching project file could not be read. Please retry.');
        }
        return {filename, source: files[filename], menuText};
    };
})();
