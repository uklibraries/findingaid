var requests = (function() {
    var $ = jQuery;
    var count = 0;
    var request_type = null;
    var datepicker_initialized = false;
    var collection_title;

    function sortkey(label) {
        return label.replace(/\d+/g, function (number) {
            return "00000".substr(number.length - 1) + number;
        });
    }

    function update() {
        update_count();
        highlight_request_type();
        reset_hidden_fields();
        revalidate();
        if (0 === model.get_count()) {
            model.disable_all();
        }
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
           $('.fa-extra-options').removeClass('fa-request-hidden');
           $('#fa-request-submit').removeClass('fa-request-hidden');
        }
        else {
           $('.fa-request-summary-note').html('No items have been requested.');
           $('.fa-request-options').addClass('fa-request-hidden');
           $('.fa-extra-options').addClass('fa-request-hidden');
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
        var item;
        var count = model.get_count();
        $('.fa-request-hidden-field').remove();
        $('.fa-folder-input').remove();
        model.enable_current();
        var folder_inputs = [];
        for (var i = 0; i < model.get_count(); ++i) {
            var folder = model.get(i);
            folder_inputs.push(folder_input_view.render(folder));
        }
        $('.fa-request-fieldset').append(folder_inputs.join(''));
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

            /* Remove hidden inputs */
            reqid = $(jid).attr('data-target') + '-reqid';
            $('input[data-root="' + reqid + '"]').remove();
            $('#' + reqid).remove();

            /* Remove removal control */
            $('#' + target).remove();
        }
        else {
            jid = '#' + id;
            target = $(jid).attr('data-target');
            volume = $('#' + target).attr('data-volume');
            container = $('#' + target).attr('data-container');
            label = $('#' + target).val();
            title_pieces = [];
            title_pieces.push(volume);
            title_pieces.push(container);
            title = title_pieces.join(', ');

            item = {
                id: id,
                label: label,
                title: title,
                volume: volume,
                container: container
            };
            pos = model.add(item);
            if (pos !== false) {
                count = model.get_count();
                $(jid).addClass('btn-success').removeClass('btn-warning').html($(jid).attr('data-active'));

                /* Display selected item and add toggle control */
                removable_element = requests_view.render({
                    id: target + '-remove',
                    label: item["label"],
                    volume: item["volume"],
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
         *   + id:        "fa-request-target-cid3644001-button"
         *   + label:     "Box 1, folder 1: Letter to Captain Russell Sturgis, aboard the Achilles, Washington D.C., 1862 March 18"
         *   + title:     "Letter to Captain Russel Sturgis, aboard the Achilles, Washington D.C., 1862 March 18"
         *   + volume:    "Box 1"
         *   + container: "folder 1"
         */
        var items = {};
        var keys = [];
        var count = 0;
        var volumes = {};
        var volume_keys = [];
        var volume_count = 0;
        var request_types = [
            "schedule-retrieval",
            "save-for-later",
            "request-reproductions"
        ];
        var default_request_type = "schedule-retrieval";
        var request_type;
        var pages_items_to_be_reproduced = '';
        var formats = [
            "A/V (may incur costs)",
            "JPG",
            "PDF",
            "TIFF"
        ];
        var format = "";
        var service_levels = [
            "Commercial/For-profit",
            "Educational/Non-profit",
            "For Publication",
            "Other",
            "Press/Journalism/Public Relations",
            "Social Media"
        ];
        var service_level = '';

        var ids = [
            'fa-datepicker',
            'fa-save-for-later',
            ''
        ];

        var subforms = [
            /* Manage the datepicker.
            *
            * This maintains a few hidden fields.  It initializes
            * the datepicker control, which must already appear in
            * the DOM.
            */
            (function () {
                var id;
                var jid;
                var hidden;
                var enabled = false;
                var initialized = false;
                var hidden_fields = [
                    '-visit',
                    '-user-review'
                ];
                var hidden_names = {
                    "-visit": "Visit",
                    "-user-review": "UserReview"
                };

                function disable_form() {
                    for (var i = 0; i < hidden_fields.length; ++i) {
                        $('#' + hidden + hidden_fields[i]).attr(
                            'name', ''
                        );
                    }
                    $(jid).attr('name', '');
                    $('#fa-schedule-retrieval-options').hide();
                    $(jid).hide();
                    enabled = false;
                }

                return {
                    init: function (id) {
                        $.getJSON("date.php", function (data) {
                            jid = '#' + id;
                            hidden = id + '-hidden';

                            /* Insert the fields which this subform must manage. */
                            var subform_template = '<div id="__HIDDEN__"><input id="__HIDDEN__-visit" type="hidden" name value="on"><input id="__HIDDEN__-user-review" type="hidden" name value="No"></div>';
                            $('.fa-request-fieldset').append(
                                subform_template.replace(
                                    new RegExp('__HIDDEN__', 'g'),
                                    hidden
                                )
                            );

                            /* Add the datepicker. */
                            $(jid).val(data.earliest);
                            $(jid).datepicker({
                                showOn: "button",
                                minDate: new Date(data.earliest),
                                beforeShowDay: $.datepicker.noWeekends,
                                dateFormat: "mm/dd/yy"
                            });
                            disable_form();

                            /* And we're done. */
                            initialized = true;
                        });
                    },
                    enable: function () {
                        for (var i = 0; i < hidden_fields.length; ++i) {
                            $('#' + hidden + hidden_fields[i]).attr(
                                'name', hidden_names[hidden_fields[i]]
                            );
                        }
                        $(jid).attr('name', 'ScheduledDate');
                        $('#fa-schedule-retrieval-options').show();
                        $(jid).show();
                        $('input[name="RequestType"]').val('Loan');
                        enabled = true;
                    },
                    disable: function () {
                        disable_form();
                    },
                    valid: function () {
                        if (!initialized) {
                            return false;
                        }
                        if ($(jid).val() === "01/01/1901") {
                            return false;
                        }
                        else {
                            return true;
                        }
                    }
                }
            })(),
            /* Manage the hidden fields for user review.
            */
            (function () {
                var id;
                var jid;
                var hidden;
                var enabled = false;
                var initialized = false;
                var hidden_fields = [
                    '-visit',
                    '-user-review'
                ];
                var hidden_names = {
                    "-visit": "Visit",
                    "-user-review": "UserReview"
                };

                function disable_form() {
                    for (var i = 0; i < hidden_fields.length; ++i) {
                        $('#' + hidden + hidden_fields[i]).attr(
                            'name', ''
                        );
                    }
                    $('#fa-save-for-later-options').hide();
                    enabled = false;
                }

                return {
                    init: function (id) {
                        jid = '#' + id;
                        hidden = id + '-hidden';

                        /* Insert the fields which this subform must manage. */
                        var subform_template = '<div id="__HIDDEN__"><input id="__HIDDEN__-visit" type="hidden" name value="on"><input id="__HIDDEN__-user-review" type="hidden" name value="Yes"></div>';
                        $('.fa-request-fieldset').append(
                            subform_template.replace(
                                new RegExp('__HIDDEN__', 'g'),
                                hidden
                            )
                        );
                        disable_form();

                        initialized = true;
                    },
                    enable: function () {
                        for (var i = 0; i < hidden_fields.length; ++i) {
                            $('#' + hidden + hidden_fields[i]).attr(
                                'name', hidden_names[hidden_fields[i]]
                            );
                        }
                        $('#fa-save-for-later-options').show();
                        $('input[name="RequestType"]').val('Loan');
                        enabled = true;
                    },
                    disable: function () {
                        disable_form();
                    },
                    valid: function () {
                        return true;
                    }
                }
            })(),
            /* Manage the inputs for reproduction requests.
            */
            (function () {
                var id;
                var jid;
                var hidden;
                var enabled = false;
                var initialized = false;
                var fields = [
                    'fa-pages',
                    'fa-format',
                    'fa-service-level',
                    'fa-project',
                    'fa-skip-estimate'
                ];
                var names = {
                    "fa-pages": "ItemInfo3",
                    "fa-format": "Format",
                    "fa-service-level": "ServiceLevel",
                    "fa-project": "ItemInfo4",
                    "fa-skip-estimate": "SkipOrderEstimate"
                };

                function disable_form() {
                    for (var i = 0; i < fields.length; ++i) {
                        $('#' + fields[i]).attr(
                            'name', ''
                        );
                    }
                    $('#fa-request-reproductions-options').hide();
                    enabled = false;
                }

                return {
                    init: function (id) {
                        disable_form();
                        initialized = true;
                    },
                    enable: function () {
                        for (var i = 0; i < fields.length; ++i) {
                            $('#' + fields[i]).attr(
                                'name', names[fields[i]]
                            );
                        }
                        $('#fa-request-reproductions-options').show();
                        $('input[name="RequestType"]').val('Copy');
                        enabled = true;
                    },
                    disable: function () {
                        disable_form();
                    },
                    valid: function () {
                        if ($('#fa-pages').attr('name') !== "ItemInfo3" ||
                            $('#fa-pages').val().length === 0) {
                            return false;
                        }
                        if ($('#fa-format').attr('name') !== "Format" ||
                            $('#fa-format').find(':selected').val() !== format) {
                            return false;
                        }
                        if ($('#fa-service-level').attr('name') !== "ServiceLevel" ||
                            $('#fa-service-level').find(':selected').val() !== service_level) {
                            return false;
                        }
                        if ($('#fa-project').attr('name') !== "ItemInfo4" ||
                            $('#fa-project').val().length === 0) {
                            return false;
                        }
                        return true;
                    }
                }
            })()
        ];

        function add_volume(volume) {
            if (volume_keys.indexOf(volume["id"]) === -1) {
                volume_keys[keys.length] = volume["id"];
                volumes[volume["id"]] = volume;
                volume_keys.sort(function (a, b) {
                    return sortkey(volumes[a]["label"]).localeCompare(sortkey(volumes[b]["label"]));
                });
                ++volume_count;
                return volume_keys.indexOf(volume["id"]);
            }
        }

        function remove_volume(id) {
            var volume;
            if (volume_keys.indexOf(id) !== -1) {
                volume_keys.splice(volume_keys.indexOf(id), 1);
                volume = volumes[id];
                delete volumes[id];
                --volume_count;
                return volume;
            }
            else {
                return false;
            }
        }

        function enable_current() {
            var rt = request_types.indexOf(request_type);
            for (var i = 0; i < request_types.length; ++i) {
                if (rt === i) {
                    subforms[i].enable();
                }
                else {
                    subforms[i].disable();
                }
            }
        }

        function disable_all() {
            for (var i = 0; i < request_types.length; ++i) {
                subforms[i].disable();
            }
        }

        return {
            add: function (item) {
                if (keys.indexOf(item["id"]) === -1) {
                    keys[keys.length] = item["id"];
                    items[item["id"]] = item;
                    keys.sort(function (a, b) {
                        return sortkey(items[a]["label"]).localeCompare(sortkey(items[b]["label"]));
                    });
                    ++count;
                    return keys.indexOf(item["id"]);
                }
                else {
                    return false;
                }
            },
            remove: function (id) {
                var item_to_delete;
                var item;
                if (keys.indexOf(id) !== -1) {
                    keys.splice(keys.indexOf(id), 1);
                    item_to_delete = items[id];
                    delete items[id];
                    --count;
                    return item_to_delete;
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
            get_volume: function (pos) {
                return volumes[volume_keys[pos]];
            },
            get_count: function () {
                return count;
            },
            get_volume_count: function () {
                return volume_count;
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
            set_format: function (new_format) {
                if (format === new_format) {
                    return false
                }
                for (var i = 0; i < formats.length; ++i) {
                    var current = formats[i];
                    if (new_format === current) {
                         format = new_format;
                         return false;
                    }
                }
                return false;
            },
            set_service_level: function (new_service_level) {
                if (service_level === new_service_level) {
                    return false
                }
                for (var i = 0; i < service_levels.length; ++i) {
                    var current = service_levels[i];
                    if (new_service_level === current) {
                         service_level = new_service_level;
                         return false;
                    }
                }
                return false;
            },
            valid: function () {
                /* XXX: additional requirements for validity? */
                var rt = request_types.indexOf(request_type);
                if (0 <= rt && rt < request_types.length) {
                    return subforms[rt].valid();
                }
                else {
                    return false;
                }
            },
            init: function () {
                for (var i = 0; i < request_types.length; ++i) {
                    subforms[i].init(ids[i]);
                }
                request_type = default_request_type;
                enable_current();
            },
            enable_current: enable_current,
            disable_all: disable_all
        }
    })();

    var requests_view = (function () {
        return {
            render: function (item) {
                var pieces = [];
                pieces.push('<li id="' + item["id"] + '">');
                pieces.push('<p class="fa-summary-item">');
                if (("removable" in item) && item["removable"]) {
                    pieces.push(' <a href="#" class="fa-request-delete" data-target="' + item["target"] + '"><span class="glyphicon glyphicon-remove" aria-hidden="true"></span></a> ');
                }
                pieces.push(item["label"]);
                pieces.push('</p></li>');
                return pieces.join('');
            }
        }
    })();

    var folder_input_view = (function () {
        var pieces = [];

        function add_input(options) {
            if ("root" in options) {
                pieces.push('<input type="hidden" class="fa-folder-input" data-root="');
                pieces.push(options["root"]);
                pieces.push('" name="');
                pieces.push(options["name"]);
                pieces.push('" value="');
                pieces.push(options["value"]);
                pieces.push('">');
            }
            else {
                pieces.push('<input type="hidden" class="fa-folder-input" id="');
                pieces.push(options["id"]);
                pieces.push('" name="');
                pieces.push(options["name"]);
                pieces.push('" value="');
                pieces.push(options["value"]);
                pieces.push('">');
            }
        }

        return {
            render: function (folder) {
                var id = folder["id"].replace(/\s+/g, '_').replace(/-/g, '_');
                pieces = [];
                /* 2 == ', '.length */
                var prefix_length = folder["volume"].length + 2;
                var label = folder["label"].substr(prefix_length);
                if (folder["volume"].length == 0) {
                  label = '';
                }
                /* */
                add_input({
                  id: id,
                  name: "Request",
                  value: id
                });
                add_input({
                  name: "ItemTitle_" + id,
                  value: collection_title + ': ' + folder["title"],
                  root: id
                });
                add_input({
                  name: "ItemVolume_" + id,
                  value: folder["volume"],
                  root: id
                });
                add_input({
                  name: "CallNumber_" + id,
                  value: $('input[name="CallNumber"]').first().val(),
                  root: id
                });
                add_input({
                  name: "ItemIssue_" + id,
                  value: label,
                  root: id
                });
                return pieces.join('');
            }
        }
    })();

    return {
        init: function (options) {
            title = options["title"];
            collection_title = options["title"];
            button_active = options["button_active"];
            button_inactive = options["button_inactive"];
            button_toc_active = options["button_toc_active"];
            button_toc_inactive = options["button_toc_inactive"];

            model.init();
            update();

            $('.fa-request-option').click(function () {
                var option = $(this).attr('data-option');
                model.set_request_type(option);
                update();
            });

            $('body').on('click', '.fa-request', function () {
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

            $('body').on('change keyup paste', '#fa-pages', function () {
                update();
            });

            $('body').on('change', '#fa-format', function () {
                var option = $(this).find(':selected').val();
                model.set_format(option);
                update();
            });

            $('body').on('change', '#fa-service-level', function () {
                var option = $(this).find(':selected').val();
                model.set_service_level(option);
                update();
            });

            $('body').on('change keyup paste', '#fa-project', function () {
                update();
            });

            $('.fa-request').removeClass('fa-request-hidden');
            $('.fa-request-summary-note').removeClass('fa-request-hidden');

            $('.fa-requestable').each(function () {
                var id = $(this).attr('id');
                if ($(this).hasClass('fa-toc')) {
                    $(this).after([
                        '<button type="button" class="btn btn-warning fa-request fa-requestable-toc" data-status="inactive" data-active="',
                        button_toc_active,
                        '" data-inactive="',
                        button_toc_inactive,
                        '" data-target="',
                        id,
                        '" id="',
                        id,
                        '-button">',
                        button_toc_inactive,
                        '</button>'
                    ].join(''));
                }
                else {
                    $(this).after([
                        '<button type="button" class="btn btn-warning fa-request fa-requestable-contents" data-status="inactive" data-active="',
                        button_active,
                        '" data-inactive="',
                        button_inactive,
                        '" data-target="',
                        id,
                        '" id="',
                        id,
                        '-button">',
                        button_inactive,
                        '</button>'
                    ].join(''));
                }
            });

            $('form.fa-request-fieldset').submit(function () {
                return model.valid();
            });
        }
    };
})();
