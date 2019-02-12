'use strict';

window.moduleAngularModules = ['ngRoute'];

var ngApp = require('./base.js');
ngApp.config(['$routeProvider', function($routeProvider) {
    $routeProvider
        .when('/rest/site/fe/matter/group/team/create', { template: require('./team/create.html'), controller: 'ctrlTeamCreate' })
        .when('/rest/site/fe/matter/group/team/config', { template: require('./team/config.html'), controller: 'ctrlTeamConfig' })
        .otherwise({ template: require('./team/home.html'), controller: 'ctrlTeamHome' });
}]);
ngApp.controller('ctrlTeam', ['$scope', 'tmsLocation', function($scope, LS) {
    $scope.gotoHome = function() {
        location.href = '/rest/site/fe/matter/group?' + LS.s('site', 'app');
    };
}]);
ngApp.controller('ctrlTeamHome', ['$scope', '$location', 'facGroupTeam', function($scope, $location, facGrpTeam) {
    $scope.config = function() {
        $location.path('/rest/site/fe/matter/group/team/config');
    };
    facGrpTeam.get().then(function(oTeam) {
        $scope.team = oTeam;
    });
}]);
ngApp.controller('ctrlTeamCreate', ['$scope', '$location', 'facGroupTeam', function($scope, $location, facGrpTeam) {
    $scope.submit = function() {
        facGrpTeam.create().then(function(oNewTeam) {
            var s = $location.search();
            s.team = oNewTeam.team_id;
            $location.path('/rest/site/fe/matter/group/team/home').search(s);
        });
    };
}]);
ngApp.controller('ctrlTeamConfig', ['$scope', '$location', function($scope, $location) {
    $scope.submit = function() {
        $location.path('/rest/site/fe/matter/group/team/home');
    };
}]);