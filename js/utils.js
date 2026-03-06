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
const CSRF_REFRESH_ACTION = 'refreshCSRFToken';

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

    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'ActionHandler.php', true);
    xhr.timeout = 10000;
    xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = () => {
        if (xhr.readyState !== XMLHttpRequest.DONE) {
            return;
        }

        let errMsg = 'Unable to refresh the current session token.';
        try
        {
            const resp = JSON.parse(xhr.responseText);
            if (xhr.status === 200 && resp && typeof resp.csrf_token === "string" && resp.csrf_token.length > 0)
            {
                setCSRFToken(resp.csrf_token);
                clearSessionRefreshGuard();
                finishRefresh(true, null);
                return;
            }
            if (typeof resp === "string" && resp.length > 0) {
                errMsg = resp;
            }
        } catch (e)
        {
            if (xhr.responseText && xhr.responseText.length > 0) {
                errMsg = xhr.responseText;
            }
        }
        finishRefresh(false, errMsg);
    };
    xhr.ontimeout = () => {
        finishRefresh(false, 'Token refresh timed out.');
    };
    xhr.send(`id=${encodeURIComponent(projectID)}&action=${CSRF_REFRESH_ACTION}`);
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

    incrementActivityIndicatorCounterAndShow();
    const xhr = new XMLHttpRequest();
    xhr.open('POST', url, true);
    xhr.timeout = requestParams.includes("build") ? 60000 : 10000;
    xhr.ontimeout = decrementActivityIndicatorCounterAndHide;
    xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = () => {
        if (xhr.readyState === XMLHttpRequest.DONE)
        {
            let respText = xhr.responseText;
            try { respText = JSON.parse(respText); } catch(e) { console.log('XHR Error: could not parse the reponse as JSON'); }
            decrementActivityIndicatorCounterAndHide();

            if (xhr.status === 401 && allowCSRFRefresh && /ActionHandler\.php(?:$|\?)/.test(url))
            {
                refreshCSRFToken(requestParams, (ok) => {
                    if (ok) {
                        ajax(url, requestParams, callbackOK, callbackErr, callbackAlways, false);
                    } else {
                        if (typeof callbackAlways === "function") {
                            callbackAlways(respText);
                        }
                        handleUnauthorizedFallback(respText, callbackErr);
                    }
                });
                return;
            }

            if (typeof callbackAlways === "function") {
                callbackAlways(respText);
            }
            if (xhr.status === 200) {
                clearSessionRefreshGuard();
                if (typeof callbackOK === "function") {
                    callbackOK(respText);
                }
            } else if (xhr.status === 401) {
                handleUnauthorizedFallback(respText, callbackErr);
            } else {
                showNotification("danger", "Oops... :(", respText.length ? respText : "Internet issue?");
                if (typeof callbackErr === "function") {
                    callbackErr(respText);
                }
            }
        }
    };

    xhr.send(`${requestParams}&csrf_token=${encodeURIComponent(window['CSRFToken'] || '')}`);
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

    const xhr = new XMLHttpRequest();
    xhr.open('POST', url, true);
    xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xhr.responseType = 'arraybuffer';

    xhr.onload = function(e) {
        if (this.status == 200) {
            clearSessionRefreshGuard();
            if (typeof callbackOK === "function") {
                callbackOK(this.response);
            }
        } else if (this.status === 401) {
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
            console.log("Error XHR arraybuffer: ", this);
            showNotification("danger", "Oops... :(", "Error trying to load the file in the emulator...");
        }
    };

    xhr.send(`${requestParams}&csrf_token=${encodeURIComponent(window['CSRFToken'] || '')}`);
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

function showNotification(notifType, title, message, endCallback, delay)
{
    if (endCallback === undefined) { endCallback = null; }
    if (delay === undefined) { delay = 2500; }
    return $.notify({
        title: title,
        message: message
    },{
        type: notifType,
        delay: Math.max(1, delay - 1000),
        placement: { from: "top", align: "center" },
        onClose: endCallback,
    });
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
