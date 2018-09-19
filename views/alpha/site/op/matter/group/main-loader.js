var timestamp, minutes;
timestamp = new Date();
minutes = timestamp.getMinutes();
minutes = Math.floor(minutes / 5) * 5;
timestamp.setMinutes(minutes);
timestamp.setMilliseconds(0);
timestamp.setSeconds(0);
require.config({
    waitSeconds: 0,
    paths: {
        "jquery": "/static/js/jquery.min",
        "domReady": '/static/js/domReady',
        "angular": "/static/js/angular.min",
        "angular-sanitize": "/static/js/angular-sanitize.min",
        "xxt-page": "/static/js/xxt.ui.page",
    },
    shim: {
        "angular": {
            exports: "angular"
        },
        "angular-sanitize": {
            deps: ['angular'],
            exports: "angular-sanitize"
        },
    },
    urlArgs: function(id, url) {
        if (/jquery|domReady|angular|angular-sanitize/.test(id)) {
            return '';
        }
        return "?bust=" + (timestamp * 1);
    }
});
require(['jquery'], function() {
    require(['xxt-page'], function(uiPage) {
        uiPage.bootstrap('/views/default/site/op/matter/group/main.js');
    });
});
