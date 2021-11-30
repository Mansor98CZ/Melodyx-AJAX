$.melodyx = {};
$.melodyx.ajax = function (options, callback) {
    var defaults = {
        success: function (data) {
            if ('pieces' in data) {
                let pieces = data.pieces;
                console.log(pieces);
                $.each(pieces, function (key, value) {
                    console.log(key);
                    let element = $('#piece-' + key);
                    console.log(element);
                    element.html(value);
                });
            }
        }
    };
    $.extend(options, defaults);
    return $.ajax(options);
}
