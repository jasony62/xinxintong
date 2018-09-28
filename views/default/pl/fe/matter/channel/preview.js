define(['frame'], function(ngApp) {
    ngApp.provider.controller('ctrlPreview', ['$scope', 'http2', 'noticebox', function($scope, http2, noticebox) {
        $scope.editPage = function(event, page) {
            event.preventDefault();
            event.stopPropagation();
            var prop = page + '_page_name',
                codeName = $scope.editing[prop];
            if (codeName && codeName.length) {
                location.href = '/rest/pl/fe/code?site=' + $scope.siteId + '&name=' + codeName;
            } else {
                http2.get('/rest/pl/fe/matter/channel/pageCreate?site=' + $scope.siteId + '&id=' + $scope.id + '&page=' + page).then(function(rsp) {
                    $scope.editing[prop] = rsp.data.name;
                    location.href = '/rest/pl/fe/code?site=' + $scope.siteId + '&name=' + rsp.data.name;
                });
            }
        };
        $scope.resetPage = function(event, page) {
            event.preventDefault();
            event.stopPropagation();
            if (window.confirm('重置操作将覆盖已经做出的修改，确定重置？')) {
                var codeName = $scope.editing[page + '_page_name'];
                if (codeName && codeName.length) {
                    http2.get('/rest/pl/fe/matter/channel/pageReset?site=' + $scope.siteId + '&id=' + $scope.id + '&page=' + page).then(function(rsp) {
                        location.href = '/rest/pl/fe/code?site=' + $scope.siteId + '&name=' + codeName;
                    });
                } else {
                    http2.get('/rest/pl/fe/matter/channel/pageCreate?site=' + $scope.siteId + '&id=' + $scope.id + '&page=' + page).then(function(rsp) {
                        $scope.editing[prop] = rsp.data.name;
                        location.href = '/rest/pl/fe/code?site=' + $scope.siteId + '&name=' + rsp.data.name;
                    });
                }
            }
        };
        $scope.applyToHome = function() {
            var url = '/rest/pl/fe/matter/home/apply?site=' + $scope.siteId + '&type=channel&id=' + $scope.id;
            http2.get(url).then(function(rsp) {
                noticebox.success('完成申请！');
            });
        };
    }]);
});