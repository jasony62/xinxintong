
define(['frame'], function(ngApp) {
    /**
     * app setting controller
     */
    ngApp.provider.controller('ctrlUsers', ['$scope', 'http2', '$q','$uibModal','noticebox',function($scope,  http2, $q, $uibModal, noticebox) {
        $scope.$parent.subView = 'users';
        //退出信息墙功能  删除所有用户
        $scope.quit = function() {
            var vcode;
            vcode = prompt('是否要退出所有在线用户？，若是，请输入讨论组名称。');
            //下面需修改，与杨总讨论
            if (vcode === $scope.wall.title) {
                http2.get('/rest/pl/fe/matter/wall/users/quit?id=' + $scope.id + '&site=' + $scope.siteId , function(rsp) {
                    $scope.users = null;
                    delete $scope.app.sourceApp;
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
                        app : '',
                        appType : 'enroll'
                    } ;
                    $scope2.$watch('data.appType',function(newValus){
                        var url ;
                        if(newValus === 'enroll'){
                            url = '/rest/pl/fe/matter/enroll/list?page=1&size=999&site=' + $scope.siteId;
                            delete $scope2.data.includeEnroll;

                        }else if(newValus === 'signin'){
                            url = '/rest/pl/fe/matter/signin/list?page=1&size=999&site=' + $scope.siteId;
                            $scope2.data.includeEnroll = 'Y';
                        }
                        http2.get(url , function(rsp) {
                            $scope2.apps = rsp.data.apps;
                        });
                    });
                    $scope2.close = function() {
                        $mi.dismiss();
                    };
                    $scope2.ok = function() {
                        if ($scope2.data) {
                            $mi.close($scope2.data);
                        } else {
                            $mi.dismiss();
                        }
                    };
                }]
            }).result.then(function(data) {
                    var params;
                    if(data.app){
                        params = {
                            app: data.app.id,
                            appType: data.appType,
                        };
                        data.appType === 'signin' && (params.includeEnroll = data.includeEnroll);
                        http2.post('/rest/pl/fe/matter/wall/users/import?site=' + $scope.siteId + '&app=' + $scope.id, params, function(rsp) {
                            $scope.app.sourceApp = data.app;
                            $scope.app.data_schemas = JSON.parse(rsp.data.data_schemas);
                            $scope.open(null);
                        });
                    }
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
        //移除活动
        //$scope.cancelSourceApp = function() {
        //    $scope.app.source_app = '';
        //    $scope.app.data_schemas = '';
        //    delete $scope.app.sourceApp;
        //    $scope.update(['source_app', 'data_schemas']);
        //};
        //同步用户
        $scope.syncByApp = function() {
            //if ($scope.app.sourceApp) {
                http2.get('/rest/pl/fe/matter/group/player/syncByApp?site=' + $scope.siteId + '&app=' + $scope.id, function(rsp) {
                    noticebox.success('同步' + rsp.data + '个用户');
                    //刷新页面
                    $scope.doSearch();
                });
            //}
        };
    }]);
});

