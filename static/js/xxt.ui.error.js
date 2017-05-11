'use strict';
(function() {
    function doXhr(method, url, data) {
        var xhr = new XMLHttpRequest();
        xhr.open(method, url, true);
        xhr.setRequestHeader("Content-type", "application/json;charset=UTF-8");
        xhr.setRequestHeader("Accept", "application/json");
        xhr.send(JSON.stringify(data));
    }
    window.onerror = function(msg, url, line, column) {
        var message = [
            'Message: ' + msg,
            'URL: ' + url,
            'Line: ' + line,
            'Column: ' + column,
        ].join(' - ');
        doXhr('post', '/rest/log/add', { src: 'js', msg: message });
        console.log(message);
    };
})();
