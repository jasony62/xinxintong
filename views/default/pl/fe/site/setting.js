var app = angular.module('app', ['ngRoute', 'ui.tms', 'matters.xxt']);
app.config(['$locationProvider', '$routeProvider', function($lp, $rp) {
    $lp.html5Mode(true);
}]);
app.factory('MemberSchema', function($q, http2) {
    var MemberSchema = function(siteId) {
        this.siteId = siteId;
        this.baseUrl = '/rest/pl/fe/site/memberschema/';
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
app.controller('ctrlSite', ['$scope', '$location', 'http2', function($scope, $location, http2) {
    $scope.siteId = $location.search().site;
    http2.get('/rest/pl/fe/site/get?site=' + $scope.siteId, function(rsp) {
        $scope.site = rsp.data;
    });
}]);
app.controller('ctrlSet', ['$scope', 'http2', function($scope, http2) {
    $scope.sub = 'basic';
    $scope.gotoSub = function(name) {
        $scope.sub = name;
    };
    $scope.gotoSns = function(name) {
        location.href = '/rest/pl/fe/site/sns/' + name + '?site=' + $scope.siteId;
    };
    $scope.update = function(name) {
        var p = {};
        p[name] = $scope.site[name];
        http2.post('/rest/pl/fe/site/update?site=' + $scope.siteId, p, function(rsp) {});
    };
    $scope.setPic = function() {
        var options = {
            callback: function(url) {
                $scope.site.heading_pic = url + '?_=' + (new Date()) * 1;;
                $scope.update('heading_pic');
            }
        };
        $scope.$broadcast('mediagallery.open', options);
    };
    $scope.removePic = function() {
        $scope.features.heading_pic = '';
        $scope.update('heading_pic');
    };
    $scope.editPage = function(event, page) {
        event.preventDefault();
        event.stopPropagation();
        var pageid = $scope.site[page + '_page_id'];
        if (pageid === '0') {
            http2.get('/rest/pl/fe/site/pageCreate?site=' + $scope.siteId + '&page=' + page, function(rsp) {
                $scope.site[prop] = new String(rsp.data.id);
                location.href = '/rest/code?pid=' + rsp.data.id;
            });
        } else {
            location.href = '/rest/code?pid=' + pageid;
        }
    };
    $scope.resetPage = function(event, page) {
        event.preventDefault();
        event.stopPropagation();
        if (window.confirm('重置操作将覆盖已经做出的修改，确定重置？')) {
            var pageid = $scope.site[page + '_page_id'];
            if (pageid === '0') {
                http2.get('/rest/pl/fe/site/pageCreate?site=' + $scope.siteId + '&page=' + page, function(rsp) {
                    $scope.site[prop] = new String(rsp.data.id);
                    location.href = '/rest/code?pid=' + rsp.data.id;
                });
            } else {
                http2.get('/rest/pl/fe/site/pageReset?site=' + $scope.siteId + '&page=' + page, function(rsp) {
                    location.href = '/rest/code?pid=' + pageid;
                });
            }
        }
    };
}]);
app.controller('ctrlAdmin', ['$scope', '$modal', 'http2', function($scope, $modal, http2) {
    $scope.admins = [];
    $scope.isAdmin = true;
    $scope.add = function() {
        var url = '/rest/pl/fe/site/adminCreate';
        http2.get(url, function(rsp) {
            $scope.admins.push(rsp.data);
            $scope.select(rsp.data);
        });
    };
    $scope.remove = function(admin) {
        http2.get('/rest/pl/fe/site/adminRemove?uid=' + admin.uid, function(rsp) {
            var index = $scope.admins.indexOf(admin);
            $scope.admins.splice(index, 1);
            $scope.selected = false;
        });
    };
    $scope.select = function(admin) {
        $scope.selected = admin;
    };
}]);
app.controller('ctrlMember', ['$scope', 'http2', '$http', '$modal', 'MemberSchema', function($scope, http2, $http, $modal, MemberSchema) {
    var service = {
        memberSchema: new MemberSchema($scope.id)
    };
    var shiftAttr = function(schema) {
        schema.attrs = {
            mobile: schema.attr_mobile.split(''),
            email: schema.attr_email.split(''),
            name: schema.attr_name.split('')
        };
        angular.forEach(schema.extattr, function(ea) {
            ea.cfg2 = ea.cfg.split('');
        });
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
        ['姓名', 'name', [0, 1, -2, 3, -4, -5]],
    ];
    $scope.fullUrl = function(schema) {
        var url = '';
        !/^http/.test(schema.url) && (url = 'http://' + location.host);
        return url + schema.url + '?site=' + $scope.siteId + '&schema=' + schema.id;
    };
    $scope.addSchema = function() {
        var url = '/rest/pl/fe/site/memberschema/create?site=' + $scope.siteId;
        http2.get(url, function(rsp) {
            $scope.schemas.push(rsp.data);
        });
    };
    $scope.delSchema = function(api) {
        var url = '/rest/mp/authapi/delete?id=' + api.authid;
        http2.get(url, function(rsp) {
            var i = $scope.authapis.indexOf(api);
            $scope.authapis.splice(i, 1);
        });
    };
    $scope.updSchema = function(schema, field) {
        var pv = {};
        pv[field] = (/entry_statement|acl_statement|notpass_statement/.test(field)) ? encodeURIComponent(schema[field]) : schema[field];
        service.memberSchema.update(schema, pv).then(function() {
            if (field === 'type') {
                schema.url = rsp.data.url;
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
        $modal.open({
            templateUrl: 'schemaEditor.html',
            controller: ['$modalInstance', '$scope', function($mi, $scope) {
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
        $modal.open({
            templateUrl: 'schemaEditor.html',
            controller: ['$modalInstance', '$scope', 'attr', function($mi, $scope, attr) {
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
        if (schema.code_id != 0)
            location.href = '/rest/code?pid=' + schema.code_id;
        else {
            http2.get('/rest/code/create', function(rsp) {
                var nv = {
                    'code_id': rsp.data.id
                };
                service.memberSchema.update(schema, nv).then(function(rsp) {
                    schema.code_id = nv.code_id;
                    location.href = '/rest/code?pid=' + nv.code_id;
                });
            });
        }
    };
    $scope.resetCode = function(schema) {
        if (schema.code_id === '0') {
            http2.get('/rest/code/create', function(rsp) {
                var nv = {
                    'code_id': rsp.data.id
                };
                service.memberSchema.update(schema, nv).then(function(rsp) {
                    schema.code_id = nv.code_id;
                    location.href = '/rest/code?pid=' + nv.code_id;
                });
            });
        } else {
            if (window.confirm('重置操作将覆盖已经做出的修改，确定重置？')) {
                http2.get('/rest/pl/fe/site/memberschema/pageReset?site=' + $scope.id + '&codeId=' + schema.code_id, function(rsp) {
                    location.href = '/rest/code?pid=' + schema.code_id;
                });
            }
        }
    };
    service.memberSchema.get('N').then(function(schemas) {
        angular.forEach(schemas, function(schema) {
            shiftAttr(schema);
            $scope.schemas.push(schema);
        });
        if ($scope.schemas.length === 0) {
            $scope.schemas.push({
                type: 'inner',
                valid: 'N'
            });
        }
    });
}]);