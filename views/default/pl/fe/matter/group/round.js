define(['frame'], function(ngApp) {
    ngApp.provider.controller('ctrlRound', ['$scope', '$anchorScroll', '$timeout', '$location', '$uibModal', 'srvGroupRound', 'srvRecordConverter', function($scope, $anchorScroll, $timeout, $location, $uibModal, srvGroupRound, srvRecordConverter) {
        srvGroupRound.list().then(function(rounds) {
            $scope.rounds = rounds;
        });
        $scope.configRule = function() {
            srvGroupRound.config().then(function() {
                srvGroupRound.list().then(function(rounds) {
                    $scope.rounds = rounds;
                });
            });
        };
        $scope.emptyRule = function() {
            srvGroupRound.empty();
        };
        $scope.addRound = function() {
            srvGroupRound.add().then(function(oNewRound) {
                $timeout(function() {
                    $location.hash(oNewRound.round_id);
                    $anchorScroll();
                });
            });
        };
        $scope.removeRound = function(oRound) {
            srvGroupRound.remove(oRound);
        };
        $scope.updateRound = function(oRound, name) {
            srvGroupRound.update(oRound, name);
        };
        $scope.addTarget = function(oRound) {
            $uibModal.open({
                templateUrl: 'targetEditor.html',
                resolve: {
                    schemas: function() {
                        return angular.copy($scope.app.data_schemas);
                    }
                },
                controller: ['$uibModalInstance', '$scope', 'schemas', function($mi, $scope, schemas) {
                    $scope.schemas = schemas;
                    $scope.target = {};
                    $scope.cancel = function() {
                        $mi.dismiss();
                    };
                    $scope.ok = function() {
                        $mi.close($scope.target);
                    };
                }],
                backdrop: 'static',
            }).result.then(function(target) {
                oRound.targets.push(target);
                $scope.saveTargets(oRound);
            });
        };
        $scope.moveUpTarget = function(oRound, oTarget) {
            var targets = oRound.targets,
                index = targets.indexOf(oTarget);

            if (index > 0) {
                targets.splice(index, 1);
                targets.splice(index - 1, 0, oTarget);
                $scope.saveTargets(oRound);
            }
        };
        $scope.moveDownTarget = function(oRound, oTarget) {
            var targets = oRound.targets,
                index = targets.indexOf(oTarget);

            if (index < targets.length - 1) {
                targets.splice(index, 1);
                targets.splice(index + 1, 0, oTarget);
                $scope.saveTargets(oRound);
            }
        };
        $scope.removeTarget = function(oRound, i) {
            oRound.targets.splice(i, 1);
            $scope.saveTargets(oRound);
        };
        $scope.labelTarget = function(target) {
            var schema, labels = [];
            angular.forEach(target, function(v, k) {
                if (k !== '$$hashKey' && v && v.length) {
                    if (schema = $scope.app._schemasById[k]) {
                        labels.push(schema.title + ':' + srvRecordConverter.value2Html(v, schema));
                    }
                }
            });
            return labels.join(',');
        };
        $scope.saveTargets = function(oRound) {
            $scope.updateRound(oRound, 'targets');
        };
    }]);
});