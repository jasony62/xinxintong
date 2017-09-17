define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlApp', ['$scope', '$location', 'http2', 'cstApp', function($scope, $location, http2, cstApp) {
        var _oMission, _oCriteria, hash;
        $scope.scenarioNames = cstApp.scenarioNames;
        if (hash = $location.hash()) {
            if (/,/.test(hash)) {
                hash = hash.split(',');
                $scope.matterType = hash[0];
                $scope.matterScenario = hash[1];
            } else {
                $scope.matterType = hash;
                $scope.matterScenario = null;
            }
        } else {
            $scope.matterType = '';
            $scope.matterScenario = null;
        }
        $scope.criteria = _oCriteria = {
            pid: 'ALL'
        };
        $scope.addWall = function() {
            var url = '/rest/pl/fe/matter/wall/create?mission=' + _oMission.id,
                config = {
                    proto: {
                        title: _oMission.title + '-信息墙'
                    }
                };
            http2.post(url, config, function(rsp) {
                location.href = '/rest/pl/fe/matter/wall?site=' + _oMission.siteid + '&id=' + rsp.data;
            })

        };
        $scope.addEnroll = function(assignedScenario) {
            location.href = '/rest/pl/fe/matter/enroll/shop?site=' + _oMission.siteid + '&mission=' + _oMission.id + '&scenario=' + (assignedScenario || '');
        };
        $scope.addSignin = function() {
            var url = '/rest/pl/fe/matter/signin/create?site=' + _oMission.siteid + '&mission=' + _oMission.id,
                config = {
                    proto: {
                        title: _oMission.title + '-签到'
                    }
                };
            http2.post(url, config, function(rsp) {
                location.href = '/rest/pl/fe/matter/signin?site=' + _oMission.siteid + '&id=' + rsp.data.id;
            });
        };
        $scope.addGroup = function() {
            var url = '/rest/pl/fe/matter/group/create?site=' + _oMission.siteid + '&mission=' + _oMission.id + '&scenario=split',
                config = {
                    proto: {
                        title: _oMission.title + '-分组'
                    }
                };
            http2.post(url, config, function(rsp) {
                location.href = '/rest/pl/fe/matter/group/main?site=' + _oMission.siteid + '&id=' + rsp.data.id;
            });
        };
        $scope.addMatter = function(matterType) {
            if (/quiz|voting|registration|group_week_report|score_sheet|common/.test(matterType)) {
                $scope.addEnroll(matterType);
            } else {
                $scope['add' + matterType[0].toUpperCase() + matterType.substr(1)]();
            }
        };
        $scope.openMatter = function(matter, subView) {
            var url = '/rest/pl/fe/matter/',
                type = matter.type || $scope.matterType,
                id = matter.id;
            url += type;
            if (subView) {
                url += '/' + subView;
            }
            location.href = url + '?id=' + id + '&site=' + _oMission.siteid;
        };
        $scope.removeMatter = function(evt, matter) {
            var type = matter.type || $scope.matterType,
                id = matter.id,
                title = matter.title,
                url = '/rest/pl/fe/matter/';

            evt.stopPropagation();
            if (window.confirm('确定删除：' + title + '？')) {
                url += type + '/remove?app=' + id + '&site=' + _oMission.siteid;
                http2.get(url, function(rsp) {
                    $scope.matters.splice($scope.matters.indexOf(matter), 1);
                });
            }
        };
        $scope.copyMatter = function(evt, matter) {
            var type = (matter.type || $scope.matterType),
                id = matter.id,
                url = '/rest/pl/fe/matter/';

            evt.stopPropagation();
            url += type + '/copy?app=' + id + '&site=' + _oMission.siteid + '&mission=' + _oMission.id;
            http2.get(url, function(rsp) {
                location.href = '/rest/pl/fe/matter/' + type + '?site=' + _oMission.siteid + '&id=' + rsp.data.id;
            });
        };
        $scope.chooseScenario = function(scenario) {
            if (scenario) {
                $location.hash('enroll,' + scenario);
            }
        };
        $scope.list = function() {
            var url, matterType;

            matterType = $scope.matterType;
            if (matterType === '') {
                url = '/rest/pl/fe/matter/mission/matter/list?id=' + _oMission.id;
                url += '&matterType=app';
                http2.post(url, { byTitle: _oCriteria.byTitle, mission_phase_id: _oCriteria.pid }, function(rsp) {
                    rsp.data.forEach(function(matter) {
                        matter._operator = matter.modifier_name || matter.creater_name;
                        matter._operateAt = matter.modifiy_at || matter.create_at;
                    });
                    $scope.matters = rsp.data;
                });
            } else {
                url = '/rest/pl/fe/matter/' + matterType;
                url += '/list?mission=' + _oMission.id;
                if (matterType === 'enroll' && $scope.matterScenario) {
                    url += '&scenario=' + $scope.matterScenario;
                }
                http2.post(url, { byTitle: _oCriteria.byTitle }, function(rsp) {
                    if (/enroll|signin|group/.test(matterType)) {
                        $scope.matters = rsp.data.apps;
                    } else {
                        $scope.matters = rsp.data;
                    }
                });
            }
        };
        $scope.$watch('mission', function(nv) {
            if (!nv) return;
            _oMission = nv;
            $scope.list();
        });
    }]);
});