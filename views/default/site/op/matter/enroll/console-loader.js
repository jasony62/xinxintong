window.loading = {
    finish: function() {
        var eleLoading, eleStyle;
        eleLoading = document.querySelector('.loading');
        eleLoading.parentNode.removeChild(eleLoading);
        eleStyle = document.querySelector('#loadingStyle');
        eleStyle.parentNode.removeChild(eleStyle);
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
                "jquery": '/static/js/jquery.min',
                "bootstrap": '/static/js/bootstrap.min',
                "domReady": '/static/js/domReady',
                "angular": "/static/js/angular.min",
                "ui-bootstrap": '/static/js/ui-bootstrap-tpls.min',
                "util.site": "/views/default/site/util",
            },
            shim: {
                "angular": {
                    exports: "angular"
                },
            },
            urlArgs: function(id, url) {
                if (/jquery|bootstrap|domReady|angular/.test(id)) {
                    return '';
                }
                return "?bust=" + (timestamp * 1);
            },
        });
        require(['jquery'], function() {
            require(['bootstrap'], function() {
                require(['angular'], function() {
                    require(['ui-bootstrap'], function() {
                        requirejs(['/views/default/site/op/matter/enroll/console.js']);
                    });
                });
            });
        });
    }
};
window.loading.load();
