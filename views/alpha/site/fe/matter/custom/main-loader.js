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
                "jQuery": "/static/js/jquery.min",
                "angular": "/static/js/angular.min",
                "xxt-page": "/static/js/xxt.ui.page",
                "xxt-share": "/static/js/xxt.share",
            },
            shim: {
                "angular": {
                    exports: "angular"
                },
                "xxt-share": {
                    exports: "xxt-share"
                },
            },
            urlArgs: function(id, url) {
                if (/^[xxt-|main]/.test(id)) {
                    return "?bust=" + (timestamp * 1);
                }
                return '';
            }
        });
        require(['jQuery'], function() {
            require(['xxt-page'], function(loader) {
                loader.bootstrap('/views/default/site/fe/matter/custom/main.js');
            });
        });
    }
};
if (/MicroMessenger/i.test(navigator.userAgent)) {
    var siteId = location.search.match(/[\?&]site=([^&]*)/)[1];
    requirejs(["https://res.wx.qq.com/open/js/jweixin-1.0.0.js"], function(wx) {
        var xhr = new XMLHttpRequest();
        xhr.open('GET', "/rest/site/fe/matter/article/wxjssdksignpackage?site=" + siteId + "&url=" + encodeURIComponent(location.href.split('#')[0]), true);
        xhr.onreadystatechange = function() {
            if (xhr.readyState == 4) {
                if (xhr.status >= 200 && xhr.status < 400) {
                    try {
                        eval("(" + xhr.responseText + ')');
                        if (signPackage) {
                            window.wx = wx;
                            signPackage.debug = false;
                            signPackage.jsApiList = ['hideOptionMenu', 'onMenuShareTimeline', 'onMenuShareAppMessage'];
                            wx.config(signPackage);
                        }
                        window.loading.load();
                    } catch (e) {
                        alert('local error:' + e.toString());
                    }
                } else {
                    alert('http error:' + xhr.statusText);
                }
            };
        }
        xhr.send();
    });
} else {
    window.loading.load();
}
