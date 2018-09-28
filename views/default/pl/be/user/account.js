define(['main'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlAccount', ['$scope', '$uibModal', 'http2', 'noticebox', function($scope, $uibModal, http2, noticebox) {
        function doSearch(page) {
            !page && (page = $scope.page.at);
            http2.post('/rest/pl/be/user/account/list?page=' + page + '&size=' + $scope.page.size, filter).then(function(rsp) {
                $scope.users = rsp.data[0];
                rsp.data[1] && ($scope.page.total = rsp.data[1]);
            });
        };
        var filter, page;
        $scope.filter = filter = {};
        $scope.page = page = {
            at: 1,
            size: 30,
        };
        $scope.pageChanged = function() {
            doSearch();
        };
        $scope.resetFilter = function() {
            filter.email = '';
            $scope.doSearch(1);
        };
        $scope.changeGroup = function(user) {
            http2.post('/rest/pl/be/user/account/changeGroup?uid=' + user.uid, {
                'gid': user.group_id
            }, function(rsp) {});
        };
        $scope.remove = function(user) {
            var vcode;
            vcode = prompt('是否要删除用户？，若是，请输入用户昵称。');
            if (vcode === user.nickname) {
                $http.get('/rest/pl/be/user/account/remove?uid=' + user.uid, function(rsp) {
                    if (rsp.err_code != 0) {
                        alert(rsp.err_msg);
                        return;
                    }
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
                        password: '123456'
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
        http2.get('/rest/pl/be/user/group/list').then(function(rsp) {
            $scope.groups = rsp.data;
            doSearch(1);
        });
    }]);
});