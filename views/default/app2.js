console.log('aaaa.....');
app = angular.module('xxtApp', ["ngSanitize", "ngRoute"]);
app.config(['$locationProvider', function ($lp) {
    $lp.html5Mode(true);
}]);
app.config(['$routeProvider', function ($rp) {
    $rp.when('/views/default/app2.tpl.htm', {
        template: function () {
            console.log('tttt:' + location.search);
            return '<div>test</div><button ng-click="changeView()">change</button><script>console.log("hello");</script>';
        },
        controller: 'ctrl',
    });
}]);
app.controller('ctrl', ['$location', '$scope', '$route', '$sce', '$timeout', '$q', function ($location, $scope, $route, $sce, $timeout, $q) {
    //$scope.page = 1;
    $scope.changeView = function () {
        console.log('change view...');
        $location.path('/views/default/app2.tpl.htm').search({ page: (new Date()).getTime() });
        //$route.reload();
    };
}]);