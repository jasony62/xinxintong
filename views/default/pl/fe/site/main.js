var app = angular.module('app', ['ngRoute', 'ui.tms', 'matters.xxt']);
app.config(['$locationProvider', '$routeProvider', function($lp, $rp) {
    $lp.html5Mode(true);
    $rp.when('/rest/pl/fe/site/setting', {
        templateUrl: '/views/default/pl/fe/site/setting.html?_=1',
        controller: 'ctrlSet',
    }).when('/rest/pl/fe/site/console', {
        templateUrl: '/views/default/pl/fe/site/console.html?_=1',
        controller: 'ctrlConsole',
    }).when('/rest/pl/fe/site/matter', {
        templateUrl: '/views/default/pl/fe/site/matter.html?_=1',
        controller: 'ctrlMatter',
    }).otherwise({
        templateUrl: '/views/default/pl/fe/site/console.html?_=1',
        controller: 'ctrlConsole'
    });
}]);
app.factory('Authapi', function($q, http2) {
    var Authapi = function() {
        this.baseUrl = '/rest/mp/authapi/';
    };
    Authapi.prototype.get = function(own) {
        var deferred, url;
        deferred = $q.defer();
        own === undefined && (own === 'N');
        url = this.baseUrl + 'list?own=' + own;
        http2.get(url, function(rsp) {
            deferred.resolve(rsp.data);
        });
        return deferred.promise;
    }
    Authapi.prototype.update = function(api, updated) {
        var deferred, url;
        deferred = $q.defer();
        url = this.baseUrl + 'update?type=' + api.type;
        if (api.authid) url += '&id=' + api.authid;
        http2.post(url, updated, function(rsp) {
            deferred.resolve(rsp.data);
        });
        return deferred.promise;
    };
    return Authapi;
});
app.factory('Mp', function($q, http2) {
    var Mp = function() {};
    Mp.prototype.get = function() {
        var deferred;
        deferred = $q.defer();
        http2.get('/rest/mp/mpaccount/get', function(rsp) {
            deferred.resolve(rsp.data);
        });
        return deferred.promise;
    };
    Mp.prototype.relayGet = function() {
        var deferred;
        deferred = $q.defer();
        http2.get('/rest/mp/relay/get', function(rsp) {
            deferred.resolve(rsp.data);
        });
        return deferred.promise;
    };
    return Mp;
});
app.controller('ctrlSite', ['$scope', '$location', 'http2', function($scope, $location, http2) {
    $scope.subView = 'console';
    $scope.$on('$routeChangeSuccess', function(evt, nextRoute, lastRoute) {
        $scope.subView = nextRoute.loadedTemplateUrl.match(/\/([^\/]+)\.html/)[1];
    });
    $scope.id = $location.search().id;
    http2.get('/rest/pl/fe/site/get?id=' + $scope.id, function(rsp) {
        $scope.site = rsp.data;
    });
}]);
app.controller('ctrlSet', ['$scope', 'http2', function($scope, http2) {
    $scope.sub = 'basic';
    $scope.gotoSub = function(name) {
        $scope.sub = name;
    };
    $scope.gotoSns = function(name) {
        location.href = '/rest/pl/fe/site/sns/' + name + '?id=' + $scope.id;
    };
    $scope.update = function(name) {
        var p = {};
        p[name] = $scope.site[name];
        http2.post('/rest/pl/fe/site/update?id=' + $scope.id, p, function(rsp) {});
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
            http2.get('/rest/pl/fe/site/pageCreate?id=' + $scope.id + '&page=' + page, function(rsp) {
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
                http2.get('/rest/pl/fe/site/pageCreate?id=' + $scope.id + '&page=' + page, function(rsp) {
                    $scope.site[prop] = new String(rsp.data.id);
                    location.href = '/rest/code?pid=' + rsp.data.id;
                });
            } else {
                http2.get('/rest/pl/fe/site/pageReset?id=' + $scope.id + '&page=' + page, function(rsp) {
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
app.controller('ctrlConsole', ['$scope', 'http2', function($scope, http2) {
    $scope.open = function(matter) {
        if (matter.matter_type === 'article') {
            location.href = '/rest/pl/fe/matter/article?id=' + matter.matter_id + '&site=' + $scope.id;
        } else if (matter.matter_type === 'enroll') {
            location.href = '/rest/pl/fe/matter/enroll?id=' + matter.matter_id + '&site=' + $scope.id;
        } else if (matter.matter_type === 'mission') {
            location.href = '/rest/mp/mission/setting?id=' + matter.matter_id + '&site=' + $scope.id;
        }
    };
    $scope.addArticle = function() {
        http2.get('/rest/mp/matter/article/create?mpid=' + $scope.id, function(rsp) {
            location.href = '/rest/mp/matter/article?id=' + rsp.data;
        });
    };
    $scope.addEnroll = function() {
        var url;
        url = '/rest/mp/app/enroll/create?mpid=' + $scope.id;
        http2.post(url, {}, function(rsp) {
            location.href = '/rest/mp/app/enroll/detail?aid=' + rsp.data.id;
        });
    };
    $scope.addTask = function() {
        http2.get('/rest/mp/mission/create?mpid=' + $scope.id, function(rsp) {
            location.href = '/rest/mp/mission/setting?id=' + rsp.data.id;
        });
    };
    http2.get('/rest/pl/fe/site/console/recent?id=' + $scope.id, function(rsp) {
        $scope.matters = rsp.data.matters;
    });
}]);
app.controller('ctrlAuth', ['$scope', 'http2', '$http', '$modal', 'Mp', 'Authapi', function($scope, http2, $http, $modal, Mp, Authapi) {
    var service = {
        mp: new Mp(),
        authapi: new Authapi()
    };
    var shiftAuthapiAttr = function(authapi) {
        authapi.attrs = {
            mobile: authapi.attr_mobile.split(''),
            email: authapi.attr_email.split(''),
            name: authapi.attr_name.split(''),
            password: authapi.attr_password.split('')
        };
        angular.forEach(authapi.extattr, function(ea) {
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
    $scope.authapis = [];
    $scope.pAuthapis = [];
    $scope.authAttrOps = [
        ['手机', 'mobile', [0, 1, 2, 3, 4, 5]],
        ['邮箱', 'email', [0, 1, 2, 3, 4, 5]],
        ['姓名', 'name', [0, 1, -2, 3, -4, -5]],
        ['密码', 'password', [0, -1, -2, -3, -4, -5]],
    ];
    $scope.fullAuthUrl = function(authapi) {
        var url = '';
        !/^http/.test(authapi.url) && (url = 'http://' + location.host);
        return url + authapi.url + '?mpid=' + $scope.mpaccount.mpid + '&authid=' + authapi.authid;
    };
    $scope.canImport2Qy = function(authapi) {
        return $scope.mpaccount.mpsrc === 'qy' && authapi.valid === 'Y' && authapi.type === 'cus';
    };
    $scope.canSyncFromQy = function(authapi) {
        return $scope.mpaccount.mpsrc === 'qy' && authapi.valid === 'Y';
    };
    $scope.addAuthapi = function() {
        var url = '/rest/mp/authapi/create';
        http2.get(url, function(rsp) {
            $scope.authapis.push(rsp.data);
        });
    };
    $scope.delAuthapi = function(api) {
        var url = '/rest/mp/authapi/delete?id=' + api.authid;
        http2.get(url, function(rsp) {
            var i = $scope.authapis.indexOf(api);
            $scope.authapis.splice(i, 1);
        });
    };
    $scope.updAuthapi = function(api, field) {
        var url, pv;
        url = '/rest/mp/authapi/update?type=' + api.type;
        api.authid && (url += '&id=' + api.authid);
        pv = {};
        pv[field] = (/entry_statement|acl_statement|notpass_statement/.test(field)) ? encodeURIComponent(api[field]) : api[field];
        http2.post(url, pv, function(rsp) {
            if (field === 'type') {
                api.url = rsp.data.url;
            }
        });
    };
    $scope.updAuthAttr = function(authapi, item) {
        var attrs = authapi.attrs[item],
            p = {};
        if ((item === 'mobile' || item === 'email') && attrs[5] === '1') {
            attrs[0] = '0';
            attrs[1] = '1';
            attrs[2] = '1';
            attrs[3] = '1';
        }
        p['attr_' + item] = attrs.join('');
        http2.post('/rest/mp/authapi/updateUserauth?authid=' + authapi.authid, p);
    };
    $scope.updAuthExtattr = function(authapi, attr) {
        attr.cfg = attr.cfg2.join('');
        $scope.updAuthapi(authapi, 'extattr');
    };
    $scope.addExtattr = function(authapi) {
        $modal.open({
            templateUrl: 'authapiEditor.html',
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
            if (!authapi.extattr) authapi.extattr = [];
            authapi.extattr.push(attr);
            $scope.updAuthapi(authapi, 'extattr');
        });
    };
    $scope.editExtattr = function(authapi, attr) {
        $modal.open({
            templateUrl: 'authapiEditor.html',
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
                authapi.extattr.splice(authapi.extattr.indexOf(attr), 1);
            }
            $scope.updAuthapi(authapi, 'extattr');
        });
    };
    $scope.gotoCode = function(authapi) {
        if (authapi.auth_code_id != 0)
            location.href = '/rest/code?pid=' + authapi.auth_code_id;
        else {
            http2.get('/rest/code/create', function(rsp) {
                var nv = {
                    'auth_code_id': rsp.data.id
                };
                service.authapi.update(authapi, nv).then(function(rsp) {
                    authapi.auth_code_id = nv.auth_code_id;
                    location.href = '/rest/code?pid=' + nv.auth_code_id;
                });
            });
        }
    };
    $scope.resetCode = function(authapi) {
        if (authapi.auth_code_id === '0') {
            http2.get('/rest/code/create', function(rsp) {
                var nv = {
                    'auth_code_id': rsp.data.id
                };
                service.authapi.update(authapi, nv).then(function(rsp) {
                    authapi.auth_code_id = nv.auth_code_id;
                    location.href = '/rest/code?pid=' + nv.auth_code_id;
                });
            });
        } else {
            if (window.confirm('重置操作将覆盖已经做出的修改，确定重置？')) {
                http2.get('/rest/mp/authapi/pageReset?codeId=' + authapi.auth_code_id, function(rsp) {
                    location.href = '/rest/code?pid=' + authapi.auth_code_id;
                });
            }
        }
    };
    $scope.import2QyRunning = false;
    $scope.sync2QyRunning = false;
    $scope.syncFromQyRunning = false;
    $scope.import2Qy = function(authapi) {
        var url = authapi.url + '/import2Qy';
        url += '?mpid=' + $scope.mpaccount.mpid;
        url += '&authid=' + authapi.authid;
        var doImport = function(param) {
            $scope.import2QyRunning = true;
            var url2 = url;
            param && (url2 += '&next=' + param.next);
            param && param.step !== undefined && (url2 += '&step=' + param.step);
            $http.get(url2).success(function(rsp) {
                $scope.import2QyRunning = false;
                if (angular.isString(rsp))
                    $scope.$root.errmsg = rsp;
                else if (rsp.err_code != 0)
                    $scope.$root.errmsg = rsp.err_msg;
                else if (rsp.data.param) {
                    $scope.$root.progmsg = rsp.data.param.desc + (rsp.data.param.step ? '，剩余批次：' + rsp.data.param.left : '');
                    if (rsp.data.param.next)
                        doImport(rsp.data.param);
                } else {
                    if (rsp.data.warning !== undefined && rsp.data.warning.length)
                        $scope.$root.errmsg = JSON.stringify(rsp.data.warning);
                    else
                        $scope.$root.progmsg = '同步操作完成';
                }
            });
        };
        doImport();
    };
    $scope.sync2Qy = function(authapi) {
        var url = authapi.url + '/sync2Qy';
        url += '?mpid=' + $scope.mpaccount.mpid;
        url += '&authid=' + authapi.authid;
        $scope.sync2QyRunning = true;
        http2.get(url, function(rsp) {
            if (rsp.err_code === 0) {
                $scope.$root.progmsg = "完成";
            }
            $scope.sync2QyRunning = false;
        }, {
            autoBreak: false
        });
    };
    $scope.syncFromQy = function(authapi) {
        var url = authapi.url + '/syncFromQy';
        url += '?mpid=' + $scope.mpaccount.mpid;
        url += '&authid=' + authapi.authid;
        $scope.syncFromQyRunning = true;
        http2.get(url, function(rsp) {
            if (rsp.err_code === 0) {
                $scope.$root.progmsg = "同步" + rsp.data[0] + "个部门，" + rsp.data[1] + "个用户，" + rsp.data[2] + "个标签";
            }
            $scope.syncFromQyRunning = false;
        }, {
            autoBreak: false
        });
    };
    service.mp.relayGet().then(function(data) {
        $scope.relays = data;
    });
    service.mp.get().then(function(data) {
        $scope.mpaccount = data;
        service.authapi.get('N').then(function(data) {
            var i, l, authapi;
            angular.forEach(data, function(authapi) {
                shiftAuthapiAttr(authapi);
                if ($scope.mpaccount.mpid === authapi.mpid) {
                    $scope.authapis.push(authapi);
                } else {
                    $scope.pAuthapis.push(authapi);
                }
            });
            if ($scope.authapis.length === 0) {
                $scope.authapis.push({
                    type: 'inner',
                    valid: 'N'
                });
            }
        });
    });
}]);
app.controller('ctrlMatter', ['$scope', 'http2', function($scope, http2) {}]);