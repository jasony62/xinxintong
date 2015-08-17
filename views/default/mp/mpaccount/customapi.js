xxtApp.controller('apiCtrl', ['$scope', 'http2', '$http', '$modal', 'Mp', 'Authapi', function($scope, http2, $http, $modal, Mp, Authapi) {
    var service = {};
    service.mp = new Mp();
    service.authapi = new Authapi();
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
    $scope.update = function(name) {
        var p = {};
        p[name] = $scope.features[name];
        http2.post('/rest/mp/mpaccount/updateFeature', p);
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
        var url = '/rest/mp/authapi/update?type=' + api.type;
        if (api.authid) url += '&id=' + api.authid;
        var p = {};
        if (/entry_statement|acl_statement|notpass_statement/.test(field))
            p[field] = encodeURIComponent(api[field]);
        else
            p[field] = api[field];
        http2.post(url, p, function(rsp) {
            if (api === $scope.ia) {
                rsp.data.url2 = 'http://' + location.host + rsp.data.url + '?mpid=' + $scope.mpaccount.mpid + '&authid=' + rsp.data.authid;
                shiftAuthapiAttr(rsp.data);
                $scope.ia = rsp.data;
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
    $scope.import2QyRunning = false;
    $scope.import2Qy = function(authapi) {
        var url = authapi.url + '/import2Qy';
        url += '?mpid=' + $scope.mpaccount.mpid;
        url += '&authid=' + authapi.authid;
        var doImport = function(param) {
            $scope.import2QyRunning = true;
            var url2 = url;
            param && (url2 += '&next=' + param.next);
            param && param.step && (url2 += '&step=' + param.step);
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
        http2.get(url, function(rsp) {
            $scope.$root.infomsg = rsp.data;
        });
    };
    $scope.syncFromQy = function(authapi) {
        $scope.taskRunning = true;
        var url = authapi.url + '/syncFromQy';
        url += '?mpid=' + $scope.mpaccount.mpid;
        url += '&authid=' + authapi.authid;
        http2.get(url, function(rsp) {
            $scope.$root.progmsg = "同步" + rsp.data[0] + "个部门，" + rsp.data[1] + "个用户，" + rsp.data[2] + "个标签";
            $scope.taskRunning = false;
        });
    };
    var shiftAuthapiAttr = function(authapi) {
        authapi.attrs = {
            mobile: [],
            email: [],
            name: [],
            password: []
        };
        var k, j, item, setting;
        for (k in authapi.attrs) {
            item = authapi.attrs[k];
            setting = authapi['attr_' + k];
            for (j = 0; j < 6; j++)
                item.push(setting.charAt(j));
        }
        var ea;
        for (k in authapi.extattr) {
            ea = authapi.extattr[k];
            ea.cfg2 = [];
            for (j = 0; j < 6; j++)
                ea.cfg2.push(ea.cfg.charAt(j));
        }
    };
    $scope.addRelay = function() {
        http2.get('/rest/mp/relay/add', function(rsp) {
            $scope.relays.push(rsp.data);
        });
    };
    $scope.updateRelay = function(r, name) {
        var p = {};
        p[name] = r[name];
        http2.post('/rest/mp/relay/update?id=' + r.id, p);
    };
    $scope.delRelay = function(r) {
        var url = '/rest/mp/relay/remove?id=' + r.id;
        http2.get(url, function(rsp) {
            var i = $scope.relays.indexOf(r);
            $scope.relays.splice(i, 1);
        });
    };
    service.mp.relayGet().then(function(data) {
        $scope.relays = data;
    });
    service.mp.get().then(function(data) {
        $scope.mpaccount = data;
        service.authapi.get('Y').then(function(data) {
            var i, l, authapi;
            if (data.length > 0)
                for (i = 0, l = data.length; i < l; i++) {
                    authapi = data[i];
                    if (authapi.type === 'inner') {
                        $scope.ia = authapi;
                        $scope.ia.url2 = 'http://' + location.host + $scope.ia.url + '?mpid=' + $scope.mpaccount.mpid + '&authid=' + $scope.ia.authid;
                    } else
                        $scope.authapis.push(authapi);
                    shiftAuthapiAttr(authapi);
                }!$scope.ia && ($scope.ia = {
                    type: 'inner',
                    valid: 'N'
                });
        });
    });
}]);