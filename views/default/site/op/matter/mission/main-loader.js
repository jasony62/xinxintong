window.loading = {
    finish: function() {
        var eleLoading, eleStyle;
        eleLoading = document.querySelector('.loading');
        eleLoading.parentNode.removeChild(eleLoading);
        eleStyle = document.querySelector('#loadingStyle');
        eleStyle.parentNode.removeChild(eleStyle);
    },
    load: function() {
        require.config({
            paths: {
                "domReady": '/static/js/domReady',
                "angular": "/static/js/angular.min",
                "angular-sanitize": "/static/js/angular-sanitize.min",
                "xxt-page": "/static/js/xxt.ui.page",
            },
            shim: {
                "angular": {
                    exports: "angular"
                },
                "angular-sanitize": {
                    deps: ['angular'],
                    exports: "angular-sanitize"
                },
            },
            urlArgs: "bust=" + (new Date() * 1)
        });
        require(['xxt-page'], function(uiPage) {
            uiPage.bootstrap('/views/default/site/op/matter/mission/main.js');
        });
    }
};
window.loading.load();
