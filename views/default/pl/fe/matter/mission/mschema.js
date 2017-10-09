define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlMschema', ['$scope', '$location', '$uibModal', 'http2', 'srvSite', function($scope, $location, $uibModal, http2, srvSite) {
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
                $location.hash(mschema.id);
                $scope.doSearch(1);
                listInvite(mschema);
            }
        };
        $scope.createMschema = function() {
            var url, proto;
            if ($scope.mission && $scope.mission.siteid) {
                url = '/rest/pl/fe/site/member/schema/create?site=' + $scope.mission.siteid;
                proto = { valid: 'Y', matter_id: $scope.mission.id, matter_type: $scope.mission.type, title: $scope.mission.title + '-通讯录' + ($scope.mschemas.length + 1) };
                http2.post(url, proto, function(rsp) {
                    $scope.mschemas.push(rsp.data);
                    selected.mschema = rsp.data;
                    $scope.chooseMschema();
                });
            }
        };
        $scope.removeMschema = function() {
            var url;
            url = '/rest/pl/fe/site/member/schema/update?site=' + $scope.mission.siteid + '&id=' + selected.mschema.id;
            http2.post(url, { valid: 'N' }, function(rsp) {
                var index;
                index = $scope.mschemas.indexOf(selected.mschema);
                $scope.mschemas.splice(index, 1);
                if ($scope.mschemas.length) {
                    selected.mschema = index === 0 ? $scope.mschemas[0] : $scope.mschemas[index - 1];
                    $scope.chooseMschema();
                } else {
                    $scope.members = [];
                    $scope.page.at = 1;
                    $scope.page.total = 0;
                    $scope.invites = [];
                }
            });
        };
        $scope.doSearch = function(page) {
            page && ($scope.page.at = page);
            var url, filter = '';
            if ($scope.page.keyword !== '') {
                filter = '&kw=' + $scope.page.keyword;
                filter += '&by=' + $scope.page.searchBy;
            }
            url = '/rest/pl/fe/site/member/list?site=' + selected.mschema.siteid + '&schema=' + selected.mschema.id;
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
                    http2.post('/rest/pl/fe/site/member/update?site=' + $scope.mission.siteid + '&id=' + member.id, newData, function(rsp) {
                        angular.extend(member, newData);
                    });
                } else if (rst.action === 'remove') {
                    http2.get('/rest/pl/fe/site/member/remove?site=' + $scope.mission.siteid + '&id=' + member.id, function() {
                        $scope.members.splice($scope.members.indexOf(member), 1);
                    });
                }
            });
        };
        $scope.editInvite = function(oInvite) {
            $uibModal.open({
                templateUrl: '/views/default/pl/fe/_module/mschemaInvite.html',
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
                templateUrl: '/views/default/pl/fe/_module/mschemaInvite.html',
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
        $scope.$watch('mission', function(oMission) {
            if (!oMission) return;
            srvSite.memberSchemaList(oMission, true).then(function(aMemberSchemas) {
                var hashMschemaId = $location.hash();
                $scope.mschemas = aMemberSchemas;
                if ($scope.mschemas.length) {
                    if (hashMschemaId) {
                        for (var i = $scope.mschemas.length - 1; i >= 0; i--) {
                            if ($scope.mschemas[i].id === hashMschemaId) {
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
        });
    }]);
});