if (/MicroMessenger/.test(navigator.userAgent)) {
    //signPackage.debug = true;
    signPackage.jsApiList = ['hideOptionMenu', 'onMenuShareTimeline', 'onMenuShareAppMessage'];
    wx.config(signPackage);
}
angular.module('xxt', ['infinite-scroll']).config(['$locationProvider', function($locationProvider) {
    $locationProvider.html5Mode(true);
}]).controller('ctrl', ['$scope', '$location', '$http', '$q', function($scope, $location, $http, $q) {
    var siteId, channelId, shareby;
    /*关键词*/
    keyword = $location.search().keyword;
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
    $scope.searchKeyword = keyword;
    /*重置输入框*/
    $scope.reset = function() {
        $scope.searchKeyword = '';
    }
    /*标签展示*/
    $scope.transform = function(obj) {
        var arr = [];
        for (var item in obj) {
            arr.push(obj[item].title);
        }
        return arr.join('/');
    }
    $http.get('').success(rsp){
        $scope.matters = rsp.data;
    }
    $scope.search = function () {
        $http.post('').success(rsp){
            $scope.matters = rsp.data;
        }
    }
    $scope.open = function(opened) {
        location.href = opened.url;
    };
    var getChannel = function() {
        var deferred = $q.defer();
        $http.get('/rest/site/fe/matter/channel/get?site=' + siteId + '&id=' + channelId).success(function(rsp) {
            $scope.user = rsp.data.user;
            $scope.channel = rsp.data.channel;
            if (/MicroMessenge|Yixin/i.test(navigator.userAgent)) {
                setShare();
            }
            deferred.resolve();
            if ($scope.Matter.matters) {
                // 任意类型素材情况下不支持分页
                if ($scope.channel.matter_type === '') {
                    $scope.Matter.matters.end = true;
                }
            }
            $http.post('/rest/site/fe/matter/logAccess?site=' + siteId + '&id=' + channelId + '&type=channel&title=' + $scope.channel.title + '&shareby=' + shareby, {
                search: location.search.replace('?', ''),
                referer: document.referrer
            });
        }).error(function(content, httpCode) {
            if (httpCode === 401) {
                var el = document.createElement('iframe');
                el.setAttribute('id', 'frmAuth');
                el.onload = function() {
                    this.height = document.documentElement.clientHeight;
                };
                document.body.appendChild(el);
                if (content.indexOf('http') === 0) {
                    window.onAuthSuccess = function() {
                        el.style.display = 'none';
                        getChannel();
                    };
                    el.setAttribute('src', content);
                    el.style.display = 'block';
                } else {
                    if (el.contentDocument && el.contentDocument.body) {
                        el.contentDocument.body.innerHTML = content;
                        el.style.display = 'block';
                    }
                }
            } else {
                alert(content);
            }
        });
        return deferred.promise;
    };
    getChannel();
}]);