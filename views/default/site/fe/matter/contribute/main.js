//var setWxShare = function(title, link, desc, img) {
//    window.wx.onMenuShareTimeline({
//        title: window.xxt.share.options.descAsTitle ? desc : title,
//        link: link,
//        imgUrl: img,
//        success: function() {
//            try {
//                window.xxt.share.options.logger && window.xxt.share.options.logger('T');
//            } catch (ex) {
//                alert('share failed:' + ex.message);
//            }
//        },
//        cancel: function() {}
//    });
//    window.wx.onMenuShareAppMessage({
//        title: title,
//        desc: desc,
//        link: link,
//        imgUrl: img,
//        success: function() {
//            try {
//                window.xxt.share.options.logger && window.xxt.share.options.logger('F');
//            } catch (ex) {
//                alert('share failed:' + ex.message);
//            }
//        },
//        cancel: function() {}
//    });
//};
//var setYxShare = function(title, link, desc, img) {
//    var shareData = {
//        'img_url': img,
//        'link': link,
//        'title': title,
//        'desc': desc
//    };
//    window.YixinJSBridge.on('menu:share:appmessage', function(argv) {
//        try {
//            window.xxt.share.options.logger && window.xxt.share.options.logger('F');
//        } catch (ex) {
//            alert('share failed:' + ex.message);
//        }
//        window.YixinJSBridge.invoke('sendAppMessage', shareData, function(res) {});
//    });
//    window.YixinJSBridge.on('menu:share:timeline', function(argv) {
//        try {
//            window.xxt.share.options.logger && window.xxt.share.options.logger('T');
//        } catch (ex) {
//            alert('share failed:' + ex.message);
//        }
//        window.YixinJSBridge.invoke('shareTimeline', shareData, function(res) {});
//    });
//};
//window.xxt === undefined && (window.xxt = {});
//window.xxt.share = {
//    options: {},
//    set: function(title, link, desc, img, fnOther) {
//        if (/Android/i.test(navigator.userAgent) || /iPhone/i.test(navigator.userAgent) || /iPad/i.test(navigator.userAgent)) {
//            //if (true) {
//            img && img.length && img.indexOf('http') === -1 && (img = 'http://' + location.hostname + img);
//            if (/MicroMessenger/i.test(navigator.userAgent) && window.wx !== undefined) {
//                window.wx.ready(function() {
//                    setWxShare(title, link, desc, img);
//                });
//            } else if (/YiXin/i.test(navigator.userAgent)) {
//                //} else if (true) {
//                if (window.YixinJSBridge === undefined) {
//                    document.addEventListener('YixinJSBridgeReady', function() {
//                        setYxShare(title, link, desc, img);
//                    }, false);
//                } else {
//                    setYxShare(title, link, desc, img);
//                }
//            } else if (fnOther && typeof fnOther === 'function') {
//                fnOther(title, link, desc, img);
//            }
//        }
//    }
//};
ngApp = angular.module('xxtApp', ['ngRoute', 'ui.tms']);
ngApp.config(['$locationProvider', function($locationProvider) {
    $locationProvider.html5Mode(true);
}]);
ngApp.controller('ctrlContribute', ['$scope', '$location', 'http2', function($scope, $location, http2) {
    console.log(xxt.share);
    $scope.siteId = $location.search().site;
    $scope.appId = $location.search().app;
    $scope.entries = [];
    $scope.user = {} ;
    http2.get('/rest/site/fe/matter/contribute/entry/list?site=' + $scope.siteId + '&app=' + $scope.appId, function(rsp) {
        $scope.entries = rsp.data.entries;
        $scope.user = rsp.data.user;
        if (window.wx || /Yixin/i.test(navigator.userAgent)) {
            setMpShare();
        };
    });
    setMpShare = function() {
        var shareid, sharelink;
        shareid = $scope.user.uid + (new Date()).getTime();
        xxt.share.options.logger = function(shareto) {
            /*var url = "/rest/mi/matter/logShare";
             url += "?shareid=" + shareid;
             url += "&site=" + siteId;
             url += "&id=" + id;
             url += "&type=article";
             url += "&title=" + $scope.article.title;
             url += "&shareto=" + shareto;
             //url += "&shareby=" + shareby;
             $http.get(url);*/
        };
        //分享链接
        sharelink = 'http://' + location.hostname + '/rest/site/fe/matter';
        sharelink += '/contribute';
        sharelink += '?site=' + $scope.siteId;
        sharelink += '&app=' + $scope.appId;
        //sharelink += "&shareby=" + shareid;
        //???
        xxt.share.set($scope.entries[0].title, sharelink,$scope.entries[0].summary,$scope.entries[0].pic);
    };
    $scope.initiate = function(entry) {
        var url = '/rest/site/fe/matter/contribute/initiate';
        url += '?site=' + $scope.siteId;
        url += '&entry=' + entry.pk;
        location.href = url;
    };
    $scope.review = function(entry) {
        var url = '/rest/site/fe/matter/contribute/review';
        url += '?site=' + $scope.siteId;
        url += '&entry=' + entry.pk;
        location.href = url;
    };
    $scope.typeset = function(entry) {
        var url = '/rest/site/fe/matter/contribute/typeset';
        url += '?site=' + $scope.siteId;
        url += '&entry=' + entry.pk;
        location.href = url;
    };
}]);