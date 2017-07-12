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
              var re = new RegExp(word.source, "gi");
              for (var dir = -1; dir <= 1; dir += 2) {
                  var line = cur.line, endLine = Math.min(Math.max(line + dir * range, editor.firstLine()), editor.lastLine()) + dir;
                  for (; line != endLine; line += dir) {
                      var text = editor.getLine(line), m;
                      while (m = re.exec(text)) {
                          if (line == cur.line && m[0] === curWord) continue;
                          if ((!curWord || m[0].lastIndexOf(curWord, 0) == 0) && !Object.prototype.hasOwnProperty.call(seen, m[0])) {
                              seen[m[0]] = true;
                              list.push(m[0]);
                          }
                      }
                  }
              }
          }

          const fuzzyRegex = new RegExp(curWord.split("").reduce((a, b) => a + '[^' + b + ']*' + b), 'gi');

          // from ctags, fuzzy
          if (typeof(window.ctags) === 'object') {
              Array.prototype.push.apply(list, window.ctags.filter((val) => val.n.match(fuzzyRegex)).map((val) => val.n).sort());
          }
          if (!isEditorASM)
          {
              // from sdk_ctags, fuzzy
              if (typeof(window.sdk_ctags) === 'object' && window.enable_sdk_ctags) {
                  Array.prototype.push.apply(list, window.sdk_ctags.filter((val) => val.n.match(fuzzyRegex)).map((val) => val.n).sort());
              }
          }
          if (isEditorASM)
          {
              // from ti84pceInc_ctags, fuzzy
              if (typeof(window.ti84pceInc_ctags) === 'object' && !window.enable_ti84pceInc_ctags) {
                  Array.prototype.push.apply(list, window.ti84pceInc_ctags.filter((val) => val.n.match(fuzzyRegex)).map((val) => val.n).sort());
              }
          }

          list = list.sort( (str1, str2) => {
              str1 = str1.toUpperCase();
              str2 = str2.toUpperCase();
              const isPrefix1 = str1.startsWith(upperCaseCurWord);
              const isPrefix2 = str2.startsWith(upperCaseCurWord);
              if (isPrefix1 && !isPrefix2) {
                  return -1;
              }
              if (isPrefix2 && !isPrefix1) {
                  return 1;
              }
              return str1 < str2 ? -1 : str1 > str2 ? 1 : 0;
          });
      }

      // remove duplicates
      list = list.filter((v, i, a) => a.indexOf(v) === i);

        return {
          list: list,
          from: CodeMirror.Pos(cur.line, start),
          to: CodeMirror.Pos(cur.line, end)
      }
  });
});
