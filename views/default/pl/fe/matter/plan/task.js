define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlTask', ['$scope', 'http2', 'srvPlanApp', '$uibModal', function($scope, http2, srvPlanApp, $uibModal) {
        var _oApp;
        $scope.rows = {
            allSelected: 'N',
            selected: {},
            count: 0,
            change: function(index) {
                this.selected[index] ? this.count++ : this.count--;
            },
            reset: function() {
                this.allSelected = 'N';
                this.selected = {};
                this.count = 0;
            }
        };
        $scope.$watch('rows.allSelected', function(checked) {
            var index = 0;
            if (checked === 'Y') {
                while (index < $scope.tasks.length) {
                    $scope.rows.selected[index++] = true;
                }
                $scope.rows.count = $scope.tasks.length;
            } else if (checked === 'N') {
                $scope.rows.reset();
            }
        });
        $scope.doSearch = function() {
            http2.get('/rest/pl/fe/matter/plan/task/list?app=' + _oApp.id, function(rsp) {
                $scope.tasks = rsp.data.tasks;
            });
        };
        $scope.gotoTask = function(oTask) {
            location.href = '/rest/pl/fe/matter/plan/taskDetail?id=' + _oApp.id + '&site=' + _oApp.siteid + '&task=' + oTask.id;
        };
        $scope.batchVerify = function(rows) {
            var ids = [],
                selectedTasks = [];
            for (var p in rows.selected) {
                if (rows.selected[p] === true) {
                    ids.push($scope.tasks[p].id);
                    selectedTasks.push($scope.tasks[p]);
                }
            }
            if (ids.length) {
                http2.post('/rest/pl/fe/matter/plan/task/batchVerify?app=' + _oApp.id, {
                    ids: ids
                }, function(rsp) {
                    selectedTasks.forEach(function(oTask) {
                        oTask.verified = 'Y';
                    });
                });
            }
        };
        srvPlanApp.get().then(function(oApp) {
            _oApp = oApp;
            $scope.doSearch();
        });
    }]);
});