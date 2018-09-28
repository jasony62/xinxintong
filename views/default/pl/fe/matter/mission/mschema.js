define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlMschema', ['$scope', '$location', '$uibModal', 'http2', 'tmsSchema', 'srvSite', 'CstNaming', 'pushnotify', 'noticebox', function($scope, $location, $uibModal, http2, tmsSchema, srvSite, CstNaming, pushnotify, noticebox) {
        function listInvite(oSchema) {
            http2.get('/rest/pl/fe/site/member/invite/list?schema=' + oSchema.id).then(function(rsp) {
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
                $scope.rows = {
                    allSelected: 'N',
                    selected: {},
                    count: 0,
                    change: function(index) {
                        this.selected[index] ? this.count++ : this.count--;
                    },
                    reset: function() {
                        this.allSelected = 'N';
                        this.selected = {};
                        this.count = 0;
                    }
                };
                $location.hash(mschema.id);
                $scope.doSearch(1);
                //listInvite(mschema);
            }
        };
        $scope.createMschema = function() {
            var url, proto;
            if ($scope.mission && $scope.mission.siteid) {
                url = '/rest/pl/fe/site/member/schema/create?site=' + $scope.mission.siteid;
                proto = { valid: 'Y', matter_id: $scope.mission.id, matter_type: $scope.mission.type, title: $scope.mission.title + '-通讯录' + ($scope.mschemas.length + 1) };
                http2.post(url, proto).then(function(rsp) {
                    $scope.mschemas.push(rsp.data);
                    selected.mschema = rsp.data;
                    $scope.chooseMschema();
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
            url = '/rest/pl/fe/site/member/list?site=' + selected.mschema.siteid + '&schema=' + selected.mschema.id;
            url += '&page=' + $scope.page.at + '&size=' + $scope.page.size + filter
            url += '&contain=total';
            http2.get(url).then(function(rsp) {
                var members;
                members = rsp.data.members;
                if (members.length) {
                    if (selected.mschema.extAttrs.length) {
                        members.forEach(function(oMember) {
                            oMember._extattr = tmsSchema.member.getExtattrsUIValue(selected.mschema.extAttrs, oMember);
                        });
                    }
                }
                $scope.members = members;
                $scope.page.total = rsp.data.total;
            });
        };
        $scope.editMember = function(oMember) {
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
                    $scope.member = angular.copy(oMember);
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
                        };
                    http2.post('/rest/pl/fe/site/member/update?site=' + $scope.mission.siteid + '&id=' + oMember.id, newData).then(function(rsp) {
                        angular.extend(oMember, newData);
                        oMember._extattr = tmsSchema.member.getExtattrsUIValue(selected.mschema.extAttrs, oMember);
                    });
                } else if (rst.action === 'remove') {
                    http2.get('/rest/pl/fe/site/member/remove?site=' + $scope.mission.siteid + '&id=' + oMember.id).then(function() {
                        $scope.members.splice($scope.members.indexOf(oMember), 1);
                    });
                }
            });
        };
        $scope.notify = function(isBatch) {
            var rows = isBatch ? $scope.rows : null;
            var options = {
                matterTypes: CstNaming.notifyMatter,
                sender: 'schema:' + selected.mschema.id
            };
            pushnotify.open(selected.mschema.siteid, function(notify) {
                var url, targetAndMsg = {};
                if (notify.matters.length) {
                    if (rows) {
                        targetAndMsg.users = [];
                        Object.keys(rows.selected).forEach(function(key) {
                            if (rows.selected[key] === true) {
                                var rec = $scope.members[key];
                                targetAndMsg.users.push({ id: rec.id, userid: rec.userid });
                            }
                        });
                    }
                    targetAndMsg.message = notify.message;

                    url = '/rest/pl/fe/site/member/notice/send?site=' + selected.mschema.siteid;
                    targetAndMsg.schema = selected.mschema.id;
                    targetAndMsg.tmplmsg = notify.tmplmsg.id;

                    http2.post(url, targetAndMsg).then(function(data) {
                        noticebox.success('发送完成');
                    });
                }
            }, options);
        }
        $scope.$watch('rows.allSelected', function(nv) {
            var index = 0;
            if (nv == 'Y') {
                while (index < $scope.members.length) {
                    $scope.rows.selected[index++] = true;
                }
                $scope.rows.count = $scope.members.length;
            } else if (nv == 'N') {
                $scope.rows.reset();
            }
        });
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