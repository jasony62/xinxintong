xxtApp.filter('transState', function() {
    return function(input) {
        var out = "";
        input = parseInt(input);
        switch (input) {
            case 0:
                out = '未审核';
                break;
            case 1:
                out = '审核通过';
                break;
            case 2:
                out = '审核未通过';
                break;

        }
        return out;
    }
});

xxtApp.controller('wallCtrl', ['$scope', '$http', '$location', 'http2', function($scope, $http, $location, http2) {
    $scope.wid = $location.search().wall;
    $scope.subView = 'setting';
    $scope.back = function() {
        location.href = '/rest/mp/app/wall';
    };
    $scope.update = function(name) {
        var nv = {};
        nv[name] = $scope.wall[name];
        http2.post('/rest/mp/app/wall/update?wall=' + $scope.wid, nv);
    };
    $scope.$watch('subView', function(nv) {
        if (nv !== 'approve' && $scope.worker) {
            $scope.worker.terminate();
        }
    });
    http2.get('/rest/mp/mpaccount/get', function(rsp) {
        $scope.mpaccount = rsp.data;
        http2.get('/rest/mp/app/wall/get?wall=' + $scope.wid, function(rsp) {
            $scope.wall = rsp.data;
        });
    });
}]);
define(['frame'], function(ngApp) {
    /**
     * app setting controller
     */
    ngApp.provider.controller('ctrlDetail', ['$scope', '$q', 'http2',function($scope, $q, http2) {
        return function(input) {
            var out = "";
            input = parseInt(input);
            switch (input) {
                case 0:
                    out = '未审核';
                    break;
                case 1:
                    out = '审核通过';
                    break;
                case 2:
                    out = '审核未通过';
                    break;

            }
            return out;
        }
        http2.get('/rest/mp/mpaccount/get', function(rsp) {
            $scope.mpaccount = rsp.data;
            http2.get('/rest/mp/app/wall/get?wall=' + $scope.wid, function(rsp) {
                $scope.wall = rsp.data;
            });
        });
    }]);
});