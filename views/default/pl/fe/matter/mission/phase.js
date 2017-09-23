define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlPhase', ['$scope', 'http2', 'noticebox', function($scope, http2, noticebox) {
        $scope.numberOfNewPhases = 1;
        var newPhase = function() {
            var data = {
                title: '阶段' + ($scope.phases.length + 1)
            };
            /*设置阶段的缺省起止时间*/
            (function() {
                var nextDay = new Date(),
                    lastEndAt;
                if ($scope.phases.length) {
                    lastEndAt = 0;
                    angular.forEach($scope.phases, function(phase) {
                        if (phase.end_at > lastEndAt) {
                            lastEndAt = phase.end_at;
                        }
                    });
                    /* 最后一天的下一天 */
                    nextDay.setTime(lastEndAt * 1000 + 86400000);
                } else {
                    /* tomorrow */
                    nextDay.setTime(nextDay.getTime() + 86400000);
                }
                data.start_at = nextDay.setHours(0, 0, 0, 0) / 1000;
                data.end_at = nextDay.setHours(23, 59, 59, 0) / 1000;
            })();

            return data;
        };
        $scope.add = function() {
            var phase;
            if ($scope.numberOfNewPhases > 0) {
                phase = newPhase();
                http2.post('/rest/pl/fe/matter/mission/phase/create?mission=' + $scope.mission.id, phase, function(rsp) {
                    $scope.phases.push(rsp.data);
                    $scope.numberOfNewPhases--;
                    if ($scope.numberOfNewPhases > 0) {
                        $scope.add();
                    }
                });
            }
        };
        $scope.update = function(phase, name) {
            var modifiedData = {};
            modifiedData[name] = phase[name];
            http2.post('/rest/pl/fe/matter/mission/phase/update?mission=' + $scope.mission.id + '&id=' + phase.phase_id, modifiedData, function(rsp) {
                noticebox.success('完成保存');
            });
        };
        $scope.remove = function(phase) {
            if (window.confirm('确定删除项目阶段？')) {
                http2.get('/rest/pl/fe/matter/mission/phase/remove?mission=' + $scope.mission.id + '&id=' + phase.phase_id, function(rsp) {
                    $scope.phases.splice($scope.phases.indexOf(phase), 1);
                });
            }
        };
        $scope.$on('xxt.tms-datepicker.change', function(event, data) {
            var prop;
            if (data.state.indexOf('phase.') === 0) {
                prop = data.state.substr(6);
                data.obj[prop] = data.value;
                $scope.update(data.obj, prop);
            }
        });
        $scope.$watch('mission', function(mission) {
            if (mission) {
                $scope.phases = mission.phases;
            }
        });
    }]);
});