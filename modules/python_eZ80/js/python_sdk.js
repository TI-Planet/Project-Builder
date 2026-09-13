/* TI-Python completion context for the shared CodeMirror ctags hint provider. */
(() => {
    'use strict';
    const documents = new WeakMap();

    function importContext(cm) {
        let cached = documents.get(cm);
        if (!cached) {
            cached = {value: null};
            documents.set(cm, cached);
            cm.on('changes', () => { cached.value = null; });
        }
        if (cached.value) return cached.value;
        // Let CodeMirror distinguish real imports from comments and multiline strings.
        const lines = [];
        for (let line = cm.firstLine(); line <= cm.lastLine(); line++) {
            lines.push(cm.getLineTokens(line).map(token => /\b(?:string|comment)/.test(token.type || '')
                ? ' '.repeat(token.string.length) : token.string).join(''));
        }
        const code = lines.join('\n').replace(/\\\n/g, ' ');
        const modules = new Map(), names = new Map(), stars = new Set(), instances = new Map();
        const statements = /(?:^|[;\n])\s*(?:from\s+(\w+)\s+import\s+(\([^)]*\)|[^\n;]+)|import\s+([^\n;]+))/g;
        for (const match of code.matchAll(statements)) {
            const module = match[1];
            const entries = (match[2] || match[3]).replace(/[()\n]/g, ' ').split(',');
            for (const entry of entries) {
                const binding = /^\s*(\w+|\*)(?:\s+as\s+(\w+))?\s*$/.exec(entry);
                if (!binding) continue;
                if (!module) modules.set(binding[2] || binding[1], binding[1]);
                else if (binding[1] === '*') stars.add(module);
                else names.set(binding[2] || binding[1], {module, name: binding[1]});
            }
        }
        // Resolve common SDK constructors such as t = Turtle() or c = charts.chart().
        for (const match of code.matchAll(/(?:^|[;\n])\s*(\w+)\s*=\s*(\w+(?:\.\w+)*)\s*\(/g)) {
            instances.set(match[1], match[2]);
        }
        return cached.value = {lines, modules, names, stars, instances};
    }

    window.getEditorCompletionContext = (cm, {cur, curLine, start}) => {
        if (cm.getMode().name !== 'python') return null;
        const tags = window.sdk_ctags || [];
        const byModule = new Map();
        for (const tag of tags) {
            const module = tag.file?.replace(/\.py$/, '');
            if (!byModule.has(module)) byModule.set(module, []);
            byModule.get(module).push(tag);
        }
        const members = (module, scope = '') => (byModule.get(module) || []).filter(tag => tag.k !== 'module' && (tag.s || '') === scope);
        const only = sdkCtags => ({force: true, skipAnyWord: true, ctags: [], sdkCtags});
        if (/\b(?:string|comment)/.test(cm.getTokenAt(cur).type || '')) return only([]);
        const context = importContext(cm);
        const prefix = curLine.slice(0, start);
        const before = context.lines.slice(0, cur.line).concat(prefix).join('\n').replace(/\\\n/g, ' ');
        const fromImport = /(?:^|[;\n])\s*from\s+(\w+)\s+import\s*(?:\([^)]*|[^()\n;]*)$/.exec(before);
        if (fromImport) return only(/\bas\s+$/.test(prefix) ? [] : members(fromImport[1]));
        if (/^\s*(?:from|import)\s+(?:\w+(?:\s+as\s+\w+)?\s*,\s*)*$/.test(prefix)) {
            return only(tags.filter(tag => tag.k === 'module'));
        }
        const qualifier = /\b(\w+(?:\.\w+)*)\.$/.exec(prefix);
        if (qualifier) {
            const resolve = (qualified, resolving = new Set()) => {
                const [base, ...path] = qualified.split('.');
                if (resolving.has(base)) return null;
                resolving.add(base);
                let module = context.modules.get(base), scope = '';
                const binding = context.names.get(base);
                if (binding) {
                    module = binding.module;
                    scope = binding.name;
                } else if (!module) {
                    module = [...context.stars].find(name => members(name).some(tag => tag.n === base));
                    scope = base;
                }
                if (!module && context.instances.has(base)) {
                    const instance = resolve(context.instances.get(base), resolving);
                    if (!instance) return null;
                    const split = instance.scope.split('.'), name = split.pop();
                    if (!members(instance.module, split.join('.')).some(tag => tag.n === name && tag.k === 'class')) return null;
                    ({module, scope} = instance);
                }
                if (!byModule.has(module)) return null;
                // Follow re-exported module aliases, for example ce_chart.plt.color.
                for (const part of [null, ...path]) {
                    if (part !== null) scope = scope ? scope + '.' + part : part;
                    const split = scope.split('.'), name = split.pop();
                    const tag = members(module, split.join('.')).find(tag => tag.n === name);
                    if (tag?.m) { module = tag.m; scope = ''; }
                }
                return {module, scope};
            };
            const resolved = resolve(qualifier[1]);
            if (resolved) return only(members(resolved.module, resolved.scope));
            return {sdkCtags: []};
        }
        // Bare names must have been imported; module functions do not become globals.
        const imported = [];
        for (const [alias, module] of context.modules) {
            const tag = (byModule.get(module) || []).find(tag => tag.k === 'module');
            if (tag) imported.push({...tag, n: alias});
        }
        for (const module of context.stars) imported.push(...members(module));
        for (const [alias, binding] of context.names) {
            const tag = members(binding.module).find(tag => tag.n === binding.name);
            if (tag) imported.push({...tag, n: alias});
        }
        return {sdkCtags: imported};
    };
})();
