define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlUser', ['$scope', 'http2', 'srvPlanApp', '$uibModal', function($scope, http2, srvPlanApp, $uibModal) {
        var _oApp;
        $scope.doSearch = function() {
            http2.get('/rest/pl/fe/matter/plan/user/list?app=' + _oApp.id, function(rsp) {
                $scope.users = rsp.data.users;
            });
        };
        srvPlanApp.get().then(function(oApp) {
            _oApp = oApp;
            $scope.doSearch();
        });
    }])
});