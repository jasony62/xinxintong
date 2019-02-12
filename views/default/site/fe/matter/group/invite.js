'use strict';

var ngApp = require('./base.js');
ngApp.controller('ctrlInvite', ['$scope', 'tmsLocation', 'facGroupApp', 'facGroupTeam', function($scope, LS, facGrpApp, facGrpTeam) {
    $scope.accept = function() {
        facGrpTeam.join().then(function() {
            location.href = '/rest/site/fe/matter/group/team/home?' + LS.s('site', 'app', 'team');
        });
    };
    facGrpApp.get().then(function(oApp) {
        $scope.app = oApp;
        facGrpTeam.get().then(function(oTeam) {
            $scope.team = oTeam;
        });
    });
}]);