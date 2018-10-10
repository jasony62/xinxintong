define(['main'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlPage', ['$scope', 'http2', function($scope, http2) {
        $scope.edit = function() {
            var name = $scope.wx.follow_page_name;
            if (name && name.length) {
                location.href = '/rest/pl/fe/code?site=' + $scope.siteId + '&name=' + name;
            } else {
                http2.get('/rest/pl/fe/site/sns/wx/page/create?site=' + $scope.siteId).then(function(rsp) {
                    $scope.wx.follow_page_id = rsp.data.id;
                    $scope.wx.follow_page_name = rsp.data.name;
                    location.href = '/rest/pl/fe/code?site=' + $scope.siteId + '&name=' + rsp.data.name;
                });
            }
        };
        $scope.reset = function() {
            if (window.confirm('重置操作将覆盖已经做出的修改，确定重置？')) {
                var name = $scope.wx.follow_page_name;
                if (name && name.length) {
                    http2.get('/rest/pl/fe/site/sns/wx/page/reset?site=' + $scope.siteId + '&name=' + name).then(function(rsp) {
                        location.href = '/rest/pl/fe/code?site=' + $scope.siteId + '&name=' + name;
                    });
                } else {
                    http2.get('/rest/pl/fe/site/sns/wx/page/create?site=' + $scope.siteId).then(function(rsp) {
                        $scope.wx.follow_page_name = rsp.data.name;
                        location.href = '/rest/pl/fe/code?site=' + $scope.siteId + '&name=' + rsp.data.name;
                    });
                }
            }
        };
    }]);
});