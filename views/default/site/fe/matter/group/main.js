'use strict';

var ngApp = require('./base.js');
ngApp.controller('ctrlMain', ['$scope', 'tmsLocation', 'facGroupApp', 'facGroupTeam', function($scope, LS, facGrpApp, facGrpTeam) {
    $scope.gotoTeam = function(oTeam) {
        location.href = '/rest/site/fe/matter/group/team/home?' + LS.s('site', 'app') + '&team=' + oTeam.team_id;
    };
    $scope.createTeam = function() {
        location.href = '/rest/site/fe/matter/group/team/create?' + LS.s('site', 'app');
    };
    facGrpApp.get().then(function(oApp) {
        $scope.app = oApp;
        facGrpTeam.list().then(function(teams) {
            $scope.teams = teams;
        });
    });
}]);