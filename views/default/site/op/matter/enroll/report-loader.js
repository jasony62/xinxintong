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
                "highcharts": '/static/js/highcharts',
                "highcharts-exporting": '/static/js/highcharts/exporting',
                "ui-tms": '/static/js/ui-tms',
                "ui-xxt": '/static/js/xxt.ui',
                "schema.ui.xxt": '/asset/js/xxt.ui.schema',
                "notice.ui.xxt": '/asset/js/xxt.ui.notice',
                "sys.chart": '/views/default/pl/fe/_module/sys.chart',
                "service.matter": '/views/default/pl/fe/_module/matter.service',
                "enrollService": '/views/default/pl/fe/matter/enroll/lib/enroll.service',
                "page": '/views/default/pl/fe/matter/enroll/lib/page',
                "schema": '/views/default/pl/fe/matter/enroll/lib/schema',
                "wrap": '/views/default/pl/fe/matter/enroll/lib/wrap',
            },
            shim: {
                "angular": {
                    exports: "angular"
                },
                "enrollService": {
                    exprots: "service.enroll",
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
        require(['ui-tms'], function() {
            require(['ui-xxt'], function() {
                require(['schema.ui.xxt'], function() {
                    require(['notice.ui.xxt'], function() {
                        require(['highcharts'], function() {
                            require(['sys.chart'], function() {
                                requirejs(['/views/default/site/op/matter/enroll/report.js']);
                            });
                        });
                    });
                });
            });
        });
    }
};
window.loading.load();