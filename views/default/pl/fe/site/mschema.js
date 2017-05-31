define(['require'], function(require) {
    'use strict';
    var ngApp = angular.module('app', ['ngRoute', 'ui.bootstrap', 'ui.tms', 'ui.xxt', 'service.matter']);
    ngApp.config(['srvSiteProvider', function(srvSiteProvider) {
        var siteId = location.search.match(/site=([^&]*)/)[1];
        console.log(siteId);
        srvSiteProvider.config(siteId);
    }]);
    ngApp.factory('MemberSchema', function($q, http2) {
        var MemberSchema = function(siteId) {
            this.siteId = siteId;
            this.baseUrl = '/rest/pl/fe/site/member/schema/';
        };
        MemberSchema.prototype.get = function(own) {
            var deferred, url;
            deferred = $q.defer();
            own === undefined && (own === 'N');
            url = this.baseUrl;
            url += 'list?site=' + this.siteId;
            url += '&own=' + own;
            http2.get(url, function(rsp) {
                deferred.resolve(rsp.data);
            });
            return deferred.promise;
        }
        MemberSchema.prototype.update = function(oSchema, updated) {
            var deferred, url;
            deferred = $q.defer();
            url = this.baseUrl;
            url += 'update?site=' + this.siteId;
            url += '&type=' + oSchema.type;
            if (oSchema.id) url += '&id=' + oSchema.id;
            http2.post(url, updated, function(rsp) {
                deferred.resolve(rsp.data);
            });
            return deferred.promise;
        };
        return MemberSchema;
    });
    ngApp.controller('ctrlMschema', ['$scope', 'http2', 'srvSite', 'MemberSchema', function($scope, http2, srvSite, MemberSchema) {
        function shiftAttr(oSchema) {
            oSchema.attrs = {
                mobile: oSchema.attr_mobile.split(''),
                email: oSchema.attr_email.split(''),
                name: oSchema.attr_name.split('')
            };
            angular.forEach(oSchema.extattr, function(ea) {
                ea.cfg2 = ea.cfg.split('');
            });
        }
        var service = {};

        srvSite.get().then(function(site) {
            $scope.site = site;
            service.memberSchema = new MemberSchema(site.id);
            service.memberSchema.get('N').then(function(schemas) {
                var entryMschemaId, oEntryMschema;
                if (location.hash) {
                    entryMschemaId = location.hash.substr(1);
                }
                schemas.forEach(function(schema) {
                    shiftAttr(schema);
                    $scope.schemas.push(schema);
                    if (schema.id === entryMschemaId) {
                        oEntryMschema = schema;
                    }
                });
                if ($scope.schemas.length === 0) {
                    $scope.schemas.push({
                        type: 'inner',
                        valid: 'N',
                        attrs: {
                            mobile: ['0', '0', '0', '0', '0', '0', '0'],
                            email: ['0', '0', '0', '0', '0', '0', '0'],
                            name: ['0', '0', '0', '0', '0', '0', '0']
                        }
                    });
                }
                if (oEntryMschema) {
                    $scope.chooseSchema(oEntryMschema);
                } else if (schemas.length) {
                    $scope.chooseSchema(schemas[0]);
                }
            });
        });
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
        $scope.schemas = [];
        $scope.attrOps = [
            ['手机', 'mobile', [0, 1, 2, 3, 4, 5]],
            ['邮箱', 'email', [0, 1, 2, 3, 4, 5]],
            ['姓名', 'name', [-99, 1, -2, 3, -4, -5]],
        ];
        $scope.chooseSchema = function(oSchema) {
            $scope.choosedSchema = oSchema;
        };
        $scope.fullUrl = function() {
            var url = '';
            !/^http/.test($scope.choosedSchema.url) && (url = 'http://' + location.host);
            return url + $scope.choosedSchema.url + '?site=' + $scope.site.id + '&schema=' + $scope.choosedSchema.id;
        };
        $scope.addSchema = function() {
            var url = '/rest/pl/fe/site/member/schema/create?site=' + $scope.site.id;
            http2.post(url, {}, function(rsp) {
                shiftAttr(rsp.data);
                $scope.schemas.push(rsp.data);
            });
        };
        $scope.delSchema = function() {
            var url, schema;
            schema = $scope.choosedSchema;
            url = '/rest/pl/fe/site/member/schema/delete?site=' + $scope.site.id + '&id=' + schema.id;
            http2.get(url, function(rsp) {
                var i = $scope.schemas.indexOf(schema);
                $scope.schemas.splice(i, 1);
                $scope.choosedSchema = null;
            });
        };
        $scope.updQy = function(field) {
            var schema = $scope.choosedSchema;
            if (schema.qy_ab === 'Y') {
                $scope.schemas.forEach(function(s) {
                    if (s !== schema && s.qy_ab === 'Y') {
                        schema.qy_ab = 'N';
                        alert('您已经定义了"企业号同步通信录使用",请先取消');
                        return;
                    }
                })
                schema.qy_ab === 'Y' && ($scope.updSchema(schema, field));
            } else {
                $scope.updSchema(field);
            }
        }
        $scope.updSchema = function(field) {
            var pv = {},
                schema = $scope.choosedSchema;
            pv[field] = (/entry_statement|acl_statement|notpass_statement/.test(field)) ? encodeURIComponent(schema[field]) : schema[field];
            service.memberSchema.update($scope.choosedSchema, pv).then(function(data) {
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
            service.memberSchema.update($scope.choosedSchema, p);
        };
        $scope.addExtattr = function() {
            $uibModal.open({
                templateUrl: 'schemaEditor.html',
                controller: ['$uibModalInstance', '$scope', function($mi, $scope) {
                    $scope.attr = {
                        id: 'ea' + (new Date()).getTime(),
                        label: '扩展属性',
                        cfg: '000000'
                    };
                    $scope.close = function() {
                        $mi.dismiss();
                    };
                    $scope.ok = function() {
                        $mi.close($scope.attr);
                    };
                }],
                backdrop: 'static'
            }).result.then(function(attr) {
                if (!$scope.choosedSchema.extattr) $scope.choosedSchema.extattr = [];
                $scope.choosedSchema.extattr.push(attr);
                $scope.updSchema('extattr');
            });
        };
        $scope.editExtattr = function(attr) {
            $uibModal.open({
                templateUrl: 'schemaEditor.html',
                controller: ['$uibModalInstance', '$scope', 'attr', function($mi, $scope, attr) {
                    $scope.canRemove = true;
                    $scope.attr = angular.copy(attr);
                    $scope.close = function() {
                        $mi.dismiss();
                    };
                    $scope.remove = function() {
                        $mi.close({
                            action: 'remove'
                        });
                    };
                    $scope.ok = function() {
                        $mi.close({
                            action: 'update',
                            data: $scope.attr
                        });
                    };
                }],
                backdrop: 'static',
                resolve: {
                    attr: function() {
                        return attr;
                    }
                }
            }).result.then(function(rst) {
                if (rst.action === 'update') {
                    attr.id = rst.data.id;
                    attr.label = rst.data.label;
                } else if (rst.action === 'remove') {
                    $scope.choosedSchema.extattr.splice($scope.choosedSchema.extattr.indexOf(attr), 1);
                }
                $scope.updSchema('extattr');
            });
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
                    service.memberSchema.update($scope.choosedSchema, nv).then(function(rsp) {
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
                    service.memberSchema.update($scope.choosedSchema, nv).then(function(rsp) {
                        $scope.choosedSchema.code_id = nv.code_id;
                        $scope.choosedSchema.page_code_name = nv.page_code_name;
                        location.href = '/rest/pl/fe/code?site=' + $scope.site.id + '&name=' + nv.page_code_name;
                    });
                });
            }
        };
    }]);
    /**
     * 微信场景二维码
     */
    ngApp.controller('ctrlWxQrcode', ['$scope', 'http2', function($scope, http2) {
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
    /***/
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
    /***/
    return ngApp;
});
