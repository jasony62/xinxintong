define(['frame'], function(ngApp) {
    'use strict';
    /**
     * 模板任务
     */
    ngApp.provider.controller('ctrlSchemaTask', ['$scope', '$timeout', '$anchorScroll', '$uibModal', 'http2', 'cstApp', 'srvPlanApp', function($scope, $timeout, $anchorScroll, $uibModal, http2, CstApp, srvPlanApp) {
        var _oApp;
        $scope.cstApp = CstApp;
        $scope.addTask = function() {
            http2.post('/rest/pl/fe/matter/plan/schema/task/add?plan=' + _oApp.id, {}, function(rsp) {
                var oNewTask;
                oNewTask = rsp.data;
                $scope.tasks.push(rsp.data);
                $timeout(function() {
                    var eleTask;
                    eleTask = document.querySelector('#task-' + oNewTask.id);
                    eleTask.classList.add('blink');
                    $anchorScroll('task-' + oNewTask.id);
                    $timeout(function() {
                        eleTask.classList.remove('blink');
                    }, 1000);
                });
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
                    $scope2.app = _oApp;
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
            if (window.confirm('确认删除？')) {
                http2.get('/rest/pl/fe/matter/plan/schema/task/remove?task=' + oTask.id, function(rsp) {
                    var tasks, index;
                    tasks = $scope.tasks;
                    index = tasks.indexOf(oTask);
                    tasks.splice(index, 1);
                    for (; index < tasks.length; index++) {
                        tasks[index].task_seq--;
                    }
                });
            }
        };
        $scope.copyTask = function(oTask) {
            http2.get('/rest/pl/fe/matter/plan/schema/task/copy?task=' + oTask.id, function(rsp) {
                var index, oNewTask;
                index = $scope.tasks.indexOf(oTask);
                oNewTask = rsp.data;
                $scope.tasks.splice(index + 1, 0, oNewTask);
                for (var i = oNewTask.task_seq, ii = $scope.tasks.length; i < ii; i++) {
                    $scope.tasks[i].task_seq++;
                }
                $timeout(function() {
                    var eleTask;
                    eleTask = document.querySelector('#task-' + oNewTask.id);
                    eleTask.classList.add('blink');
                    $timeout(function() {
                        eleTask.classList.remove('blink');
                    }, 1000);
                });
            });
        };
        $scope.moveTask = function(oTask, step) {
            var index;
            if (step === 0 || (parseInt(oTask.task_seq) + step < 1) || (parseInt(oTask.task_seq) + step > $scope.tasks.length)) {
                return;
            }
            index = $scope.tasks.indexOf(oTask);
            http2.get('/rest/pl/fe/matter/plan/schema/task/move?task=' + oTask.id + '&step=' + step, function(rsp) {
                var oMovedTask;
                oMovedTask = rsp.data;
                $scope.tasks.splice(index, 1);
                $scope.tasks.splice(oMovedTask.task_seq - 1, 0, oTask);
                oTask.task_seq = oMovedTask.task_seq;
                if (step > 0) {
                    $scope.tasks[index].task_seq--;
                } else if (step < 0) {
                    $scope.tasks[index].task_seq++;
                }
            });
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
            if (_oTask) {
                http2.post('/rest/pl/fe/matter/plan/schema/action/add?task=' + _oTask.id, {}, function(rsp) {
                    $scope.actions.push(rsp.data);
                });
            }
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