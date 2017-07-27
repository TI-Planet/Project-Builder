// CodeMirror, copyright (c) by Marijn Haverbeke and others
// Distributed under an MIT license: http://codemirror.net/LICENSE

// Modified by Adrien 'Adriweb' Bertrand to add ctags as source in addition to anyword

(function(mod) {
  if (typeof exports == "object" && typeof module == "object") // CommonJS
    mod(require("../../lib/codemirror"));
  else if (typeof define == "function" && define.amd) // AMD
    define(["../../lib/codemirror"], mod);
  else // Plain browser env
    mod(CodeMirror);
})(function(CodeMirror) {
  "use strict";

    var WORD = /[\w$]+/, RANGE = 500;

    const makeCompletion = (tag) => {
        const retType = (tag.r && !tag.r.startsWith("__anon")) ? (` -> ${tag.r}`) : '';
        return {
            text: tag.n,
            rest: tag.a ? `<i class="text-muted">${tag.a.replace(/,/g, ', ')}${retType}</i>` : '',
            className: `ctag-type-${tag.k}`,
            hint: (ed, data, comp) => { ed.replaceRange(comp.text, comp.from || data.from, comp.to || data.to, "complete"); },
            render: (elt, data, cur) => {
                const newEl = document.createElement("span");
                newEl.innerHTML = (cur.htmlName || cur.text) + cur.rest;
                elt.appendChild(newEl);
            },
        }
    };

    CodeMirror.registerHelper("hint", "any_and_ctags", function(editor, options)
    {
      var word = options && options.word || WORD;
      var range = options && options.range || RANGE;
      var cur = editor.getCursor(), curLine = editor.getLine(cur.line);
      var end = cur.ch, start = end;
      while (start && word.test(curLine.charAt(start - 1))) --start;
      var curWord = start != end && curLine.slice(start, end);

      var list = options && options.list || [], seen = {};

      if (curWord)
      {
          const upperCaseCurWord = curWord.toUpperCase();

          const isEditorASM = editor.getMode().name === 'z80';

          if (!isEditorASM)
          {
              // from anywhere, non fuzzy
              const re = new RegExp(word.source, "gi");
              for (var dir = -1; dir <= 1; dir += 2) {
                  let line = cur.line, endLine = Math.min(Math.max(line + dir * range, editor.firstLine()), editor.lastLine()) + dir;
                  for (; line !== endLine; line += dir) {
                      let text = editor.getLine(line), m;
                      while (m = re.exec(text)) {
                          if (line === cur.line && m[0] === curWord) continue;
                          if ((!curWord || m[0].lastIndexOf(curWord, 0) === 0) && !Object.prototype.hasOwnProperty.call(seen, m[0])) {
                              seen[m[0]] = true;
                              list.push( { text: m[0] } );
                          }
                      }
                  }
              }
          }

          const fuzzyRegex = new RegExp(curWord.split("").reduce((a, b) => a + '[^' + b + ']*' + b), 'gi');

          // from ctags, fuzzy
          if (typeof(window.ctags) === 'object') {
              Array.prototype.push.apply(list, window.ctags.filter((val) => val.n.match(fuzzyRegex)).map((val) => makeCompletion(val) ));
          }
          if (!isEditorASM)
          {
              // from sdk_ctags, fuzzy
              if (typeof(window.sdk_ctags) === 'object' && window.enable_sdk_ctags) {
                  Array.prototype.push.apply(list, window.sdk_ctags.filter((val) => val.n.match(fuzzyRegex)).map((val) => makeCompletion(val) ));
              }
          }
          if (isEditorASM)
          {
              // from ti84pceInc_ctags, fuzzy
              if (typeof(window.ti84pceInc_ctags) === 'object' && window.enable_ti84pceInc_ctags) {
                  Array.prototype.push.apply(list, window.ti84pceInc_ctags.filter((val) => val.n.match(fuzzyRegex)).map((val) => makeCompletion(val) ));
              }
          }

          // Credits to Runer112 for suggesting this sort function
          list = list.sort( (val1, val2) => {
              const str1 = val1.text.toUpperCase();
              const str2 = val2.text.toUpperCase();
              const index1 = str1.indexOf(upperCaseCurWord);
              const index2 = str2.indexOf(upperCaseCurWord);
              const isPrefix1 = index1 === 0;
              const isPrefix2 = index2 === 0;
              if (isPrefix1 && !isPrefix2) {
                  return -1;
              }
              if (isPrefix2 && !isPrefix1) {
                  return 1;
              }
              const isSubstr1 = index1 >= 0;
              const isSubstr2 = index2 >= 0;
              if (isSubstr1 && !isSubstr2) {
                  return -1;
              }
              if (isSubstr2 && !isSubstr1) {
                  return 1;
              }
              return str1 < str2 ? -1 : str1 > str2 ? 1 : 0;
          });

          // Make matches bold
          list = list.map( (tag) => {
              let i, tagCurFrom = 0;
              tag.htmlName = tag.text;
              for (i=0; i<curWord.length; i++)
              {
                  const tagCurIdx = tag.htmlName.toLowerCase().indexOf(curWord[i].toLowerCase(), tagCurFrom);
                  if (tagCurIdx < 0) { return tag; } // shouldn't happen
                  const replacement = `<b>${tag.htmlName[tagCurIdx]}</b>`;
                  tag.htmlName = tag.htmlName.substr(0, tagCurIdx) + replacement + tag.htmlName.substr(tagCurIdx+1);
                  tagCurFrom = tagCurIdx+replacement.length;
              }
              return tag;
          });
      }

      // remove unwanted stuff
      list = list.filter((thing) => !thing.text.startsWith('__anon'));

      // remove duplicates (keeping the best one if dup)
      let namesSeen = {};
      list = list.filter( (thing, i) => {
          if (namesSeen[thing.text]) {
              return false; // already seen
          }
          const otherIdx = list.findIndex((t, num) => { return num !== i && t.text === thing.text; });
          if (otherIdx === -1 || thing.className) // no dup found, or current is better
          {
              namesSeen[thing.text] = true;
              return true;
          }
          return false; // don't take this one, take the dup
      });

      return {
          list: list,
          from: CodeMirror.Pos(cur.line, start),
          to: CodeMirror.Pos(cur.line, end)
      }
  });
});
