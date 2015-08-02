angular.module('xxt', ['infinite-scroll']).config(['$locationProvider', function ($locationProvider) {
    $locationProvider.html5Mode(true);
}]).controller('ctrl', ['$scope', '$location', '$http', '$q', function ($scope, $location, $http, $q) {
    var mpid, channelId, shareby;
    mpid = $location.search().mpid;
    channelId = $location.search().id;
    shareby = $location.search().shareby ? $location.search().shareby : '';
    $scope.Matter = {
        matters: [],
        busy: false,
        page: 1,
        orderby: 'time',
        changeOrderby: function () {
            this.reset();
        },
        reset: function () {
            this.matters = [];
            this.busy = false;
            this.end = false;
            this.page = 1;
            this.nextPage();
        },
        nextPage: function () {
            if (this.end) return;
            var url, _this = this;
            this.busy = true;
            url = '/rest/mi/matter/byChannel';
            url += '?mpid=' + mpid;
            url += '&id=' + channelId;
            url += '&orderby=' + this.orderby;
            url += '&page=' + this.page;
            url += '&size=10';
            $http.get(url).success(function (rsp) {
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
    $scope.open = function (opened) {
        location.href = opened.url;
    };
    var getChannel = function () {
        var deferred = $q.defer();
        $http.get('/rest/mi/channel/get?mpid=' + mpid + '&id=' + channelId).success(function (rsp) {
            $scope.channel = rsp.data;
            deferred.resolve();
            $http.get('/rest/mi/matter/logAccess?mpid=' + mpid + '&id=' + channelId + '&type=channel&title=' + $scope.channel.title + '&shareby=' + shareby);
        }).error(function (content, httpCode) {
            if (httpCode === 401) {
                var el = document.createElement('iframe');
                el.setAttribute('id', 'frmAuth');
                el.onload = function () { this.height = document.documentElement.clientHeight; };
                document.body.appendChild(el);
                if (content.indexOf('http') === 0) {
                    window.onAuthSuccess = function () {
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
