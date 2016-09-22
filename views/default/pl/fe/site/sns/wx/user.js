define(['main'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlUser', ['$scope', 'http2', function($scope, http2) {
        $scope.subview = 'fans';
        $scope.shiftSubview = function(subview) {
            $scope.subview = subview;
        }
        http2.get('/rest/pl/fe/site/sns/wx/group/list?site=' + $scope.siteId, function(rsp) {
            $scope.groups = rsp.data;
        });
    }]);
    ngApp.provider.controller('ctrlFan', ['$scope', 'http2', function($scope, http2) {
        $scope.SexMap = {
            '0': '未知',
            '1': '男',
            '2': '女',
            '3': '无效值'
        };
        $scope.page = {
            at: 1,
            size: 30,
            keyword: ''
        };
        $scope.order = 'time';
        $scope.selectedGroup = null;
        $scope.doSearch = function(page) {
            var param;
            param = '?site=' + $scope.siteId;
            if (page) {
                $scope.page.at = page;
            }
            param += '&page=' + $scope.page.at + '&size=' + $scope.page.size;
            if ($scope.page.keyword && $scope.page.keyword.length > 0) {
                param += '&keyword=' + $scope.page.keyword;
            }
            if ($scope.selectedGroup) {
                param += '&gid=' + $scope.selectedGroup.id;
            }
            param += '&order=' + $scope.order;
            http2.get('/rest/pl/fe/site/sns/wx/user/list' + param, function(rsp) {
                $scope.users = rsp.data.fans;
                $scope.page.total = rsp.data.total;
            });
        };
        $scope.keywordKeyup = function(evt) {
            if (evt.which === 13) $scope.doSearch();
        };
        $scope.refresh = function() {
            var finish = 0;
            var doRefresh = function(step, nextOpenid) {
                var url, params;
                url = '/rest/mp/user/fans/refreshAll';
                params = [];
                step && params.push('step=' + step);
                nextOpenid && params.push('nextOpenid=' + nextOpenid);
                params.length && (url += '?' + params.join('&'));
                http2.get(url, function(rsp) {
                    if (angular.isObject(rsp) && rsp.err_code === 0) {
                        if (rsp.data.left > 0) {
                            doRefresh(rsp.data.step, rsp.data.nextOpenid);
                        } else if (rsp.data.nextOpenid) {
                            doRefresh(0, rsp.data.nextOpenid);
                        } else {
                            $scope.backRunning = false;
                        }
                        finish += rsp.data.finish;
                        alert('更新数量：' + finish + '/' + rsp.data.total);
                    }
                }, {
                    autoBreak: false
                });
            };
            doRefresh(0);
        };
        $scope.doSearch();
    }]);
    ngApp.provider.controller('ctrlGroup', ['$scope', 'http2', function($scope, http2) {
        $scope.page = {
            at: 1,
            size: 30
        };
        $scope.edit = function(g) {
            if ($scope.editing !== g) {
                $scope.editing = g;
            }
        };
        $scope.addGroup = function() {
            var newObj = {
                name: '新分组'
            };
            $scope.groups.push(newObj);
            $scope.editing = newObj;
        };
        $scope.save = function() {
            if ($scope.editing.id === undefined) {
                http2.post('/rest/mp/user/fans/addGroup', $scope.editing, function(rsp) {
                    $scope.editing.id = rsp.data.id;
                });
            } else {
                http2.post('/rest/mp/user/fans/updateGroup', $scope.editing);
            }
        };
        $scope.refresh = function() {
            http2.get('/rest/pl/fe/site/sns/wx/group/refresh?site=' + $scope.siteId, function(rsp) {
                alert('更新用户分组数量：' + rsp.data);
            });
        };
    }]);
});