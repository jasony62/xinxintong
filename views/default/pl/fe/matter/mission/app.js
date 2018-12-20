define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlApp', ['$scope', 'http2', 'facListFilter', 'CstNaming', '$uibModal', function($scope, http2, facListFilter, CstNaming, $uibModal) {
        var _oMission, _oCriteria, hash;
        $scope.scenarioes = CstNaming.scenario;
        $scope.matterNames = CstNaming.matter;
        $scope.criteria = _oCriteria = {
            byStar: 'N',
            pid: 'ALL',
            filter: {},
            matterType: ''
        };
        $scope.filter = facListFilter.init(function() {
            $scope.list();
        }, _oCriteria.filter);
        $scope.addWall = function(assignedScenario) {
            location.href = '/rest/pl/fe/matter/wall/shop?site=' + _oMission.siteid + '&mission=' + _oMission.id;
        };
        $scope.addEnroll = function() {
            location.href = '/rest/pl/fe/matter/enroll/shop?site=' + _oMission.siteid + '&mission=' + _oMission.id;
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
        $scope.addMemberschema = function() {
            var url, proto;
            url = '/rest/pl/fe/site/member/schema/create?site=' + $scope.mission.siteid;
            proto = { valid: 'Y', matter_id: _oMission.id, matter_type: _oMission.type, title: _oMission.title + '-通讯录' };
            http2.post(url, proto).then(function(rsp) {
                location.href = '/rest/pl/fe/site/mschema?site=' + _oMission.siteid + '#' + rsp.data.id;
            });
        };
        $scope.addMatter = function(matterType) {
            if (!matterType) {
                matterType = _oCriteria.matterType;
            }
            $scope['add' + matterType[0].toUpperCase() + matterType.substr(1)]();
        };
        $scope.openMatter = function(oMatter, subView) {
            var url, type, id;
            type = oMatter.type || _oCriteria.matterType;
            id = oMatter.id;
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
        $scope.toggleStar = function(oMatter) {
            var url;
            if (oMatter.star) {
                if (oMatter.id && oMatter.type) {
                    url = '/rest/pl/fe/delTop?site=' + oMatter.siteid + '&id=' + oMatter.id + '&type=' + oMatter.type;
                    http2.get(url).then(function(rsp) {
                        delete oMatter.star;
                    });
                }
            } else {
                if (oMatter.id && oMatter.type) {
                    url = '/rest/pl/fe/top?site=' + oMatter.siteid + '&matterId=' + oMatter.id + '&matterType=' + oMatter.type + '&matterTitle=' + oMatter.title;
                    http2.get(url).then(function(rsp) {
                        oMatter.star = rsp.data;
                    });
                }
            }
        };
        $scope.removeMatter = function(evt, matter) {
            var type = matter.type || _oCriteria,
                id = matter.id,
                title = matter.title,
                url = '/rest/pl/fe/matter/';

            evt.stopPropagation();
            if (window.confirm('确定删除：' + title + '？')) {
                url += type + '/remove?app=' + id + '&site=' + _oMission.siteid;
                http2.get(url).then(function(rsp) {
                    $scope.matters.splice($scope.matters.indexOf(matter), 1);
                });
            }
        };
        $scope.copyMatter = function(evt, matter) {
            var type = (matter.matter_type || matter.type || _oCriteria.matterType),
                id = (matter.matter_id || matter.id),
                siteid = matter.siteid,
                url = '/rest/pl/fe/matter/';

            evt.stopPropagation();
            if (type == 'enroll') {
                $uibModal.open({
                    templateUrl: '/views/default/pl/fe/_module/copyMatter.html?_=3',
                    controller: ['$scope', '$uibModalInstance', 'http2', function($scope2, $mi, http2) {
                        var criteria;
                        $scope2.pageOfMission = {};
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
                            var url = '/rest/pl/fe/matter/mission/list?site=' + siteid + '&fields=id,title',
                                params = { byTitle: criteria.byTitle };
                            http2.post(url, params, { page: $scope2.pageOfMission }).then(function(rsp) {
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
                        $scope2.cancel = function() {
                            $mi.dismiss();
                        }
                        $scope2.doMission();
                    }],
                    backdrop: 'static'
                }).result.then(function(result) {
                    url += type + '/copy?site=' + siteid + '&app=' + id + '&mission=' + result.mission + '&cpRecord=' + result.cpRecord + '&cpEnrollee=' + result.cpEnrollee;
                    http2.get(url).then(function(rsp) {
                        location.href = '/rest/pl/fe/matter/enroll/preview?site=' + rsp.data.siteid + '&id=' + rsp.data.id;
                    });
                });
            } else {
                url += type + '/copy?app=' + id + '&site=' + _oMission.siteid + '&mission=' + _oMission.id;
                http2.get(url).then(function(rsp) {
                    location.href = '/rest/pl/fe/matter/' + type + '?site=' + _oMission.siteid + '&id=' + rsp.data.id;
                });
            }
        };
        $scope.list = function() {
            var url, data;
            data = {};
            if (_oCriteria.byTime) {
                data.byTime = _oCriteria.byTime;
            }
            if (_oCriteria.byStar) {
                data.byStar = _oCriteria.byStar;
            }
            if (_oCriteria.filter.by === 'title') {
                data.byTitle = _oCriteria.filter.keyword;
            }
            url = '/rest/pl/fe/matter/mission/matter/list?id=' + _oMission.id;
            url += '&matterType=' + (_oCriteria.matterType === '' ? 'app' : _oCriteria.matterType);
            http2.post(url, data).then(function(rsp) {
                rsp.data.forEach(function(oMatter) {
                    oMatter._operator = oMatter.modifier_name || oMatter.creater_name;
                    oMatter._operateAt = oMatter.modifiy_at || oMatter.create_at;
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
            http2.post(url, { 'is_public': isPublic }).then(function(rsp) {
                oMatter.is_public = isPublic;
            });
        };
        $scope.$watch('mission', function(nv) {
            if (!nv) return;
            _oMission = nv;
            $scope.$watch('criteria', function(nv) {
                $scope.list();
            }, true);
        });
    }]);
});