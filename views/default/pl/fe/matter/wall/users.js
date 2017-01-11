
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
                    delete $scope.wall.sourceApp;
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
                        url+='&onlySns=Y';
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
                            $scope.wall.sourceApp = data.app;
                            $scope.doSearch();
                        });
                    }
                });
        };

        //刷新
        $scope.doSearch = function() {
            http2.get('/rest/pl/fe/matter/wall/users/list?id=' + $scope.id + '&site=' + $scope.siteId, function(rsp) {
                $scope.users = rsp.data;
            });
        };
        $scope.doSearch();
        //同步用户
        $scope.syncByApp = function() {
            if ($scope.wall.sourceApp) {
                http2.get('/rest/pl/fe/matter/wall/users/syncByApp?site=' + $scope.siteId + '&app=' + $scope.id, function(rsp) {
                    noticebox.success('同步' + rsp.data + '个用户');
                    //刷新页面
                    $scope.doSearch();
                });
            }
        };
    }]);
});

