'use strict';

var ngApp = require('./base.js');
ngApp.controller('ctrlMain', ['$scope', 'tmsLocation', 'facGroupApp', 'facGroupTeam', 'facGroupUser', function($scope, LS, facGrpApp, facGrpTeam, facGroupUser) {
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
            facGroupUser.get().then(function(oUser) {
                $scope.user = oUser;
                if (oUser.records && oUser.records.teams && oUser.records.teams.length) {
                    teams.forEach(function(oTeam) {
                        if (oUser.records[oTeam.team_id] && oUser.records[oTeam.team_id].is_leader === 'Y') {
                            oTeam.is_leader = 'Y';
                        }
                    });
                }
            });
        });
    });
}]);