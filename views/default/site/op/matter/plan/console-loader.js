window.loading = {
    finish: function() {
        var eleLoading, eleStyle;
        if (eleLoading = document.querySelector('.loading')) {
            eleLoading.parentNode.removeChild(eleLoading);
        }
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
                "angular-route": "/static/js/angular-route.min",
                "angular-sanitize": '/static/js/angular-sanitize.min',
                "ui-bootstrap": '/static/js/ui-bootstrap-tpls.min',
                "highcharts": '/static/js/highcharts',
                "highcharts-exporting": '/static/js/highcharts/exporting',
                "ui-tms": '/static/js/ui-tms',
                "ui-xxt": '/static/js/xxt.ui',
                "schema.ui.xxt": '/asset/js/xxt.ui.schema',
                "service.matter": '/views/default/pl/fe/_module/matter.service',
                "planService": '/views/default/pl/fe/matter/plan/lib/plan.service',
                "page": '/views/default/pl/fe/matter/enroll/lib/page',
                "schema": '/views/default/pl/fe/matter/enroll/lib/schema',
                "wrap": '/views/default/pl/fe/matter/enroll/lib/wrap',
            },
            shim: {
                "angular": {
                    exports: "angular"
                },
                "planService": {
                    exprots: "service.plan",
                    deps: ['service.matter']
                }
            },
            urlArgs: function(id, url) {
                if (/bootstrap|domReady|angular|highcharts/.test(id)) {
                    return '';
                }
                if (/xxt|tms/.test(id)) {
                    return "?_=1";
                }
                return "?bust=" + (timestamp * 1);
            },
        });
        require(['angular'], function() {
            require(['angular-route'], function() {
                require(['angular-sanitize'], function() {
                    require(['ui-bootstrap'], function() {
                        require(['ui-tms'], function() {
                            require(['ui-xxt'], function() {
                                require(['schema.ui.xxt'], function() {
                                    require(['highcharts'], function() {
                                        requirejs(['/views/default/site/op/matter/plan/console.js']);
                                    });
                                });
                            });
                        });
                    });
                });
            });
        });
    }
};
window.loading.load();