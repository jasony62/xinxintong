if (/MicroMessenger/.test(navigator.userAgent)) {
    //signPackage.debug = true;
    signPackage.jsApiList = ['hideOptionMenu', 'onMenuShareTimeline', 'onMenuShareAppMessage'];
    wx.config(signPackage);
}
var app = angular.module('xxt', ['infinite-scroll']).config(['$locationProvider', function($locationProvider) {
    $locationProvider.html5Mode(true);
}]);
app.controller('ctrl', ['$scope', '$location', '$http', '$q', function($scope, $location, $http, $q) {
    var siteId, channelId, shareby, keyWord, tagid;
    /*关键词*/
    keyWord = $location.search().keyword;
    siteId = $location.search().site;
    channelId = $location.search().id;
    shareby = $location.search().shareby ? $location.search().shareby : '';
    $scope.tagid = tagid = $location.search().tagid;
    var setShare = function() {
        var shareid, sharelink;
        shareid = $scope.user.vid + (new Date()).getTime();
        window.xxt.share.options.logger = function(shareto) {
            var url = "/rest/site/fe/matter/logShare";
            url += "?shareid=" + shareid;
            url += "&site=" + siteId;
            url += "&id=" + channelId;
            url += "&type=channel";
            url += "&title=" + $scope.channel.title;
            url += "&shareto=" + shareto;
            url += "&shareby=" + shareby;
            $http.get(url);
        };
        sharelink = location.href;
        if (/shareby=/.test(sharelink))
            sharelink = sharelink.replace(/shareby=[^&]*/, 'shareby=' + shareid);
        else
            sharelink += "&shareby=" + shareid;
        window.xxt.share.set($scope.channel.title, sharelink, $scope.channel.title, '');
    };
    /*输入框需要绑定的内容*/
    $scope.searchKeyword = keyWord;
    /*发送请求*/
    if (tagid) {
        $http.get('/rest/site/fe/matter/article/list?site=' + siteId + '&tagid=' + tagid).success(function(rsp) {
            $scope.matters = rsp.data.docs;
        });
    } else {
        $http.get('/rest/site/fe/matter/article/search/list?site=' + siteId + '&keyword=' + keyWord).success(function(rsp) {
            $scope.matters = rsp.data;
        });
    }

    $scope.keypress = function(event) {
        if (event.keyCode == 13) {
            $scope.search();
        }
    };
    $scope.search = function(event) {
        $http.post('/rest/site/fe/matter/article/search/list?site=' + siteId + '&keyword=' + $scope.searchKeyword).success(function(rsp) {
            $scope.matters = rsp.data;
            $scope.word = $scope.searchKeyword;
        });
        var url = $location.absUrl().split('&')[0] + '&keyword=' + $scope.searchKeyword;
        history.replaceState(null, '', url);
    };
    $scope.open = function(opened) {
        location.href = '/rest/site/fe/matter?site=' + siteId + '&id=' + opened.id + '&type=article';
    };
}]);