"use strict";
define(function(require) {
  var CodeMirror = require('codemirror');

  CodeMirror.defineMode("elasticquery", function() {
    var commands = ['agg', 'trans', 'join'],
        atoms = ['_exists_', '_missing_', 'field', 'source_field', 'target_field'],
        commandvals = ['terms', 'sigterms', 'card', 'min', 'max', 'avg', 'sum'],
        boolops = ['OR', 'AND', 'NOT'],
        prefixes = ['-', '!'],
        openbraces = ['{', '[', '('],
        closebraces = ['}', ']', ')'];

    var matchingbraces = {
        '{': '}]',
        '[': '}]',
        '(': ')'
    };

    function escapeRegExp(string) {
      return string.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
    }

    var command = new RegExp("((" + commands.join(")|(") + "))\\b"),
        atom = new RegExp("((" + atoms.join(")|(") + "))\\b"),
        commandval = new RegExp("((" + commandvals.join(")|(") + "))\\b"),
        boolop = new RegExp("((" + boolops.join(")|(") + "))\\b"),
        field = /[a-zA-Z0-9\\._]+/,
        string = /'|"/,
        prefix = new RegExp("((" + prefixes.map(escapeRegExp).join(")|(") + "))"),
        openbrace = new RegExp("((" + openbraces.map(escapeRegExp).join(")|(") + "))"),
        closebrace = new RegExp("((" + closebraces.map(escapeRegExp).join(")|(") + "))");

    function expect_operation(state) {
      state.field = false;
      state.operation = true;
    }

    function expect_field(state) {
      state.prefix = true;
      state.boolop = true;
      state.field = true;
      state.value = false;
    }

    function expect_value(state) {
      state.operation = false;
      state.value = true;
    }

    function token_(state, t) {
        state.space = false;
        return t;
    }

    function token_value(stream, state, exit) {
      if(exit === undefined) {
        exit = [' '];
      }
      var escaped = false, next, end = false;
      while ((next = stream.peek())) {
        var slen = state.stack.length;
        if (!escaped && (
            exit.indexOf(next) >= 0 ||
            (state.inbrace !== null && matchingbraces[state.inbrace].indexOf(next) >= 0) ||
            next == ')'
        )) {end = true; break;}
        stream.next();
        escaped = !escaped && next === "\\";
      }
      return token_(state, "variable-3");
    }

    function token_string(stream, state) {
      var escaped = false, next, end = false;
      while ((next = stream.next())) {
        if (next === state.intoken && !escaped) {end = true; break;}
        escaped = !escaped && next === "\\";
      }
      if (end || !escaped)
        state.intoken = null;
      return token_(state, "string");
    }

    function token_error(stream, state) {
      while(stream.skipToEnd());
      return token_(state, "error");
    }

    function token(stream, state) {
      var ch = stream.peek();

      // Errors are terminal!
      if(state.error) {
        stream.skipToEnd();
        return token_(state, "error");
      }

      // String continuation
      if(state.intoken !== null) {
        return token_string(stream, state);
      }

      // Comments
      if (ch === '#') {
        stream.skipToEnd();
        return "comment";
      }

      // Whitespace
      if (stream.eatSpace()) {
        state.space = true;
        return null;
      }
      if (stream.sol()) {
        state.space = true;
      }

      // Pipe
      if (ch === '|') {
        stream.next();
        state.command = true;
        return token_(state, "meta");
      }

      // Closing brace
      var slen = state.stack.length;
      if (slen > 0 && state.boolop && ch == state.stack[slen - 1]) {
        stream.next();
        state.stack.pop();
        state.space = true;
        return 'bracket';
      }

      // Parens
      if (state.space && ch == '(') {
        stream.next();
        state.stack.push(')');
        state.space = true;
        return 'bracket';
      }

      // Field/Command operation
      if (state.operation && ch === ':') {
        expect_value(state);
        stream.next();
        if (state.command && stream.peek() === '^') {
          stream.next();
        }
        return token_(state, "punctuation");
      }

      // Command value
      if (state.command && state.operation) {
        if(stream.match(commandval)) {
          state.command = false;
          state.operation = false;
          return token_(state, "keyword");
        }
        return token_error(stream, state);
      }

      // Command
      if (state.space && state.command && stream.match(command)) {
        if(stream.peek() === ':') {
          stream.next();
          state.operation = true;
        } else {
          state.command = false;
        }
        return token_(state, "keyword");
      }

      // Boolean operators
      if (state.space && state.boolop && state.field && stream.match(boolop)) {
        state.boolop = false;
        return token_(state, "atom");
      }

      // Field filter
      if (state.space && state.field) {
        if (stream.match(prefix)) {
          if (state.prefix) {
            state.prefix = false;
            return "operator";
          } else {
            return token_error(stream, state);
          }
        }
        if (stream.match(atom)) {
          expect_operation(state);
          return token_(state, "builtin");
        }
        if (stream.match(string)) {
          state.intoken = ch;
          return token_string(stream, state);
        }

        // Values
        var start = stream.pos;
        var tok = token_value(stream, state, [' ', ':']);
        // Check if we've got a value ending with a ':'.
        // If so, backtrack. This is a field.
        if (stream.peek() === ':' && stream.pos !== start) {
            stream.backUp(stream.pos - start);
        } else {
            return tok;
        }

        if (stream.match(field)) {
          expect_operation(state);
          return token_(state, "variable-2");
        }
      }

      // Field value
      if (state.value) {
        // Braces
        if(stream.match(closebrace) && state.inbrace !== null) {
          expect_field(state);
          state.inbrace = null;
          return token_(state, "bracket");
        }
        if(stream.match(openbrace) && state.inbrace === null) {
          state.inbrace = ch;
          return token_(state, "bracket");
        }

        // Whitespace
        if(stream.eatSpace()) {
          return null;
        }
        if(stream.sol()) {
          state.space = true;
        }

        var ret = null;
        if (state.inbrace === null) {
          if (ch === '/') {
            state.intoken = ch;
            stream.next();
            ret = token_string(stream, state);
          }
        }
        if (ret === null && stream.match(string)) {
          state.intoken = ch;
          stream.next();
          ret = token_string(stream, state);
        }
        if (ret === null && (stream.match(boolop) || stream.match('TO'))) {
          ret = 'atom';
        }
        if (ret === null) {
          ret = token_value(stream, state);
        }
        if(ret !== null) {
          if(state.inbrace === null) {
            expect_field(state);
          }
          return token_(state, ret);
        }
      }

      state.error = true;
      return token_(state, "error");
    }

    return {
      startState: function () {
        return {
          space: true,
          error: false,
          command: false,
          boolop: false,
          operation: false,
          prefix: true,
          field: true,
          value: true,
          intoken: null,
          inbrace: null,
          inregex: null,
          stack: [],
        };
      },
      token: token
    };
  });

  CodeMirror.defineMIME("text/x-elasticquery", "elasticquery");
});
