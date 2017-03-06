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
                "domReady": '/static/js/domReady',
                "jQuery": "/static/js/jquery.min",
                "bootstrap": "/static/js/bootstrap.min",
                "angular": "/static/js/angular.min",
                "ui-bootstrap": "/static/js/ui-bootstrap-tpls.min",
                "main": "/views/default/site/fe/user/history/main",
            },
            shim: {
                "angular": {
                    exports: "angular"
                },
            },
            urlArgs: function(id, url) {
                if (/domReady|jQuery|bootstrap|angular|ui-bootstrap/.test(id)) {
                    return '';
                }
                return "?bust=" + (timestamp * 1);
            }
        });
        require(['jQuery'], function() {
            require(['bootstrap'], function() {
                require(['angular'], function() {
                    require(['ui-bootstrap'], function() {
                        require(['main'], function() {});
                    });
                });
            });
        });
    }
};
window.loading.load();
