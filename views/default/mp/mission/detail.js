xxtApp.config(['$routeProvider', function($routeProvider) {
    $routeProvider.when('/rest/mp/mission/setting', {
        templateUrl: '/views/default/mp/mission/setting.html?_=1',
        controller: 'settingCtrl',
        resolve: {
            load: function($q) {
                var defer = $q.defer();
                (function() {
                    $.getScript('/views/default/mp/mission/setting.js', function() {
                        defer.resolve();
                    });
                })();
                return defer.promise;
            }
        }
    }).when('/rest/mp/mission/matter', {
        templateUrl: '/views/default/mp/mission/matter.html?_=1',
        controller: 'settingCtrl',
        resolve: {
            load: function($q) {
                var defer = $q.defer();
                (function() {
                    $.getScript('/views/default/mp/mission/matter.js', function() {
                        defer.resolve();
                    });
                })();
                return defer.promise;
            }
        }
    }).otherwise({
        templateUrl: '/views/default/mp/mission/setting.html?_=1',
        controller: 'settingCtrl'
    });
}]);
xxtApp.controller('ctrlMission', ['$scope', '$location', 'http2', function($scope, $location, http2) {
    $scope.id = $location.search().id;
    $scope.back = function() {
        history.back();
    };
    $scope.$on('$routeChangeSuccess', function(evt, nextRoute, lastRoute) {
        if (nextRoute.loadedTemplateUrl.indexOf('/setting') !== -1) {
            $scope.subView = 'setting';
        } else if (nextRoute.loadedTemplateUrl.indexOf('/matter') !== -1) {
            $scope.subView = 'matter';
        }
    });
    http2.get('/rest/mp/mpaccount/get', function(rsp) {
        $scope.mpaccount = rsp.data;
    });
    http2.get('/rest/mp/mission/get?id=' + $scope.id, function(rsp) {
        $scope.editing = rsp.data;
        $scope.editing.type = 'mission';
    });
}]);