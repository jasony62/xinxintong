define(['frame'], function(ngApp) {
    /**
     * app setting controller
     */
    ngApp.provider.controller('ctrlUsers', ['$scope', 'http2', '$q', '$uibModal', 'noticebox', function($scope, http2, $q, $uibModal, noticebox) {
        $scope.$parent.subView = 'users';
        //退出信息墙功能  删除所有用户
        $scope.quit = function() {
            var vcode;
            vcode = prompt('是否要退出所有在线用户？，若是，请输入信息墙名称。');
            //下面需修改，与杨总讨论
            if (vcode === $scope.wall.title) {
                http2.get('/rest/pl/fe/matter/wall/users/quit?id=' + $scope.id + '&site=' + $scope.siteId).then(function(rsp) {
                    $scope.users = null;
                    delete $scope.wall.sourceApp;
                    $scope.$root.infomsg = '操作完成';
                });
            }
        };
        //删除用户
        $scope.removeRecord = function(data) {
            http2.get('/rest/pl/fe/matter/wall/users/quit?id=' + $scope.id + '&eid=' + data.id).then(function(rsp) {
                $scope.users.splice($scope.users.indexOf($scope.users), 1);
                $scope.doSearch();
            })
        }
        //导入用户
        $scope.import = function() {
            $uibModal.open({
                templateUrl: 'importUser.html',
                windowClass: 'auto-height',
                controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                    $scope2.data = {
                        app: '',
                        appType: 'enroll'
                    };
                    $scope2.$watch('data.appType', function(newValus) {
                        var url;
                        if (newValus === 'enroll') {
                            url = '/rest/pl/fe/matter/enroll/list?page=1&size=999&site=' + $scope.siteId;
                            delete $scope2.data.includeEnroll;

                        } else if (newValus === 'signin') {
                            url = '/rest/pl/fe/matter/signin/list?page=1&size=999&site=' + $scope.siteId;
                            $scope2.data.includeEnroll = 'Y';
                        }
                        url += '&onlySns=Y';
                        http2.get(url).then(function(rsp) {
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
                if (data.app) {
                    params = {
                        app: data.app.id,
                        appType: data.appType,
                    };
                    data.appType === 'signin' && (params.includeEnroll = data.includeEnroll);
                    http2.post('/rest/pl/fe/matter/wall/users/import?site=' + $scope.siteId + '&app=' + $scope.id, params).then(function(rsp) {
                        $scope.wall.sourceApp = data.app;
                        $scope.doSearch();
                    });
                }
            });
        };
        //导入公众号或企业号的用户
        $scope.importPublic = function() {
            $uibModal.open({
                templateUrl: 'importPublicUser.html',
                windowClass: 'auto-height',
                controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                    $scope2.data = {
                        app: '',
                        appType: 'qy'
                    };
                    $scope2.page = {
                        at: 1,
                        size: 20,
                        total: 0,
                        param: function() {
                            return 'page=' + this.at + '&size=' + this.size;
                        }
                    };
                    $scope2.selected = [];
                    $scope2.doRequest = function(page) {
                        page && ($scope2.page.at = page);
                        $scope2.$watch('data.appType', function(newValus) {
                            var url = '/rest/pl/fe/matter/wall/users/importSns?site=' + $scope.siteId;
                            url += '&type=' + newValus;
                            url += '&' + $scope2.page.param();
                            http2.get(url).then(function(rsp) {
                                $scope2.publicUsers = rsp.data.fans;
                                $scope2.page.total = rsp.data.total;
                            });
                        });
                    }
                    $scope2.doRequest(1);
                    $scope2.updateInput = function($event, data) {
                        $scope2.doRequest = function(page) {
                            var url;
                            url = '/rest/pl/fe/matter/wall/users/importSns?site=' + $scope.siteId;
                            url += '&type=qy' + '&' + $scope2.page.param();
                            http2.post(url, { dept: data }).then(function(rsp) {
                                $scope2.publicUsers = rsp.data.fans;
                                $scope2.page.total = rsp.data.total;
                            });
                        }
                        $scope2.doRequest(1);
                    }
                    $scope2.isSelected = function(id) {
                        return $scope2.selected.indexOf(id) >= 0;
                    }
                    var updateSelected = function(action, option) {
                        if (action == 'add') {
                            $scope2.selected.push(option);
                        }
                        if (action == 'remove') {
                            angular.forEach($scope2.selected, function(item, index) {
                                if (item.openid == option.openid) {
                                    $scope2.selected.splice(index, 1);
                                }
                            })
                        }
                    }
                    $scope2.updateSelection = function($event, data) {
                        var checkbox = $event.target;
                        var action = (checkbox.checked ? 'add' : 'remove');
                        var option = {
                            openid: data.openid,
                            nickname: data.nickname,
                            headimgurl: data.headimgurl
                        };
                        updateSelected(action, option);
                    }
                    $scope2.close = function() {
                        $mi.dismiss();
                    };
                    $scope2.ok = function() {
                        if ($scope2.data) {
                            $mi.close($scope2.selected);
                        } else {
                            $mi.dismiss();
                        }
                    };
                }]
            }).result.then(function(data) {
                http2.post('/rest/pl/fe/matter/wall/users/userJoin?site=' + $scope.siteId + '&app=' + $scope.id + '&type=qy', data).then(function(rsp) {
                    $scope.doSearch();
                });
            });
        };
        //刷新
        $scope.doSearch = function() {
            http2.get('/rest/pl/fe/matter/wall/users/list?id=' + $scope.id + '&site=' + $scope.siteId).then(function(rsp) {
                $scope.users = rsp.data;
            });
        };
        $scope.doSearch();
        //同步用户
        $scope.syncByApp = function() {
            if ($scope.wall.sourceApp) {
                http2.get('/rest/pl/fe/matter/wall/users/syncByApp?site=' + $scope.siteId + '&app=' + $scope.id).then(function(rsp) {
                    noticebox.success('同步' + rsp.data + '个用户');
                    //刷新页面
                    $scope.doSearch();
                });
            }
        };
    }]);
});