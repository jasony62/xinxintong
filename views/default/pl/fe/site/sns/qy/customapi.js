define(['main'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlCustomapi', ['$scope', 'http2', function ($scope, http2) {
        //初始值
        $scope.page = {//分页
            at: 1,
            size: 5
        };
        //同步属性
        $scope.type = 'syncFromQy';//企业号日志查询
        $scope.syncType = 'department';//部门状态
        //切换日志方法
        $scope.changeNote = function(type){
            $scope.type = type;
            $scope.doSearch(1);
        };
        //获取日志
        //接口 /rest/site/fe/user/member/syncLog' + ?site=' + $scope.siteId + '&type=' + $scope.type  + '&page=' + $scope.page.at +'&size=' + $scope.page.size
        $scope.doSearch = function (page, syncType) {
            var url = '/rest/site/fe/user/member/syncLog';
            page && ($scope.page.at = page );
            if (syncType && syncType !== $scope.syncType) {
                $scope.syncType = syncType;
            }
            url += '?site=' + $scope.siteId;
            url += '&type=' + $scope.type;
            url += '&page=' + $scope.page.at;
            url += '&size=' + $scope.page.size;
            http2.post(url,{syncType:$scope.syncType}, function (rsp) {
                $scope.records = rsp.data.data;
                $scope.page.total = rsp.data.total;
            });
        };
        //同步方法
        //同步接口 /rest/site/fe/user/member/syncFromQy + '?site=' + $scope.siteId +&authid=' + 0
        $scope.syn = function () {
            var url = '/rest/site/fe/user/member/';
            url += $scope.type;
            url += '?site=' + $scope.siteId;
            url += '&authid=' + 0;
            http2.get(url, function (rsp) {
                if (rsp.err_code == 0) {
                    alert("同步" + rsp.data[0] + "个部门，" + rsp.data[1] + "个用户，" + rsp.data[2] + "个标签");
                    //$scope.$root.progmsg = "同步" + rsp.data[0] + "个部门，" + rsp.data[1] + "个用户，" + rsp.data[2] + "个标签";

                }
                $scope.doSearch(1);
            });
        };
        $scope.doSearch(1);
    }]);
})
