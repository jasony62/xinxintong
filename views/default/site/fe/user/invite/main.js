'use strict';

var ngApp = angular.module('app', ['ui.tms', 'http.ui.xxt']);
ngApp.controller('ctrlInvite', ['$scope', '$q', 'http2', function($scope, $q, http2) {
    var _siteId, _oPage;
    _siteId = location.search.match('site=(.*)')[1];
    $scope.page = _oPage = {
        at: 1,
        size: 10,
        join: function() {
            return '&page=' + this.at + '&size=' + this.size;
        }
    };
    $scope.openInvite = function(oInvite) {
        location.href = '/rest/site/fe/user/invite/detail?invite=' + oInvite.id;
    };
    $scope.list = function() {
        var defer, url;
        defer = $q.defer();
        url = '/rest/site/fe/user/invite/list?site=' + _siteId + _oPage.join();
        http2.get(url).then(function(rsp) {
            $scope.invites = rsp.data.invites;
            _oPage.total = rsp.data.total;
            defer.resolve(rsp.data);
        });
        return defer.promise;
    };
    http2.get('/rest/site/fe/get?site=' + _siteId).then(function(rsp) {
        $scope.site = rsp.data;
        $scope.list().then(function() {
            var eleLoading;
            eleLoading = document.querySelector('.loading');
            eleLoading.parentNode.removeChild(eleLoading);
        });
    });
}]);