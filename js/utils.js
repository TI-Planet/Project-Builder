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

/* General utilities */

// https://tc39.github.io/ecma262/#sec-array.prototype.includes
if (!Array.prototype.includes)
{
    Object.defineProperty(Array.prototype, 'includes',
    {
        value: function (searchElement, fromIndex)
        {
            if (this == null) {
                throw new TypeError('"this" is null or not defined');
            }
            var o = Object(this);
            var len = o.length >>> 0;
            if (len === 0) {
                return false;
            }
            var n = fromIndex | 0;
            var k = Math.max(n >= 0 ? n : len - Math.abs(n), 0);
            while (k < len)
            {
                if (o[k] === searchElement) {
                    return true;
                }
                k++;
            }
            return false;
        }
    });
}

// Returns a function, that, as long as it continues to be invoked, will not
// be triggered. The function will be called after it stops being called for
// N milliseconds. If `immediate` is passed, trigger the function on the
// leading edge, instead of the trailing.
function debounce(func, wait, immediate)
{
    let timeout;
    return function() {
        const context = this, args = arguments;
        const later = () => {
            timeout = null;
            if (!immediate) func.apply(context, args);
        };
        const callNow = immediate && !timeout;
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
        if (callNow) func.apply(context, args);
    }
}

function escapeRegExp(str)
{
    return str.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, "\\$&");
}

// AJAX related stuff
{
    let activityIndicatorCounter = 0;
    window.incrementActivityIndicatorCounterAndShow = function() {
        activityIndicatorCounter++;
        document.getElementById('xhrActivityIndicator').style.display = 'inline-block';
    };
    window.decrementActivityIndicatorCounterAndHide = function() {
        activityIndicatorCounter--;
        setTimeout( () => { if (activityIndicatorCounter === 0) { document.getElementById('xhrActivityIndicator').style.display = 'none'; } }, 500);
    }
}

const SESSION_REFRESH_GUARD_KEY = 'pb_session_refresh_ts';
const SESSION_REFRESH_GUARD_WINDOW_MS = 15000;

let csrfTokenRefreshInProgress = false;
let csrfTokenRefreshCallbacks = [];

function shouldAutoRefreshAfterUnauthorized()
{
    const now = Date.now();
    const lastRefreshTs = parseInt(sessionStorage.getItem(SESSION_REFRESH_GUARD_KEY), 10);
    if (!isNaN(lastRefreshTs) && ((now - lastRefreshTs) < SESSION_REFRESH_GUARD_WINDOW_MS)) {
        return false;
    }
    sessionStorage.setItem(SESSION_REFRESH_GUARD_KEY, `${now}`);
    return true;
}

function clearSessionRefreshGuard()
{
    sessionStorage.removeItem(SESSION_REFRESH_GUARD_KEY);
}

function setCSRFToken(newToken)
{
    window['CSRFToken'] = newToken;
    const tokenFields = document.querySelectorAll('input[name="csrf_token"]');
    for (let i = 0; i < tokenFields.length; i++)
    {
        tokenFields[i].value = newToken;
    }

    for (const link of document.querySelectorAll('a[href*="csrf_token="]'))
    {
        const href = link.getAttribute('href');
        if (typeof href === "string" && href.length > 0)
        {
            link.setAttribute('href', href.replace(/([?&]csrf_token=)[^&#]*/g, `$1${encodeURIComponent(newToken)}`));
        }
    }
}

function getProjectIDFromParams(params)
{
    if (window.proj && typeof window.proj.pid === "string" && window.proj.pid.length > 0) {
        return window.proj.pid;
    }
    const match = (params || '').match(/(?:^|&)id=([^&]+)/);
    return match && match[1] ? decodeURIComponent(match[1].replace(/\+/g, ' ')) : null;
}

function buildPOSTParams(params, includeCSRFToken)
{
    let body = (params || '').replace(/&+$/g, '');
    if (includeCSRFToken !== false)
    {
        body += `${body.length ? '&' : ''}csrf_token=${encodeURIComponent(window['CSRFToken'] || '')}`;
    }
    return body;
}

async function fetchPOST(url, params, timeoutMs, responseType, includeCSRFToken)
{
    const abortController = new AbortController();
    const timeoutID = setTimeout(() => { abortController.abort(); }, timeoutMs);
    try
    {
        const response = await fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                "Content-type": "application/x-www-form-urlencoded",
                "X-Requested-With": "XMLHttpRequest"
            },
            body: buildPOSTParams(params, includeCSRFToken),
            signal: abortController.signal
        });

        if (responseType === 'arraybuffer') {
            return { ok: true, status: response.status, body: await response.arrayBuffer() };
        }

        const rawText = await response.text();
        let body = rawText;
        try {
            body = JSON.parse(rawText);
        } catch (e) {
            console.log('XHR Error: could not parse the reponse as JSON');
        }
        return { ok: true, status: response.status, body: body };
    } catch (e)
    {
        if (e.name === 'AbortError') {
            return { ok: false, status: 0, body: '', timedOut: true };
        }
        console.log('Fetch Error:', e);
        return { ok: false, status: 0, body: '' };
    } finally
    {
        clearTimeout(timeoutID);
    }
}

async function fetchGET(url, timeoutMs)
{
    const abortController = new AbortController();
    const timeoutID = setTimeout(() => { abortController.abort(); }, timeoutMs);
    try
    {
        const response = await fetch(url, {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                "X-Requested-With": "XMLHttpRequest"
            },
            signal: abortController.signal
        });

        return {
            ok: true,
            status: response.status,
            body: await response.text(),
            url: response.url,
            redirected: response.redirected
        };
    } catch (e)
    {
        if (e.name === 'AbortError') {
            return { ok: false, status: 0, body: '', url: url, redirected: false, timedOut: true };
        }
        console.log('Fetch Error:', e);
        return { ok: false, status: 0, body: '', url: url, redirected: false };
    } finally
    {
        clearTimeout(timeoutID);
    }
}

function extractEditorContainerHTML(rawHTML, requiredSelector)
{
    try
    {
        const doc = new DOMParser().parseFromString(rawHTML, 'text/html');
        const editorContainer = doc.querySelector('#editorContainer');
        if (!editorContainer) {
            return null;
        }
        if (requiredSelector && !editorContainer.querySelector(requiredSelector)) {
            return null;
        }
        return editorContainer.innerHTML;
    } catch (e)
    {
        console.log('HTML Parse Error:', e);
        return null;
    }
}

let editorNavigationRequestSeq = 0;
let editorRuntimeSessionSeq = 0;
let activeRealtimeEditorCleanup = null;

function beginEditorNavigationRequest()
{
    editorNavigationRequestSeq += 1;
    return editorNavigationRequestSeq;
}

function isCurrentEditorNavigationRequest(seq)
{
    return seq === editorNavigationRequestSeq;
}

function beginEditorRuntimeSession()
{
    editorRuntimeSessionSeq += 1;
    if (typeof activeRealtimeEditorCleanup === "function")
    {
        try {
            activeRealtimeEditorCleanup();
        } catch (e) {
            console.log('Realtime cleanup error:', e);
        }
    }
    activeRealtimeEditorCleanup = null;
    return editorRuntimeSessionSeq;
}

function isCurrentEditorRuntimeSession(seq)
{
    return seq === editorRuntimeSessionSeq;
}

function registerRealtimeEditorCleanup(cleanup)
{
    activeRealtimeEditorCleanup = (typeof cleanup === "function") ? cleanup : null;
}

function updateLoadedFileSnapshot(source, sourceHash, mtime)
{
    if (typeof fakeContainer === "undefined" || !fakeContainer) {
        return;
    }
    if ('value' in fakeContainer) {
        fakeContainer.value = source;
    }
    fakeContainer.textContent = source;
    if (typeof sourceHash === "string" && sourceHash.length > 0) {
        fakeContainer.dataset.sourceHash = sourceHash;
    }
    if (mtime !== undefined && mtime !== null && `${mtime}`.length > 0) {
        fakeContainer.dataset.mtime = `${mtime}`;
    }
}

function monitorFirepadSyncHealth(firepad, editor, isActiveRuntimeSession, callbacks)
{
    const onHealthy = callbacks && typeof callbacks.onHealthy === "function" ? callbacks.onHealthy : function() {};
    const onStalled = callbacks && typeof callbacks.onStalled === "function" ? callbacks.onStalled : function() {};
    const canReportHealthy = callbacks && typeof callbacks.canReportHealthy === "function" ? callbacks.canReportHealthy : function() { return true; };
    const onSyncStateChange = callbacks && typeof callbacks.onSyncStateChange === "function" ? callbacks.onSyncStateChange : function() {};
    const stallDelayMs = callbacks && Number.isFinite(callbacks.stallDelayMs) ? callbacks.stallDelayMs : 5000;
    const recentEditGraceMs = callbacks && Number.isFinite(callbacks.recentEditGraceMs) ? callbacks.recentEditGraceMs : 4000;
    const isActive = typeof isActiveRuntimeSession === "function" ? isActiveRuntimeSession : function() { return true; };
    let syncStatusTimeoutID = null;
    let lastEditorActivityTS = Date.now();

    const reportHealthyIfAllowed = () => {
        if (!isActive()) {
            return;
        }
        if (window.navigator && window.navigator.onLine === false) {
            return;
        }
        if (!canReportHealthy()) {
            return;
        }
        onHealthy();
    };

    const clearPendingSyncCheck = () => {
        if (syncStatusTimeoutID !== null) {
            clearTimeout(syncStatusTimeoutID);
            syncStatusTimeoutID = null;
        }
    };

    const markEditorActivity = () => {
        lastEditorActivityTS = Date.now();
        reportHealthyIfAllowed();
    };

    const handleOnline = () => {
        clearPendingSyncCheck();
        reportHealthyIfAllowed();
    };

    const handleOffline = () => {
        clearPendingSyncCheck();
        if (!isActive()) {
            return;
        }
        onStalled(
            "Collaborative connection lost",
            "You appear to be offline. Wait for the connection to recover before saving shared changes."
        );
    };

    const handleSynced = (isSynced) => {
        if (!isActive()) {
            return;
        }

        clearPendingSyncCheck();
        onSyncStateChange(isSynced === true);
        if (isSynced === false)
        {
            syncStatusTimeoutID = window.setTimeout(() => {
                if (!isActive()) {
                    return;
                }
                if ((Date.now() - lastEditorActivityTS) < recentEditGraceMs) {
                    return;
                }
                if (window.navigator && window.navigator.onLine === false)
                {
                    handleOffline();
                    return;
                }
                onStalled(
                    "Collaborative sync stalled",
                    "Recent shared edits are still waiting for confirmation. Wait a moment before saving. If this persists, make a local backup and reload."
                );
            }, stallDelayMs);
        } else {
            reportHealthyIfAllowed();
        }
    };

    onSyncStateChange(false);

    if (editor && typeof editor.on === "function") {
        editor.on('change', markEditorActivity);
    }
    if (firepad && typeof firepad.on === "function") {
        firepad.on('synced', handleSynced);
    }
    window.addEventListener('online', handleOnline);
    window.addEventListener('offline', handleOffline);

    return () => {
        clearPendingSyncCheck();
        if (editor && typeof editor.off === "function") {
            editor.off('change', markEditorActivity);
        }
        if (firepad && typeof firepad.off === "function") {
            firepad.off('synced', handleSynced);
        }
        window.removeEventListener('online', handleOnline);
        window.removeEventListener('offline', handleOffline);
    };
}

function createRealtimeServerSnapshotBridge(serverSnapshotRef, isActiveRuntimeSession, userId)
{
    const isActive = typeof isActiveRuntimeSession === "function" ? isActiveRuntimeSession : function() { return true; };
    const listener = (snapshot) => {
        if (!isActive()) {
            return;
        }
        const data = snapshot && typeof snapshot.val === "function" ? snapshot.val() : null;
        if (!data || typeof data !== "object") {
            return;
        }
        if (typeof fakeContainer === "undefined" || !fakeContainer) {
            return;
        }
        const currentMtime = parseInt(fakeContainer.dataset.mtime || '', 10);
        const incomingMtime = parseInt((data.mtime !== undefined && data.mtime !== null) ? `${data.mtime}` : '', 10);
        if (!isNaN(currentMtime) && !isNaN(incomingMtime) && incomingMtime < currentMtime) {
            return;
        }
        if (typeof data.source_hash === "string" && data.source_hash.length > 0) {
            fakeContainer.dataset.sourceHash = data.source_hash;
        }
        if (data.mtime !== undefined && data.mtime !== null && `${data.mtime}`.length > 0) {
            fakeContainer.dataset.mtime = `${data.mtime}`;
        }
    };

    serverSnapshotRef.on('value', listener);

    return {
        cleanup: () => {
            serverSnapshotRef.off('value', listener);
        },
        publish: (sourceHash, mtime) => {
            if (!isActive()) {
                return;
            }
            if (typeof sourceHash !== "string" || sourceHash.length === 0) {
                return;
            }
            const payload = {
                source_hash: sourceHash,
                saved_at: (window.Firebase && Firebase.ServerValue) ? Firebase.ServerValue.TIMESTAMP : Date.now()
            };
            if (mtime !== undefined && mtime !== null && `${mtime}`.length > 0) {
                payload.mtime = mtime;
            }
            if (userId !== undefined && userId !== null && `${userId}`.length > 0) {
                payload.by = userId;
            }
            serverSnapshotRef.set(payload);
        }
    };
}

function createCollaborativeFirepadBindings(options)
{
    const firepadRef = options.firepadRef;
    const editor = options.editor;
    const user = options.user;
    const userListElement = options.userListElement;
    const isActiveRuntimeSession = options.isActiveRuntimeSession;
    const getServerSource = typeof options.getServerSource === "function" ? options.getServerSource : function() { return ''; };
    const onReady = typeof options.onReady === "function" ? options.onReady : function() {};
    const onHealthy = typeof options.onHealthy === "function" ? options.onHealthy : function() {};
    const onStalled = typeof options.onStalled === "function" ? options.onStalled : function(title, message) {
        showNotification("danger", title, message, null, 999999);
    };

    let firepad = null;
    let firepadUserList = null;
    let serverSnapshotBridge = null;
    let cleanupFirepadSyncMonitor = null;
    let firepadSessionBlocked = false;
    let firepadIsCurrentlySynced = false;

    const isActive = typeof isActiveRuntimeSession === "function" ? isActiveRuntimeSession : function() { return true; };

    const dispose = () => {
        if (typeof cleanupFirepadSyncMonitor === "function") {
            cleanupFirepadSyncMonitor();
            cleanupFirepadSyncMonitor = null;
        }
        if (serverSnapshotBridge !== null) {
            serverSnapshotBridge.cleanup();
            serverSnapshotBridge = null;
        }
        if (firepadUserList !== null) {
            firepadUserList.dispose();
            firepadUserList = null;
        }
        if (firepad !== null) {
            firepad.dispose();
            firepad = null;
        }
    };

    const setSessionBlocked = (blocked) => {
        firepadSessionBlocked = blocked === true;
        if (firepadSessionBlocked) {
            firepadIsCurrentlySynced = false;
        }
    };

    const trySync = () => {
        if (!isActive() || firepad === null) {
            return;
        }
        firepad.client_.updateCursor();
        firepad.client_.sendCursor(firepad.client_.cursor);
    };

    const isCurrentlySynced = () => {
        return firepadSessionBlocked === false && firepadIsCurrentlySynced === true && window.navigator.onLine !== false;
    };

    const publishServerSnapshot = (sourceHash, mtime) => {
        if (serverSnapshotBridge !== null) {
            serverSnapshotBridge.publish(sourceHash, mtime);
        }
    };

    const createOrReset = () => {
        dispose();
        firepadSessionBlocked = false;
        firepadIsCurrentlySynced = false;
        firepad = Firepad.fromCodeMirror(firepadRef, editor, {
            userId: user.id,
            userColor: `#${Math.floor(Math.random() * 0xFFFFFF).toString(16)}`
        });
        firepadUserList = FirepadUserList.fromDiv(firepadRef.child('users'), userListElement, user.id, user.name, user.avatar);
        serverSnapshotBridge = createRealtimeServerSnapshotBridge(firepadRef.child('_serverSnapshot'), isActiveRuntimeSession, user.id);
        cleanupFirepadSyncMonitor = monitorFirepadSyncHealth(firepad, editor, isActiveRuntimeSession, {
            canReportHealthy: () => !firepadSessionBlocked,
            onSyncStateChange: (isSynced) => { firepadIsCurrentlySynced = (isSynced === true); },
            onHealthy: onHealthy,
            onStalled: onStalled,
        });

        firepad.on('ready', () => {
            if (!isActive()) {
                return;
            }
            firepadRef.child("history").orderByKey().limitToLast(1).once('value', () => {
                if (!isActive()) {
                    return;
                }
                const serverSource = getServerSource();
                const liveSource = editor.getValue();
                const usedServerSource = firepad.isHistoryEmpty();
                if (usedServerSource) {
                    firepad.setText(serverSource);
                }
                onReady({
                    firepad: firepad,
                    firepadRef: firepadRef,
                    editor: editor,
                    serverSource: serverSource,
                    liveSource: liveSource,
                    usedServerSource: usedServerSource
                });
            });
        });
    };

    return {
        createOrReset: createOrReset,
        dispose: dispose,
        setSessionBlocked: setSessionBlocked,
        trySync: trySync,
        isCurrentlySynced: isCurrentlySynced,
        publishServerSnapshot: publishServerSnapshot,
    };
}

function registerCollaborativeFirepadGlobals(options)
{
    const getBindings = typeof options.getBindings === "function" ? options.getBindings : function() { return null; };
    const isCollaborative = typeof options.isCollaborative === "function" ? options.isCollaborative : function() { return !!(window.proj && proj.is_multi); };
    const isSessionBlocked = typeof options.isSessionBlocked === "function" ? options.isSessionBlocked : function() { return false; };

    const withBindings = (callback) => {
        const bindings = getBindings();
        if (bindings === null || typeof callback !== "function") {
            return null;
        }
        return callback(bindings);
    };

    registerRealtimeEditorCleanup(() => {
        withBindings((bindings) => { bindings.dispose(); });
    });

    window.removeMyselfFromFirepad = function()
    {
        if (!isCollaborative()) {
            return;
        }
        withBindings((bindings) => { bindings.dispose(); });
    };

    window.tryFirepadSync = function()
    {
        withBindings((bindings) => { bindings.trySync(); });
    };

    window.isRealtimeEditorCurrentlySynced = function()
    {
        return withBindings((bindings) => bindings.isCurrentlySynced()) === true;
    };

    window.publishRealtimeServerSnapshot = function(sourceHash, mtime)
    {
        withBindings((bindings) => { bindings.publishServerSnapshot(sourceHash, mtime); });
    };

    return function createOrResetFirepad()
    {
        withBindings((bindings) => {
            bindings.createOrReset();
            bindings.setSessionBlocked(isSessionBlocked());
        });
    };
}

function canProceedWithCollaborativeSave(retryFn)
{
    if (!proj.is_multi) {
        globalSaveFileRetryCount = 0;
        return true;
    }

    if (!globalSyncOK || globalSaveFileRetryCount >= 3)
    {
        showNotification("warning", "Syncing failed", "Saving now may overwrite changes and data may be lost. Please try again later (and make a local backup)", null, 999999);
        globalSaveFileRetryCount = 0;
        return false;
    }

    if (typeof isRealtimeEditorCurrentlySynced === "function" && !isRealtimeEditorCurrentlySynced())
    {
        typeof(tryFirepadSync) !== "undefined" && tryFirepadSync();
        globalSaveFileRetryCount++;
        setTimeout(retryFn, 200);
        return false;
    }

    if ((new Date).getTime() - lastChangeTS > 30000)
    {
        typeof(tryFirepadSync) !== "undefined" && tryFirepadSync();
        lastChangeTS = (new Date).getTime();
        console.log("Current session is old, trying to sync with Firepad... Retry count == " + globalSaveFileRetryCount);
        globalSaveFileRetryCount++;
        setTimeout(retryFn, 200);
        return false;
    }

    globalSaveFileRetryCount = 0;
    return true;
}

function hasUnsavedEditorSource()
{
    if (typeof editor === "undefined" || !editor || typeof editor.getValue !== "function") {
        return false;
    }
    return editor.getValue() !== lastSavedSource;
}

function isForumLoginRedirectURL(url)
{
    return typeof url === "string" && /\/forum\/ucp\.php\?mode=login\b/.test(url);
}

function fallbackToFullPageNavigation(url, message)
{
    showNotification("warning", "Refreshing editor", message || "Reloading this file normally...");
    window.onbeforeunload = null;
    setTimeout(() => { window.location.assign(url); }, 1000);
}

function refreshCSRFToken(params, callbackDone)
{
    const projectID = getProjectIDFromParams(params);
    if (!projectID)
    {
        callbackDone(false, 'No project ID');
        return;
    }

    csrfTokenRefreshCallbacks.push(callbackDone);
    if (csrfTokenRefreshInProgress) {
        return;
    }
    csrfTokenRefreshInProgress = true;

    const finishRefresh = (ok, errMsg) => {
        const callbacks = csrfTokenRefreshCallbacks.slice();
        csrfTokenRefreshCallbacks = [];
        csrfTokenRefreshInProgress = false;
        for (let i = 0; i < callbacks.length; i++)
        {
            callbacks[i](ok, errMsg);
        }
    };

    fetchPOST('ActionHandler.php', `id=${encodeURIComponent(projectID)}&action=refreshCSRFToken`, 10000, 'text', false).then((resp) => {
        let errMsg = 'Unable to refresh the current session token.';
        if (resp.ok && resp.status === 200 && resp.body && typeof resp.body.csrf_token === "string" && resp.body.csrf_token.length > 0)
        {
            setCSRFToken(resp.body.csrf_token);
            clearSessionRefreshGuard();
            finishRefresh(true, null);
            return;
        }
        if (resp.timedOut) {
            errMsg = 'Token refresh timed out.';
        } else if (typeof resp.body === "string" && resp.body.length > 0) {
            errMsg = resp.body;
        }
        finishRefresh(false, errMsg);
    });
}

function handleUnauthorizedFallback(respText, callbackErr)
{
    if (shouldAutoRefreshAfterUnauthorized())
    {
        showNotification("warning", "Session expired", "Could not refresh your session token. Reloading...");
        window.onbeforeunload = null;
        setTimeout(() => { window.location.reload(); }, 400);
    } else {
        showNotification("danger", "Not authenticated", "Please log in again on TI-Planet and reload this page.");
        if (typeof callbackErr === "function") {
            callbackErr(respText);
        }
    }
}

function ajax(url, params, callbackOK, callbackErr, callbackAlways, allowCSRFRefresh)
{
    if (allowCSRFRefresh === undefined) {
        allowCSRFRefresh = true;
    }
    const requestParams = params || '';
    const timeoutMs = requestParams.includes("build") ? 60000 : 10000;

    incrementActivityIndicatorCounterAndShow();

    fetchPOST(url, requestParams, timeoutMs, 'text', true).then((resp) => {
        decrementActivityIndicatorCounterAndHide();

        if (resp.status === 401 && allowCSRFRefresh && /ActionHandler\.php(?:$|\?)/.test(url))
        {
            refreshCSRFToken(requestParams, (ok) => {
                if (ok) {
                    ajax(url, requestParams, callbackOK, callbackErr, callbackAlways, false);
                } else {
                    if (typeof callbackAlways === "function") {
                        callbackAlways(resp.body);
                    }
                    handleUnauthorizedFallback(resp.body, callbackErr);
                }
            });
            return;
        }

        if (typeof callbackAlways === "function") {
            callbackAlways(resp.body);
        }
        if (resp.status === 200) {
            clearSessionRefreshGuard();
            if (typeof callbackOK === "function") {
                callbackOK(resp.body);
            }
        } else if (resp.status === 401) {
            handleUnauthorizedFallback(resp.body, callbackErr);
        } else {
            showNotification("danger", "Oops... :(", resp.body.length ? resp.body : "Internet issue?");
            if (typeof callbackErr === "function") {
                callbackErr(resp.body);
            }
        }
    });
}

function ajaxAction(action, extraParams, callbackOK, callbackErr, callbackAlways)
{
    ajax("ActionHandler.php", `id=${proj.pid}&action=${action}&${extraParams}`, callbackOK, callbackErr, callbackAlways);
}

function ajaxGetArrayBuffer(url, params, callbackOK, allowCSRFRefresh)
{
    if (allowCSRFRefresh === undefined) {
        allowCSRFRefresh = true;
    }
    const requestParams = params || '';

    fetchPOST(url, requestParams, 10000, 'arraybuffer', true).then((resp) => {
        if (resp.status === 200) {
            clearSessionRefreshGuard();
            if (typeof callbackOK === "function") {
                callbackOK(resp.body);
            }
        } else if (resp.status === 401) {
            if (allowCSRFRefresh && /ActionHandler\.php(?:$|\?)/.test(url)) {
                refreshCSRFToken(requestParams, (ok) => {
                    if (ok) {
                        ajaxGetArrayBuffer(url, requestParams, callbackOK, false);
                    } else {
                        handleUnauthorizedFallback('', null);
                    }
                });
            } else {
                handleUnauthorizedFallback('', null);
            }
        } else {
            console.log("Error fetch arraybuffer: ", resp);
            showNotification("danger", "Oops... :(", "Error trying to load the file in the emulator...");
        }
    });
}

function elt(tagname, cls, isHTML, content)
{
    const e = document.createElement(tagname);
    if (cls) e.className = cls;
    if (isHTML) {
        e.innerHTML = content;
    } else {
        e.appendChild( document.createTextNode(content));
    }
    return e;
}

function remove(node) {
    node && node.parentNode && node.parentNode.removeChild(node);
}

function makeTooltip(x, y, content, isHTML = false) {
    const node = elt("div", "inlineTooltip", isHTML, content);
    node.style.left = `${x}px`;
    node.style.top = `${y}px`;
    document.body.appendChild(node);
    return node;
}

function isNumeric(value)
{
    return /^\d+$/.test(value);
}

function isHexNum(value)
{
    return /^0x[0-9a-f]+$/i.test(value) || /^0[0-9a-f]*h$/i.test(value) || /^\$[0-9a-f]*/i.test(value);
}

function hasClass(el, className) {
    if (el.classList)
        return el.classList.contains(className);
    else
        return !!el.className.match(new RegExp(`(\\s|^)${className}(\\s|$)`))
}

function addClass(el, className) {
    if (el.classList)
        el.classList.add(className);
    else if (!hasClass(el, className)) el.className += ` ${className}`
}

function removeClass(el, className) {
    if (el.classList)
        el.classList.remove(className);
    else if (hasClass(el, className)) {
        const reg = new RegExp(`(\\s|^)${className}(\\s|$)`);
        el.className=el.className.replace(reg, ' ')
    }
}

const activeNotifications = [];

function closeNotificationsMatching(predicate)
{
    activeNotifications.slice().forEach((entry) => {
        if (!entry || typeof predicate !== "function" || !predicate(entry)) {
            return;
        }
        if (entry.notify && typeof entry.notify.close === "function") {
            entry.notify.close();
        }
    });
}

function closeCollaborativeSaveNotifications()
{
    closeNotificationsMatching((entry) => {
        if (!entry || typeof entry.title !== "string") {
            return false;
        }
        if (entry.title === "Syncing failed" || entry.title === "Collaborative sync stalled" || entry.title === "Collaborative connection lost") {
            return true;
        }
        if (entry.title !== "Oops... :(" || typeof entry.message !== "string") {
            return false;
        }
        return entry.message.indexOf("This file changed on the server since you opened it.") !== -1
            || entry.message.indexOf("Bad base source hash") !== -1;
    });
}

function showNotification(notifType, title, message, endCallback, delay)
{
    if (endCallback === undefined) { endCallback = null; }
    if (delay === undefined) { delay = 2500; }
    const entry = {
        type: notifType,
        title: title,
        message: message,
        notify: null,
    };
    const wrappedEndCallback = function() {
        const idx = activeNotifications.indexOf(entry);
        if (idx !== -1) {
            activeNotifications.splice(idx, 1);
        }
        if (typeof endCallback === "function") {
            endCallback.apply(this, arguments);
        }
    };
    entry.notify = $.notify({
        title: title,
        message: message
    },{
        type: notifType,
        delay: Math.max(1, delay - 1000),
        placement: { from: "top", align: "center" },
        onClose: wrappedEndCallback,
    });
    activeNotifications.push(entry);
    return entry.notify;
}

/*******************/

// The PB needs a reasonable screen size, warn mobile users
if ((window.currPbProjType?.endsWith('eZ80')) && (window.pbIsMobile || screen.width<1024 || screen.height < 550))
{
    function dispMobile()
    {
        document.getElementsByTagName("html")[0].innerHTML = "\
            <head> \
                <meta charset=\"utf-8\"> \
                <title>TI-Planet | Online Project Builder</title> \
                <style> \
                    html{height:100%;overflow:hidden;} \
                    body{height:100%;margin:8px;font-family:\"Helvetica Neue\",Helvetica,Arial,sans-serif;background-color:#ededed;} \
                </style> \
                <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no\" /> \
            </head> \
            <body> \
                <div style='display:flex;justify-content:center;align-items:center;width:100%;height:100%;text-align:center;'> \
                <span style='margin:8px;font-size:1.4em;color:#444;'>Aww, TI-Planet's Project Builder is only compatible with devices with larger displays.<br><br>Sorry :(</span> \
            </div> \
            </body>";
    }
    dispMobile();
    setTimeout(dispMobile, 200); // because lol loading
}
