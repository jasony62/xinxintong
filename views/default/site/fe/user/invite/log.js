'use strict';

var ngApp = angular.module('app', ['ui.bootstrap', 'ui.tms', 'http.ui.xxt']);
ngApp.controller('ctrlInvite', ['$scope', '$q', '$uibModal', 'http2', function($scope, $q, $uibModal, http2) {
    var _oPage;
    $scope.page = _oPage = {
        at: 1,
        size: 10,
        join: function() {
            return '&page=' + this.at + '&size=' + this.size;
        }
    };
    $scope.logList = function() {
        var defer, url;
        defer = $q.defer();
        url = '/rest/site/fe/user/invite/logList' + location.search + _oPage.join();
        http2.get(url).then(function(rsp) {
            $scope.logs = rsp.data.logs;
            _oPage.total = rsp.data.total;
            defer.resolve(rsp.data);
        });
        return defer.promise;
    };
    http2.get('/rest/site/fe/user/invite/codeGet' + location.search).then(function(rsp) {
        $scope.inviteCode = rsp.data;
        $scope.logList().then(function() {
            var eleLoading;
            eleLoading = document.querySelector('.loading');
            eleLoading.parentNode.removeChild(eleLoading);
        });
    });
}]);