(function ($) {


    $(document).ready(function () {

        $(".js-datepicker").datepicker({
            defaultDate: new Date(),
            dateFormat: "dd.mm.yy",
            firstDay: 1
        });

        //$('.js-timepicker').timepicker({
        //    showMeridian: false,
        //    defaultTime: 0,
        //    disableFocus: false
        //});


        APP.submit('.app-login', '/ajax/login', {
            callback: function (res) {
                if (res) {
                    APP.redirect('/admin/');
                }
            }
        });

        APP.submit('.app-add-group', '/ajax/groups/add', {
            callback: function (res) {
                if (res) {
                    var $res = $.parseJSON(res);
                    window.location = '/admin/groups/' + $res.values.id;
                }
            }
        });
        APP.submit('.app-edit-group', '/ajax/gtoups/edit');

        APP.submit('.app-add-device', '/ajax/devices/add', {
            callback: function (res) {
                if (res) {
                    var $res = $.parseJSON(res);
                    window.location = '/admin/devices/' + $res.values.id;
                }
            }
        });
        APP.submit('.app-edit-device', '/ajax/devices/edit');


        APP.submit('.app-edit-schedule', '/ajax/schedule/edit');

        APP.submit('.app-add-schedule', '/ajax/schedule/add', {
            callback: function (res) {
                if (res) {
                    var $res = $.parseJSON(res);
                    window.location = '/admin/schedules/' + $res.values.id;
                }
            }
        });

        APP.submit('.app-add-scenario', '/ajax/scenarios/add', {
            callback: function (res) {
                if (res) {
                    var $res = $.parseJSON(res);
                    window.location = '/admin/scenarios/' + $res.values.id;
                }
            }
        });
        APP.submit('.app-edit-scenario', '/ajax/scenario/edit');

        APP.submit('.app-add-actions', '/ajax/actions/add', {
            callback: function (res) {
                if (res) {
                    var $res = $.parseJSON(res);
                    window.location = '/admin/actions/' + $res.values.id;
                }
            }
        });
        APP.submit('.app-edit-action', '/ajax/actions/edit');

        $(document).on('keyup', '.app-filename-input', function () {
            $('.app-filename').text($(this).val());
        });
        $('.app-filename-input').trigger('keyup');

        $(document).on('change', '.app-action-scenario', function () {

            $('.js-scenario-params').html($('.app-scenario-' + $(this).val()).html());
        });
        $('.app-action-scenario').trigger('change');


        $(document).on('click', '.app-start-action', function (e) {
            $.post('/ajax/actions/start', {id: $(e.target).data('id')});
            e.preventDefault();
        });

        $(document).on('click', '.js-delete-schedule', function () {
            $.post('/ajax/schedule/delete', {id: $('.js-id').val()});
        });
        $(document).on('click', '.js-delete-group', function () {
            $.post('/ajax/groups/delete', {id: $('.js-id').val()});
        });

        $(".js-color").each(function () {
            var r = $(this).data('r') || 0;
            var g = $(this).data('g') || 0;
            var b = $(this).data('b') || 0;
            $(this).ColorPickerSliders({
                swatches: false,
                color: tinycolor("rgb " + r + ' ' + g + ' ' + b),
                order: {
                    hsl: 1,
                    rgb: 2,
                    preview: 3
                }
            });
        });

        $(document).on('change', '.js-color-clear', function () {
            if ($(this).prop('checked')) {
                $(this).closest('.form-group').find('.js-color').val('').attr('disabled', 'disabled');
            } else {
                $(this).closest('.form-group').find('.js-color').removeAttr('disabled');
            }
        });
        $('.js-color-clear').trigger('change');
        APP.submit('.app-module-action');


        $('.app-start-action[data-switcher="1"]').each(function () {
            getStatus($(this).data('id'));
        });

        function getStatus (id) {
            $.post('/ajax/actions/' + id + '/status', function (data) {
                var $data = $.parseJSON(data);
                if ($data.value) {
                    $('.app-start-action[data-id="' + id + '"]').removeClass('active');
                } else {
                    $('.app-start-action[data-id="' + id + '"]').addClass('active')
                }
                setTimeout(function () {
                    getStatus(id)
                }, 1000);
            });
        }

        var logId = 0;

        function getLog () {

            var $log = $('.js-log');

            $.post('/ajax/log', {logId: logId}, function (items) {

                items = $.parseJSON(items);
                if (items) {
                    for (var i = 0; i < items.length; i++) {
                        if (items[i].id_logs > logId) {
                            logId = items[i].id_logs;

                            $log.prepend('<div><span class="date">[' + items[i].date + ']</span><span class="message">' + items[i].message + '</span></div>')
                        }
                    }
                }
                setTimeout(function () {
                    getLog()
                }, 1000);
            });
        }

        getLog();
    });

})(jQuery);