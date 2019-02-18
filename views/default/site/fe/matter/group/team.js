'use strict';

window.moduleAngularModules = ['ngRoute'];

var ngApp = require('./base.js');
ngApp.config(['$routeProvider', function($routeProvider) {
    $routeProvider
        .when('/rest/site/fe/matter/group/team/create', { template: require('./team/create.html'), controller: 'ctrlTeamCreate' })
        .when('/rest/site/fe/matter/group/team/config', { template: require('./team/config.html'), controller: 'ctrlTeamConfig' })
        .otherwise({ template: require('./team/home.html'), controller: 'ctrlTeamHome' });
}]);
ngApp.controller('ctrlTeam', ['$scope', 'tmsLocation', 'facGroupApp', function($scope, LS, facGrpApp) {
    $scope.gotoHome = function() {
        location.href = '/rest/site/fe/matter/group?' + LS.s('site', 'app');
    };
    $scope.gotoTeamHome = function() {
        LS.path('/rest/site/fe/matter/group/team/home');
    };
    facGrpApp.get().then(function(oApp) {
        $scope.app = oApp;
        $scope.schemas = oApp.dataSchemas;
    });
}]);
ngApp.controller('ctrlTeamHome', ['$scope', '$uibModal', 'tmsLocation', 'facGroupTeam', 'facGroupRecord', function($scope, $uibModal, LS, facGrpTeam, facGrpRec) {
    $scope.config = function() {
        LS.path('/rest/site/fe/matter/group/team/config');
    };
    $scope.invite = function() {
        $uibModal.open({
            template: require('./team/invite.html'),
            controller: ['$scope', '$uibModalInstance', 'tmsLocation', function($scope2, $mi, LS) {
                $scope2.close = function() { $mi.dismiss(); };
                $scope2.inviteUrl = location.protocol + '//' + location.host + '/rest/site/fe/matter/group/invite?' + LS.s('site', 'app', 'team');
            }]
        });
    };
    $scope.quit = function(oMember) {
        facGrpTeam.quit(oMember).then(function() {
            $scope.members.splice($scope.members.indexOf(oMember), 1);
        });
    };
    facGrpTeam.get().then(function(oTeam) {
        $scope.team = oTeam;
        facGrpRec.list().then(function(members) {
            $scope.members = members;
        });
    });
}]);
ngApp.controller('ctrlTeamCreate', ['$scope', '$location', 'facGroupTeam', function($scope, $location, facGrpTeam) {
    $scope.submit = function() {
        facGrpTeam.create($scope.team, $scope.member).then(function(oNewTeam) {
            var s = $location.search();
            s.team = oNewTeam.team_id;
            $scope.gotoTeamHome();
        });
    };
    $scope.team = {};
    $scope.member = {};
}]);
ngApp.controller('ctrlTeamConfig', ['$scope', '$location', 'facGroupTeam', function($scope, $location, facGrpTeam) {
    var _oBeforeSubmitted, _oUpdated;
    _oUpdated = {};
    $scope.update = function(prop) {
        _oUpdated[prop] = $scope.team[prop];
        $scope.modified = true;
    };
    $scope.submit = function() {
        if (Object.keys(_oUpdated).length) {
            facGrpTeam.update(_oUpdated).then(function() {
                $scope.modified = false;
                _oUpdated = {};
            });
        }
    };
    $scope.modified = false;
    facGrpTeam.get().then(function(oTeam) {
        _oBeforeSubmitted = oTeam;
        $scope.team = angular.copy(_oBeforeSubmitted);
    });
}]);