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

function ajax(url, params, callbackOK, callbackErr, callbackAlways)
{
    const xhr = new XMLHttpRequest();
    xhr.open('POST', url, true);
    xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = () => {
        if (xhr.readyState == 4)
        {
            let respText = xhr.responseText;
            try { respText = JSON.parse(respText); } catch(e) {}
            if (typeof callbackAlways === "function") {
                callbackAlways(respText);
            }
            if (xhr.status == 200) {
                if (typeof callbackOK === "function") {
                    callbackOK(respText);
                }
            } else {
                showNotification("danger", "Oops... :(", respText.length ? respText : "Internet issue?");
                if (typeof callbackErr === "function") {
                    callbackErr(respText);
                }
            }
        }
    };

    params += `&csrf_token=${window['CSRFToken']}`;
    xhr.send(params);
}

function ajaxAction(action, extraParams, callbackOK, callbackErr, callbackAlways)
{
    ajax("ActionHandler.php", `id=${proj.pid}&action=${action}&${extraParams}`, callbackOK, callbackErr, callbackAlways);
}

function ajaxGetArrayBuffer(url, params, callbackOK)
{
    const xhr = new XMLHttpRequest();
    xhr.open('POST', url, true);
    xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xhr.responseType = 'arraybuffer';

    xhr.onload = function(e) {
        if (this.status == 200) {
            if (typeof callbackOK === "function") {
                callbackOK(this.response);
            }
        } else {
            console.log("Error XHR arraybuffer: ", this);
            showNotification("danger", "Oops... :(", "Error trying to load the file in the emulator...");
        }
    };

    params += `&csrf_token=${window['CSRFToken']}`;
    xhr.send(params);
}

function elt(tagname, cls /*, ... elts*/)
{
    const e = document.createElement(tagname);
    if (cls) e.className = cls;
    for (let i = 2; i < arguments.length; ++i) {
        let elt = arguments[i];
        if (typeof elt == "string")
            elt = document.createTextNode(elt);
        e.appendChild(elt);
    }
    return e;
}

function remove(node) {
    node && node.parentNode && node.parentNode.removeChild(node);
}

function makeTooltip(x, y, content) {
    const node = elt("div", "inlineTooltip", content);
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
const isMobile = navigator.userAgent.match(/(android|avantgo|iphone|ipod|blackberry|iemobile|bolt|cricket|docomo|fone|hiptop|mini|opera mini|kitkat|mobi|palm|phone|pie|webos|wos)/i);
if (isMobile || screen.width<1024 || screen.height < 550)
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
            <script> \
                (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){ \
                (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o), \
                m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m) \
                })(window,document,'script','//www.google-analytics.com/analytics.js','ga'); \
                ga('create', 'UA-25340424-5', 'auto'); \
                ga('send', 'pageview'); \
                throw new Error('Not compatible with mobiles :('); \
            </script> \
            </body>";
    }
    dispMobile();
    setTimeout(dispMobile, 200); // because lol loading
}
