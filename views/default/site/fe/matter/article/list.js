if (/MicroMessenger/.test(navigator.userAgent)) {
    //signPackage.debug = true;
    signPackage.jsApiList = ['hideOptionMenu', 'onMenuShareTimeline', 'onMenuShareAppMessage'];
    wx.config(signPackage);
}
var app = angular.module('xxt', ['infinite-scroll']).config(['$locationProvider', function($locationProvider) {
    $locationProvider.html5Mode(true);
}]);
app.controller('ctrl', ['$scope', '$location', '$http', '$q', function($scope, $location, $http, $q) {
    var siteId, channelId, shareby, keyWord;
    /*关键词*/
    keyWord = $location.search().keyword;
    siteId = $location.search().site;
    channelId = $location.search().id;
    shareby = $location.search().shareby ? $location.search().shareby : '';
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

    var deferred = $q.defer();
    var promise = deferred.promise;
    $http.get('/rest/site/fe/matter/article/search/list?site=' + siteId + '&keyword=' + keyWord).success(function(rsp) {
        if (rsp.data.length) {
            deferred.resolve(rsp.data);
        } else {
            deferred.reject("很抱歉，未找到与此相关的文章。");
        }
    });
    promise.then(function(result) {
        $scope.matters = result;
    }, function(error) {
        $scope.matters = [];
        $scope.message = error;
        return $scope.message;
    });
    $scope.keypress = function(event) {
        if (event.keyCode == 13) {
            $scope.search();
        }
    }
    $scope.search = function() {
        var deferred = $q.defer();
        var promise = deferred.promise;
        $http.post('/rest/site/fe/matter/article/search/list?site=' + siteId + '&keyword=' + $scope.searchKeyword).success(function(rsp) {
            if (rsp.data.length) {
                deferred.resolve(rsp.data);
            } else {
                deferred.reject("很抱歉，未找到与此相关的文章。");
            }
            promise.then(function(result) {
                $scope.matters = result;
            }, function(error) {
                $scope.matters = [];
                $scope.message = error;
                return $scope.message;
            });
        });
    }
    $scope.open = function(opened) {
        location.href = '/rest/site/fe/matter?site=' + siteId + '&id=' + opened.id + '&type=article';
    };
}]);
