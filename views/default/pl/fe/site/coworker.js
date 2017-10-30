define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlCoworker', ['$scope', '$location', 'http2', 'noticebox', function($scope, $location, http2, noticebox) {
        $scope.siteId = $location.search().site;
        $scope.ulabel = '';
        $scope.manager = '';
        $scope.status = false;
        $scope.transfer = function() {
            var url = '/rest/pl/fe/site/setting/admin/transferSite?site=' + $scope.site.id
            url += '&label=' + $scope.newOwner;
            http2.get(url, function(rsp) {
                noticebox.success('移交成功');
                if (rsp.data == 1) {
                    $scope.status = true;
                }
                $scope.newOwner = '';
                http2.get('/rest/pl/fe/site/setting/admin/list?site=' + $scope.siteId, function(rsp) {
                    $scope.admins = rsp.data;
                });
            });
        }
        $scope.add = function() {
            var url = '/rest/pl/fe/site/setting/admin/add?site=' + $scope.siteId;
            $scope.ulabel && $scope.ulabel.length > 0 && (url += '&ulabel=' + $scope.ulabel);
            http2.get(url, function(rsp) {
                $scope.admins.push(rsp.data);
                $scope.ulabel = '';
            });
        };
        $scope.remove = function(admin) {
            http2.get('/rest/pl/fe/site/setting/admin/remove?site=' + $scope.siteId + '&uid=' + admin.uid, function(rsp) {
                var index = $scope.admins.indexOf(admin);
                $scope.admins.splice(index, 1);
            });
        };
        //获取邀请动态链接
        $scope.makeInvite = function() {
            http2.get('/rest/pl/fe/site/coworker/makeInvite?site=' + $scope.siteId, function(rsp) {
                var url = 'http://' + location.host + rsp.data;
                $scope.inviteURL = url;
                $('#shareSite').trigger('show');
            });
        };
        $scope.closeInvite = function() {
            $scope.inviteURL = '';
            $('#shareSite').trigger('hide');
        };
        http2.get('/rest/pl/fe/site/setting/admin/list?site=' + $scope.siteId, function(rsp) {
            $scope.admins = rsp.data;
        });
        http2.get('/rest/pl/fe/site/get?site=' + $scope.siteId, function(rsp) {
            $scope.site = rsp.data;
        });
    }]);
});