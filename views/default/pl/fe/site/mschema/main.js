define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlMain', ['$scope', '$location', '$uibModal', 'http2', 'srvSite', 'srvMschema', function($scope, $location, $uibModal, http2, srvSite, srvMschema) {
        $scope.days = [{
            n: '会话',
            v: '0'
        }, {
            n: '1天',
            v: '1'
        }, {
            n: '1周',
            v: '7'
        }, {
            n: '1月',
            v: '30'
        }, {
            n: '1年',
            v: '365'
        }];
        $scope.attrOps = [
            ['手机', 'mobile', [0, 1, 2, 3, 4, 5]],
            ['邮箱', 'email', [0, 1, 2, 3, 4, 5]],
            ['姓名', 'name', [-99, 1, -2, 3, -4, -5]],
        ];
        $scope.updQy = function() {
            var schema = $scope.choosedSchema;
            if (schema.qy_ab === 'Y') {
                $scope.schemas.forEach(function(s) {
                    if (s !== schema && s.qy_ab === 'Y') {
                        schema.qy_ab = 'N';
                        alert('您已经定义了"企业号同步通信录使用",请先取消');
                        return;
                    }
                })
                schema.qy_ab === 'Y' && ($scope.updSchema('qy_ab'));
            } else {
                $scope.updSchema('qy_ab');
            }
        };
        $scope.updSchema = function(field) {
            var pv = {},
                schema = $scope.choosedSchema;
            pv[field] = schema[field];
            srvMschema.update($scope.choosedSchema, pv).then(function(data) {
                if ($scope.choosedSchema.id === undefined) {
                    shiftAttr(data);
                    angular.extend(schema, data);
                } else {
                    if (field === 'type') {
                        $scope.choosedSchema.url = data.url;
                    }
                }
            });
        };
        $scope.updAttr = function(item) {
            var attrs = $scope.choosedSchema.attrs[item],
                p = {};
            if ((item === 'mobile' || item === 'email') && attrs[5] === '1') {
                attrs[0] = '0';
                attrs[1] = '1';
                attrs[2] = '1';
                attrs[3] = '1';
            }
            p['attr_' + item] = attrs.join('');
            srvMschema.update($scope.choosedSchema, p);
        };
        $scope.gotoExtattr = function(oSchema) {
            $location.path('/rest/pl/fe/site/mschema/extattr');
        };
        $scope.gotoCode = function() {
            if ($scope.choosedSchema.page_code_name && $scope.choosedSchema.page_code_name.length) {
                location.href = '/rest/pl/fe/code?site=' + $scope.site.id + '&name=' + $scope.choosedSchema.page_code_name;
            } else {
                http2.get('/rest/pl/fe/code/create?site=' + $scope.site.id, function(rsp) {
                    var nv = {
                        'code_id': rsp.data.id,
                        'page_code_name': rsp.data.name
                    };
                    srvMschema.update($scope.choosedSchema, nv).then(function(rsp) {
                        $scope.choosedSchema.code_id = nv.code_id;
                        $scope.choosedSchema.page_code_name = nv.page_code_name;
                        location.href = '/rest/pl/fe/code?site=' + $scope.site.id + '&name=' + nv.page_code_name;
                    });
                });
            }
        };
        $scope.resetCode = function(schema) {
            if ($scope.choosedSchema.page_code_name && $scope.choosedSchema.page_code_name.length) {
                if (window.confirm('重置操作将覆盖已经做出的修改，确定重置？')) {
                    http2.get('/rest/pl/fe/site/member/schema/pageReset?site=' + $scope.site.id + '&name=' + $scope.choosedSchema.page_code_name, function(rsp) {
                        location.href = '/rest/pl/fe/code?site=' + $scope.site.id + '&name=' + $scope.choosedSchema.page_code_name;
                    });
                }
            } else {
                http2.get('/rest/pl/fe/code/create?site=' + $scope.site.id, function(rsp) {
                    var nv = {
                        'code_id': rsp.data.id,
                        'page_code_name': rsp.data.name
                    };
                    srvMschema.update($scope.choosedSchema, nv).then(function(rsp) {
                        $scope.choosedSchema.code_id = nv.code_id;
                        $scope.choosedSchema.page_code_name = nv.page_code_name;
                        location.href = '/rest/pl/fe/code?site=' + $scope.site.id + '&name=' + nv.page_code_name;
                    });
                });
            }
        };
        $scope.importSchema = function() {
            $uibModal.open({
                templateUrl: 'importSchema.html',
                controller: ['$scope', '$uibModalInstance', '$q', 'noticebox', function($scope2, $mi, $q, noticebox) {
                    http2.get('/rest/pl/fe/site/member/schema/listImportSchema?site=' + $scope.site.id + '&id=' + $scope.choosedSchema.id, function(rsp) {
                        $scope2.importSchemas = rsp.data;
                    });

                    var model;
                    $scope2.model = model = {
                        selected: []
                    };
                    $scope2.cancel = function() { $mi.dismiss(); };
                    $scope2.ok = function() {
                        var schemas = [];
                        model.selected.forEach(function(selected, index) {
                            if (selected) {
                                schemas.push($scope2.importSchemas[index].id);
                            }
                        });

                        if (schemas.length > 0) {
                            $scope2.importSchemaPost(schemas, 0);
                            $mi.close();
                        }

                    };
                    $scope2.importSchemaPost = function(schemas, rounds) {
                        var defer = $q.defer();
                        http2.post('/rest/pl/fe/site/member/schema/importSchema?site=' + $scope.site.id + '&id=' + $scope.choosedSchema.id + '&rounds=' + rounds, schemas, function(rsp) {
                            if (rsp.data.state !== 'end') {
                                var group = parseInt(rsp.data.group) + 1;
                                noticebox.success('已导入用户' + rsp.data.plan + '/' + rsp.data.total);
                                $scope2.importSchemaPost(schemas, group);
                            } else {
                                defer.resolve(rsp.data);
                                noticebox.success('已导入用户' + rsp.data.plan + '/' + rsp.data.total);
                                return defer.promise;
                            }
                        });
                    };
                }],
                backdrop: 'static',
            })
        };
    }]);
    /**
     * 微信场景二维码
     */
    ngApp.provider.controller('ctrlWxQrcode', ['$scope', 'http2', function($scope, http2) {
        var oMschema;
        $scope.create = function() {
            var url;
            url = '/rest/pl/fe/site/sns/wx/qrcode/create?site=' + oMschema.siteid;
            url += '&matter_type=mschema&matter_id=' + oMschema.id;
            http2.get(url, function(rsp) {
                $scope.qrcode = rsp.data;
            });
        };
        $scope.download = function() {
            $('<a href="' + $scope.qrcode.pic + '" download="微信二维码.jpeg"></a>')[0].click();
        };
        $scope.$watch('choosedSchema', function(mschema) {
            if (mschema) {
                oMschema = mschema;
                http2.get('/rest/pl/fe/site/member/schema/wxQrcode?site=' + oMschema.siteid + '&mschema=' + oMschema.id, function(rsp) {
                    var qrcodes = rsp.data;
                    $scope.qrcode = qrcodes.length ? qrcodes[0] : false;
                });
            }
        });
    }]);
    ngApp.provider.controller('ctrlInvite', ['$scope', '$uibModal', 'http2', function($scope, $uibModal, http2) {
        function listInvite(oSchema) {
            http2.get('/rest/pl/fe/site/member/invite/list?schema=' + oSchema.id, function(rsp) {
                $scope.invites = rsp.data.invites;
            });
        }
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
                http2.post('/rest/pl/fe/site/member/invite/add?schema=' + $scope.choosedSchema.id, option, function(rsp) {
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
        listInvite($scope.choosedSchema);
    }]);
});