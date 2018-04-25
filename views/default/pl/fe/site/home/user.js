define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlSiteuser', ['$scope', 'http2', function($scope, http2) {
        var catelogs;
        $scope.catelog = null;
        $scope.catelogs = catelogs = [];
        $scope.$watch('site', function(oSite) {
            if (oSite === undefined) return;
            http2.get('/rest/pl/fe/site/member/schema/list?site=' + oSite.id, function(rsp) {
                catelogs.splice(0, catelogs.length, { l: '站点用户', v: 'account' });
                rsp.data.forEach(function(memberSchema) {
                    catelogs.push({ l: memberSchema.title, v: 'member', obj: memberSchema })
                });
                $scope.catelog = catelogs[0];
            });
        });
    }]);
    ngApp.provider.controller('ctrlAccount', ['$scope', '$uibModal', 'http2', function($scope, $uibModal, http2) {
        $scope.page = {
            at: 1,
            size: 30,
        };
        $scope.doSearch = function(page) {
            var url = '/rest/pl/fe/site/user/account/list';
            page && ($scope.page.at = page);
            url += '?site=' + $scope.site.id;
            url += '&page=' + $scope.page.at + '&size=' + $scope.page.size;
            http2.get(url, function(rsp) {
                $scope.users = rsp.data.users;
                $scope.page.total = rsp.data.total;
            });
        };
        $scope.openProfile = function(uid, unionid) {
            location.href = '/rest/pl/fe/site/user/fans?site=' + $scope.site.id + '&uid=' + uid + '&unionid=' + unionid;
        };
        $scope.$watch('site', function(site) {
            if (site === undefined) return;
            $scope.doSearch(1);
        });
        $scope.find = function() {
            var url = '/rest/pl/fe/site/user/account/list',
                data = {
                    nickname: $scope.nickname
                };
            url += '?site=' + $scope.site.id;
            url += '&nickname=' + $scope.nickname;
            http2.post(url, data, function(rsp) {
                $scope.users = rsp.data.users;
                $scope.page.total = rsp.data.total;
            })
        }
    }]);
    ngApp.provider.controller('ctrlMember', ['$scope', '$uibModal', '$location', 'http2', function($scope, $uibModal, $location, http2) {
        $scope.$watch('catelog', function(nv) {
            if (!nv) return;
            $scope.schema = nv.obj;
            $scope.searchBys = [];
            $scope.schema.attr_name[0] == 0 && $scope.searchBys.push({
                n: '姓名',
                v: 'name'
            });
            $scope.schema.attr_mobile[0] == 0 && $scope.searchBys.push({
                n: '手机号',
                v: 'mobile'
            });
            $scope.schema.attr_email[0] == 0 && $scope.searchBys.push({
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
            url = '/rest/pl/fe/site/member/list?site=' + $scope.site.id + '&schema=' + $scope.schema.id;
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
                templateUrl: 'memberEditor.html',
                backdrop: 'static',
                resolve: {
                    schema: function() {
                        return angular.copy($scope.schema);
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
                    for (i in $scope.schema.extattr) {
                        ea = $scope.schema.extattr[i];
                        newData[ea.id] = rst.data[ea.id];
                    }
                    http2.post('/rest/pl/fe/site/member/update?site=' + $scope.site.id + '&id=' + member.id, newData, function(rsp) {
                        angular.extend(member, newData);
                    });
                } else if (rst.action === 'remove') {
                    http2.get('/rest/pl/fe/site/member/remove?site=' + $scope.site.id + '&id=' + member.id, function() {
                        $scope.members.splice($scope.members.indexOf(member), 1);
                    });
                }
            });
        };
    }]);
    ngApp.provider.controller('ctrlCoin', ['$scope', 'http2', function($scope, http2) {
        var actions = [{
            name: 'site.user.register',
            desc: '用户A首次注册 '
        }];
        $scope.rules = {};
        angular.forEach(actions, function(act) {
            var name;
            name = act.name;
            $scope.rules[name] = {
                act: name,
                desc: act.desc,
                actor_delta: 0,
                creator_delta: 0,
            };
        });
        $scope.save = function() {
            var filter = 'ID:' + $scope.site.id,
                posted = [],
                url, rule;

            for (var k in $scope.rules) {
                rule = $scope.rules[k];
                if (rule.id || rule.actor_delta != 0 || rule.creator_delta != 0) {
                    var data;
                    data = {
                        act: rule.act,
                        actor_delta: rule.actor_delta,
                        creator_delta: rule.creator_delta,
                        matter_type: 'site',
                        matter_filter: filter
                    };
                    rule.id && (data.id = rule.id);
                    posted.push(data);
                }
            }
            url = '/rest/pl/fe/site/user/coin/save?site=' + $scope.site.id;
            http2.post(url, posted, function(rsp) {
                for (var k in rsp.data) {
                    $scope.rules[k].id = rsp.data[k];
                }
            });
        };
        $scope.fetch = function() {
            var url;
            url = '/rest/pl/fe/site/user/coin/get?site=' + $scope.site.id;
            http2.get(url, function(rsp) {
                rsp.data.forEach(function(rule) {
                    var rule2 = $scope.rules[rule.act];
                    rule2.id = rule.id;
                    rule2.actor_delta = rule.actor_delta;
                    rule2.creator_delta = rule.creator_delta;
                });
            });
        };
        $scope.$watch('site', function(site) {
            if (site === undefined) return;
            $scope.fetch();
        });
    }]);
});