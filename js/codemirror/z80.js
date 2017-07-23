// CodeMirror, copyright (c) by Marijn Haverbeke and others
// Distributed under an MIT license: http://codemirror.net/LICENSE

(function(mod) {
  if (typeof exports == "object" && typeof module == "object") // CommonJS
  mod(require("../../lib/codemirror"));
  else if (typeof define == "function" && define.amd) // AMD
  define(["../../lib/codemirror"], mod);
  else // Plain browser env
  mod(CodeMirror);
})(function(CodeMirror) {
"use strict";

CodeMirror.defineMode('z80', function(_config, parserConfig) {
  var ez80 = parserConfig.ez80;
  var keywords1, keywords2;
  if (ez80) {
    keywords1 = /^(exx?|(ld|cp)([di]r?)?|[lp]ea|pop|push|ad[cd]|cpl|daa|dec|inc|neg|sbc|sub|mlt|and|bit|[cs]cf|x?or|res|ret[in]|rst|set|r[lr]c?a?|r[lr]d|s[lr]a|srl|djnz|nop|[de]i|halt|im|in([di]mr?|ir?|irx|2r?)|ot(dmr?|[id]rx|imr?)|out(0?|[di]r?|[di]2r?)|tst(io)?|slp|(rs|st)mix)(\.([sl]|[sl]?i[sl]))?\b/i;
    keywords2 = /^(call|j[pr]|ret)(\.([sl]|[sl]?i[sl]))?\b/i;
  } else {
    keywords1 = /^(exx?|(ld|cp|in)([di]r?)?|pop|push|ad[cd]|cpl|daa|dec|inc|neg|sbc|sub|and|bit|[cs]cf|x?or|res|set|r[lr]c?a?|r[lr]d|s[lr]a|srl|djnz|nop|rst|[de]i|halt|im|ot[di]r|out[di]?)\b/i;
    keywords2 = /^(call|j[pr]|ret[in]?|b_?(call|jump))\b/i;
  }

  var variables1 = /^(af?|bc?|c|de?|e|hl?|l|i[xy][hl]?|i|r|sp)\b/i;
  var variables2 = /^(n?[zc]|p[oe]?|m)\b/i;
  var errors = /^sl(ia|l|1)\b/i;
  var numbers = /^(\d[\da-f]*h|(?:\$|%)[\da-f]+|\d+d?)\b/i;
  var preproc = /^\s*[#.]\w+/i;

  return {
    startState: function() {
      return {
        context: 0
      };
    },
    token: function(stream, state) {
      if (!stream.column())
        state.context = 0;

      if (stream.eatSpace())
        return null;

      var w;

      if (stream.eatWhile(/\w/)) {
        if (ez80 && stream.eat('.')) {
          stream.eatWhile(/\w/);
        }
        w = stream.current();

        if (stream.indentation())
        {
          if ((state.context == 1 || state.context == 4) && variables1.test(w)) {
            state.context = 4;
            return 'asm-var2';
          }

          if (state.context == 2 && variables2.test(w)) {
            state.context = 4;
            return 'asm-var3';
          }

          if (keywords1.test(w)) {
            state.context = 1;
            return 'asm-keyword';
          } else if (keywords2.test(w)) {
            state.context = 2;
            return 'asm-keyword';
          } else if (numbers.test(w)) {
            return 'asm-number';
          }

          if (errors.test(w))
            return 'error';

          return 'asm-variable';

        } else if (stream.match(numbers)) {
          return 'asm-number';

        } else
        {

            if (stream.peek() === ':')
            {
                return 'asm-label';
            }
            else
            {
                if ((state.context == 1 || state.context == 4) && variables1.test(w))
                {
                    state.context = 4;
                    return 'asm-var2';
                }

                if (state.context == 2 && variables2.test(w))
                {
                    state.context = 4;
                    return 'asm-var3';
                }

                if (keywords1.test(w))
                {
                    state.context = 1;
                    return 'asm-keyword';
                } else if (keywords2.test(w))
                {
                    state.context = 2;
                    return 'asm-keyword';
                } else if (numbers.test(w))
                {
                    return 'asm-number';
                } else {
                    return 'asm-variable';
                }
            }
        }


      } else if (stream.eat(';')) {
        stream.skipToEnd();
        return 'asm-comment';
      } else if (stream.eat('"')) {
        while (w = stream.next()) {
          if (w == '"')
            break;

          if (w == '\\')
            stream.next();
        }
        return 'asm-string';
      } else if (stream.eat('\'')) {
        if (stream.match(/\\?.'/))
          return 'asm-number';
      } else if (stream.eat('.') || stream.sol() && stream.eat('#')) {
        state.context = 5;

        if (stream.eatWhile(/\w/)) {
          return stream.current().match(preproc) ? 'asm-preproc' : 'asm-def';
        }
      } else if (stream.eat('$')) {
        if (stream.eatWhile(/[\da-f]/i))
          return 'asm-number';
      } else if (stream.eat('%')) {
        if (stream.eatWhile(/[01]/))
          return 'asm-number';
      } else {
        stream.next();
      }
      return null;
    }
  };
});

CodeMirror.defineMIME("text/x-z80", "z80");
CodeMirror.defineMIME("text/x-ez80", { name: "z80", ez80: true });

});
