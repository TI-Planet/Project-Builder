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

    params += "&csrf_token=" + window['CSRFToken'];
    xhr.send(params);
}

function ajaxGetArrayBuffer(url, params, callbackOK)
{
    var xhr = new XMLHttpRequest();
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
            alert("Error XHR arraybuffer :(");
        }
    };

    params += "&csrf_token=" + window['CSRFToken'];
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

/*******************/

// The PB needs a reasonable screen size, warn mobile users

var docWidth = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;
var docHeight = window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight;
var isMobile = navigator.userAgent.match(/(android|avantgo|iphone|ipod|blackberry|iemobile|bolt|bo‌​ost|cricket|docomo|fone|hiptop|mini|opera mini|kitkat|mobi|palm|phone|pie|webos|wos)/i);
if (isMobile || docWidth<1024 || docHeight < 550)
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
