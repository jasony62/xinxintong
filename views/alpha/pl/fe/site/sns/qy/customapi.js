define(['main'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlCustomapi', ['$scope', 'http2', function ($scope, http2) {
        $scope.page = {
            at: 1,
            size: 5
        };
        //同步属性
        $scope.type = 'syncFromQy';
        $scope.syncType = 'department';
        $scope.changeNote = function(type){
            $scope.type = type;
            $scope.doSearch(1);
        };
        $scope.doSearch = function (page, syncType) {
            var url = '/rest/pl/fe/site/member/schema/syncLog';
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

        $scope.syn = function () {
            var url = '/rest/pl/fe/site/member/schema/';
            url += $scope.type;
            url += '?site=' + $scope.siteId;
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
