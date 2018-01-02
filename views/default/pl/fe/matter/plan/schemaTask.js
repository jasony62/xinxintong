define(['frame'], function(ngApp) {
    'use strict';
    /**
     * 模板任务
     */
    ngApp.provider.controller('ctrlSchemaTask', ['$scope', '$uibModal', 'http2', 'cstApp', 'srvPlanApp', function($scope, $uibModal, http2, CstApp, srvPlanApp) {
        var _oApp;
        $scope.cstApp = CstApp;
        $scope.addTask = function() {
            http2.post('/rest/pl/fe/matter/plan/schema/task/add?plan=' + _oApp.id, {}, function(rsp) {
                $scope.tasks.push(rsp.data);
            });
        };
        $scope.listTask = function() {
            http2.get('/rest/pl/fe/matter/plan/schema/task/list?plan=' + _oApp.id, function(rsp) {
                $scope.tasks = rsp.data.tasks;
            });
        };
        $scope.editTask = function(oTask) {
            $uibModal.open({
                templateUrl: 'editTask.html',
                controller: ['$scope', '$uibModalInstance', 'cstApp', function($scope2, $mi, CstApp) {
                    var oUpdated;
                    oUpdated = {};
                    $scope2.cstApp = CstApp;
                    $scope2.task = angular.copy(oTask);
                    $scope2.update = function(prop) {
                        oUpdated[prop] = $scope2.task[prop];
                    };
                    $scope2.cancel = function() {
                        $mi.dismiss();
                    };
                    $scope2.ok = function() {
                        $mi.close(oUpdated);
                    };
                    $scope2.$on('xxt.tms-datepicker.change', function(event, data) {
                        $scope2.task[data.state] = data.value;
                        $scope2.update(data.state);
                    });
                }],
                backdrop: 'static'
            }).result.then(function(oUpdated) {
                http2.post('/rest/pl/fe/matter/plan/schema/task/update?task=' + oTask.id, oUpdated, function(rsp) {
                    angular.extend(oTask, oUpdated);
                });
            });
        };
        $scope.removeTask = function(oTask) {
            http2.get('/rest/pl/fe/matter/plan/schema/task/remove?task=' + oTask.id, function(rsp) {
                var tasks, index;
                tasks = $scope.tasks;
                index = tasks.indexOf(oTask);
                tasks.splice(index, 1);
                for (; index < tasks.length; index++) {
                    tasks[index].task_seq--;
                }
            });
        };
        $scope.moveTask = function(oTask, step) {
            http2.get('/rest/pl/fe/matter/plan/schema/task/move?task=' + oTask.id + '&step=' + step, function(rsp) {});
        };
        $scope.toggleTask = function(oTask) {
            $scope.activeTask = oTask;
        };
        srvPlanApp.get().then(function(oApp) {
            _oApp = oApp;
            $scope.listTask();
        });
    }]);
    /**
     * 模板任务行动项
     */
    ngApp.provider.controller('ctrlAction', ['$scope', '$uibModal', 'http2', function($scope, $uibModal, http2) {
        function getTaskAction(oTask) {
            http2.get('/rest/pl/fe/matter/plan/schema/action/list?task=' + oTask.id, function(rsp) {
                $scope.actions = rsp.data;
            });
        }

        var _oTask;
        $scope.addAction = function() {
            http2.post('/rest/pl/fe/matter/plan/schema/action/add?task=' + _oTask.id, {}, function(rsp) {
                $scope.actions.push(rsp.data);
            });
        };
        $scope.editAction = function(oAction) {
            $uibModal.open({
                templateUrl: 'editAction.html',
                controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                    var oUpdated;
                    oUpdated = {};
                    $scope2.action = angular.copy(oAction);
                    $scope2.update = function(prop) {
                        oUpdated[prop] = $scope2.action[prop];
                    };
                    $scope2.cancel = function() {
                        $mi.dismiss();
                    };
                    $scope2.ok = function() {
                        $mi.close(oUpdated);
                    };
                }],
                backdrop: 'static'
            }).result.then(function(oUpdated) {
                http2.post('/rest/pl/fe/matter/plan/schema/action/update?action=' + oAction.id, oUpdated, function(rsp) {
                    angular.extend(oAction, oUpdated);
                });
            });
        };
        $scope.removeAction = function(oAction) {
            http2.get('/rest/pl/fe/matter/plan/schema/action/remove?action=' + oAction.id, function(rsp) {
                var actions, index;
                actions = $scope.actions;
                index = actions.indexOf(oAction);
                actions.splice(index, 1);
                for (; index < actions.length; index++) {
                    actions[index].action_seq--;
                }
            });
        };
        $scope.moveAction = function(oAction, step) {
            http2.get('/rest/pl/fe/matter/plan/schema/action/move?action=' + oAction.id + '&step=' + step, function(rsp) {});
        };
        $scope.$watch('activeTask', function(oTask) {
            _oTask = oTask;
            if (oTask) {
                getTaskAction(oTask);
            }
        })
    }]);
});