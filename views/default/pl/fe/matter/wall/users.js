
define(['frame'], function(ngApp) {
    /**
     * app setting controller
     */
    ngApp.provider.controller('ctrlUsers', ['$scope', '$q', 'http2','$uibModal',function($scope, $q, http2, $uibModal) {
        $scope.$parent.subView = 'users';
        //退出信息墙功能
        $scope.quit = function() {
            var vcode;
            vcode = prompt('是否要退出所有在线用户？，若是，请输入讨论组名称。');
            //下面需修改，与杨总讨论
            if (vcode === $scope.wall.title) {
                http2.get('/rest/pl/fe/matter/wall/users/quit?id=' + $scope.id + '&site=' + $scope.siteId , function(rsp) {
                    $scope.users = null;
                    $scope.$root.infomsg = '操作完成';
                });
            }
        };
        //导入用户
        $scope.import = function() {
            $uibModal.open({
                templateUrl: 'importUser.html',
                windowClass: 'auto-height',
                controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                    http2.get('/rest/pl/fe/matter/enroll/list?page=1&size=999&site=' + $scope.siteId, function(rsp) {
                        $scope2.apps = rsp.data.apps;

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
                    http2.get('/rest/pl/fe/matter/wall/users/import?id=' + $scope.id + '&app=' + app.id +'&site='+ app.siteid, function(rsp) {
                        $scope.$root.infomsg = '导入用户数：' + rsp.data;
                        $scope.doSearch();
                    });
                });
        };
        //导出用户
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
                    http2.get('/rest/pl/fe/matter/enroll/list?page=1&size=999&site=' + $scope.siteId, function(rsp) {
                        $scope2.apps = rsp.data.apps;
                    });
                }]
            }).result.then(function(params) {
                    var url;
                    url = '/rest/pl/fe/matter/wall/users/export?id=' + $scope.id + '&site=' +$scope.siteId;
                    url += '&app=' + params.app.id;
                    url += '&onlySpeaker=' + params.options.onlySpeaker;
                    http2.get(url, function(rsp) {
                        $scope.$root.infomsg = '导出用户数：' + rsp.data;
                    });
                });
        };
        //刷新
        $scope.doSearch = function() {
            http2.get('/rest/pl/fe/matter/wall/users/list?id=' + $scope.id + '&site=' + $scope.siteId, function(rsp) {
                $scope.users = rsp.data;
            });
        };
        $scope.doSearch();
    }]);
});

//
//
//(function() {
//    xxtApp.register.controller('usersCtrl', ['$scope', '$uibModal', 'http2', function($scope, $uibModal, http2) {
//        $scope.$parent.subView = 'users';
//        //退出信息墙功能
//        $scope.quit = function() {
//            var vcode;
//            vcode = prompt('是否要退出所有在线用户？，若是，请输入讨论组名称。');
//            if (vcode === $scope.wall.title) {
//                http2.get('/rest/mp/app/wall/users/quit?wall=' + $scope.wid, function(rsp) {
//                    $scope.users = null;
//                    $scope.$root.infomsg = '操作完成';
//                });
//            }
//        };
//        //导入用户
//        $scope.import = function() {
//            $uibModal.open({
//                templateUrl: 'importUser.html',
//                windowClass: 'auto-height',
//                controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
//                    http2.get('/rest/mp/app/enroll/list?page=1&size=999', function(rsp) {
//                        $scope2.apps = rsp.data[0];
//                    });
//                    $scope2.chooseApp = function(app) {
//                        $scope2.selectedApp = app;
//                    };
//                    $scope2.close = function() {
//                        $mi.dismiss();
//                    };
//                    $scope2.ok = function() {
//                        if ($scope2.selectedApp) {
//                            $mi.close($scope2.selectedApp);
//                        } else {
//                            $mi.dismiss();
//                        }
//                    };
//                }]
//            }).result.then(function(app) {
//                    http2.get('/rest/mp/app/wall/users/import?wall=' + $scope.wid + '&app=' + app.id, function(rsp) {
//                        $scope.$root.infomsg = '导入用户数：' + rsp.data;
//                        $scope.doSearch();
//                    });
//                });
//        };
//        //导出用户
//        $scope.export = function() {
//            $uibModal.open({
//                templateUrl: 'exportUser.html',
//                windowClass: 'auto-height',
//                controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
//                    $scope2.options = {
//                        onlySpeaker: 'N'
//                    };
//                    $scope2.chooseApp = function(app) {
//                        $scope2.selectedApp = app;
//                    };
//                    $scope2.close = function() {
//                        $mi.dismiss();
//                    };
//                    $scope2.ok = function() {
//                        if ($scope2.selectedApp) {
//                            $mi.close({
//                                app: $scope2.selectedApp,
//                                options: $scope2.options
//                            });
//                        } else {
//                            $mi.dismiss();
//                        }
//                    };
//                    http2.get('/rest/mp/app/enroll/list?page=1&size=999', function(rsp) {
//                        $scope2.apps = rsp.data[0];
//                    });
//                }]
//            }).result.then(function(params) {
//                    var url;
//                    url = '/rest/mp/app/wall/users/export?wall=' + $scope.wid;
//                    url += '&app=' + params.app.id;
//                    url += '&onlySpeaker=' + params.options.onlySpeaker;
//                    http2.get(url, function(rsp) {
//                        $scope.$root.infomsg = '导出用户数：' + rsp.data;
//                    });
//                });
//        };
//        //刷新
//        $scope.doSearch = function() {
//            http2.get('/rest/mp/app/wall/users/list?wall=' + $scope.wid, function(rsp) {
//                $scope.users = rsp.data;
//            });
//        };
//        $scope.doSearch();
//    }]);
//})();