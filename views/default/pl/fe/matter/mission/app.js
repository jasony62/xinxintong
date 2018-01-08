define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlApp', ['$scope', '$location', 'http2', 'facListFilter', 'CstNaming', '$uibModal', function($scope, $location, http2, facListFilter, CstNaming, $uibModal) {
        var _oMission, _oCriteria, hash;
        $scope.scenarioes = CstNaming.scenario;
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
        var aUnionMatterTypes;
        aUnionMatterTypes = [];
        CstNaming.matter.appOrder.forEach(function(name) {
            if (name === 'enroll') {
                $scope.scenarioes.enrollIndex.forEach(function(scenario) {
                    aUnionMatterTypes.push({ name: 'enroll.' + scenario, label: $scope.scenarioes.enroll[scenario] });
                });
            } else {
                aUnionMatterTypes.push({ name: name, label: CstNaming.matter.app[name] });
            }
        });
        $scope.unionMatterTypes = aUnionMatterTypes;
        $scope.unionType = '';
        $scope.criteria = _oCriteria = {
            pid: 'ALL',
            filter: {}
        };
        $scope.filter = facListFilter.init(function() {
            $scope.list();
        }, _oCriteria.filter);
        $scope.addWall = function(assignedScenario) {
            location.href = '/rest/pl/fe/matter/wall/shop?site=' + _oMission.siteid + '&mission=' + _oMission.id + '&scenario=' + (assignedScenario || '');
        };
        $scope.addEnroll = function(assignedScenario) {
            location.href = '/rest/pl/fe/matter/enroll/shop?site=' + _oMission.siteid + '&mission=' + _oMission.id + '&scenario=' + (assignedScenario || '');
        };
        $scope.addSignin = function() {
            location.href = '/rest/pl/fe/matter/signin/plan?site=' + _oMission.siteid + '&mission=' + _oMission.id;
        };
        $scope.addGroup = function() {
            location.href = '/rest/pl/fe/matter/group/plan?site=' + _oMission.siteid + '&mission=' + _oMission.id;
        };
        $scope.addPlan = function() {
            location.href = '/rest/pl/fe/matter/plan/plan?site=' + _oMission.siteid + '&mission=' + _oMission.id;
        };
        $scope.addMatter = function(matterType) {
            if (!matterType) {
                matterType = $scope.matterType;
            }
            if (/^enroll.*/.test(matterType)) {
                if (matterType === 'enroll') {
                    if ($scope.matterScenario) {
                        $scope.addEnroll($scope.matterScenario);
                    }
                } else {
                    $scope.addEnroll(matterType.split('.')[1]);
                }
            } else {
                $scope['add' + matterType[0].toUpperCase() + matterType.substr(1)]();
            }
        };
        $scope.openMatter = function(matter, subView) {
            var url, type, id;
            type = matter.type || $scope.matterType;
            id = matter.id;
            if (type === 'memberschema') {
                url = '/rest/pl/fe/site/mschema?site=' + _oMission.siteid + '#' + id;
                location.href = url;
            } else {
                url = '/rest/pl/fe/matter/';
                url += type;
                if (subView) {
                    url += '/' + subView;
                }
                location.href = url + '?id=' + id + '&site=' + _oMission.siteid;
            }
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
            var type = (matter.matter_type || matter.type || $scope.matterType),
                id = (matter.matter_id || matter.id),
                siteid = matter.siteid,
                url = '/rest/pl/fe/matter/';

            evt.stopPropagation();
            if (type == 'enroll') {
                $uibModal.open({
                    templateUrl: '/views/default/pl/fe/_module/copyMatter.html?_=1',
                    controller: ['$scope', '$uibModalInstance', 'http2', function($scope2, $mi, http2) {
                        var criteria;
                        $scope2.pageOfMission = {
                            at: '1',
                            size: '5',
                            j: function() {
                                return '&page=' + this.at + '&size=' + this.size;
                            }
                        };
                        $scope2.criteria = criteria = {
                            'mission_id': '',
                            'byTitle': '',
                            'isMatterData': 'N',
                            'isMatterAction': 'N'
                        };
                        $scope2.$watch('criteria.isMatterData', function(nv) {
                            if (nv === 'Y') { criteria.isMatterAction = 'Y' };
                        });
                        $scope2.doMission = function() {
                            var url = '/rest/pl/fe/matter/mission/list?site=' + siteid + $scope2.pageOfMission.j() + '&fields=id,title',
                                params = { byTitle: criteria.byTitle };
                            http2.post(url, params, function(rsp) {
                                if (rsp.data) {
                                    $scope2.missions = rsp.data.missions;
                                    $scope2.pageOfMission.total = rsp.data.total;
                                }
                            });
                        };
                        $scope2.cleanCriteria = function() {
                            $scope2.criteria.byTitle = '';
                            $scope2.doMission();
                        }
                        $scope2.ok = function() {
                            $mi.close({
                                cpRecord: criteria.isMatterData,
                                cpEnrollee: criteria.isMatterAction,
                                mission: criteria.mission_id
                            });
                        };
                        $scope2.cancle = function() {
                            $mi.dismiss();
                        }
                        $scope2.doMission();
                    }],
                    backdrop: 'static'
                }).result.then(function(result) {
                    url += type + '/copy?site=' + siteid + '&app=' + id + '&mission=' + result.mission + '&cpRecord=' + result.cpRecord + '&cpEnrollee=' + result.cpEnrollee;
                    http2.get(url, function(rsp) {
                        location.href = '/rest/pl/fe/matter/enroll/preview?site=' + rsp.data.siteid + '&id=' + rsp.data.id;
                    });
                });
            } else {
                url += type + '/copy?app=' + id + '&site=' + _oMission.siteid + '&mission=' + _oMission.id;
                http2.get(url, function(rsp) {
                    location.href = '/rest/pl/fe/matter/' + type + '?site=' + _oMission.siteid + '&id=' + rsp.data.id;
                });
            }
        };
        $scope.list = function() {
            var url, data, matterType;
            data = {};
            if (_oCriteria.byTime) {
                data.byTime = _oCriteria.byTime;
            }
            if (_oCriteria.pid) {
                data.mission_phase_id = _oCriteria.pid;
            }
            if (_oCriteria.filter.by === 'title') {
                data.byTitle = _oCriteria.filter.keyword;
            }
            if ($scope.matterScenario !== '') {
                data.byScenario = $scope.matterScenario;
            }
            matterType = $scope.matterType;
            url = '/rest/pl/fe/matter/mission/matter/list?id=' + _oMission.id;
            if (matterType === '') {
                url += '&matterType=app';
            } else {
                url += '&matterType=' + matterType;
            }
            http2.post(url, data, function(rsp) {
                rsp.data.forEach(function(matter) {
                    matter._operator = matter.modifier_name || matter.creater_name;
                    matter._operateAt = matter.modifiy_at || matter.create_at;
                });
                $scope.matters = rsp.data;
            });
        };
        $scope.togglePublic = function(oMatter) {
            var isPublic, url;
            if (oMatter.is_public) {
                isPublic = oMatter.is_public === 'Y' ? 'N' : 'Y';
            }
            url = '/rest/pl/fe/matter/mission/matter/update?site=' + _oMission.siteid + '&id=' + _oMission.id + '&matterType=' + oMatter.type + '&matterId=' + oMatter.id;
            http2.post(url, { 'is_public': isPublic }, function(rsp) {
                oMatter.is_public = isPublic;
            });
        };
        $scope.$watch('mission', function(nv) {
            if (!nv) return;
            _oMission = nv;
            $scope.$watch('unionType', function(nv) {
                var aUnionType;
                if (nv !== undefined) {
                    aUnionType = nv.split('.');
                    $scope.matterType = aUnionType[0];
                    if (aUnionType.length === 2) {
                        $scope.matterScenario = aUnionType[1];
                    } else {
                        $scope.matterScenario = '';
                    }
                    $scope.list();
                }
            });
        });
    }]);
});