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

if (!String.prototype.trim) {
    String.prototype.trim = function () {
        return this.replace(/^[\s\uFEFF\xA0]+|[\s\uFEFF\xA0]+$/g, '');
    };
}

function parseResponseHeaders(headerStr) {
    var headers = {};
    if (!headerStr) {
        return headers;
    }
    var headerPairs = headerStr.split('\u000d\u000a');
    for (var i = 0; i < headerPairs.length; i++) {
        var headerPair = headerPairs[i];
        // Can't use split() here because it does the wrong thing
        // if the header value has the string ": " in it.
        var index = headerPair.indexOf('\u003a\u0020');
        if (index > 0) {
            var key = headerPair.substring(0, index);
            var val = headerPair.substring(index + 2);
            headers[key] = val;
        }
    }
    return headers;
}

function ajax(url, params, callbackOK, callbackErr, callbackAlways)
{
    var xhr = new XMLHttpRequest();
    xhr.open('POST', url, true);
    xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function() {
        if (xhr.readyState == 4)
        {
            var lastHeaders = parseResponseHeaders(xhr.getAllResponseHeaders());
            if (lastHeaders['pb-csrf-token'] !== undefined) {
                window.CSRFToken = lastHeaders['pb-csrf-token'];
                document.getElementById('newProjLink').setAttribute('href', "/pb/?new=1&csrf_token=" + window.CSRFToken);
            }

            if (typeof callbackAlways === "function") {
                callbackAlways(xhr.responseText);
            }
            if (xhr.status == 200 && typeof callbackOK === "function") {
                callbackOK(xhr.responseText);
            } else {
                if (typeof callbackErr === "function")
                {
                    callbackErr(xhr.responseText);
                } else {
                    console.log(xhr.responseText);
                    alert(xhr.responseText);
                }
            }
        }
    };

    params += "&csrf_token=" + window.CSRFToken;
    xhr.send(params);
}

function elt(tagname, cls /*, ... elts*/)
{
    var e = document.createElement(tagname);
    if (cls) e.className = cls;
    for (var i = 2; i < arguments.length; ++i) {
        var elt = arguments[i];
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
    var node = elt("div", "inlineTooltip", content);
    node.style.left = x + "px";
    node.style.top = y + "px";
    document.body.appendChild(node);
    return node;
}

function isNumeric(value)
{
    return /^\d+$/.test(value);
}

function updateQueryStringParameter(uri, key, value) {
  var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
  var separator = uri.indexOf('?') !== -1 ? "&" : "?";
  if (uri.match(re)) {
    return uri.replace(re, '$1' + key + "=" + value + '$2');
  }
  else {
    return uri + separator + key + "=" + value;
  }
}

function hasClass(el, className) {
  if (el.classList)
    return el.classList.contains(className)
  else
    return !!el.className.match(new RegExp('(\\s|^)' + className + '(\\s|$)'))
}

function addClass(el, className) {
  if (el.classList)
    el.classList.add(className)
  else if (!hasClass(el, className)) el.className += " " + className
}

function removeClass(el, className) {
  if (el.classList)
    el.classList.remove(className)
  else if (hasClass(el, className)) {
    var reg = new RegExp('(\\s|^)' + className + '(\\s|$)')
    el.className=el.className.replace(reg, ' ')
  }
}
