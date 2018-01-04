define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlTaskDetail', ['$scope', 'http2', 'srvRecordConverter', 'srvPlanApp', '$uibModal', function($scope, http2, srvRecordConverter, srvPlanApp, $uibModal) {
        var _oTask, _oUpdated;

        // 更新的任务数据
        _oUpdated = {};
        $scope.modified = false;
        $scope.updateTask = function(prop) {
            $scope.modified = true;
            _oUpdated[prop] = _oTask[prop];
        };
        $scope.saveTask = function() {
            http2.post('/rest/pl/fe/matter/plan/task/update' + location.search, _oUpdated, function(rsp) {
                $scope.modified = false;
            });
        };
        srvPlanApp.get().then(function(oApp) {
            http2.get('/rest/pl/fe/matter/plan/task/get' + location.search, function(rsp) {
                $scope.task = _oTask = rsp.data;
                $scope.data = _oTask.data;
                $scope.supplement = _oTask.supplement;
                _oTask.taskSchema.actions.forEach(function(oAction) {
                    if (oApp.checkSchemas && oApp.checkSchemas.length) {
                        oAction.checkSchemas = [].concat(oApp.checkSchemas, oAction.checkSchemas);
                    }
                    oAction.checkSchemas.forEach(function(oSchema) {
                        srvRecordConverter.forEdit(oSchema, _oTask.data[oAction.id]);
                    });
                });
            });
        });
    }])
});