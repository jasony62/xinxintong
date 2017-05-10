define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlMain', ['$scope', '$uibModal', 'templateShop', 'http2', 'noticebox', function($scope, $uibModal, templateShop, http2, noticebox) {
        var criteria2;
        $scope.criteria2 = criteria2 = {
            scope: 'top'
        };
        $scope.changeScope = function(scope) {
            criteria2.scope = scope;
        };
        $scope.matterNames = {
            'article': '单图文',
            'news': '多图文',
            'channel': '频道',
            'link': '链接',
            'contribute': '投稿',
            'text': '文本',
            'custom': '定制页',
            'enroll': '登记',
            'signin': '签到',
            'group': '分组',
            'lottery': '抽奖',
            'wall': '信息墙',
            'mission': '项目',
            'site': '团队'
        };
        $scope.load = function(id) {
                location.href = '/rest/pl/fe/site/setting?site=' + id;
            }
            /*新建素材*/
        var _fns = {
            createSite: function() {
                var defer = $q.defer(),
                    url = '/rest/pl/fe/site/create?_=' + (new Date() * 1);

                http2.get(url, function(rsp) {
                    defer.resolve(rsp.data);
                });
                return defer.promise;
            },
            addLink: function(site) {
                http2.get('/rest/pl/fe/matter/link/create?site=' + site.id, function(rsp) {
                    location.href = '/rest/pl/fe/matter/link?site=' + site.id + '&id=' + rsp.data.id;
                });
            },
            addArticle: function(site) {
                http2.get('/rest/pl/fe/matter/article/create?site=' + site.id, function(rsp) {
                    location.href = '/rest/pl/fe/matter/article?site=' + site.id + '&id=' + rsp.data.id;
                });
            },
            addNews: function(site) {
                http2.get('/rest/pl/fe/matter/news/create?site=' + site.id, function(rsp) {
                    location.href = '/rest/pl/fe/matter/news?site=' + site.id + '&id=' + rsp.data.id;
                });
            },
            addChannel: function(site) {
                http2.get('/rest/pl/fe/matter/channel/create?site=' + site.id, function(rsp) {
                    location.href = '/rest/pl/fe/matter/channel?site=' + site.id + '&id=' + rsp.data.id;
                });
            },
            addEnroll: function(site, scenario) {
                $('body').trigger('click');
                templateShop.choose(site.id, 'enroll', scenario).then(function(choice) {
                    if (choice) {
                        if (choice.source === 'share') {
                            var url, data = choice.data;
                            url = '/rest/pl/fe/matter/enroll/createByOther?site=' + site.id + '&template=' + data.id;
                            http2.get(url, function(rsp) {
                                location.href = '/rest/pl/fe/matter/enroll?site=' + site.id + '&id=' + rsp.data.id;
                            });
                        } else if (choice.source === 'platform') {
                            var url, config, data = choice.data;
                            url = '/rest/pl/fe/matter/enroll/create?site=' + site.id;
                            config = {};
                            if (data) {
                                url += '&scenario=' + data.scenario.name;
                                url += '&template=' + data.template.name;
                                if (data.simpleSchema && data.simpleSchema.length) {
                                    config.simpleSchema = data.simpleSchema;
                                }
                            }
                            http2.post(url, config, function(rsp) {
                                location.href = '/rest/pl/fe/matter/enroll?site=' + site.id + '&id=' + rsp.data.id;
                            });
                        } else if (choice.source === 'file') {
                            var url, data = choice.data;
                            url = '/rest/pl/fe/matter/enroll/createByFile?site=' + site.id;
                            http2.post(url, data, function(rsp) {
                                location.href = '/rest/pl/fe/matter/enroll?site=' + site.id + '&id=' + rsp.data.id;
                            });
                        }
                    } else {
                        var url;
                        url = '/rest/pl/fe/matter/enroll/create?site=' + site.id;
                        http2.post(url, {}, function(rsp) {
                            location.href = '/rest/pl/fe/matter/enroll?site=' + site.id + '&id=' + rsp.data.id;
                        });
                    }
                });
            },
            addSignin: function(site) {
                http2.get('/rest/pl/fe/matter/signin/create?site=' + site.id, function(rsp) {
                    location.href = '/rest/pl/fe/matter/signin?site=' + site.id + '&id=' + rsp.data.id;
                });
            },
            addGroup: function(site) {
                http2.get('/rest/pl/fe/matter/group/create?site=' + site.id + '&scenario=split', function(rsp) {
                    location.href = '/rest/pl/fe/matter/group/main?site=' + site.id + '&id=' + rsp.data.id;
                });
            },
            addLottery: function(site) {
                http2.get('/rest/pl/fe/matter/lottery/create?site=' + site.id, function(rsp) {
                    location.href = '/rest/pl/fe/matter/lottery?site=' + site.id + '&id=' + rsp.data;
                });
            },
            addContribute: function(site) {
                http2.get('/rest/pl/fe/matter/contribute/create?site=' + site.id, function(rsp) {
                    location.href = '/rest/pl/fe/matter/contribute?site=' + site.id + '&id=' + rsp.data.id;
                });
            },
            addMission: function(site) {
                http2.get('/rest/pl/fe/matter/mission/create?site=' + site.id, function(rsp) {
                    location.href = '/rest/pl/fe/matter/mission?site=' + site.id + '&id=' + rsp.data.id;
                });
            },
            addCustom: function(site) {
                http2.get('/rest/pl/fe/matter/custom/create?site=' + site.id, function(rsp) {
                    location.href = '/rest/pl/fe/matter/custom?site=' + site.id + '&id=' + rsp.data;
                });
            },
            addMerchant: function(site) {
                http2.get('/rest/pl/fe/matter/merchant/shop/create?site=' + site.id, function(rsp) {
                    location.href = '/rest/pl/fe/matter/merchant/shop?site=' + site.id + '&id=' + rsp.data;
                });
            },
            addWall: function(site) {
                http2.get('/rest/pl/fe/matter/wall/create?site=' + site.id, function(rsp) {
                    location.href = '/rest/pl/fe/matter/wall?site=' + site.id + '&id=' + rsp.data;
                });
            },
            addText: function(site) {
                location.href = '/rest/pl/fe/matter/text?site=' + site.id;
            }
        };

        function addMatter(site, matterType, scenario) {
            var fnName = 'add' + matterType[0].toUpperCase() + matterType.substr(1);
            _fns[fnName].call(_fns, site, scenario);
        }
        $scope.popoverAddMatter = function() {
            var target = $('#popoverAddMatter');
            if (target.data('popover') === 'Y') {
                target.trigger('hide').data('popover', 'N');
            } else {
                target.trigger('show').data('popover', 'Y');
            }
        };
        $scope.addMatter = function(matterType, scenario) {
            if (matterType == 'site') {
                var url = '/rest/pl/fe/site/create?_=' + (new Date() * 1);
                http2.get(url, function(rsp) {
                    location.href = '/rest/pl/fe/site/setting?site=' + rsp.data.id;
                });
            }
            $('#popoverAddMatter').trigger('hide');
            $('#missionAddMatter').trigger('hide');
            $('#activityAddMatter').trigger('hide');
            $('#infoAddMatter').trigger('hide');
            if ($scope.criteria.sid != '') {
                var site = { id: $scope.criteria.sid };
                addMatter(site, matterType, scenario);
            } else {
                var url = '/rest/pl/fe/site/list?_=' + (new Date() * 1);
                http2.get(url, function(rsp) {
                    var sites = rsp.data;
                    if (sites.length === 1) {
                        addMatter(sites[0], matterType);
                    } else if (sites.length === 0) {
                        createSite().then(function(site) {
                            addMatter(site, matterType, scenario);
                        });
                    } else {
                        $uibModal.open({
                            templateUrl: 'addMatterSite.html',
                            dropback: 'static',
                            controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                                var data;
                                $scope2.mySites = sites;
                                $scope2.data = data = {};
                                $scope2.ok = function() {
                                    if (data.index !== undefined) {
                                        $mi.close(sites[data.index]);
                                    } else {
                                        $mi.dismiss();
                                    }
                                };
                                $scope2.cancel = function() {
                                    $mi.dismiss();
                                };
                            }]
                        }).result.then(function(site) {
                            addMatter(site, matterType, scenario);
                        });
                    }
                });
            }
        };

        /*置顶*/
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
            var url = '/rest/pl/fe/matter/' + matter.matter_type;
            if (subView) {
                url += '/' + subView;
            }
            url += '?id=' + matter.matter_id + '&site=' + matter.siteid;
            location.href = url;
        };
        $scope.setHome = function(site) {
            location.href = '/rest/pl/fe/site/home?site=' + site.siteid;
        };
        $scope.openConsole = function(site) {
            location.href = '/rest/pl/fe/site?site=' + site.siteid;
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
        $scope.$watch('criteria.sid', function(nv) {
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
        $scope.$watch('criteria.sid', function(nv) {
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
            $('body').click();
        };
        $scope.cleanFilter = function() {
            filter.byTitle = '';
            $('body').click();
        };
        $scope.$watch('criteria.sid', function(nv) {
            angular.extend(filter, { bySite: nv });
        });
        $scope.$watch('filter', function(nv) {
            if (!nv) return;
            $scope.list();
        }, true);
    }]);
    ngApp.provider.controller('ctrlMission', ['$scope', 'http2', function($scope, http2) {
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
            var url = '/rest/pl/fe/matter/mission/create?site=' + $scope.criteria.sid;
            http2.get(url, function(rsp) {
                location.href = '/rest/pl/fe/matter/mission?site=' + rsp.data.id + '&id=' + rsp.data.id;
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
            $('body').click();
        };
        $scope.cleanFilter = function() {
            filter.byTitle = '';
            $('body').click();
        };
        $scope.$watch('criteria.sid', function(nv) {
            angular.extend(filter, { bySite: nv });
        });
        $scope.$watch('filter', function(nv) {
            if (!nv) return;
            $scope.list();
        }, true);
        $scope.listSite();
    }]);
    ngApp.provider.controller('ctrlActivity', ['$scope', 'http2', function($scope, http2) {
        var criteria3, page, filter, filter2;
        $scope.filter = filter = {};
        $scope.filter2 = filter2 = {};
        $scope.scenarioNames = {
            'common': '通用登记',
            'registration': '报名',
            'voting': '投票',
            'quiz': '测验',
            'group_week_report': '周报'
        };
        $scope.criteria3 = criteria3 = {
            matterType: 'enroll'
        }
        $scope.changeMatter = function(type) {
            criteria3.matterType = type;
        }
        $scope.activityAddMatter = function() {
            var target = $('#activityAddMatter');
            if (target.data('popover') === 'Y') {
                target.trigger('hide').data('popover', 'N');
            } else {
                target.trigger('show').data('popover', 'Y');
            }
        }
        $scope.page = page = {
            at: 1,
            size: 12,
            j: function() {
                return 'page=' + this.at + '&size=' + this.size;
            }
        };
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
        $scope.doFilter = function() {
            angular.extend(filter, filter2);
            $('body').click();
        };
        $scope.cleanFilter = function() {
            filter.byTitle = '';
            $('body').click();
        };
        $scope.$watch('criteria3.matterType', function(nv) {
            angular.extend(filter, { byType: nv });
        })
        $scope.$watch('criteria.sid', function(nv) {
            angular.extend(filter, { bySite: nv });
        });
        $scope.$watch('filter', function(nv) {
            if (!nv) return;
            $scope.list();
        }, true);
    }]);
    ngApp.provider.controller('ctrlInfo', ['$scope', '$uibModal', 'http2', function($scope, $uibModal, http2) {
        var criteria4, page, filter, filter2;
        $scope.filter = filter = {};
        $scope.filter2 = filter2 = {};
        $scope.criteria4 = criteria4 = {
            matterType: 'article'
        }
        $scope.changeMatter = function(type) {
            criteria4.matterType = type;
        };
        $scope.infoAddMatter = function() {
            var target = $('#infoAddMatter');
            if (target.data('popover') === 'Y') {
                target.trigger('hide').data('popover', 'N');
            } else {
                target.trigger('show').data('popover', 'Y');
            }
        };
        $scope.page = page = {
            at: 1,
            size: 12,
            j: function() {
                return 'page=' + this.at + '&size=' + this.size;
            }
        };
        $scope.list = function() {
            var url = '/rest/pl/fe/recent?' + page.j();
            http2.post(url, filter, function(rsp) {
                $scope.matters = rsp.data.matters;
                $scope.page.total = rsp.data.total;
            });
        };
        $scope.doFilter = function() {
            angular.extend(filter, filter2);
            $('body').click();
        };
        $scope.cleanFilter = function() {
            filter.byTitle = '';
            $('body').click();
        };
        $scope.$watch('criteria4.matterType', function(nv) {
            angular.extend(filter, { byType: nv });
        })
        $scope.$watch('criteria.sid', function(nv) {
            angular.extend(filter, { bySite: nv });
        });
        $scope.$watch('filter', function(nv) {
            if (!nv) return;
            $scope.list();
        }, true);
    }]);
    ngApp.provider.controller('ctrlSiteUser', ['$scope', 'http2', function($scope, http2) {}]);
    ngApp.provider.controller('ctrlMember', ['$scope', '$uibModal', '$location', 'http2', function($scope, $uibModal, $location, http2) {
        $scope.selectedMschema = null;
        $scope.$watch('selectedMschema', function(nv) {
            if (!nv) return;
            $scope.searchBys = [];
            nv.attr_name[0] == 0 && $scope.searchBys.push({
                n: '姓名',
                v: 'name'
            });
            nv.attr_mobile[0] == 0 && $scope.searchBys.push({
                n: '手机号',
                v: 'mobile'
            });
            nv.attr_email[0] == 0 && $scope.searchBys.push({
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
        });
        $scope.doSearch = function(page) {
            page && ($scope.page.at = page);
            var url, filter = '';
            if ($scope.page.keyword !== '') {
                filter = '&kw=' + $scope.page.keyword;
                filter += '&by=' + $scope.page.searchBy;
            }
            url = '/rest/pl/fe/site/member/list?site=' + $scope.criteria.sid + '&schema=' + $scope.selectedMschema.id;
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
                        return angular.copy($scope.selectedMschema);
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
                    for (i in $scope.selectedMschema.extattr) {
                        ea = $scope.selectedMschema.extattr[i];
                        newData[ea.id] = rst.data[ea.id];
                    }
                    http2.post('/rest/pl/fe/site/member/update?site=' + $scope.criteria.sid + '&id=' + member.id, newData, function(rsp) {
                        angular.extend(member, newData);
                    });
                } else if (rst.action === 'remove') {
                    http2.get('/rest/pl/fe/site/member/remove?site=' + $scope.criteria.sid + '&id=' + member.id, function() {
                        $scope.members.splice($scope.members.indexOf(member), 1);
                    });
                }
            });
        };
        http2.get('/rest/pl/fe/site/member/schema/list?site=' + $scope.criteria.sid, function(rsp) {
            $scope.mschemas = rsp.data;
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
            url += '?site=' + $scope.criteria.sid;
            url += '&page=' + $scope.page.at + '&size=' + $scope.page.size;
            http2.get(url, function(rsp) {
                $scope.users = rsp.data.users;
                $scope.page.total = rsp.data.total;
            });
        };
        $scope.openProfile = function(uid) {
            //location.href = '/rest/pl/fe/site/user/fans?site=' + $scope.criteria.sid + '&uid=' + uid;
        };
        $scope.find = function() {
            var url = '/rest/pl/fe/site/user/account/list',
                data = {
                    nickname: $scope.nickname
                };
            url += '?site=' + $scope.criteria.sid;
            url += '&nickname=' + $scope.nickname;
            http2.post(url, data, function(rsp) {
                $scope.users = rsp.data.users;
                $scope.page.total = rsp.data.total;
            })
        };
        $scope.doSearch(1);
    }]);
    ngApp.provider.controller('ctrlRecycle', ['$scope', 'http2', function($scope, http2) {
        var t = (new Date() * 1);
        $scope.recycle = function() {
            //获取回收站信息
            var url = '/rest/pl/fe/site/wasteList?_=' + t;
            http2.get(url, function(rsp) {
                $scope.sites0 = rsp.data;
            });
        };
        $scope.restoreSite = function(site) {
            //恢复删除站点
            var url = '/rest/pl/fe/site/recover?site=' + site.id;
            http2.get(url, function(rsp) {
                location.href = '/rest/pl/fe/site?site=' + site.id;
            })
        };
        $scope.recycle();
    }]);
});
