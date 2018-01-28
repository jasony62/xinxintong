define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlMschema', ['$scope', '$location', '$sce', '$uibModal', 'http2', 'srvSite', function($scope, $location, $sce, $uibModal, http2, srvSite) {
        function value2Label(oSchema, value) {
            var label, aVal, aLab = [];

            if (label = value) {
                if (oSchema.ops && oSchema.ops.length) {
                    if (oSchema.type === 'single') {
                        for (var i = 0, ii = oSchema.ops.length; i < ii; i++) {
                            if (oSchema.ops[i].v === label) {
                                label = oSchema.ops[i].l;
                                break;
                            }
                        }
                    } else if (oSchema.type === 'multiple') {
                        aVal = [];
                        for (var k in label) {
                            if (label[k] === 'Y') {
                                aVal.push(k);
                            }
                        }
                        oSchema.ops.forEach(function(op) {
                            aVal.indexOf(op.v) !== -1 && aLab.push(op.l);
                        });
                        label = aLab.join(',');
                    }
                }
            } else {
                label = '';
            }
            return $sce.trustAsHtml(label);
        }

        function processExtattr(oMember) {
            oMember._extattr = {};
            selected.mschema.extAttrs.forEach(function(oExtAttr) {
                if (/single|multiple/.test(oExtAttr.type)) {
                    if (oMember.extattr[oExtAttr.id]) {
                        oMember._extattr[oExtAttr.id] = value2Label(oExtAttr, oMember.extattr[oExtAttr.id]);
                    }
                } else {
                    oMember._extattr[oExtAttr.id] = oMember.extattr[oExtAttr.id];
                }
            });
        }

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
                //listInvite(mschema);
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
            if (window.confirm('确认删除通讯录？')) {
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
            http2.get(url, function(rsp) {
                var members;
                members = rsp.data.members;
                if (members.length) {
                    if (selected.mschema.extAttrs.length) {
                        members.forEach(function(oMember) {
                            processExtattr(oMember);
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
                    http2.post('/rest/pl/fe/site/member/update?site=' + $scope.mission.siteid + '&id=' + oMember.id, newData, function(rsp) {
                        angular.extend(oMember, newData);
                        processExtattr(oMember);
                    });
                } else if (rst.action === 'remove') {
                    http2.get('/rest/pl/fe/site/member/remove?site=' + $scope.mission.siteid + '&id=' + oMember.id, function() {
                        $scope.members.splice($scope.members.indexOf(oMember), 1);
                    });
                }
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