define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlMain', ['$scope', '$uibModal', 'http2', 'noticebox', 'cstApp', function($scope, $uibModal, http2, noticebox, cstApp) {
        $scope.matterNames = cstApp.matterNames;
        $scope.toggleStar = function(oMatter) {
            var url;
            if (oMatter.star) {
                if (oMatter.id && oMatter.type) {
                    url = '/rest/pl/fe/delTop?site=' + oMatter.siteid + '&id=' + oMatter.id + '&type=' + oMatter.type;
                    http2.get(url, function(rsp) {
                        delete oMatter.star;
                    });
                }
            } else {
                if (oMatter.id && oMatter.type) {
                    url = '/rest/pl/fe/top?site=' + oMatter.siteid + '&matterId=' + oMatter.id + '&matterType=' + oMatter.type + '&matterTitle=' + oMatter.title;
                    http2.get(url, function(rsp) {
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
                        templateUrl: 'copyMatter.html',
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
                                'isMatterData': 'N',
                                'isMatterAction': 'N'
                            };
                            $scope2.$watch('criteria.isMatterData', function(nv) {
                                if(nv==='Y') {criteria.isMatterAction='Y'};
                            });
                            $scope2.doMission = function() {
                                var url = '/rest/pl/fe/matter/mission/list?site=' + siteid + $scope2.pageOfMission.j();
                                http2.get(url, function(rsp) {
                                    if(rsp.data) {
                                        $scope2.missions = rsp.data.missions;
                                        $scope2.pageOfMission.total = rsp.data.total;
                                    }
                                });
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
                        url += type + '/copy?site=' + siteid + '&app=' + id +'&mission=' + result.mission + '&cpRecord=' + result.cpRecord + '&cpEnrollee=' + result.cpEnrollee;
                        http2.get(url, function(rsp) {
                            location.href = '/rest/pl/fe/matter/enroll/preview?site=' + rsp.data.siteid + '&id=' + rsp.data.id;
                        });
                    });
                    break;
                case 'signin':
                case 'wall':
                case 'group':
                    url += type + '/copy?app=' + id + '&site=' + siteid;
                    break;
                default:
                    alert('指定素材不支持复制');
                    return;
            }
            if(type !== 'enroll') {
                http2.get(url, function(rsp) {
                    location.href = '/rest/pl/fe/matter/' + type + '?site=' + rsp.data.siteid + '&id=' + rsp.data.id;
                });
            }
        };
    }]);
    ngApp.provider.controller('ctrlMission', ['$scope', 'http2', 'facListFilter', function($scope, http2, facListFilter) {
        var _oPage, filter2, t = (new Date() * 1);
        $scope.page = _oPage = {
            at: 1,
            size: 12,
            j: function() {
                return 'page=' + this.at + '&size=' + this.size;
            }
        };
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
            url = '/rest/pl/fe/matter/mission/listByUser?_=' + t + '&' + _oPage.j();
            http2.post(url, _oCriteria, function(rsp) {
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
            _oCriteria.bySite = nv;
            //$scope.getMatterTag();
            $scope.$watch('criteria', function(nv) {
                if (!nv) return;
                $scope.list(1);
            }, true);
        });
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
        var aUnionMatterTypes;
        aUnionMatterTypes = [];
        cstApp.matterNames.appOrder.forEach(function(name) {
            if (name === 'enroll') {
                CstNaming.scenario.enrollIndex.forEach(function(scenario) {
                    aUnionMatterTypes.push({ name: 'enroll.' + scenario, label: CstNaming.scenario.enroll[scenario] });
                });
            } else {
                aUnionMatterTypes.push({ name: name, label: cstApp.matterNames.app[name] });
            }
        });
        $scope.unionMatterTypes = aUnionMatterTypes;
        $scope.scenarioNames = CstNaming.scenario.enroll;
        $scope.page = _oPage = {
            at: 1,
            size: 12,
            j: function() {
                return 'page=' + this.at + '&size=' + this.size;
            }
        };
        $scope.list = function(pageAt) {
            var url, oMatter,
                t = (new Date * 1);

            pageAt && (_oPage.at = pageAt);
            oMatter = _oCriteria.matter;
            if (_oCriteria.bySite) {
                if (oMatter.type) {
                    url = '/rest/pl/fe/matter/' + oMatter.type + '/list?site=' + _oCriteria.bySite;
                    if (oMatter.type === 'enroll') {
                        url += '&scenario=' + oMatter.scenario;
                    } else if (oMatter.type === 'signin') {
                        url += '&cascaded=opData';
                    }
                    url += '&' + _oPage.j() + '&_=' + t;
                    http2.post(url, { byTitle: _oCriteria.filter.keyword, byStar: _oCriteria.byStar }, function(rsp) {
                        $scope.matters = rsp.data.apps;
                        _oPage.total = rsp.data.total;
                    });
                } else {
                    url = '/rest/pl/fe/matter/bySite?site=' + _oCriteria.bySite + '&category=app';
                    url += '&' + _oPage.j() + '&_=' + t;
                    http2.post(url, { byTitle: _oCriteria.filter.keyword, byStar: _oCriteria.byStar }, function(rsp) {
                        $scope.matters = rsp.data.matters;
                        _oPage.total = rsp.data.total;
                    });
                }
            }
        };
        var _oCriteria;
        $scope.unionType = '';
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
            _oCriteria.bySite = nv;
            //$scope.getMatterTag();
            $scope.$watch('unionType', function(nv) {
                var aUnionType;
                if (nv) {
                    aUnionType = nv.split('.');
                    _oCriteria.matter.type = aUnionType[0];
                    if (aUnionType.length === 2) {
                        _oCriteria.matter.scenario = aUnionType[1];
                    } else {
                        delete _oCriteria.matter.scenario;
                    }
                } else {
                    _oCriteria.matter.type = '';
                    _oCriteria.matter.scenario = '';
                }
            });
            $scope.$watch('criteria', function(nv, ov) {
                if (!nv) return;
                $scope.list(1);
            }, true);
        });
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
        $scope.page = _oPage = {
            at: 1,
            size: 12,
            j: function() {
                return 'page=' + this.at + '&size=' + this.size;
            }
        };
        $scope.list = function() {
            var url,
                t = (new Date * 1);

            if (_oCriteria.bySite) {
                if (_oCriteria.matter.type) {
                    url = '/rest/pl/fe/matter/' + _oCriteria.matter.type + '/list?site=' + _oCriteria.bySite + '&' + _oPage.j() + '&_=' + t;
                    _oCriteria.matter.type == 'channel' && (url += '&cascade=N');
                    http2.post(url, { byTitle: _oCriteria.filter.keyword, byStar: _oCriteria.byStar }, function(rsp) {
                        $scope.matters = rsp.data.docs || rsp.data.apps;
                        _oPage.total = rsp.data.total;
                    });
                } else {
                    url = '/rest/pl/fe/matter/bySite?site=' + _oCriteria.bySite + '&category=doc&' + _oPage.j() + '&_=' + t;
                    _oCriteria.matter.type == 'channel' && (url += '&cascade=N');
                    http2.post(url, { byTitle: _oCriteria.filter.keyword, byStar: _oCriteria.byStar }, function(rsp) {
                        $scope.matters = rsp.data.matters;
                        _oPage.total = rsp.data.total;
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
            _oCriteria.bySite = nv;
            //$scope.getMatterTag();
            $scope.$watch('criteria', function(nv) {
                if (!nv) return;
                $scope.list(1);
            }, true);
        });
    }]);
    ngApp.provider.controller('ctrlUser', ['$scope', '$location', 'http2', function($scope, $location, http2) {
        var oSelected;
        $scope.selected = oSelected = {
            mschema: null
        };
        $scope.catelog = 'member';
        $scope.createMschema = function() {
            var url;
            if ($scope.frameState.sid) {
                url = '/rest/pl/fe/site/member/schema/create?site=' + $scope.frameState.sid;
                http2.post(url, { valid: 'Y' }, function(rsp) {
                    location.href = '/rest/pl/fe/site/mschema?site=' + $scope.frameState.sid + '#' + rsp.data.id;
                });
            }
        };
        $scope.$watch('frameState.sid', function(siteId) {
            if (siteId) {
                http2.get('/rest/pl/fe/site/member/schema/list?site=' + siteId, function(rsp) {
                    $scope.mschemas = rsp.data;
                    if ($scope.mschemas.length) {
                        if ($location.search().mschema) {
                            for (var i in $scope.mschemas) {
                                if ($scope.mschemas[i].id == $location.search().mschema) {
                                    oSelected.mschema = $scope.mschemas[i];
                                    break;
                                }
                            }
                        } else {
                            oSelected.mschema = $scope.mschemas[0];
                        }
                        //$scope.chooseMschema();
                    }
                });
            } else {
                $scope.mschemas = [];
                oSelected.mschema = null;
            }
        });
    }]);
    ngApp.provider.controller('ctrlMember', ['$scope', '$location', '$uibModal', 'http2', 'facListFilter', function($scope, $location, $uibModal, http2, facListFilter) {
        function listInvite(oSchema) {
            http2.get('/rest/pl/fe/site/member/invite/list?schema=' + oSchema.id, function(rsp) {
                $scope.invites = rsp.data.invites;
            });
        }
        var _oMschema, _oCriteria;
        _oCriteria = {
            filter: { by: '', keyword: '' }
        };
        $scope.filter = facListFilter.init(function() {
            $scope.doSearch(1);
        }, _oCriteria.filter);
        $scope.$parent.$watch('selected.mschema', function(oMschema) {
            if (oMschema) {
                $scope.page = {
                    at: 1,
                    size: 30,
                };
                $scope.mschema = _oMschema = oMschema;
                $scope.doSearch(1);
                listInvite(oMschema);
            }
        });
        $scope.doSearch = function(pageAt) {
            var url, filter = '';
            pageAt && ($scope.page.at = pageAt);
            if (_oCriteria.filter.by && _oCriteria.filter.keyword) {
                filter = '&kw=' + _oCriteria.filter.keyword;
                filter += '&by=' + _oCriteria.filter.by;
            }
            url = '/rest/pl/fe/site/member/list?site=' + $scope.frameState.sid + '&schema=' + _oMschema.id;
            url += '&page=' + $scope.page.at + '&size=' + $scope.page.size + filter
            url += '&contain=total';
            http2.get(url, function(rsp) {
                var i, member, members = rsp.data.members;
                for (i in members) {
                    member = members[i];
                    if (member.extattr) {
                        try {
                            member.extattr = JSON.parse(member.extattr);
                        } catch (e) {
                            member.extattr = {};
                        }
                    }
                }
                $scope.members = members;
                $scope.page.total = rsp.data.total;
            });
        };
        $scope.editMember = function(member) {
            $uibModal.open({
                templateUrl: '/views/default/pl/fe/_module/memberEditor.html?_=1',
                backdrop: 'static',
                resolve: {
                    schema: function() {
                        return angular.copy(_oMschema);
                    }
                },
                controller: ['$uibModalInstance', '$scope', 'schema', function($mi, $scope, schema) {
                    $scope.schema = schema;
                    $scope.member = angular.copy(member);
                    $scope.canShow = function(name) {
                        return schema && schema['attr_' + name].charAt(0) === '0';
                    };
                    $scope.close = function() {
                        $mi.dismiss();
                    };
                    $scope.ok = function() {
                        $mi.close({
                            action: 'update',
                            data: $scope.member
                        });
                    };
                    $scope.remove = function() {
                        $mi.close({
                            action: 'remove'
                        });
                    };
                }]
            }).result.then(function(rst) {
                if (rst.action === 'update') {
                    var data = rst.data,
                        newData = {
                            verified: data.verified,
                            name: data.name,
                            mobile: data.mobile,
                            email: data.email,
                            email_verified: data.email_verified,
                            extattr: data.extattr
                        },
                        i, ea;
                    for (i in $scope.mschema.extattr) {
                        ea = $scope.mschema.extattr[i];
                        newData[ea.id] = rst.data[ea.id];
                    }
                    http2.post('/rest/pl/fe/site/member/update?site=' + $scope.frameState.sid + '&id=' + member.id, newData, function(rsp) {
                        angular.extend(member, newData);
                    });
                } else if (rst.action === 'remove') {
                    http2.get('/rest/pl/fe/site/member/remove?site=' + $scope.frameState.sid + '&id=' + member.id, function() {
                        $scope.members.splice($scope.members.indexOf(member), 1);
                    });
                }
            });
        };
    }]);
    ngApp.provider.controller('ctrlSiteAccount', ['$scope', '$uibModal', 'http2', 'facListFilter', 'srvSite', function($scope, $uibModal, http2, facListFilter, srvSite) {
        var _oFilter, _oPage;
        _oFilter = {};
        $scope.page = _oPage = {
            at: 1,
            size: 30,
        };
        $scope.filter = facListFilter.init(function() {
            $scope.doSearch(1);
        }, _oFilter);
        $scope.openProfile = function(oUser) {
            location.href = '/rest/pl/fe/site/user?site=' + $scope.frameState.sid + '&uid=' + oUser.uid + '&unionid=' + oUser.unionid;
        };
        $scope.doSearch = function(pageAt) {
            var url, data;
            pageAt && ($scope.page.at = pageAt);
            url = '/rest/pl/fe/site/user/account/list';
            url += '?site=' + $scope.frameState.sid;
            url += '&page=' + _oPage.at + '&size=' + _oPage.size;
            data = {};
            if (_oFilter.by === 'nickname' && _oFilter.keyword) {
                data.nickname = _oFilter.keyword;
            }
            http2.post(url, data, function(rsp) {
                $scope.users = rsp.data.users;
                _oPage.total = rsp.data.total;
            });
        };
        $scope.$watch('frameState.sid', function(sid) {
            if (sid) {
                $scope.doSearch(1);
                srvSite.snsList(sid).then(function(aSns) {
                    $scope.sns = aSns;
                });
            }
        });
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
            url = '/rest/pl/fe/site/subscriberList';
            pageAt && ($scope.page.at = pageAt);
            url += '?site=' + $scope.frameState.sid;
            url += '&category=client';
            url += '&page=' + _oPage.at + '&size=' + _oPage.size;
            data = {};
            if (_oFilter.by === 'nickname' && _oFilter.keyword) {
                data.nickname = _oFilter.keyword;
            }
            http2.post(url, data, function(rsp) {
                $scope.users = rsp.data.subscribers;
                _oPage.total = rsp.data.total;
            });
        };
        $scope.doSearch(1);
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
                http2.post(url, data, function(rsp) {
                    $scope.matters = rsp.data.matters;
                });
            }
        };
        $scope.restoreSite = function(site) {
            //恢复删除的站点
            var url = '/rest/pl/fe/site/recover?site=' + site.id;
            http2.get(url, function(rsp) {
                location.href = '/rest/pl/fe/site?site=' + site.id;
            })
        };
        $scope.restoreMatter = function(matter) {
            var url = '/rest/pl/fe/matter/' + matter.matter_type + '/restore' + '?site=' + matter.siteid + '&id=' + matter.matter_id;
            http2.get(url, function(rsp) {
                location.href = '/rest/pl/fe/matter/' + matter.matter_type + '?site=' + matter.siteid + '&id=' + matter.matter_id;
            });
        };
        $scope.list();
    }]);
});