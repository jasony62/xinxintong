'use strict';
(function () {
    function doXhr(method, url, data) {
        var xhr = new XMLHttpRequest();
        xhr.open(method, url, true);
        xhr.setRequestHeader("Content-type", "application/json;charset=UTF-8");
        xhr.setRequestHeader("Accept", "application/json");
        xhr.send(JSON.stringify(data));
    }
    window.onerror = function (msg, url, line, column, error) {
        var message;
        if (msg === 'Uncaught ReferenceError: WeixinJSBridge is not defined') {
            return false;
        }
        message = [
            'Message: ' + msg,
            'URL: ' + url,
            'Line: ' + line,
            'Column: ' + (column || ''),
            'Error Stack: ' + ((error && error.stack) ? JSON.stringify(error.stack) : '')
        ].join(' - ');
        doXhr('post', '/rest/log/add', {
            src: 'js',
            msg: message
        });
        return false;
    };
    // 两种方法作用完全相同吗？
    // window.addEventListener('error', function (eventErr) {
    //     var message;
    //     message = [
    //         'Message: ' + eventErr.message,
    //         'URL: ' + eventErr.filename || '',
    //         'Line: ' + eventErr.lineno || '',
    //         'Column: ' + (eventErr.colno || ''),
    //         'Error Stack: ' + ((eventErr.error && eventErr.error.stack) ? JSON.stringify(eventErr.error.stack) : '')
    //     ].join(' - ');
    //     doXhr('post', '/rest/log/add', {
    //         src: 'js',
    //         msg: message
    //     });
    //     return false;
    // }, true);
})();