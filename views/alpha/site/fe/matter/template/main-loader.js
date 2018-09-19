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
        "domReady": '/static/js/domReady',
        "jquery": "/static/js/jquery.min",
        "angular": "/static/js/angular.min",
        "angular-sanitize": "/static/js/angular-sanitize.min",
        "ui-bootstrap": "/static/js/ui-bootstrap-tpls.min",
        "ui-tms": "/static/js/ui-tms",
        "xxt-page": "/static/js/xxt.ui.page",
    },
    shim: {
        "bootstrap": {
            deps: ['jquery'],
        },
        "angular": {
            deps: ['jquery'],
            exports: "angular"
        },
    },
    urlArgs: function(id, url) {
        if (/^[xxt-]/.test(id)) {
            return "?bust=" + (timestamp * 1);
        }
        return '';
    }
});
require(['jquery'], function() {
    require(['angular'], function(angular) {
        require(['angular-sanitize'], function() {
            require(['ui-bootstrap'], function() {
                require(['ui-tms'], function() {
                    require(['xxt-page'], function(loader) {
                        loader.bootstrap('/views/default/site/fe/matter/template/main.js?_=' + (timestamp * 1));
                    });
                });
            });
        });
    });
});