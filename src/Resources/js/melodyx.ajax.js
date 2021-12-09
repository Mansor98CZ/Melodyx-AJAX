$.melodyx = {};
$.melodyx.ajax = function (options, callback) {
    var defaults = {
        success: function (data) {
            if ('pieces-error' in data) {
                let show = true;
                let melodyx = $('#melodyx-ajax-error-wrapper');
                melodyx.show();
                melodyx.html(data['pieces-error']);
                $(document).keyup(function (e) {
                    if (e.which === 27) {
                        if (show) {
                            melodyx.hide();
                            show = false;
                        } else {
                            melodyx.show();
                            show = true;
                        }
                    }
                })
            }
            if ('pieces' in data) {
                let pieces = data.pieces;
                $.each(pieces, function (key, value) {
                    console.log(key);
                    let element = $('#piece-' + key);
                    element.html(value);
                });
            }
        }
    };
    $.extend(options, defaults);
    return $.ajax(options);
}
