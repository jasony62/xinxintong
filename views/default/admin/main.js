app = angular.module('xxt', ['ui.bootstrap']);
app.config(['$locationProvider', function($locationProvider) {
    $locationProvider.html5Mode(true);
}]);
app.controller('SysCtrl', ['$scope', '$http', function($scope, $http) {
    $scope.changeSubView = function(url) {
        var t = (new Date()).getTime();
        $scope.subViewUrl = url + '?_=' + t;
    };
    $scope.gotoUserView = function() {
        $scope.changeSubView('/page/admin/user');
    };
    $scope.gotoGroupView = function() {
        $scope.changeSubView('/page/admin/group');
    };
    $scope.gotoSyslogView = function() {
        $scope.changeSubView('/page/admin/syslog');
    };
    $scope.changeSubView('/page/admin/user');
}]);
app.controller('UserCtrl', ['$rootScope', '$scope', '$http', function($rootScope, $scope, $http) {
    var doSearch = function(page) {
        !page && (page = $scope.page.current);
        $http.get('/rest/admin/user?page=' + page + '&size=' + $scope.page.size).
        success(function(rsp) {
            $scope.users = rsp.data[0];
            rsp.data[1] && ($scope.page.total = rsp.data[1]);
        });
    };
    $rootScope.activeView = 'user';
    $scope.page = {
        current: 1,
        size: 30,
    };
    $scope.pageChanged = function() {
        doSearch();
    };
    $scope.changeGroup = function(user) {
        $http.post('/rest/admin/changeGroup?uid=' + user.uid, 'gid=' + user.group_id, {
            'headers': {
                'Content-Type': 'application/x-www-form-urlencoded'
            }
        }).
        success(function(rsp) {});
    };
    $scope.remove = function(user) {
        var vcode;
        vcode = prompt('是否要删除用户？，若是，请输入用户昵称。');
        if (vcode === user.nickname) {
            $http.get('/rest/admin/removeUser?uid=' + user.uid).
            success(function(rsp) {
                if (rsp.err_code != 0) {
                    alert(rsp.err_msg);
                    return;
                }
                var i = $scope.users.indexOf(user);
                $scope.users.splice(i, 1);
            });
        }
    };
    $http.get('/rest/admin/group').
    success(function(rsp) {
        $scope.groups = rsp.data;
        doSearch(1);
    });
}]);
app.controller('GroupCtrl', ['$rootScope', '$scope', '$http', function($rootScope, $scope, $http) {
    $rootScope.activeView = 'group';
    $scope.add = function() {
        $http.post('/rest/admin/addGroup', 'name=' + $scope.groupName, {
            'headers': {
                'Content-Type': 'application/x-www-form-urlencoded'
            }
        }).
        success(function(rsp) {
            $scope.groups.push(rsp.data);
        });
    };
    $scope.update = function(group, pname) {
        var data = {};
        data[pname] = group[pname];
        $http.post('/rest/admin/updateGroup?gid=' + group.group_id, data).
        success(function(rsp) {});
    };
    $scope.remove = function(group) {
        $http.post('/rest/admin/removeGroup?gid=' + group.group_id).
        success(function(rsp) {
            var i = $scope.groups.indexOf(group);
            $scope.groups.splice(i, 1);
        });
    };
    $http.get('/rest/admin/group').
    success(function(rsp) {
        $scope.groups = rsp.data;
        for (var i in $scope.groups) {
            var group = $scope.groups[i];
            if (group.asdefault == 1) {
                $scope.defaultGroup = group;
                break;
            }
        }
    });
}]);
app.controller('ctrlSyslog', ['$rootScope', '$scope', '$http', function($rootScope, $scope, $http) {
    $scope.$root.activeView = 'syslog';
    $scope.page = {
        at: 1,
        size: 50,
        total: 0,
        param: function() {
            return 'page=' + this.at + '&size=' + this.size;
        }
    };
    $scope.fetch = function() {
        var url;
        url = '/rest/admin/syslog/list?' + $scope.page.param();
        $http.get(url).success(function(rsp) {
            $scope.page.total = rsp.data.total;
            $scope.logs = rsp.data.logs;
        });
    };
    $scope.remove = function(log, index) {
        if (window.confirm('确定删除？')) {
            var url;
            url = '/rest/admin/syslog/remove?id=' + log.id;
            $http.get(url).success(function(rsp) {
                $scope.logs.splice(index, 1);
                $scope.page.total--;
            });
        }
    };
    $scope.fetch();
}]);