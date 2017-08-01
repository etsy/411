"use strict";
define(function(require) {
    var $ = require('jquery'),
        _ = require('underscore'),
        URI = require('uri'),
        Autosize = require('autosize'),
        CodeMirror = require('codemirror'),
        Moment = require('moment'),
        Data = require('data');


    // Turn a timestamp into a datestring.
    var formatDate = function(ts) {
        if(ts === 0) {
            return 'N/A';
        }

        var date = Moment.unix(ts);
        return date.format("ddd, DD MMM YYYY HH:mm:ssZ");
    };

    // Turn a number into a timestring.
    function formatTime(mins) {
        var weeks = 0;
        var days = 0;
        var hours = 0;
        mins = parseInt(mins, 10);
        if(isNaN(mins)) {
            mins = 0;
        }
        if(mins >= 60) {
            hours = (mins / 60) | 0;
            mins = (mins % 60);
        }
        if(hours >= 24) {
            days = (hours / 24) | 0;
            hours = (hours % 24);
        }
        if(days >= 7) {
            weeks = (days / 7) | 0;
            days = (days % 7);
        }
        var str = '';
        if(weeks > 0) {
            str += weeks + ' week' + (weeks == 1 ? '':'s') + ' ';
        }
        if(days > 0) {
            str += days + ' day' + (days == 1 ? '':'s') + ' ';
        }
        if(hours > 0) {
            str += hours + ' hour' + (hours == 1 ? '':'s') + ' ';
        }
        if((hours === 0 && days === 0 && weeks === 0) || mins > 0) {
            str += mins + ' minute' + (mins == 1 ? '':'s');
        }
        return str;
    }

    // Turn a datestring into a timestamp.
    var getTimestamp = function(str) {
        var date = _.isUndefined(str) || str.length === 0 ? new Date():new Date(str);
        return parseInt(date.getTime() / 1000, 10);
    };

    /**
     * Parse out assignee information from a string.
     * @param {string} str - Input string.
     * @return {Array} - The parsed information.
     */
    var parseAssignee = function(str) {
        var assignee = [0, 0];
        if(_.isString(str)) {
            var raw = str.split(',');
            if(raw.length == 2) {
                assignee = [parseInt(raw[0], 10), parseInt(raw[1], 10)];
            }
        }

        return assignee;
    };

    // Generate an assignee string.
    var generateAssignee = function(type, data) {
        return type + ',' + data;
    };

    var getAssigneeName = function(assignee_type, assignee, users, groups, def_ret, prefix) {
        var target = null;
        switch(assignee_type) {
            case 0: target = users; break;
            case 1: target = groups; break;
        }

        var def = assignee !== 0 ? 'Unknown':'Unassigned';
        var name = _.isUndefined(def_ret) ? def:def_ret;
        var obj = target.get(assignee);
        if(obj) {
            switch(assignee_type) {
                case 0: name = (prefix ? 'User: ':'') + obj.get('real_name'); break;
                case 1: name = (prefix ? 'Group: ':'') + obj.get('name'); break;
            }
        }
        return name;
    };

    var getUserName = function(user_id, users, def_ret) {
        def_ret = def_ret || 'Unknown';
        return getAssigneeName(0, user_id, users, null, def_ret);
    };

    // Return the current query as an object.
    var parseQuery = function(url) {
        var link = new URI(window.location.href);
        var query = link.query(true);
        for(var k in query) {
            if(k.substr(-2) != '[]') {
                continue;
            }
            var key = k.substr(0, k.length - 2);
            query[key] = _.isArray(query[k]) ? query[k]:[query[k]];
            delete query[k];
        }
        return query;
    };

    // Fire off a synchronous request.
    var request = function(options) {
        options.url = options.url || '';
        options.method = options.method || 'get';

        var form = $('<form />', {
            action: options.url,
            target: '_blank',
            method: options.method,
            style: 'display: none;'
        });
        $.each(options.data, function (name, value) {
            $('<input />', {
                type: 'hidden',
                name: name,
                value: value
            }).appendTo(form);
        });
        form.appendTo('body').submit().remove();
    };

    // Slugify a string.
    var slug = function(x) {
        return x.toLowerCase().replace(' ', '_');
    };

    // Check if a rect is visible.
    var visible = function(rect, bounds) {
        return (
            rect.top >= bounds.top &&
            rect.left >= bounds.left &&
            rect.bottom <= $(window).height() - bounds.bottom &&
            rect.right <= $(window).width() - bounds.right
        );
    };

    // Initialize CodeMirror
    var initCodeMirror = function(elem, config) {
        var cm = CodeMirror.fromTextArea(elem[0], _.extend(config, {
            viewportMargin: Infinity
        }));
        cm.setSize(-1, 150);

        elem.data('codemirror', cm);
        $(cm.display.wrapper).addClass('form-control');
        cm.on('blur', function() {
            elem.text(cm.getValue());
        });
        // FIXME: Possibly add an 'attach' method on views?
        // Could also use the autorefresh addon.
        setTimeout(function() {
            cm.refresh();
        }, 250);

        return cm;
    };

    // Initialize a tags input field.
    var initTags = function(elems) {
        elems.select2({
            createSearchChoice: function(term) {
                return {id: term, text: term};
            },
            tags: [],
            tokenSeparators: [',', ' ']
        });
    };

    // Base select function.
    var initSelect = function(elems, opts, search) {
        var config = {
            allowClear: true,
        };
        _.extend(config, opts);

        if(!('placeholder' in config)) {
            config.placeholder = !search ? 'Unassigned':' ';
        }
        elems.select2(config);
    };

    // Initialize a search select input field.
    var initSearchSelect = function(elems, searches, search, options) {
        options = options || {};

        var search_search = function(q) {
            var term = q.term.toLowerCase();
            var results = _.map(
                searches.filter(function(model) {
                    return model.get('name').toString().toLowerCase().indexOf(term) !== -1;
                }),
                function(model) {
                    return {id: model.id, text: model.get('name') + ''};
                }
            );

            q.callback({results: results});
        };

        var search_init = function(elem, callback) {
            var ret = searches.get(elem.val());
            if(ret) {
                ret = {id: ret.id, text: ret.get('name') + ''};
            }

            callback(ret);
        };
        var new_options = {
            initSelection: search_init,
            query: search_search
        };
        _.extend(new_options, options);

        initSelect(elems, new_options, search);
    };

    // Initialize a renderer select input field.
    var initRendererSelect = function(elems, renderers, options) {
        options = options || [];

        var data = [];
        for(var k in renderers) {
            data.push({id: k, text: k});
        }

        var new_options = {
            data: data
        };
        _.extend(new_options, options);

        initSelect(elems, new_options, true);
    };

    // Initialize a datetime select input field.
    var initTimeSelect = function(elems, options) {
        options = options || {};

        var fmt = options.format || formatTime;
        var intvs = [1, 5, 10, 15, 30, 60, 90, 120, 180, 360, 720, 1440, 2880, 4320, 10080, 20160];
        if(options.allow_zero) {
            intvs.unshift(0);
        }
        var default_results = [];
        for(var i = 0; i < intvs.length; ++i) {
            default_results.push({id: intvs[i], text: fmt(intvs[i])});
        }
        var time_search = function(q) {
            var results = _.clone(default_results);
            if(options.allowClear || q.term > 0) {
                results.unshift({id: q.term, text: fmt(q.term)});
            }
            q.callback({results: results});
        };

        var time_init = function(elem, callback) {
            var ret = {id: elem.val(), text: fmt(elem.val())};

            callback(ret);
        };
        var new_options = {
            initSelection: time_init,
            query: time_search,
            placeholder: 'Unspecified'
        };
        _.extend(new_options, options);

        initSelect(elems, new_options);
    };

    // Initialize an user select input field.
    var initUserSelect = function(elems, users, search, options) {
        options = options || {};

        var user_search = function(q) {
            var term = q.term.toLowerCase();
            var results = [];
            if(search) {
                results.push({id: 0, text: 'Unassigned'});
            }
            results = results.concat(_.map(
                users.filter(function(model) {
                    return model.get('real_name').toString().toLowerCase().indexOf(term) !== -1;
                }),
                function(model) {
                    return {id: model.id, text: model.get('real_name') + ''};
                }
            ));

            q.callback({results: results});
        };

        var user_init = function(elem, callback) {
            var obj = users.get(elem.val());
            var ret = {id: 0, text: 'Unknown'};
            if(obj) {
                ret = {id: obj.id, text: obj.get('real_name') + ''};
            } else if(search) {
                ret = {id: 0, text: 'Unassigned'};
            }

            callback(ret);
        };
        var new_options = {
            initSelection: user_init,
            query: user_search
        };
        _.extend(new_options, options);

        initSelect(elems, new_options, search);
    };

    // Initialize an assignee select input field
    var initAssigneeSelect = function(elems, users, groups, search, options) {
        options = options || {};

        var assignee_search = function(q) {
            var term = q.term.toLowerCase();
            var results = [];
            if(search) {
                results.push({id: '0,0', text: 'Unassigned'});
            }
            users.each(function(model) {
                if(model.get('real_name').toString().toLowerCase().indexOf(term) !== -1) {
                    results.push({id: '0,' + model.id, text: 'User: ' + model.get('real_name')});
                }
            });
            groups.each(function(model) {
                if(model.get('name').toString().toLowerCase().indexOf(term) !== -1) {
                    results.push({id: '1,' + model.id, text: 'Group: ' + model.get('name')});
                }
            });

            q.callback({results: results});
        };

        var assignee_init = function(elem, callback) {
            var assignee = parseAssignee(elem.val());
            var ret = {id: '0,0', text: 'Unknown'};
            switch(assignee[0]) {
                case 0:
                    if(search && assignee[1] === 0) {
                        ret = {id: '0,0', text: 'Unassigned'};
                    } else {
                        var obj = users.get(assignee[1]);
                        if(obj) {
                            ret = {id: '0,' + obj.id, text: 'User: ' + obj.get('real_name')};
                        } else {}
                    }
                    break;
                case 1:
                    var obj = groups.get(assignee[1]);
                    if(obj) {
                        ret = {id: '1,' + obj.id, text: 'Group: ' + obj.get('name')};
                    }
                    break;
            }

            callback(ret);
        };
        var new_options = {
            initSelection: assignee_init,
            query: assignee_search
        };
        _.extend(new_options, options);

        initSelect(elems, new_options, search);
    };

    // Initialize a filter select.
    var initFilterSelect = function(elems, filters, options) {
        options = options || {};

        var filter_search = function(q) {
            var term = q.term.toLowerCase();
            var results = [];
            for(var k in filters) {
                if(filters[k].toLowerCase().indexOf(term) !== -1) {
                    results.push({id: k, text: filters[k]});
                }
            }

            q.callback({results: results});
        };
        var new_options = {
            query: filter_search
        };
        _.extend(new_options, options);

        initSelect(elems, new_options, true);
    };

    // Initialize a target select.
    var initTargetSelect = function(elems, targets, options) {
        options = options || {};

        var target_search = function(q) {
            var term = q.term.toLowerCase();
            var results = [];
            for(var k in targets) {
                if(targets[k].toLowerCase().indexOf(term) !== -1) {
                    results.push({id: k, text: targets[k]});
                }
            }

            q.callback({results: results});
        };

        var new_options = {
            query: target_search
        };
        _.extend(new_options, options);

        initSelect(elems, new_options, true);
    };

    // Init a select-all.
    var initSelectAll = function(sel) {
        sel.click(function() {
            this.setSelectionRange(0, this.value.length);
        });
    };

    /**
     * Serialize a form into an object.
     * @param f {Selector} A jQuery selector.
     * @param ignore_blank {boolean} Whether to ignore empty fields.
     * @param ignore_checks {boolean} Whether to ignore check inputs.
     */
    var serializeForm = function(f, ignore_blank, ignore_checks) {
        var data = {};
        var inps = f.find(':input').filter(function() {
            var hf = $(this).closest('.hidden-form');
            if(hf.length === 0) return true;
            return hf.has(f).length;
        });
        var arr = inps.serializeArray();

        if(!ignore_checks) {
            inps.filter('input[type=checkbox][value=on]').each(function(i, e) {
                arr.push({'name': e.name, 'value': e.checked});
            });
        }

        inps.filter('input[type=number]').each(function(i, e) {
            arr.push({'name': e.name, 'value': e.value | 0});
        });

        for(var i = 0; i < arr.length; ++i) {
            if(ignore_blank && _.isString(arr[i].value) && !arr[i].value) {
                continue;
            }
            var key = arr[i].name;
            var val = arr[i].value;
            var array = false;
            if(key.substr(-2) == '[]') {
                array = true;
                key = key.substr(0, key.length - 2);
            }
            if(array) {
                if(!(key in data)) {
                    data[key] = [];
                }
                if(val.length) {
                    data[key].push(val);
                }
            } else {
                data[key] = val;
            }
        }

        return data;
    };

    /**
     * Return a message level string.
     * @param {int} level - The message level.
     * @return {string} The level.
     */
    function getLevel(i) {
        var levels = ['danger', 'warning', 'success', 'info'];
        if(_.isUndefined(i) || i >= levels.length) {
            i = 0;
        }
        return levels[i];
    }

    /**
     * Format data for rendering.
     */
    var formatFields = function(inp, data) {
        var ret = {};
        for(var k in inp) {
            ret[k] = {data: data[k], value:inp[k]};
        }
        return ret;
    };

    var autosize = function(sel) {
        Autosize(sel);
        sel.data('autosize', true);
    };

    var download = function(data, filename) {
        var link = $('<a />', {
            download: filename,
            href: 'data:text/plain;base64,' + btoa(data),
        });

        link.appendTo('body');
        link[0].click();
        link.remove();
    };

    function flatten_recurse(data, key, ret) {
        for(var k in data) {
            var next_key = key ? key + '.' + k:k;
            if(_.isArray(data[k]) || _.isObject(data[k])) {
                flatten_recurse(data[k], next_key, ret);
            } else {
                ret[next_key] = data[k];
            }
        }
    }
    var flatten = function(data) {
        var ret = {};
        flatten_recurse(data, null, ret);
        return ret;
    };

    return {
        formatDate: formatDate,
        formatTime: formatTime,
        getTimestamp: getTimestamp,
        generateAssignee: generateAssignee,
        parseAssignee: parseAssignee,
        getAssigneeName: getAssigneeName,
        getUserName: getUserName,
        parseQuery: parseQuery,
        request: request,
        slug: slug,
        visible: visible,
        initCodeMirror: initCodeMirror,
        initTags: initTags,
        initSelect: initSelect,
        initTimeSelect: initTimeSelect,
        initRendererSelect: initRendererSelect,
        initUserSelect: initUserSelect,
        initSearchSelect: initSearchSelect,
        initAssigneeSelect: initAssigneeSelect,
        initFilterSelect: initFilterSelect,
        initTargetSelect: initTargetSelect,
        initSelectAll: initSelectAll,
        serializeForm: serializeForm,
        getLevel: getLevel,
        formatFields: formatFields,
        autosize: autosize,
        download: download,
        flatten: flatten,
    };
});
