'use strict';
var ngApp = angular.module('app', ['ngRoute', 'ui.bootstrap', 'ui.tms', 'http.ui.xxt', 'notice.ui.xxt']);
ngApp.config(['$locationProvider', '$provide', '$controllerProvider', '$routeProvider', function($lp, $provide, $cp, $rp) {
    var RouteParam = function(name) {
        var baseURL = '/views/default/pl/be/user/';
        this.templateUrl = baseURL + name + '.html?_=' + (new Date * 1);
        this.controller = 'ctrl' + name[0].toUpperCase() + name.substr(1);
    };
    $lp.html5Mode(true);
    ngApp.provider = {
        controller: $cp.register,
        service: $provide.service,
    };
    $rp.when('/rest/pl/be/user/account', new RouteParam('account'))
        .when('/rest/pl/be/user/group', new RouteParam('group'))
        .otherwise(new RouteParam('account'));
}]);
ngApp.controller('ctrlMain', ['$scope', function($scope) {
    $scope.subView = '';
    $scope.$on('$locationChangeSuccess', function(event, currentRoute) {
        var subView = currentRoute.match(/([^\/]+?)$/);
        $scope.subView = subView[1] === 'user' ? 'account' : subView[1];
    });
}]);
ngApp.controller('ctrlAccount', ['$scope', '$uibModal', 'http2', 'noticebox', function($scope, $uibModal, http2, noticebox) {
    function doSearch(pageAt) {
        pageAt && (_oPage.at = pageAt);
        http2.post('/rest/pl/be/user/account/list', _oFilter, { page: _oPage }).then(function(rsp) {
            $scope.users = rsp.data.accounts;
        });
    };
    var _oFilter, _oPage;
    $scope.filter = _oFilter = {
        prop: 'email',
        keyword: ''
    };
    $scope.byProps = { email: '登录账号', nickname: '账号昵称' };
    $scope.page = _oPage = { size: 30 };
    $scope.pageChanged = function() {
        doSearch();
    };
    $scope.resetFilter = function() {
        _oFilter.keyword = '';
        doSearch(1);
    };
    $scope.changeGroup = function(user) {
        http2.post('/rest/pl/be/user/account/changeGroup?uid=' + user.uid, {
            'gid': user.group_id
        });
    };
    $scope.remove = function(user) {
        var vcode;
        vcode = prompt('是否要删除用户？，若是，请输入用户昵称。');
        if (vcode === user.nickname) {
            http2.get('/rest/pl/be/user/account/remove?uid=' + user.uid).then(function(rsp) {
                var i = $scope.users.indexOf(user);
                $scope.users.splice(i, 1);
            });
        }
    };
    $scope.resetPwd = function(user) {
        $uibModal.open({
            templateUrl: 'resetPassword.html',
            backdrop: 'static',
            controller: ['$uibModalInstance', '$scope', function($mi, $scope) {
                $scope.data = {
                    password: 'dev189!@'
                };
                $scope.close = function() {
                    $mi.dismiss();
                };
                $scope.ok = function() {
                    $mi.close($scope.data);
                };
            }]
        }).result.then(function(data) {
            data.uid = user.uid;
            http2.post('/rest/pl/be/user/account/resetPwd', data).then(function(rsp) {
                noticebox.success('修改完成');
            });
        });
    };
    $scope.forbideUser = function(user) {
        if (window.confirm('确定关闭帐号【' + user.uname + '】？')) {
            http2.post('/rest/pl/be/user/account/forbide', { uid: user.uid }).then(function(rsp) {
                user.forbidden = '1';
            });
        }
    };
    $scope.activeUser = function(user) {
        if (window.confirm('确定关闭帐号【' + user.uname + '】？')) {
            http2.post('/rest/pl/be/user/account/active', { uid: user.uid }).then(function(rsp) {
                user.forbidden = '0';
            });
        }
    };
    http2.get('/rest/pl/be/user/group/list').then(function(rsp) {
        $scope.groups = rsp.data;
        doSearch(1);
    });
}]);
ngApp.controller('ctrlGroup', ['$scope', 'http2', 'noticebox', function($scope, http2, noticebox) {
    $scope.add = function() {
        http2.post('/rest/pl/be/user/group/add', {
            'name': $scope.groupName
        }, function(rsp) {
            $scope.groups.push(rsp.data);
            $scope.groupName = '';
        });
    };
    $scope.update = function(group, pname) {
        var data = {};
        data[pname] = group[pname];
        http2.post('/rest/pl/be/user/group/update?gid=' + group.group_id, data).then(function(rsp) {
            noticebox.success('修改完成');
        });
    };
    $scope.remove = function(group) {
        http2.get('/rest/pl/be/user/group/remove?gid=' + group.group_id).then(function(rsp) {
            var i = $scope.groups.indexOf(group);
            $scope.groups.splice(i, 1);
        });
    };
    http2.get('/rest/pl/be/user/group/list').then(function(rsp) {
        var i, group;
        $scope.groups = rsp.data;
        for (i in $scope.groups) {
            group = $scope.groups[i];
            if (group.asdefault == 1) {
                $scope.defaultGroup = group;
                break;
            }
        }
    });
}]);