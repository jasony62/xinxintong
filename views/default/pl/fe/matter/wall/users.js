
define(['frame'], function(ngApp) {
    /**
     * app setting controller
     */
    ngApp.provider.controller('ctrlUsers', ['$scope', 'http2', '$q','$uibModal','noticebox',function($scope,  http2, $q, $uibModal, noticebox) {
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
                    $scope2.data = {
                        appType : 'enroll'
                    } ;
                    $scope2.$watch('data.appType',function(newValus){
                        var url ;
                        if(newValus === 'enroll'){
                            url = '/rest/pl/fe/matter/enroll/list?page=1&size=999&site=' + $scope.siteId;

                        }else if(newValus === 'signin'){
                            url = '/rest/pl/fe/matter/signin/list?page=1&size=999&site=' + $scope.siteId;
                        }
                        http2.get(url , function(rsp) {
                            $scope2.apps = rsp.data.apps;
                        });
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
                    console.log( app);
                    http2.get('/rest/pl/fe/matter/wall/users/import?id=' + $scope.id + '&app=' + app.id +'&site='+ app.siteid + '&type=' + app.type, function(rsp) {
                        //$scope.$root.infomsg = '导入用户数：' + rsp.data;
                        // 显示导入人数
                        noticebox.success('导入用户数：' + rsp.data);
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
                        //$scope.$root.infomsg = '导出用户数：' + rsp.data;
                        noticebox.success('导出用户数：' + rsp.data);
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

