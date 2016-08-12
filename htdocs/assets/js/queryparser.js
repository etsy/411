"use strict";
define(function(require) {
    var _ = require('underscore');


    var ParseException = _.extend(Error);

    /**
     * A query parser
     * Breaks down kv pairs into an object and maps values to their integer
     * representation, as determined by the keys attributes.
     *
     * As an example, consider the query string "a:b c:[d] e:[f g]" and keys:
     * {
     *   a: null,
     *   b: {d: 0},
     *   e: {f: null, g: null}
     * }
     *
     * The object that would be returned is:
     * {
     *   a:["b"],
     *   c:[0],
     *   e"["f", "g"]
     * }
     */
    var QueryParser = function(keys) {
        this.keys = keys;
    };

    // Tokenize a query string for further parsing.
    QueryParser.prototype.tokenize = function(str) {
        if(!str) {
            return [];
        }
        if(str.length > 1024) {
            throw new ParseException('Input too long');
        }

        var tokens = [];
        var curr = [];
        for(var i = 0; i < str.length; ++i) {
            var chr = str[i];
            var keep = false;
            switch(chr) {
                case '[':
                case ']':
                case ':':
                    keep = true;
                    /* falls through */
                case ' ':
                case "\t":
                case ',':
                    if(curr.length) {
                        tokens.push(curr.join(''));
                        curr = [];
                    }
                    if(keep) {
                        tokens.push(chr);
                    }
                    break;
                default:
                    curr.push(chr);
                    break;
            }
        }
        if(curr.length) {
            tokens.push(curr.join(''));
        }

        return tokens;
    };

    QueryParser.prototype._parse = function(tokens) {
        var ret = {};
        var stk = 0;
        var node = ret;
        var node = {};
        var ktok = null;
        var vtok = [];
        var val = false;
        for (var k in tokens) {
            var tok = tokens[k];
            switch(tok) {
                case '[':
                    if(stk > 0) {
                        throw new ParseException('Unexpected: "]"');
                    }
                    ++stk;
                    break;
                case ']':
                    if(stk === 0) {
                        throw new ParseException('Unexpected: "]"');
                    }
                    if(val) {
                        if(vtok.length === 0) {
                            throw new ParseException('Empty group: "' + ktok + '"');
                        }
                        ret[ktok] = vtok;
                        ktok = null;
                        vtok = [];
                        val = false;
                    }
                    --stk;
                    break;
                case ':':
                    if(!ktok || val) {
                        throw new ParseException('Unexpected: ":"');
                    }
                    val = true;
                    break;
                default:
                    if(val) {
                        vtok.push(tok);
                        if(stk === 0) {
                            ret[ktok] = vtok;
                            ktok = null;
                            vtok = [];
                            val = false;
                        }
                    } else {
                        if(ktok) {
                            ret[ktok] = vtok;
                            if(vtok.length === 0) {
                                throw new ParseException('Empty group: "' + ktok + '"');
                            }
                        }
                        ktok = tok;
                        if(ktok in ret) {
                            throw new ParseException('Key exists: "' + ktok + '"');
                        }
                    }
                    break;
            }
        }


        if(ktok) {
            if(vtok.length === 0) {
                throw new ParseException('Empty group: "' + ktok + '"');
            }
            ret[ktok] = vtok;
        }

        if(stk > 0) {
            throw new ParseException('Mismatched group');
        }

        var final_ret = {};
        for (var k in ret) {
            var v = ret[k];
            if(!(k in this.keys)) {
                throw new ParseException('Unknown key: "' + k + '"');
            }
            var vals = this.keys[k];
            if(!vals) {
                final_ret[k] = v;
            } else {
                for (var kk in v) {
                    var vv = v[kk];
                    if(vv in vals) {
                        if(!(k in final_ret)) {
                            final_ret[k] = [];
                        }
                        final_ret[k].push(
                            vals[vv] === null ? vv:parseInt(vals[vv], 10)
                        );
                    } else {
                        throw new ParseException('Unknown value: "' + vv + '"');
                    }
                }
            }
        }

        return final_ret;
    };

    QueryParser.prototype.parse = function(str) {
        return this._parse(this.tokenize(str));
    };

    return QueryParser;
});
