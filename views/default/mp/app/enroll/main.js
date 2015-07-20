xxtApp.controller('enrollCtrl', ['$rootScope', '$scope', 'http2', function ($rootScope, $scope, http2) {
    $scope.page = { at: 1, size: 30 }
    $rootScope.floatToolbar = { matterShop: true };
    $scope.doSearch = function () {
        var url = '/rest/mp/app/enroll/get?page=' + $scope.page.at + '&size=' + $scope.page.size + '&contain=total';
        $scope.fromParent && $scope.fromParent === 'Y' && (url += '&src=p');
        http2.get(url, function (rsp) {
            $scope.activities = rsp.data[0];
            $scope.page.total = rsp.data[1];
        });
    };
    $scope.open = function (aid) {
        if (aid === undefined) {
            http2.get('/rest/mp/app/enroll/create', function (rsp) {
                location.href = '/rest/mp/app/enroll/detail?aid=' + rsp.data.id;
            });
        } else
            location.href = '/rest/mp/app/enroll/detail?aid=' + aid;
    };
    $scope.removeAct = function (act, event) {
        event.preventDefault();
        event.stopPropagation();
        http2.get('/rest/mp/app/enroll/remove?aid=' + act.id, function (rsp) {
            var i = $scope.activities.indexOf(act);
            $scope.activities.splice(i, 1);
        });
    };
    $scope.copyAct = function (copied, event) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }
        var url = '/rest/mp/app/enroll/copy?';
        if (copied.id)
            url += 'aid=' + copied.id;
        else if (copied.shopid)
            url += 'shopid=' + copied.shopid;
        http2.get(url, function (rsp) {
            location.href = '/rest/mp/app/enroll/detail?aid=' + rsp.data.id;
        });
    };
    $scope.$on('xxt.float-toolbar.shop.open', function (event) {
        $scope.$emit('mattershop.open', { type: 'enroll' });
    });
    $scope.$on('xxt.float-toolbar.shop.copy', function (event, item) {
        $scope.copyAct({ shopid: item.id });
    });
    $scope.doSearch();
}]);
