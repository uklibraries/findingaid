var requests = (function() {
    var $ = jQuery;
    var count = 0;
    var request_type = null;

    function update() {
        update_count();
        highlight_request_type();
        revalidate();
        reset_hidden_fields();
    }

    function update_count() {
        var count = model.get_count();
        if (count > 0) {
            if (count == 1) {
               $('.fa-request-summary-note').html('You are requesting 1 item.');
            }
            else {
               $('.fa-request-summary-note').html('You are requesting ' + count + ' items.');
            }
           $('.fa-request-options').removeClass('fa-request-hidden');
           $('#fa-request-submit').removeClass('fa-request-hidden');
        }
        else {
           $('.fa-request-summary-note').html('No items have been requested.');
           $('.fa-request-options').addClass('fa-request-hidden');
           $('#fa-request-submit').addClass('fa-request-hidden');
        }
    }

    function revalidate() {
        if (model.valid()) {
            $('#fa-request-submit').removeAttr('disabled');
        }
        else {
            $('#fa-request-submit').attr('disabled', 'disabled');
        }
    }

    function reset_hidden_fields() {
        $('.fa-request-hidden-field').remove();
        count = model.get_count();
        var pieces = [];
        var item;
        var blocks = {}
        var block;
        var block_pieces = [];
        var notes = [];
        for (var i = 0; i < count; ++i) {
            item = model.get(i); 
            notes.push('* ' + item["label"]);
            block = item["block"];
            if (!(block in blocks)) {
                blocks[block] = [];
                block_pieces.push(block);
            }
            pieces.push(input_view.render(item));
        }
        if ('save-for-later' === model.get_request_type() ||
            'request-reproductions' === model.get_request_type()) {
            pieces.push(generic_input_view.render([
                {
                    name: "UserReview",
                    value: "Yes"
                },
                {
                    name: "ItemVolume",
                    value: block_pieces.join(', ')
                },
                {
                    name: "Notes",
                    value: notes.join("\n")
                }
            ]));
        }
        $('.fa-request-fieldset').append(pieces.join(''));
    }

    function highlight_request_type() {
        if (request_type && (request_type === model.get_request_type())) {
            /* nothing to do */
        }
        else {
            request_type = model.get_request_type();
            $('button.fa-request-option').addClass('btn-default').removeClass('btn-primary');
            $('button[data-option="' + request_type + '"]').addClass('btn-primary').removeClass('btn-default');
            $('div.fa-request-option').addClass('fa-request-hidden');
            if (model.get_count() > 0) {
                $('[data-option="' + request_type + '"]').removeClass('fa-request-hidden');
            }
            if ("save-for-later" === request_type) {
                $('form.fa-request-fieldset').attr('action', 'https://requests-libraries.uky.edu/remoteauth/aeon.dll?Action=10&Form=20&Value=DefaultRequest');
            }
            else if ("request-reproductions" === request_type) {
                $('form.fa-request-fieldset').attr('action', 'https://requests-libraries.uky.edu/remoteauth/aeon.dll?Action=10&Form=2&Value=PhotoduplicationRequest');
            }
        }
    }

    function valid() {
        if ("save-for-later" === request_type) {
            return true;
        }
        else {
            return false;
        }
    }

    function toggle(id) {
        var jid;
        var target;
        var item;
        var pos;
        var count;
        var removable_element;
        var neighbor;
        if (model.has(id)) {
            item = model.remove(id);
            jid = '#' + id;
            $(jid).addClass('btn-warning').removeClass('btn-success').html($(jid).attr('data-inactive'));
            target = $(jid).attr('data-target') + '-remove';
            $('#' + target).remove();
        }
        else {
            jid = '#' + id;
            target = $(jid).attr('data-target');
            item = {
                id: id,
                label: $('#' + target).val()
            }
            pos = model.add(item);
            if (pos !== false) {
                count = model.get_count();
                $(jid).addClass('btn-success').removeClass('btn-warning').html($(jid).attr('data-active'));
                removable_element = requests_view.render({
                    id: target + '-remove',
                    label: item["label"],
                    removable: true,
                    target: target
                });
                if (count == 1) {
                    $('#fa-requests-list').append(removable_element);
                }
                else if (pos == 0) {
                    neighbor = model.get(pos + 1);
                    target = $('#' + neighbor["id"]).attr('data-target') + '-remove';
                    $('#' + target).before(removable_element);
                }
                else {
                    neighbor = model.get(pos - 1);
                    target = $('#' + neighbor["id"]).attr('data-target') + '-remove';
                    $('#' + target).after(removable_element);
                }
            }
        }
    }

    var model = (function () {
        /* Each item should specify the following metadata:
         * 
         *   + id: e.g., "fa-request-target-cid3644001-button"
         *   + label: e.g., "Box 1, folder 1: Letter to Captain Russell Sturgis, aboard the Achilles, Washington D.C., 1862 March 18"
         */
        var items = {};
        var keys = [];
        var count = 0;
        var request_types = [
            "schedule-retrieval",
            "save-for-later",
            "request-reproductions"
        ];
        var default_request_type = "save-for-later";
        var request_type;
 
        return {
            add: function (item) {
                if (keys.indexOf(item["id"]) === -1) {
                    keys[keys.length] = item["id"];
                    item["block"] = item["label"].split(', ')[0];
                    items[item["id"]] = item;
                    keys.sort(function (a, b) {
                        return items[a]["label"].localeCompare(items[b]["label"]);
                    });
                    ++count;
                    return keys.indexOf(item["id"]);
                }
                else {
                    return false;
                }
            },
            remove: function (id) {
                var item;
                if (keys.indexOf(id) !== -1) {
                    keys.splice(keys.indexOf(id), 1);
                    item = items[id];
                    delete items[id];
                    --count;
                    return item;
                }
                else {
                    return false;
                }
            },
            has: function (id) {
                return (keys.indexOf(id) !== -1);
            },
            get: function (pos) {
                return items[keys[pos]];
            },
            get_count: function () {
                return count;
            },
            get_request_type: function () {
                return request_type;
            },
            set_request_type: function (new_request_type) {
                if (request_type === new_request_type) {
                    return false;
                }
                for (var i = 0; i < request_types.length; ++i) {
                    var current = request_types[i];
                    if (new_request_type === current) {
                        request_type = new_request_type;
                        return false;
                    }
                }
                return false;
            },
            valid: function () {
                if ("save-for-later" === request_type) {
                    return true;
                }
                else if ("request-reproductions" === request_type) {
                    return true;
                }
                else {
                    return false;
                }
            },
            init: function () {
                request_type = default_request_type;
            }
        }
    })();

    var requests_view = (function () {
        return {
            render: function (item) {
                var pieces = [];
                pieces.push('<li id="' + item["id"] + '">');
                pieces.push('<p>');
                if (("removable" in item) && item["removable"]) {
                    pieces.push(' <a href="#" class="fa-request-delete" data-target="' + item["target"] + '"><span class="glyphicon glyphicon-remove" aria-hidden="true"></span></a>');
                }
                pieces.push(' ' + item["label"] + '</p>');
                pieces.push('</li>');
                return pieces.join('');
            }
        }
    })();

    var input_view = (function () {
        return {
            render: function (item) {
                var pieces = [];
                pieces.push('<input class="fa-request-hidden-field" type="hidden" id="' + item["id"] + '-field">');
                return pieces.join('');
            }
        }
    })();

    var generic_input_view = (function () {
        return {
            render: function (fields) {
                var pieces = [];
                for (var i = 0; i < fields.length; ++i) {
                   pieces.push('<input class="fa-request-hidden-field" type="hidden" name="' + fields[i]["name"] + '" value="' + fields[i]["value"] + '">');
                }
                return pieces.join('');
            }
        }
    })();

    return {
        init: function (options) {
            title = options["title"];

            model.init();
            update();

            $('.fa-request-option').click(function () {
                var option = $(this).attr('data-option');
                model.set_request_type(option);
                update();
            });

            $('.fa-request').click(function () {
                var id = $(this).attr('id');
                toggle(id);
                update();
                return false;
            });

            $('body').on('click', '.fa-request-delete', function () {
                var id = $(this).closest('a').attr('data-target') + '-button';
                toggle(id);
                update();
                return false;
            });

            $('.fa-request').removeClass('fa-request-hidden');
            $('.fa-request-summary-note').removeClass('fa-request-hidden');
        }
    };
})();
