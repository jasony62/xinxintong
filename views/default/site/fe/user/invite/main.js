'use strict';

var ngApp = angular.module('app', ['ui.tms', 'http.ui.xxt']);
ngApp.controller('ctrlInvite', ['$scope', '$q', 'tmsLocation', 'http2', function($scope, $q, LS, http2) {
    var _oPage;
    $scope.page = _oPage = {};
    $scope.openInvite = function(oInvite) {
        location.href = '/rest/site/fe/user/invite/detail?invite=' + oInvite.id;
    };
    $scope.list = function() {
        var defer;
        defer = $q.defer();
        http2.get(LS.j('list', 'site'), { page: _oPage }).then(function(rsp) {
            $scope.invites = rsp.data.invites;
            defer.resolve(rsp.data);
        });
        return defer.promise;
    };
    http2.get('/rest/site/fe/get?site=' + LS.s().site).then(function(rsp) {
        $scope.site = rsp.data;
        $scope.list().then(function() {
            var eleLoading;
            eleLoading = document.querySelector('.loading');
            eleLoading.parentNode.removeChild(eleLoading);
        });
    });
}]);