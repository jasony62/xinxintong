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
                "angular": "/static/js/angular.min",
                "util.site": "/views/default/site/util",
            },
            shim: {
                "angular": {
                    exports: "angular"
                },
            },
            deps: ['/views/default/site/op/matter/enroll/console.js'],
            urlArgs: function(id, url) {
                if (/domReady|angular/.test(id)) {
                    return '';
                }
                return "?bust=" + (timestamp * 1);
            }
        });
    }
};
window.loading.load();
