define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlMain', ['$scope', '$uibModal', 'templateShop', 'http2', 'noticebox', 'cstApp', function($scope, $uibModal, templateShop, http2, noticebox, cstApp) {
        $scope.matterNames = cstApp.matterNames;
        $scope.stickTop = function(m) {
            var url;
            if (!m.matter_type && !m.mission_id) {
                url = '/rest/pl/fe/top?site=' + m.id + '&matterId=' + m.id + '&matterType=site' + '&matterTitle=' + m.name;
            } else if (!m.matter_type && m.mission_id) {
                url = '/rest/pl/fe/top?site=' + m.siteid + '&matterId=' + m.mission_id + '&matterType=mission' + '&matterTitle=' + m.title;
            } else {
                url = '/rest/pl/fe/top?site=' + m.siteid + '&matterId=' + m.matter_id + '&matterType=' + m.matter_type + '&matterTitle=' + m.matter_title;
            }
            http2.get(url, function(rsp) {
                noticebox.success('完成置顶');
                $scope.$emit('fromCtrlRecentStickTop', m);
            })
        };
        $scope.openMatter = function(matter, subView) {
            var type, id;
            if ($scope.frameState.sid == '') {
                type = matter.matter_type;
                id = matter.matter_id;
            } else {
                type = matter.type;
                id = matter.id;
            }

            var url = '/rest/pl/fe/matter/' + type;
            if (subView) {
                url += '/' + subView;
            }
            url += '?id=' + id + '&site=' + matter.siteid;
            location.href = url;
        };
        $scope.setHome = function(site) {
            location.href = '/rest/pl/fe/site/home?site=' + site.siteid;
        };
        $scope.openConsole = function(site) {
            location.href = '/rest/pl/fe/site?site=' + site.siteid;
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
                case 'signin':
                case 'wall':
                case 'group':
                    url += type + '/copy?app=' + id + '&site=' + siteid;
                    break;
                default:
                    alert('指定素材不支持复制');
                    return;
            }
            http2.get(url, function(rsp) {
                if (type === 'enroll') {
                    location.href = '/rest/pl/fe/matter/enroll/preview?site=' + rsp.data.siteid + '&id=' + rsp.data.id;
                } else {
                    location.href = '/rest/pl/fe/matter/' + type + '?site=' + rsp.data.siteid + '&id=' + rsp.data.id;
                }
            });
        };
        $scope.$on('fromCtrlRecentStickTop', function(event, data) {
            $scope.$broadcast('toCtrlTopList', data);
        });
    }]);
    ngApp.provider.controller('ctrlTop', ['$scope', 'http2', 'noticebox', function($scope, http2, noticebox) {
        var page;
        $scope.page = page = {
            at: 1,
            size: 9,
            j: function() {
                return 'page=' + this.at + '&size=' + this.size;
            }
        };
        $scope.list = function(sid) {
            var url;
            if (sid) {
                url = '/rest/pl/fe/topList?' + page.j() + '&site=' + sid;
            } else {
                url = '/rest/pl/fe/topList?' + page.j();
            }
            http2.get(url, function(rsp) {
                $scope.top = rsp.data.matters;
                $scope.page.total = rsp.data.total;
            })
        };
        $scope.removeTop = function(t, i) {
            var url = '/rest/pl/fe/delTop?site=' + t.siteid + '&id=' + t.matter_id + '&type=' + t.matter_type;
            http2.get(url, function(rsp) {
                $scope.top.splice(i, 1);
                $scope.page.total--;
                noticebox.success('完成')
            })
        };
        $scope.$on('toCtrlTopList', function(event, data) {
            //数据不完全一致，直接调用接口刷新
            $scope.list();
        });
        $scope.$watch('frameState.sid', function(nv) {
            $scope.list(nv);
        }, true);
    }])
    ngApp.provider.controller('ctrlRecent', ['$scope', 'http2', function($scope, http2, noticebox) {
        var url, page, filter;
        $scope.filter = filter = {};
        $scope.page = page = {
            at: 1,
            size: 12,
            j: function() {
                return 'page=' + this.at + '&size=' + this.size;
            }
        };
        $scope.matterType = '';
        $scope.list = function(pageAt) {
            var url = '/rest/pl/fe/recent?' + page.j();
            if (pageAt) {
                page.at = pageAt;
            }
            http2.post(url, filter, function(rsp) {
                $scope.matters = rsp.data.matters;
                $scope.page.total = rsp.data.total;
            });
        };
        $scope.$watch('frameState.sid', function(nv) {
            angular.extend(filter, { bySite: nv });
        });
        $scope.$watch('filter', function(nv) {
            if (!nv) return;
            $scope.list();
        }, true);
    }]);
    ngApp.provider.controller('ctrlSite', ['$scope', 'http2', function($scope, http2) {
        var t = (new Date() * 1),
            filter, filter2;
        $scope.filter = filter = {};
        $scope.filter2 = filter2 = {};
        $scope.create = function() {
            var url = '/rest/pl/fe/site/create?_=' + t;
            http2.get(url, function(rsp) {
                location.href = '/rest/pl/fe/site/setting?site=' + rsp.data.id;
            });
        };
        $scope.list = function() {
            var url = '/rest/pl/fe/site/list?_=' + t;
            http2.post(url, filter, function(rsp) {
                $scope.sites = rsp.data;
            });
        };
        $scope.setHome = function(site) {
            location.href = '/rest/pl/fe/site/home?site=' + site.id;
        };
        $scope.openConsole = function(site) {
            location.href = '/rest/pl/fe/site?site=' + site.id;
        };
        $scope.doFilter = function() {
            angular.extend(filter, filter2);
        };
        $scope.cleanFilter = function() {
            filter.byTitle = filter2.byTitle = '';
        };
        $scope.$watch('frameState.sid', function(nv) {
            angular.extend(filter, { bySite: nv });
        });
        $scope.$watch('filter', function(nv) {
            if (!nv) return;
            $scope.list();
        }, true);
    }]);
    ngApp.provider.controller('ctrlMission', ['$scope', 'http2', '$uibModal', function($scope, http2, $uibModal) {
        var page, filter, filter2, t = (new Date() * 1);
        $scope.page = page = {
            at: 1,
            size: 12,
            j: function() {
                return 'page=' + this.at + '&size=' + this.size;
            }
        };
        $scope.filter = filter = {};
        $scope.filter2 = filter2 = {};
        $scope.missionAddMatter = function() {
            var target = $('#missionAddMatter');
            if (target.data('popover') === 'Y') {
                target.trigger('hide').data('popover', 'N');
            } else {
                target.trigger('show').data('popover', 'Y');
            }
        };
        $scope.open = function(mission, subView) {
            location.href = '/rest/pl/fe/matter/mission/' + subView + '?site=' + mission.siteid + '&id=' + mission.mission_id;
        };
        $scope.create = function() {
            var url = '/rest/pl/fe/matter/mission/create?site=' + $scope.frameState.sid;
            http2.get(url, function(rsp) {
                location.href = '/rest/pl/fe/matter/mission?site=' + rsp.data.siteid + '&id=' + rsp.data.id;
            });
        };
        $scope.listSite = function() {
            var url = '/rest/pl/fe/matter/mission/listSite?_=' + t;
            http2.get(url, function(rsp) {
                $scope.missionSites = rsp.data.sites;
            });
        };
        $scope.list = function() {
            var url = '/rest/pl/fe/matter/mission/listByUser?_=' + t + '&' + page.j();
            http2.post(url, filter, function(rsp) {
                $scope.missions = rsp.data.missions;
                $scope.page.total = rsp.data.total;
            });
        };
        $scope.doFilter = function() {
            angular.extend(filter, filter2);
        };
        $scope.cleanFilter = function() {
            filter.byTitle = filter2.byTitle = '';
        };
        $scope.cleanFilterTag = function() {
            filter.byTags = filter2.byTags = '';
        };
        $scope.$watch('frameState.sid', function(nv) {
            angular.extend(filter, { bySite: nv });
            $scope.getMatterTag();
        });
        $scope.$watch('filter', function(nv) {
            if (!nv) return;
            $scope.list();
        }, true);
        $scope.matterTags = function() {
            $scope.matterTagsFram(filter, filter2);
        };
        $scope.listSite();
    }]);
    ngApp.provider.controller('ctrlActivity', ['$scope', '$location', 'http2', 'cstApp', '$uibModal', function($scope, $location, http2, cstApp, $uibModal) {
        var lsearch, filter, filter2, page;
        if (window.localStorage) {
            $scope.$watch('filter', function(nv) {
                if (nv) {
                    window.localStorage.setItem("pl.fe.activity.filter", JSON.stringify(nv));
                }
            }, true);
            if (filter = window.localStorage.getItem("pl.fe.activity.filter")) {
                filter = JSON.parse(filter);
            } else {
                filter = { byType: 'enroll' };
            }
        } else {
            filter = { byType: 'enroll' };
        }
        lsearch = $location.search();
        if (lsearch.type) {
            filter.byType = lsearch.type;
        }
        $scope.filter = filter;
        $scope.filter2 = filter2 = {};
        if (filter.byType) {
            filter2.byTitle = filter.byTitle;
            filter2.byTags = filter.byTags
        }
        $scope.scenarioNames = cstApp.scenarioNames;
        $scope.changeMatter = function(type) {
            filter.byType = type;
            filter.scenario = '';
        };
        $scope.page = page = {
            at: 1,
            size: 12,
            j: function() {
                return 'page=' + this.at + '&size=' + this.size;
            }
        };
        $scope.list = function(pageAt) {
            var url = '/rest/pl/fe/recent?' + page.j(),
                t = (new Date() * 1),
                url2;
            if (pageAt) {
                page.at = pageAt;
            }
            if (filter.bySite == '') {
                http2.post(url, filter, function(rsp) {
                    $scope.matters = rsp.data.matters;
                    $scope.page.total = rsp.data.total;
                });
            } else {
                if (filter.scenario !== '') {
                    url2 = '/rest/pl/fe/matter/' + filter.byType + '/list?site=' + filter.bySite + '&scenario=' + filter.scenario + '&' + page.j() + '&_=' + t;
                } else {
                    url2 = '/rest/pl/fe/matter/' + filter.byType + '/list?site=' + filter.bySite + '&' + page.j() + '&_=' + t;
                }
                http2.post(url2, { byTitle: filter.byTitle, byTags: filter.byTags }, function(rsp) {
                    if (rsp.data.apps === null) {
                        $scope.matters = [];
                    } else {
                        $scope.matters = rsp.data.apps || rsp.data;
                    }
                    $scope.page.total = rsp.data.total;
                });
            }
        };
        $scope.doFilter = function() {
            angular.extend(filter, filter2);
        };
        $scope.cleanFilter = function() {
            filter.byTitle = filter2.byTitle = '';
        };
        $scope.cleanFilterTag = function() {
            filter.byTags = filter2.byTags = '';
        };
        $scope.$watch('frameState.sid', function(nv) {
            angular.extend(filter, { bySite: nv });
            $scope.getMatterTag();
        });
        $scope.$watch('filter', function(nv) {
            if (!nv) return;
            $scope.list();
        }, true);
        $scope.matterTags = function() {
            $scope.matterTagsFram(filter, filter2);
        };
    }]);
    ngApp.provider.controller('ctrlInfo', ['$scope', '$uibModal', 'http2', function($scope, $uibModal, http2) {
        var page, filter, filter2;
        if (window.localStorage) {
            $scope.$watch('filter', function(nv) {
                if (nv) {
                    window.localStorage.setItem("pl.fe.info.filter", JSON.stringify(nv));
                }
            }, true);
            if (filter = window.localStorage.getItem("pl.fe.info.filter")) {
                filter = JSON.parse(filter);
            } else {
                filter = { byType: 'article' };
            }
        } else {
            filter = { byType: 'article' };
        }
        $scope.filter = filter;
        $scope.filter2 = filter2 = {};
        if (filter.byType) {
            filter2.byTitle = filter.byTitle;
            filter2.byTags = filter.byTags
        }
        $scope.changeMatter = function(type) {
            filter.byType = type;
        };
        $scope.page = page = {
            at: 1,
            size: 12,
            j: function() {
                return 'page=' + this.at + '&size=' + this.size;
            }
        };
        $scope.list = function() {
            var url = '/rest/pl/fe/recent?' + page.j(),
                t = (new Date() * 1),
                url2 = '/rest/pl/fe/matter/' + filter.byType + '/list?site=' + filter.bySite + '&' + page.j() + '&_=' + t;

            if (filter.bySite == '') {
                http2.post(url, filter, function(rsp) {
                    $scope.matters = rsp.data.matters;
                    $scope.page.total = rsp.data.total;
                });
            } else {
                filter.byType == 'channel' ? url2 += '&cascade=N' : url2;
                http2.post(url2, { byTitle: filter.byTitle, byTags: filter.byTags }, function(rsp) {
                    switch (filter.byType) {
                        case 'article':
                            $scope.matters = rsp.data.articles;
                            break;
                        case 'contribute':
                            $scope.matters = rsp.data.apps;
                            break;
                        case 'custom':
                            $scope.matters = rsp.data.customs;
                            break;
                        default:
                            $scope.matters = rsp.data;
                    }
                    $scope.page.total = rsp.data.total;
                });
            }
        };
        $scope.doFilter = function() {
            angular.extend(filter, filter2);
        };
        $scope.cleanFilter = function() {
            filter.byTitle = filter2.byTitle = '';
        };
        $scope.$watch('frameState.sid', function(nv) {
            angular.extend(filter, { bySite: nv });
            $scope.getMatterTag();
        });
        $scope.$watch('filter', function(nv) {
            if (!nv) return;
            $scope.list();
        }, true);
        $scope.matterTags = function() {
            $scope.matterTagsFram(filter, filter2);
        };
        $scope.cleanFilterTag = function() {
            filter.byTags = filter2.byTags = '';
        };
    }]);
    ngApp.provider.controller('ctrlUser', ['$scope', 'http2', function($scope, http2) {}]);
    ngApp.provider.controller('ctrlMember', ['$scope', '$location', '$uibModal', 'http2', function($scope, $location, $uibModal, http2) {
        function listInvite(oSchema) {
            http2.get('/rest/pl/fe/site/member/invite/list?schema=' + oSchema.id, function(rsp) {
                $scope.invites = rsp.data.invites;
            });
        }
        var selected;
        $scope.selected = selected = {
            mschema: null
        };
        $scope.chooseMschema = function() {
            var mschema;
            if (mschema = selected.mschema) {
                $scope.searchBys = [];
                mschema.attr_name[0] == 0 && $scope.searchBys.push({
                    n: '姓名',
                    v: 'name'
                });
                mschema.attr_mobile[0] == 0 && $scope.searchBys.push({
                    n: '手机号',
                    v: 'mobile'
                });
                mschema.attr_email[0] == 0 && $scope.searchBys.push({
                    n: '邮箱',
                    v: 'email'
                });
                $scope.page = {
                    at: 1,
                    size: 30,
                    keyword: '',
                    searchBy: $scope.searchBys[0].v
                };
                $scope.doSearch(1);
                listInvite(mschema);
            }
        };
        $scope.createMschema = function() {
            var url;
            if ($scope.frameState.sid) {
                url = '/rest/pl/fe/site/member/schema/create?site=' + $scope.frameState.sid;
                http2.post(url, { valid: 'Y' }, function(rsp) {
                    location.href = '/rest/pl/fe/site/mschema?site=' + $scope.frameState.sid + '#' + rsp.data.id;
                });
            }
        };
        $scope.doSearch = function(page) {
            page && ($scope.page.at = page);
            var url, filter = '';
            if ($scope.page.keyword !== '') {
                filter = '&kw=' + $scope.page.keyword;
                filter += '&by=' + $scope.page.searchBy;
            }
            url = '/rest/pl/fe/site/member/list?site=' + $scope.frameState.sid + '&schema=' + selected.mschema.id;
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
                        return angular.copy($scope.selected.mschema);
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
                    for (i in selected.mschema.extattr) {
                        ea = selected.mschema.extattr[i];
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
        $scope.createEnrollApp = function(oSchema) {
            http2.post('/rest/pl/fe/matter/enroll/createByMschema?mschema=' + oSchema.id, {}, function(rsp) {
                location.href = '/rest/pl/fe/matter/enroll?site=' + rsp.data.siteid + '&id=' + rsp.data.id;
            });
        };
        $scope.editInvite = function(oInvite) {
            $uibModal.open({
                templateUrl: 'inviteEditor.html',
                backdrop: 'static',
                controller: ['$uibModalInstance', '$scope', function($mi, $scope2) {
                    $scope2.option = { max_count: oInvite.max_count, expire_at: oInvite.expire_at };
                    $scope2.cancel = function() {
                        $mi.dismiss();
                    };
                    $scope2.ok = function() {
                        $mi.close($scope2.option);
                    };
                }]
            }).result.then(function(option) {
                http2.post('/rest/pl/fe/site/member/invite/update?invite=' + oInvite.id, option, function(rsp) {
                    angular.extend(oInvite, rsp.data);
                });
            });
        };
        $scope.addInvite = function() {
            $uibModal.open({
                templateUrl: 'inviteEditor.html',
                backdrop: 'static',
                controller: ['$uibModalInstance', '$scope', function($mi, $scope2) {
                    $scope2.option = { max_count: 1 };
                    $scope2.cancel = function() {
                        $mi.dismiss();
                    };
                    $scope2.ok = function() {
                        $mi.close($scope2.option);
                    };
                }]
            }).result.then(function(option) {
                http2.post('/rest/pl/fe/site/member/invite/add?schema=' + selected.mschema.id, option, function(rsp) {
                    $scope.invites.push(rsp.data);
                });
            });
        };
        $scope.stopInvite = function(oInvite) {
            http2.post('/rest/pl/fe/site/member/invite/update?invite=' + oInvite.id, { stop: 'Y' }, function(rsp) {
                angular.extend(oInvite, rsp.data);
            });
        };
        $scope.startInvite = function(oInvite) {
            http2.post('/rest/pl/fe/site/member/invite/update?invite=' + oInvite.id, { stop: 'N' }, function(rsp) {
                angular.extend(oInvite, rsp.data);
            });
        };
        $scope.removeInvite = function(oInvite) {
            http2.post('/rest/pl/fe/site/member/invite/update?invite=' + oInvite.id, { state: 0 }, function(rsp) {
                oInvite.state = '0';
            });
        };
        $scope.restoreInvite = function(oInvite) {
            http2.post('/rest/pl/fe/site/member/invite/update?invite=' + oInvite.id, { state: 1 }, function(rsp) {
                oInvite.state = '1';
            });
        };
        $scope.$watch('frameState.sid', function(siteId) {
            if (siteId) {
                http2.get('/rest/pl/fe/site/member/schema/list?site=' + siteId, function(rsp) {
                    $scope.mschemas = rsp.data;
                    if ($scope.mschemas.length) {
                        if ($location.search().mschema) {
                            for (var i in $scope.mschemas) {
                                if ($scope.mschemas[i].id == $location.search().mschema) {
                                    selected.mschema = $scope.mschemas[i];
                                    break;
                                }
                            }
                        } else {
                            selected.mschema = $scope.mschemas[0];
                        }
                        $scope.chooseMschema();
                    }
                });
            } else {
                $scope.mschemas = [];
                selected.mschema = null;
            }
        });
    }]);
    ngApp.provider.controller('ctrlSiteAccount', ['$scope', '$uibModal', 'http2', function($scope, $uibModal, http2) {
        $scope.page = {
            at: 1,
            size: 30,
        };
        $scope.doSearch = function(page) {
            var url = '/rest/pl/fe/site/user/account/list';
            page && ($scope.page.at = page);
            url += '?site=' + $scope.frameState.sid;
            url += '&page=' + $scope.page.at + '&size=' + $scope.page.size;
            http2.get(url, function(rsp) {
                $scope.users = rsp.data.users;
                $scope.page.total = rsp.data.total;
            });
        };
        $scope.openProfile = function(uid) {
            //location.href = '/rest/pl/fe/site/user/fans?site=' + $scope.frameState.sid + '&uid=' + uid;
        };
        $scope.find = function() {
            var url = '/rest/pl/fe/site/user/account/list',
                data = {
                    nickname: $scope.nickname
                };
            url += '?site=' + $scope.frameState.sid;
            url += '&nickname=' + $scope.nickname;
            http2.post(url, data, function(rsp) {
                $scope.users = rsp.data.users;
                $scope.page.total = rsp.data.total;
            })
        };
        $scope.doSearch(1);
    }]);
    ngApp.provider.controller('ctrlSiteSubscribe', ['$scope', '$uibModal', 'http2', function($scope, $uibModal, http2) {
        $scope.page = {
            at: 1,
            size: 30,
        };
        $scope.doSearch = function(page) {
            var url = '/rest/pl/fe/site/subscriberList';
            page && ($scope.page.at = page);
            url += '?site=' + $scope.frameState.sid;
            url += '&category=client';
            url += '&page=' + $scope.page.at + '&size=' + $scope.page.size;
            http2.get(url, function(rsp) {
                $scope.users = rsp.data.subscribers;
                $scope.page.total = rsp.data.total;
            });
        };
        $scope.openProfile = function(uid) {
            //location.href = '/rest/pl/fe/site/user/fans?site=' + $scope.frameState.sid + '&uid=' + uid;
        };
        $scope.find = function() {
            var url = '/rest/pl/fe/site/subscriberList',
                data = {
                    nickname: $scope.nickname
                };
            url += '?site=' + $scope.frameState.sid;
            url += '&category=client';
            http2.post(url, data, function(rsp) {
                $scope.users = rsp.data.subscribers;
                $scope.page.total = rsp.data.total;
            })
        };
        $scope.doSearch(1);
    }]);
    ngApp.provider.controller('ctrlRecycle', ['$scope', 'http2', function($scope, http2) {
        var t = (new Date() * 1),
            filter, filter2;
        $scope.filter = filter = {};
        $scope.filter2 = filter2 = {};
        $scope.list = function() {
            if (filter.bySite == '') {
                var url = '/rest/pl/fe/site/wasteList?_=' + t,
                    url2 = '/rest/pl/fe/site/console/recycle?site=' + filter.bySite + '&_=' + t,
                    urls = [url, url2];
                $scope.matters = [];
                urls.forEach(function(item) {
                    http2.post(item, filter, function(rsp) {
                        var data = rsp.data.matters || rsp.data;
                        data.forEach(function(matter) {
                            $scope.matters.push(matter);
                        });
                    });
                });
            } else {
                var url = '/rest/pl/fe/site/console/recycle?site=' + filter.bySite + '&_=' + t;
                http2.post(url, filter, function(rsp) {
                    $scope.matters = rsp.data.matters;
                });
            }
        };
        $scope.doFilter = function() {
            angular.extend(filter, filter2);
        };
        $scope.cleanFilter = function() {
            filter.byTitle = filter2.byTitle = '';
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
        $scope.$watch('frameState.sid', function(nv) {
            angular.extend(filter, { bySite: nv });
        });
        $scope.$watch('filter', function(nv) {
            if (!nv) return;
            $scope.list();
        }, true);
    }]);
});