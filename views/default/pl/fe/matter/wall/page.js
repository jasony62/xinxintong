define(['frame'], function(ngApp) {
    /**
     * app setting controller
     */
    ngApp.provider.controller('ctrlPage', ['$scope', '$q', 'http2', function($scope, $q, http2) {
        (function() {
            new ZeroClipboard(document.querySelectorAll('.text2Clipboard'));
        })();
        $scope.$parent.subView = 'page';
        $scope.$watch('wall', function(nv) {
            if (nv) {
                $scope.url = 'http://' + location.host + '/rest/site/op/matter/wall?site=' + $scope.siteId + '&wall=' + nv.id;
            }
        });
        //显示信息
        $scope.pageTypes = [{
            type: 'op',
            name: '信息墙.大屏幕'
        }];
        http2.get('/rest/pl/fe/matter/wall/page/list?id=' + $scope.id + '&site=' + $scope.siteId, function(rsp) {
            $scope.pages = {};
            angular.forEach(rsp.data, function(page) {
                $scope.pages[page.type] = page;
            });
        });
        //去代码页面
        $scope.gotoCode = function(page) {
            window.open('/rest/pl/fe/code?site=' + page.siteid + '&name=' + page.code_name, '_self');
        };
        //重置页面
        $scope.resetCode = function(page) {
            if (window.confirm('重置后将丢失已经做过的修改，确定操作？')) {
                http2.get('/rest/pl/fe/matter/wall/page/reset?id=' + $scope.id + '&page=' + page.id, function(rsp) {
                    $scope.gotoCode(page);
                });
            }
        }
    }]);
});
