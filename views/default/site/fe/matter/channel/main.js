if (/MicroMessenger/.test(navigator.userAgent)) {
    //signPackage.debug = true;
    signPackage.jsApiList = ['hideOptionMenu', 'onMenuShareTimeline', 'onMenuShareAppMessage'];
    wx.config(signPackage);
}
angular.module('xxt', ['infinite-scroll']).config(['$locationProvider', function($locationProvider) {
    $locationProvider.html5Mode(true);
}]).controller('ctrl', ['$scope', '$location', '$http', '$q', function($scope, $location, $http, $q) {
    var siteId, channelId, shareby;
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
    $scope.Matter = {
        matters: [],
        busy: false,
        page: 1,
        orderby: 'time',
        changeOrderby: function() {
            this.reset();
        },
        reset: function() {
            this.matters = [];
            this.busy = false;
            this.end = false;
            this.page = 1;
            this.nextPage();
        },
        nextPage: function() {
            //if ($scope.channel === undefined) return;
            if (this.end) return;
            var url, _this = this;
            this.busy = true;
            url = '/rest/site/fe/matter/channel/mattersGet';
            url += '?site=' + siteId;
            url += '&id=' + channelId;
            url += '&orderby=' + this.orderby;
            url += '&page=' + this.page;
            url += '&size=10';
            $http.get(url).success(function(rsp) {
                if (rsp.data.length) {
                    var matters = rsp.data;
                    for (var i = 0, l = matters.length; i < l; i++) {
                        _this.matters.push(matters[i]);
                    }
                    _this.page++;
                } else {
                    _this.end = true;
                }
                _this.busy = false;
            });
        }
    };
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