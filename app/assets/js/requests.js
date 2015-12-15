var requests = (function() {
    var $ = jQuery;
    var count = 0;
    var request_type = null;
    var datepicker_initialized = false;

    function update() {
        update_count();
        highlight_request_type();
        reset_hidden_fields();
        revalidate();
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
        $('input[name="Notes"]').val('');
        var item;
        var count = model.get_count();
        var pieces = [];
        pieces.push('User notes:');
        pieces.push($('#extra_notes').val());
        pieces.push('');
        pieces.push('Item notes:');
        pieces.push($('#ItemTitle').val());
        for (var i = 0; i < count; ++i) {
            item = model.get(i);
            pieces.push('* ' + item["label"]);
        }
        $('input[name="Notes"]').val(pieces.join("\n"));
        $('.fa-request-hidden-field').remove();
        if ('save-for-later' === model.get_request_type()) {
            $('.fa-request-fieldset').append('<input type="hidden" class="fa-request-hidden-field" name="Visit" value="on"><input type="hidden" class="fa-request-hidden-field" name="UserReview" value="Yes">');
            $('#fa-schedule-retrieval-options').hide();
            $('#fa-datepicker').attr('name', '');
            $('#fa-pages').attr('name', '');
            $('#fa-format').attr('name', '');
            $('#fa-service-level').attr('name', '');
            $('#fa-project').attr('name', '');
            $('#fa-request-reproductions-options').hide();
        }
        else if ('schedule-retrieval' === model.get_request_type()) {
            if (!datepicker_initialized) {
                $.getJSON("date.php", function (data) {
                    $('#fa-datepicker').val(data.earliest);
                    $('.fa-request-fieldset').append('<input type="hidden" class="fa-request-hidden-field" name="Visit" value="on"><input type="hidden" class="fa-request-hidden-field" name="UserReview" value="No">');
                    $('#fa-datepicker').datepicker({
                        showOn: "button",
                        minDate: new Date(data.earliest),
                        beforeShowDay: $.datepicker.noWeekends,
                        dateFormat: "mm/dd/yy"
                    });
                    datepicker_initialized = true;
                });
            }
            $('#fa-datepicker').attr('name', 'ScheduledDate');
            $('#fa-schedule-retrieval-options').show();
            $('#fa-pages').attr('name', '');
            $('#fa-format').attr('name', '');
            $('#fa-service-level').attr('name', '');
            $('#fa-project').attr('name', '');
            $('#fa-request-reproductions-options').hide();
        }
        else if ('request-reproductions' === model.get_request_type()) {
            $('#fa-schedule-retrieval-options').hide();
            $('#fa-datepicker').attr('name', '');
            $('#fa-pages').attr('name', 'ItemInfo3');
            $('#fa-format').attr('name', 'Format');
            $('#fa-service-level').attr('name', 'ServiceLevel');
            $('#fa-project').attr('name', 'ItemInfo4');
            $('#fa-request-reproductions-options').show();
        }
        $('.fa-volume-input').remove();
        volume_inputs = [];
        for (var i = 0; i < model.get_volume_count(); ++i) {
            volume = model.get_volume(i);
            volume_inputs.push(volume_input_view.render(volume));
        }
        $('.fa-request-fieldset').append(volume_inputs.join(''));
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
        var default_request_type = "save-for-later";
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
            "Rush",
            "Standard"
        ];
        var service_level = '';

        function add_volume(volume) {
            if (volume_keys.indexOf(volume["id"]) === -1) {
                volume_keys[keys.length] = volume["id"];
                volumes[volume["id"]] = volume;
                volume_keys.sort(function (a, b) {
                    return volumes[a]["label"].localeCompare(volumes[b]["label"]);
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
                    add_volume({
                        id: item["volume"],
                        label: item["volume"]
                    });
                    return keys.indexOf(item["id"]);
                }
                else {
                    return false;
                }
            },
            remove: function (id) {
                var item_to_delete;
                var volume_id;
                var volume_represented;
                var item;
                if (keys.indexOf(id) !== -1) {
                    keys.splice(keys.indexOf(id), 1);
                    item_to_delete = items[id];
                    volume_id = item_to_delete["volume"];
                    delete items[id];
                    --count;
                    /* Are there any representatives of this volume left? */
                    volume_represented = false;
                    for (var i = 0; i < model.get_count(); ++i) {
                        item = items[keys[i]];
                        if (item["volume"] === volume_id) {
                            volume_represented = true;
                            break;
                        }
                    }
                    if (!volume_represented) {
                        remove_volume(volume_id);
                    }
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
                if ('save-for-later' === request_type) {
                    return true;
                }
                else if ('schedule-retrieval' === request_type) {
                    if ($('#fa-datepicker').attr('name') === "ScheduledDate") {
                        return true;
                    }
                    return false;
                }
                else if ('request-reproductions' === request_type) {
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
                    pieces.push(' <a href="#" class="fa-request-delete" data-target="' + item["target"] + '"><span class="glyphicon glyphicon-remove" aria-hidden="true"></span></a> ');
                }
                pieces.push(item["label"]);
                pieces.push('</p></li>');
                return pieces.join('');
            }
        }
    })();

    var volume_input_view = (function () {
        var pieces = [];

        function add_input(options) {
            if ("root" in options) {
                pieces.push('<input type="hidden" class="fa-volume-input" data-root="');
                pieces.push(options["root"]);
                pieces.push('" name="');
                pieces.push(options["name"]);
                pieces.push('" value="');
                pieces.push(options["value"]);
                pieces.push('">');
            }
            else {
                pieces.push('<input type="hidden" class="fa-volume-input" id="');
                pieces.push(options["id"]);
                pieces.push('" name="');
                pieces.push(options["name"]);
                pieces.push('" value="');
                pieces.push(options["value"]);
                pieces.push('">');
            }
        }

        return {
            render: function (volume) {
                var id = volume["id"].replace(/\s+/g, '_');
                pieces = [];
                add_input({
                  id: id,
                  name: "Request",
                  value: id
                });
                add_input({
                  name: "ItemTitle_" + id,
                  value: $('#ItemTitle').val(),
                  root: id
                });
                add_input({
                  name: "ItemVolume_" + id,
                  value: volume["label"],
                  root: id
                });
                add_input({
                  name: "CallNumber_" + id,
                  value: $('input[name="CallNumber"]').first().val(),
                  root: id
                });
                return pieces.join('');
            }
        }
    })();

    return {
        init: function (options) {
            title = options["title"];
            button_active = options["button_active"];
            button_inactive = options["button_inactive"];

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

            $('body').on('change', '.fa-extra-input', function () {
                update();
            });

            $('.fa-request').removeClass('fa-request-hidden');
            $('.fa-request-summary-note').removeClass('fa-request-hidden');

            $('.fa-requestable').each(function () {
                var id = $(this).attr('id');
                $(this).after([
                    '<button type="button" class="btn btn-warning fa-request" data-status="inactive" data-active="',
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
            });

            $('form.fa-request-fieldset').submit(function () {
                if (!model.valid()) {
                    return false;
                }
                return true;
            });
        }
    };
})();
