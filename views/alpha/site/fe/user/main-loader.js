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
                "xxt-http": "/asset/js/xxt.ui.http",
                "xxt-image": "/asset/js/xxt.ui.image"
            },
            shim: {
                "angular": {
                    exports: "angular"
                },
            },
            urlArgs: function(id, url) {
                if (/angular/.test(id)) {
                    return '';
                }
                return "?bust=" + (timestamp * 1);
            }
        });
        require(['angular'], function(angular) {
            require(['angular-sanitize'], function() {
                require(['xxt-http'], function() {
                    require(['xxt-image'], function() {
                        require(['main']);
                    });
                });
            });
        });
    }
};
window.loading.load();
