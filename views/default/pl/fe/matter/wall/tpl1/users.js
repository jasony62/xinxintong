(function() {
    xxtApp.register.controller('usersCtrl', ['$scope', '$uibModal', 'http2', function($scope, $uibModal, http2) {
        $scope.$parent.subView = 'users';
        $scope.doSearch = function() {
            http2.get('/rest/mp/app/wall/users/list?wall=' + $scope.wid, function(rsp) {
                $scope.users = rsp.data;
            });
        };
        $scope.import = function() {
            $uibModal.open({
                templateUrl: 'importUser.html',
                windowClass: 'auto-height',
                controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                    http2.get('/rest/mp/app/enroll/list?page=1&size=999', function(rsp) {
                        $scope2.apps = rsp.data[0];
                    });
                    $scope2.chooseApp = function(app) {
                        $scope2.selectedApp = app;
                    };
                    $scope2.close = function() {
                        $mi.dismiss();
                    };
                    $scope2.ok = function() {
                        if ($scope2.selectedApp) {
                            $mi.close($scope2.selectedApp);
                        } else {
                            $mi.dismiss();
                        }
                    };
                }]
            }).result.then(function(app) {
                http2.get('/rest/mp/app/wall/users/import?wall=' + $scope.wid + '&app=' + app.id, function(rsp) {
                    $scope.$root.infomsg = '导入用户数：' + rsp.data;
                    $scope.doSearch();
                });
            });
        };
        $scope.export = function() {
            $uibModal.open({
                templateUrl: 'exportUser.html',
                windowClass: 'auto-height',
                controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                    $scope2.options = {
                        onlySpeaker: 'N'
                    };
                    $scope2.chooseApp = function(app) {
                        $scope2.selectedApp = app;
                    };
                    $scope2.close = function() {
                        $mi.dismiss();
                    };
                    $scope2.ok = function() {
                        if ($scope2.selectedApp) {
                            $mi.close({
                                app: $scope2.selectedApp,
                                options: $scope2.options
                            });
                        } else {
                            $mi.dismiss();
                        }
                    };
                    http2.get('/rest/mp/app/enroll/list?page=1&size=999', function(rsp) {
                        $scope2.apps = rsp.data[0];
                    });
                }]
            }).result.then(function(params) {
                var url;
                url = '/rest/mp/app/wall/users/export?wall=' + $scope.wid;
                url += '&app=' + params.app.id;
                url += '&onlySpeaker=' + params.options.onlySpeaker;
                http2.get(url, function(rsp) {
                    $scope.$root.infomsg = '导出用户数：' + rsp.data;
                });
            });
        };
        $scope.quit = function() {
            var vcode;
            vcode = prompt('是否要退出所有在线用户？，若是，请输入讨论组名称。');
            if (vcode === $scope.wall.title) {
                http2.get('/rest/mp/app/wall/users/quit?wall=' + $scope.wid, function(rsp) {
                    $scope.users = null;
                    $scope.$root.infomsg = '操作完成';
                });
            }
        };
        $scope.doSearch();
    }]);
})();