angular.module('xxtApp', ['infinite-scroll']).config(['$locationProvider', function ($locationProvider) {
    $locationProvider.html5Mode(true);
}]).controller('channelCtrl', ['$scope', '$location', '$http', function ($scope, $location, $http) {
    var mpid = $location.search().mpid, channelId = $location.search().id;
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
    $scope.$watch('jsonParams', function (nv) {
        if (nv && nv.length) {
            var params = JSON.parse(decodeURIComponent(nv.replace(/\+/, '%20')));
            $scope.channel = params.channel;
        }
    });
}]);
