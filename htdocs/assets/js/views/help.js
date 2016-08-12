"use strict";
define(function(require) {
    var Templates = require('templates'),
        ModalView = require('views/modal');


    var HelpModalView = ModalView.extend({
        subTemplate: Templates['help'],
        title: 'Keyboard Shortcuts',
        large: true,
        parseKeyStr: function(keyStr) {
            var tkeys = [];
            var keys = keyStr.split('+');
            for(var i = 0; i < keys.length; ++i) {
                var tkey = keys[i];
                switch(keys[i]) {
                    case 'command':
                        tkey = '⌘'; break;
                    case 'shift':
                        tkey = '⇧'; break;
                    case 'backspace':
                        tkey = '⌫'; break;
                    case 'return':
                    case 'enter':
                        tkey = '⏎'; break;
                    case 'tab':
                        tkey = '⇥'; break;
                    case 'alt':
                    case 'option':
                        tkey = '⌥'; break;
                    case 'ctrl':
                    case 'control':
                        tkey = '⌃'; break;
                }
                tkeys.push(tkey);
            }
            return tkeys;
        },
        _render: function() {
            var shortcuts = [];
            var names = ['Global', 'Page'];
            for(var i = 0; i < this.App.keysHelp.length; ++i) {
                var section_shortcuts = [];
                var keysHelp = this.App.keysHelp[i];
                for(var k in keysHelp) {
                    var help = keysHelp[k];
                    section_shortcuts.push({keys: this.parseKeyStr(k), help: help});
                }
                shortcuts.push({shortcuts: section_shortcuts, name: names[i]});
            }
            this.vars = {shortcuts: shortcuts};

            ModalView.prototype._render.call(this);
        }
    });

    return HelpModalView;
});
