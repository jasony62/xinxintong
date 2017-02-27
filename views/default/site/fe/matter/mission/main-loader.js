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
                "ui-xxt": "/static/js/xxt.ui",
                "tmsSiteuser": "/static/js/xxt.ui.siteuser",
                "matterService": "/views/default/pl/fe/_module/matter.service",
                "main": "/views/default/site/fe/matter/mission/main",
            },
            shim: {
                "angular": {
                    exports: "angular"
                },
            },
            urlArgs: function(id, url) {
                if (/domReady|angular/.test(id)) {
                    return '';
                }
                return "?bust=" + (timestamp * 1);
            }
        });
        require(['angular'], function() {
            require(['angular-sanitize'], function() {
                require(['ui-bootstrap', 'ui-xxt'], function() {
                    require(['main'], function() {});
                })
            })
        });
    }
};
window.loading.load();
