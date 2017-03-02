define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlNotice', ['$scope', 'srvTmplmsgNotice', function($scope, srvTmplmsgNotice) {
        var oPage, aBatches;
        $scope.page = oPage = {};
        $scope.batches = aBatches = [];
        $scope.detail = function(batch) {
            srvTmplmsgNotice.detail(batch).then(function(logs) {
                $scope.logs = logs;
                $scope.activeBatch = batch;
            })
        };
        $scope.$watch('app', function(app) {
            if (!app) return;
            srvTmplmsgNotice.init('enroll:' + app.id, oPage, aBatches);
            srvTmplmsgNotice.list();
        });
    }]);
});
