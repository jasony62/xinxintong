define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlMain', ['$scope', '$uibModal', 'http2', 'noticebox', 'cstApp', function($scope, $uibModal, http2, noticebox, cstApp) {
        $scope.matterNames = cstApp.matterNames;
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
        $scope.openMatter = function(matter, subView) {
            var type, id, url;
            type = matter.type || matter.matter_type;
            id = matter.matter_id || matter.id;
            url = '/rest/pl/fe/matter/' + type;
            if (subView) {
                url += '/' + subView;
            }
            url += '?id=' + id + '&site=' + matter.siteid;
            location.href = url;
        };
        $scope.copyMatter = function(evt, matter) {
            var type = (matter.matter_type || matter.type || $scope.matterType),
                id = (matter.matter_id || matter.id),
                siteid = matter.siteid,
                url = '/rest/pl/fe/matter/';

            evt.stopPropagation();
            switch (type) {
                case 'article':
                    url += type + '/copy?id=' + id + '&site=' + siteid;
                    break;
                case 'enroll':
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
                                var url = '/rest/pl/fe/matter/mission/list?site=' + siteid + '&field=id,title',
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
                    break;
                case 'signin':
                case 'group':
                    url += type + '/copy?app=' + id + '&site=' + siteid;
                    break;
                default:
                    alert('指定素材不支持复制');
                    return;
            }
            if (type !== 'enroll') {
                http2.get(url).then(function(rsp) {
                    location.href = '/rest/pl/fe/matter/' + type + '?site=' + rsp.data.siteid + '&id=' + rsp.data.id;
                });
            }
        };
        $scope.$watch('frameState.sid', function(nv) {
            if (nv === '_coworker') {
                $scope.changeScope('mission');
            }
        });
    }]);
    ngApp.provider.controller('ctrlMission', ['$scope', 'http2', 'facListFilter', function($scope, http2, facListFilter) {
        var _oPage, filter2, t = (new Date() * 1);
        $scope.page = _oPage = {};
        $scope.filter2 = filter2 = {};
        $scope.openMission = function(mission, subView, matterType, scenario) {
            var url;
            url = '/rest/pl/fe/matter/mission/' + subView + '?site=' + mission.siteid + '&id=' + mission.id;
            if (subView === 'matter') {
                if (scenario) {
                    url += '#' + scenario;
                } else if (matterType) {
                    url += '#' + matterType;
                }
            }
            location.href = url;
        };
        $scope.createMission = function() {
            if ($scope.frameState.sid) {
                location.href = '/rest/pl/fe/matter/mission/plan?site=' + $scope.frameState.sid;
            }
        };
        $scope.list = function(pageAt) {
            var url;
            pageAt && (_oPage.at = pageAt);
            url = '/rest/pl/fe/matter/mission/listByUser?_=' + t;
            http2.post(url, _oCriteria, { page: _oPage }).then(function(rsp) {
                $scope.missions = rsp.data.missions;
                _oPage.total = rsp.data.total;
            });
        };
        $scope.cleanFilterTag = function() {
            _oCriteria.byTags = filter2.byTags = '';
        };
        $scope.matterTags = function() {
            $scope.matterTagsFram(_oCriteria, filter2);
        };
        var _oCriteria;
        $scope.criteria = _oCriteria = {
            orderBy: '',
            filter: {},
            bySite: '',
            byStar: 'N'
        };
        $scope.filter = facListFilter.init(null, _oCriteria.filter);
        $scope.$watch('frameState.sid', function(nv) {
            if (!nv) return;
            _oCriteria.bySite = nv;
            //$scope.getMatterTag();
        });
        $scope.$watch('criteria', function(nv) {
            if (!nv) return;
            $scope.list(1);
        }, true);
    }]);
    ngApp.provider.controller('ctrlActivity', ['$scope', '$location', 'http2', 'CstNaming', 'cstApp', '$uibModal', 'facListFilter', function($scope, $location, http2, CstNaming, cstApp, $uibModal, facListFilter) {
        var lsearch, filter2, _oPage;
        // if (window.localStorage) {
        //     $scope.$watch('filter', function(nv) {
        //         if (nv) {
        //             window.localStorage.setItem("pl.fe.activity.filter", JSON.stringify(nv));
        //         }
        //     }, true);
        //     if (filter = window.localStorage.getItem("pl.fe.activity.filter")) {
        //         filter = JSON.parse(filter);
        //     } else {
        //         filter = { byType: 'enroll' };
        //     }
        // } else {
        //     filter = { byType: 'enroll' };
        // }
        //lsearch = $location.search();
        //if (lsearch.type) {
        //    filter.byType = lsearch.type;
        //}
        $scope.filter2 = filter2 = {};
        // if (filter.byType) {
        //     filter2.byTitle = filter.byTitle;
        //     filter2.byTags = filter.byTags
        // }
        $scope.scenarioNames = CstNaming.scenario.enroll;
        $scope.page = _oPage = {};
        $scope.list = function(pageAt) {
            var url, oMatter,
                t = (new Date * 1);

            pageAt && (_oPage.at = pageAt);
            oMatter = _oCriteria.matter;
            if (_oCriteria.bySite) {
                if (oMatter.type) {
                    url = '/rest/pl/fe/matter/' + oMatter.type + '/list?site=' + _oCriteria.bySite;
                    if (oMatter.type === 'signin') {
                        url += '&cascaded=opData';
                    }
                    url += '&_=' + t;
                    http2.post(url, { byTitle: _oCriteria.filter.keyword, byStar: _oCriteria.byStar }, { page: _oPage }).then(function(rsp) {
                        $scope.matters = rsp.data.apps;
                    });
                } else {
                    url = 'rest/pl/fe/matter/bySite?site=' + _oCriteria.bySite + '&category=app';
                    url += '&_=' + t;
                    http2.post(url, { byTitle: _oCriteria.filter.keyword, byStar: _oCriteria.byStar }, { page: _oPage }).then(function(rsp) {
                        $scope.matters = rsp.data.matters;
                    });
                }
            }
        };
        var _oCriteria;
        $scope.criteria = _oCriteria = {
            matter: {},
            orderBy: '',
            filter: {},
            bySite: '',
            byStar: 'N'
        };
        $scope.filter = facListFilter.init(null, _oCriteria.filter);
        //$scope.cleanFilterTag = function() {
        //    filter.byTags = filter2.byTags = '';
        //};
        //$scope.matterTags = function() {
        //    $scope.matterTagsFram(filter, filter2);
        //};
        $scope.$watch('frameState.sid', function(nv) {
            if (!nv) return;
            _oCriteria.bySite = nv;
            //$scope.getMatterTag();
        });
        $scope.$watch('criteria', function(nv, ov) {
            if (!nv) return;
            $scope.list(1);
        }, true);
    }]);
    ngApp.provider.controller('ctrlDoc', ['$scope', '$uibModal', 'http2', 'facListFilter', function($scope, $uibModal, http2, facListFilter) {
        var _oPage, filter2;
        // if (window.localStorage) {
        //     $scope.$watch('filter', function(nv) {
        //         if (nv) {
        //             window.localStorage.setItem("pl.fe.info.filter", JSON.stringify(nv));
        //         }
        //     }, true);
        //     if (filter = window.localStorage.getItem("pl.fe.info.filter")) {
        //         filter = JSON.parse(filter);
        //     } else {
        //         filter = { byType: 'article' };
        //     }
        // } else {
        //     filter = { byType: 'article' };
        // }
        $scope.filter2 = filter2 = {};
        // if (filter.byType) {
        //     filter2.byTitle = filter.byTitle;
        //     filter2.byTags = filter.byTags
        // }
        $scope.page = _oPage = {};
        $scope.list = function() {
            var url,
                t = (new Date * 1);

            if (_oCriteria.bySite) {
                if (_oCriteria.matter.type) {
                    url = '/rest/pl/fe/matter/' + _oCriteria.matter.type + '/list?site=' + _oCriteria.bySite + '&_=' + t;
                    _oCriteria.matter.type == 'channel' && (url += '&cascade=N');
                    http2.post(url, { byTitle: _oCriteria.filter.keyword, byStar: _oCriteria.byStar }, { page: _oPage }).then(function(rsp) {
                        $scope.matters = rsp.data.docs || rsp.data.apps;
                    });
                } else {
                    url = '/rest/pl/fe/matter/bySite?site=' + _oCriteria.bySite + '&category=doc&_=' + t;
                    _oCriteria.matter.type == 'channel' && (url += '&cascade=N');
                    http2.post(url, { byTitle: _oCriteria.filter.keyword, byStar: _oCriteria.byStar }, { page: _oPage }).then(function(rsp) {
                        $scope.matters = rsp.data.matters;
                    });
                }
            }
        };
        $scope.matterTags = function() {
            $scope.matterTagsFram(filter, filter2);
        };
        $scope.cleanFilterTag = function() {
            filter.byTags = filter2.byTags = '';
        };
        var _oCriteria;
        $scope.criteria = _oCriteria = {
            matter: { type: '' },
            orderBy: '',
            filter: {},
            bySite: '',
            byStar: 'N'
        };
        $scope.filter = facListFilter.init(null, _oCriteria.filter);
        $scope.$watch('frameState.sid', function(nv) {
            if (!nv) return;
            _oCriteria.bySite = nv;
            //$scope.getMatterTag();
        });
        $scope.$watch('criteria', function(nv) {
            if (!nv) return;
            $scope.list(1);
        }, true);
    }]);
    ngApp.provider.controller('ctrlSiteSubscribe', ['$scope', '$uibModal', 'http2', 'facListFilter', function($scope, $uibModal, http2, facListFilter) {
        var _oFilter, _oPage;
        _oFilter = {};
        $scope.page = _oPage = {
            at: 1,
            size: 30,
        };
        $scope.filter = facListFilter.init(function() {
            $scope.doSearch(1);
        }, _oFilter);
        $scope.doSearch = function(pageAt) {
            var url, data;
            pageAt && ($scope.page.at = pageAt);
            url = '/rest/pl/fe/site/subscriberList';
            url += '?site=' + $scope.frameState.sid;
            url += '&category=client';
            data = {};
            if (_oFilter.by === 'nickname' && _oFilter.keyword) {
                data.nickname = _oFilter.keyword;
            }
            http2.post(url, data, { page: _oPage }).then(function(rsp) {
                $scope.users = rsp.data.subscribers;
                _oPage.total = rsp.data.total;
            });
        };
        $scope.$watch('frameState.sid', function(sid) {
            if (sid) {
                $scope.doSearch(1);
            }
        });
        $scope.$on('site.user.refresh', function() {
            $scope.doSearch();
        });
    }]);
    ngApp.provider.controller('ctrlRecycle', ['$scope', 'http2', 'facListFilter', function($scope, http2, facListFilter) {
        var _oFilter, t = (new Date * 1);
        _oFilter = {};
        $scope.filter = facListFilter.init(function() {
            $scope.list();
        }, _oFilter);
        $scope.list = function() {
            if ($scope.frameState.sid) {
                var url, data;
                url = '/rest/pl/fe/site/console/recycle?site=' + $scope.frameState.sid + '&_=' + t;
                data = {};
                if (_oFilter.by === 'title' && _oFilter.keyword) {
                    data.byTitle = _oFilter.keyword;
                }
                http2.post(url, data).then(function(rsp) {
                    $scope.matters = rsp.data.matters;
                });
            }
        };
        $scope.restoreSite = function(site) {
            //恢复删除的站点
            var url = '/rest/pl/fe/site/recover?site=' + site.id;
            http2.get(url).then(function(rsp) {
                location.href = '/rest/pl/fe/site?site=' + site.id;
            })
        };
        $scope.restoreMatter = function(oMatter) {
            var url;
            if (oMatter.matter_type === 'memberschema') {
                url = '/rest/pl/fe/site/member/schema/restore' + '?site=' + oMatter.siteid + '&id=' + oMatter.matter_id;
                http2.get(url).then(function(rsp) {
                    location.href = '/rest/pl/fe/site/mschema?site=' + oMatter.siteid + '#' + oMatter.matter_id;
                });
            } else {
                url = '/rest/pl/fe/matter/' + oMatter.matter_type + '/restore' + '?site=' + oMatter.siteid + '&id=' + oMatter.matter_id;
                http2.get(url).then(function(rsp) {
                    location.href = '/rest/pl/fe/matter/' + oMatter.matter_type + '?site=' + oMatter.siteid + '&id=' + oMatter.matter_id;
                });
            }
        };
        $scope.list();
    }]);
});