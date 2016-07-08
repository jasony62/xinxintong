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
xxtApp.config(['$routeProvider', function($routeProvider) {
    $routeProvider.when('/rest/pl/fe/matter/wall/users', {
        templateUrl: '/views/default/pl/fe/matter/wall/users.html?_=1',
        controller: 'usersCtrl',
        resolve: {
            load: function($q) {
                var defer = $q.defer();
                (function() {
                    $.getScript('/views/default/pl/fe/matter/wall/users.js', function() {
                        defer.resolve();
                    });
                })();
                return defer.promise;
            }
        }
    }).when('/rest/pl/fe/matter/wall/approve', {
        templateUrl: '/views/default/pl/fe/matter/wall/approve.html?_=2',
        controller: 'approveCtrl',
        resolve: {
            load: function($q) {
                var defer = $q.defer();
                (function() {
                    $.getScript('/views/default/pl/fe/matter/wall/approve.js', function() {
                        defer.resolve();
                    });
                })();
                return defer.promise;
            }
        }
    }).when('/rest/pl/fe/matter/wall/message', {
        templateUrl: '/views/default/pl/fe/matter/wall/message.html?_=1',
        controller: 'messageCtrl',
        resolve: {
            load: function($q) {
                var defer = $q.defer();
                (function() {
                    $.getScript('/views/default/pl/fe/matter/wall/message.js', function() {
                        defer.resolve();
                    });
                })();
                return defer.promise;
            }
        }
    }).when('/rest/pl/fe/matter/wall/page', {
        templateUrl: '/views/default/pl/fe/matter/wall/page.html?_=1',
        controller: 'pageCtrl',
        resolve: {
            load: function($q) {
                var defer = $q.defer();
                (function() {
                    $.getScript('/views/default/pl/fe/matter/wall/page.js', function() {
                        defer.resolve();
                    });
                })();
                return defer.promise;
            }
        }
    }).otherwise({
        templateUrl: '/views/default/pl/fe/matter/wall/setting.html?_=2',
        controller: 'settingCtrl',
        resolve: {
            load: function($q) {
                var defer = $q.defer();
                (function() {
                    $.getScript('/views/default/pl/fe/matter/wall/setting.js', function() {
                        defer.resolve();
                    });
                })();
                return defer.promise;
            }
        }
    });
}]);
xxtApp.controller('wallCtrl', ['$scope', '$http', '$location', 'http2', function($scope, $http, $location, http2) {
    $scope.wid = $location.search().wall;
    $scope.subView = 'setting';
    $scope.back = function() {
        location.href = '/rest/pl/fe/matter/wall';
    };
    $scope.update = function(name) {
        var nv = {};
        nv[name] = $scope.wall[name];
        http2.post('/rest/pl/fe/matter/wall/update?wall=' + $scope.wid, nv);
    };
    $scope.$watch('subView', function(nv) {
        if (nv !== 'approve' && $scope.worker) {
            $scope.worker.terminate();
        }
    });
    http2.get('/rest/mp/mpaccount/get', function(rsp) {
        $scope.mpaccount = rsp.data;
        http2.get('/rest/pl/fe/matter/wall/get?wall=' + $scope.wid, function(rsp) {
            $scope.wall = rsp.data;
        });
    });
}]);