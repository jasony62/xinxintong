window.loading = {
    finish: function() {
        var eleLoading, eleStyle;
        eleLoading = document.querySelector('.loading');
        eleLoading.parentNode.removeChild(eleLoading);
    },
    load: function() {
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
                "angular": "/static/js/angular.min",
                "angular-sanitize": "/static/js/angular-sanitize.min",
                "ui-bootstrap": "/static/js/ui-bootstrap-tpls.min",
                "ui-tms": "/static/js/ui-tms",
                "ui-xxt": "/static/js/xxt.ui",
                "xxt-page": "/static/js/xxt.ui.page",
                "matterService": "/views/default/pl/fe/_module/matter.service",
                "missionService": '/views/default/pl/fe/matter/mission/lib/mission.service',
            },
            shim: {
                "angular": {
                    exports: "angular"
                },
                "angular-sanitize": {
                    deps: ['angular'],
                    exports: "angular-sanitize"
                },
                "ui-tms": {
                    deps: ['angular-sanitize'],
                },
                "matterService": {
                    deps: ['ui-xxt', 'ui-bootstrap', 'angular-sanitize'],
                },
                "missionService": {
                    deps: ['matterService'],
                },
            },
            urlArgs: function(id, url) {
                if (/domReady|angular|angular-sanitize|ui-bootstrap/.test(id)) {
                    return '';
                }
                return "?bust=" + (timestamp * 1);
            }
        });
        require(['ui-tms'], function() {
            require(['missionService'], function() {
                require(['xxt-page'], function(uiPage) {
                    uiPage.bootstrap('/views/default/site/op/matter/mission/main.js');
                });
            });
        });
    }
};
window.loading.load();
