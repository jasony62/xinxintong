define(['frame'], function(ngApp) {
    ngApp.provider.controller('ctrlTeam', ['$scope', '$anchorScroll', '$timeout', '$location', '$uibModal', 'srvGroupTeam', 'tmsSchema', function($scope, $anchorScroll, $timeout, $location, $uibModal, srvGrpTeam, tmsSchema) {
        srvGrpTeam.list().then(function(teams) {
            $scope.teams = teams;
            teams.forEach(function(oTeam) {
                oTeam._before = angular.copy(oTeam);
            });
        });
        $scope.configRule = function() {
            srvGrpTeam.config().then(function() {
                srvGrpTeam.list().then(function(teams) {
                    $scope.teams = teams;
                });
            });
        };
        $scope.emptyRule = function() {
            srvGrpTeam.empty();
        };
        $scope.addTeam = function() {
            srvGrpTeam.add().then(function(oNewTeam) {
                $timeout(function() {
                    $location.hash(oNewTeam.team_id);
                    $anchorScroll();
                });
            });
        };
        $scope.removeTeam = function(oTeam) {
            srvGrpTeam.remove(oTeam);
        };
        $scope.updateTeam = function(oTeam, name) {
            srvGrpTeam.update(oTeam, name);
        };
        $scope.addTarget = function(oTeam) {
            $uibModal.open({
                templateUrl: 'targetEditor.html',
                resolve: {
                    schemas: function() {
                        return angular.copy($scope.app.dataSchemas);
                    }
                },
                controller: ['$uibModalInstance', '$scope', 'schemas', function($mi, $scope, schemas) {
                    $scope.schemas = schemas;
                    $scope.target = {};
                    $scope.cancel = function() { $mi.dismiss(); };
                    $scope.ok = function() {
                        $mi.close($scope.target);
                    };
                }],
                backdrop: 'static',
            }).result.then(function(target) {
                oTeam.targets.push(target);
                $scope.saveTargets(oTeam);
            });
        };
        $scope.moveUpTarget = function(oTeam, oTarget) {
            var targets = oTeam.targets,
                index = targets.indexOf(oTarget);

            if (index > 0) {
                targets.splice(index, 1);
                targets.splice(index - 1, 0, oTarget);
                $scope.saveTargets(oTeam);
            }
        };
        $scope.moveDownTarget = function(oTeam, oTarget) {
            var targets = oTeam.targets,
                index = targets.indexOf(oTarget);

            if (index < targets.length - 1) {
                targets.splice(index, 1);
                targets.splice(index + 1, 0, oTarget);
                $scope.saveTargets(oTeam);
            }
        };
        $scope.removeTarget = function(oTeam, i) {
            oTeam.targets.splice(i, 1);
            $scope.saveTargets(oTeam);
        };
        $scope.labelTarget = function(target) {
            var schema, labels = [];
            angular.forEach(target, function(v, k) {
                if (k !== '$$hashKey' && v && v.length) {
                    if (schema = $scope.app._schemasById[k]) {
                        labels.push(schema.title + ':' + tmsSchema.value2Html(schema, v));
                    }
                }
            });
            return labels.join(',');
        };
        $scope.saveTargets = function(oTeam) {
            $scope.updateRound(oTeam, 'targets');
        };
    }]);
});