window.loading = {
    finish: function() {
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
                "xxt-image": "/static/js/xxt.image",
                "xxt-geo": "/static/js/xxt.geo",
                "enroll-directive": "/views/default/site/fe/matter/signin/directive",
                "enroll-common": "/views/default/site/fe/matter/signin/common",
                "main": '/views/default/site/fe/matter/signin/preview'
            },
            shim: {
                "angular": {
                    exports: "angular"
                },
                "angular-sanitize": {
                    deps: ['angular'],
                    exports: "angular-sanitize"
                },
                "xxt-share": {
                    exports: "xxt-share"
                },
                "xxt-image": {
                    exports: "xxt-image"
                },
                "xxt-geo": {
                    exports: "xxt-geo"
                },
                "enroll-common": {
                    deps: ['angular-sanitize'],
                    exports: "enroll-common"
                },
                "enroll-directive": {
                    deps: ['enroll-common'],
                    exports: "enroll-directive"
                },
            },
            urlArgs: function(id, url) {
                if (/domReady|angular|angular-sanitize/.test(id)) {
                    return '';
                }
                return "?bust=" + (timestamp * 1);
            }
        });
        require(['main']);
    }
};
window.loading.load();
