define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlBasic', ['$scope', 'http2', 'mediagallery', function($scope, http2, mediagallery) {
        $scope.remove = function() {
            if (window.confirm('确定删除团队【' + $scope.site.name + '】？')) {
                var url = '/rest/pl/fe/site/remove?site=' + $scope.site.id;
                http2.get(url).then(function(rsp) {
                    location.href = '/rest/pl/fe';
                });
            }
        };
        $scope.quit = function() {
            if (window.confirm('确定退出团队【' + $scope.site.name + '】？')) {
                var url = '/rest/pl/fe/site/setting/admin/remove?site=' + $scope.site.id + '&uid=' + $scope.site.uid;
                http2.get(url).then(function(rsp) {
                    location.href = '/rest/pl/fe';
                });
            }
        };
        $scope.setPic = function() {
            var options = {
                callback: function(url) {
                    $scope.site.heading_pic = url + '?_=' + (new Date * 1);
                    $scope.update('heading_pic');
                }
            };
            mediagallery.open($scope.site.id, options);
        };
        $scope.removePic = function() {
            $scope.site.heading_pic = '';
            $scope.update('heading_pic');
        };
        $scope.editPage = function(event, page) {
            event.preventDefault();
            event.stopPropagation();
            var prop = page + '_page_name',
                name = $scope.site[prop];
            if (name && name.length) {
                location.href = '/rest/pl/fe/code?site=' + $scope.site.id + '&name=' + name;
            } else {
                http2.get('/rest/pl/fe/site/pageCreate?site=' + $scope.site.id + '&page=' + page).then(function(rsp) {
                    $scope.site[prop] = rsp.data.name;
                    location.href = '/rest/pl/fe/code?site=' + $scope.site.id + '&name=' + rsp.data.name;
                });
            }
        };
        $scope.resetPage = function(event, page) {
            event.preventDefault();
            event.stopPropagation();
            if (window.confirm('重置操作将覆盖已经做出的修改，确定重置？')) {
                var name = $scope.site[page + '_page_name'];
                if (name && name.length) {
                    http2.get('/rest/pl/fe/site/pageReset?site=' + $scope.site.id + '&page=' + page).then(function(rsp) {
                        location.href = '/rest/pl/fe/code?site=' + $scope.site.id + '&name=' + name;
                    });
                } else {
                    http2.get('/rest/pl/fe/site/pageCreate?site=' + $scope.site.id + '&page=' + page).then(function(rsp) {
                        $scope.site[prop] = rsp.data.name;
                        location.href = '/rest/pl/fe/code?site=' + $scope.site.id + '&name=' + rsp.data.name;
                    });
                }
            }
        };
        $scope.openPage = function(page) {
            var name = $scope.site[page + '_page_name'];
            if (name) {
                location.href = '/rest/site/home?site=' + $scope.site.id;
            }
        };
    }]);
});