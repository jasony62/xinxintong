define(['require'], function(require) {
    var ngApp = angular.module('app', ['ngRoute', 'ui.bootstrap', 'ui.tms', 'ui.xxt']);
    ngApp.config(['$locationProvider', '$provide', '$controllerProvider', '$routeProvider', function($lp, $provide, $cp, $rp) {
        var RouteParam = function(name, loadjs) {
            var baseURL = '/views/default/pl/fe/site/setting/';
            this.templateUrl = baseURL + name + '.html?_=' + (new Date() * 1);
            this.controller = 'ctrl' + name[0].toUpperCase() + name.substr(1);
            if (loadjs) {
                this.resolve = {
                    load: function($q) {
                        var defer = $q.defer();
                        require([baseURL + name + '.js'], function() {
                            defer.resolve();
                        });
                        return defer.promise;
                    }
                };
            }
        };
        $lp.html5Mode(true);
        ngApp.provider = {
            controller: $cp.register,
            service: $provide.service,
        };
        $rp.when('/rest/pl/fe/site/setting/basic', new RouteParam('basic'))
            .when('/rest/pl/fe/site/setting/page', new RouteParam('page', true))
            .when('/rest/pl/fe/site/setting/mschema', new RouteParam('mschema'))
            .when('/rest/pl/fe/site/setting/admin', new RouteParam('admin', true))
            .when('/rest/pl/fe/site/setting/notice', new RouteParam('notice', true))
            .when('/rest/pl/fe/site/setting/tmplmsg', new RouteParam('tmplmsg', true))
            .otherwise(new RouteParam('basic'));
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
        MemberSchema.prototype.update = function(schema, updated) {
            var deferred, url;
            deferred = $q.defer();
            url = this.baseUrl;
            url += 'update?site=' + this.siteId;
            url += '&type=' + schema.type;
            if (schema.id) url += '&id=' + schema.id;
            http2.post(url, updated, function(rsp) {
                deferred.resolve(rsp.data);
            });
            return deferred.promise;
        };
        return MemberSchema;
    });
    ngApp.controller('ctrlSite', ['$scope', '$location', 'http2', function($scope, $location, http2) {
        $scope.siteId = $location.search().site;
        http2.get('/rest/pl/fe/site/get?site=' + $scope.siteId, function(rsp) {
            $scope.site = rsp.data;
        });
    }]);
    ngApp.controller('ctrlSetting', ['$scope', 'http2', 'mediagallery', 'noticebox', function($scope, http2, mediagallery, noticebox) {
        var recommenSite, navSite;
        $scope.subView = '';
        $scope.$on('$locationChangeSuccess', function(event, currentRoute) {
            var subView = currentRoute.match(/([^\/]+?)\?/);
            $scope.subView = subView ? (subView[1] === 'setting' ? 'basic' : subView[1]) : 'basic';
        });
        $scope.update = function(name) {
            var p = {};
            p[name] = $scope.site[name];
            http2.post('/rest/pl/fe/site/update?site=' + $scope.siteId, p, function(rsp) {});
        };
        $scope.remove = function() {
            if (window.confirm('确定删除团队【' + $scope.site.name + '】？')) {
                var url = '/rest/pl/fe/site/remove?site=' + $scope.siteId;
                http2.get(url, function(rsp) {
                    location.href = '/rest/pl/fe';
                });
            }
        };
        $scope.quit = function() {
            if (window.confirm('确定退出团队？')) {
                var url = '/rest/pl/fe/site/setting/admin/remove?site=' + $scope.siteId + '&uid=' + $scope.site.uid;
                http2.get(url, function(rsp) {
                    location.href = '/rest/pl/fe';
                });
            }
        };
        $scope.setPic = function() {
            var options = {
                callback: function(url) {
                    $scope.site.heading_pic = url + '?_=' + (new Date() * 1);
                    $scope.update('heading_pic');
                }
            };
            mediagallery.open($scope.siteId, options);
        };
        $scope.removePic = function() {
            $scope.site.heading_pic = '';
            $scope.update('heading_pic');
        };
        $scope.editPage = function(event, page) {
            event.preventDefault();
            event.stopPropagation();
            var prop = page + '_page_name',
                name = $scope.site[prop];
            if (name && name.length) {
                location.href = '/rest/pl/fe/code?site=' + $scope.siteId + '&name=' + name;
            } else {
                http2.get('/rest/pl/fe/site/pageCreate?site=' + $scope.siteId + '&page=' + page, function(rsp) {
                    $scope.site[prop] = rsp.data.name;
                    location.href = '/rest/pl/fe/code?site=' + $scope.siteId + '&name=' + rsp.data.name;
                });
            }
        };
        $scope.resetPage = function(event, page) {
            event.preventDefault();
            event.stopPropagation();
            if (window.confirm('重置操作将覆盖已经做出的修改，确定重置？')) {
                var name = $scope.site[page + '_page_name'];
                if (name && name.length) {
                    http2.get('/rest/pl/fe/site/pageReset?site=' + $scope.siteId + '&page=' + page, function(rsp) {
                        location.href = '/rest/pl/fe/code?site=' + $scope.siteId + '&name=' + name;
                    });
                } else {
                    http2.get('/rest/pl/fe/site/pageCreate?site=' + $scope.siteId + '&page=' + page, function(rsp) {
                        $scope.site[prop] = rsp.data.name;
                        location.href = '/rest/pl/fe/code?site=' + $scope.siteId + '&name=' + rsp.data.name;
                    });
                }
            }
        };
        $scope.openPage = function(page) {
            var name = $scope.site[page + '_page_name'];
            if (name) {
                location.href = '/rest/site/home?site=' + $scope.siteId;
            }
        };
        $scope.gotoSns = function(snsName) {
            location.href = '/rest/pl/fe/site/sns/' + snsName + '?site=' + $scope.siteId;
        };
        /* 下面两段代码的逻辑要优化 */
        // http2.get('/rest/pl/be/platform/get', function(rsp) {
        //     if (rsp.data.home_nav) {
        //         $scope.home_nav = rsp.data.home_nav;
        //         $scope.home_nav.forEach(function(item) {
        //             if (item.site.id == $scope.site.id) {
        //                 $scope.navSite = navSite = item;
        //             }
        //         })
        //     }
        // });
        // http2.get('/rest/pl/be/home/recommend/listSite', function(rsp) {
        //     $scope.sites = rsp.data.sites;
        //     $scope.sites.forEach(function(item) {
        //         if (item.siteid == $scope.site.id) {
        //             $scope.recommenSite = recommenSite = item;
        //         }
        //     });
        // });
    }]);
    ngApp.controller('ctrlBasic', ['$scope', function($scope) {
        (function() {
            var text2Clipboard = new ZeroClipboard(document.querySelectorAll('.text2Clipboard'));
        })();
        $scope.homeURL = location.protocol + '//' + location.host + '/rest/site/home?site=' + $scope.siteId;
    }]);
    ngApp.controller('ctrlMschema', ['$scope', 'http2', '$http', '$uibModal', 'MemberSchema', function($scope, http2, $http, $uibModal, MemberSchema) {
        function shiftAttr(schema) {
            schema.attrs = {
                mobile: schema.attr_mobile.split(''),
                email: schema.attr_email.split(''),
                name: schema.attr_name.split('')
            };
            angular.forEach(schema.extattr, function(ea) {
                ea.cfg2 = ea.cfg.split('');
            });
        };
        var service = {
            memberSchema: new MemberSchema($scope.siteId)
        };
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
        $scope.fullUrl = function(schema) {
            var url = '';
            !/^http/.test(schema.url) && (url = location.protocol + '//' + location.host);
            return url + schema.url + '?site=' + $scope.siteId + '&schema=' + schema.id;
        };
        $scope.addSchema = function() {
            var url = '/rest/pl/fe/site/member/schema/create?site=' + $scope.siteId;
            http2.post(url, {}, function(rsp) {
                shiftAttr(rsp.data);
                $scope.schemas.push(rsp.data);
            });
        };
        $scope.delSchema = function(schema) {
            var url = '/rest/pl/fe/site/member/schema/delete?site=' + $scope.siteId + '&id=' + schema.id;
            http2.get(url, function(rsp) {
                var i = $scope.schemas.indexOf(schema);
                $scope.schemas.splice(i, 1);
            });
        };
        $scope.updQy = function(schema, field) {
            if (schema.qy_ab === 'Y') {
                angular.forEach($scope.schemas, function(s) {
                    if (s !== schema && s.qy_ab === 'Y') {
                        schema.qy_ab = 'N';
                        alert('您已经定义了"企业号同步通信录使用",请先取消');
                        return;
                    }
                })
                schema.qy_ab === 'Y' && ($scope.updSchema(schema, field));
            } else {
                $scope.updSchema(schema, field);
            }
        }
        $scope.updSchema = function(schema, field) {
            var pv = {};
            service.memberSchema.update(schema, pv).then(function(data) {
                if (schema.id === undefined) {
                    shiftAttr(data);
                    angular.extend(schema, data);
                } else {
                    if (field === 'type') {
                        schema.url = data.url;
                    }
                }
            });
        };
        $scope.updAttr = function(schema, item) {
            var attrs = schema.attrs[item],
                p = {};
            if ((item === 'mobile' || item === 'email') && attrs[5] === '1') {
                attrs[0] = '0';
                attrs[1] = '1';
                attrs[2] = '1';
                attrs[3] = '1';
            }
            p['attr_' + item] = attrs.join('');
            service.memberSchema.update(schema, p);
        };
        $scope.addExtattr = function(schema) {
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
                if (!schema.extattr) schema.extattr = [];
                schema.extattr.push(attr);
                $scope.updSchema(schema, 'extattr');
            });
        };
        $scope.editExtattr = function(schema, attr) {
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
                    schema.extattr.splice(schema.extattr.indexOf(attr), 1);
                }
                $scope.updSchema(schema, 'extattr');
            });
        };
        $scope.gotoCode = function(schema) {
            if (schema.page_code_name && schema.page_code_name.length) {
                location.href = '/rest/pl/fe/code?site=' + $scope.siteId + '&name=' + schema.page_code_name;
            } else {
                http2.get('/rest/pl/fe/code/create?site=' + $scope.siteId, function(rsp) {
                    var nv = {
                        'code_id': rsp.data.id,
                        'page_code_name': rsp.data.name
                    };
                    service.memberSchema.update(schema, nv).then(function(rsp) {
                        schema.code_id = nv.code_id;
                        schema.page_code_name = nv.page_code_name;
                        location.href = '/rest/pl/fe/code?site=' + $scope.siteId + '&name=' + nv.page_code_name;
                    });
                });
            }
        };
        $scope.resetCode = function(schema) {
            if (schema.page_code_name && schema.page_code_name.length) {
                if (window.confirm('重置操作将覆盖已经做出的修改，确定重置？')) {
                    http2.get('/rest/pl/fe/site/member/schema/pageReset?site=' + $scope.siteId + '&name=' + schema.page_code_name, function(rsp) {
                        location.href = '/rest/pl/fe/code?site=' + $scope.siteId + '&name=' + schema.page_code_name;
                    });
                }
            } else {
                http2.get('/rest/pl/fe/code/create?site=' + $scope.siteId, function(rsp) {
                    var nv = {
                        'code_id': rsp.data.id,
                        'page_code_name': rsp.data.name
                    };
                    service.memberSchema.update(schema, nv).then(function(rsp) {
                        schema.code_id = nv.code_id;
                        schema.page_code_name = nv.page_code_name;
                        location.href = '/rest/pl/fe/code?site=' + $scope.siteId + '&name=' + nv.page_code_name;
                    });
                });
            }
        };
        service.memberSchema.get('N').then(function(schemas) {
            schemas.forEach(function(schema) {
                shiftAttr(schema);
                $scope.schemas.push(schema);
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
        });
    }]);
    /***/
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
    /***/
    return ngApp;
});