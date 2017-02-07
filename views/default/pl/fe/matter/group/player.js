define(['frame'], function(ngApp) {
    ngApp.provider.controller('ctrlPlayer', ['$scope', 'srvApp', 'srvRound', 'srvPlayer', function($scope, srvApp, srvRound, srvPlayer) {
        $scope.activeRound = null;
        srvRound.list().then(function(rounds) {
            $scope.rounds = rounds;
        });
        $scope.importByApp = function() {
            srvApp.importByApp().then(function() {
                $scope.openRound(null);
            });
        };
        $scope.cancelSourceApp = function() {
            srvApp.cancelSourceApp();
        };
        $scope.syncByApp = function(data) {
            srvApp.syncByApp().then(function(count) {
                if (count > 0) {
                    srvPlayer.list();
                }
            });
        };
        $scope.configRule = function() {
            srvRound.config();
        };
        $scope.emptyRule = function() {
            srvRound.empty().then(function() {
                if ($scope.activeRound !== null) {
                    $scope.openRound(null);
                } else {
                    srvPlayer.list(null);
                }
            });
        };
        $scope.openRound = function(round) {
            $scope.activeRound = round;
        };
        $scope.addRound = function() {
            srvRound.add();
        };
        $scope.removeRound = function() {
            srvRound.remove($scope.activeRound).then(function() {
                $scope.activeRound = null;
            });
        };
        $scope.export = function() {
            srvApp.export();
        };
        $scope.execute = function() {
            srvPlayer.execute();
        };
    }]);
    ngApp.provider.controller('ctrlRound', ['$scope', 'srvRound', function($scope, srvRound) {
        $scope.activeTabIndex = 0;
        $scope.activeTab = function(index) {
            $scope.activeTabIndex = index;
        };
        $scope.updateRound = function(name) {
            srvRound.update($scope.activeRound, name);
        };
    }]);
    ngApp.provider.controller('ctrlRule', ['$scope', '$uibModal', 'http2', 'noticebox', 'srvRecordConverter', function($scope, $uibModal, http2, noticebox, srvRecordConverter) {
        $scope.aTargets = null;
        $scope.$watch('activeRound', function(round) {
            $scope.aTargets = (!round || round.targets.length === 0) ? [] : eval(round.targets);
        });
        $scope.addTarget = function() {
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
                $scope.aTargets.push(target);
                $scope.saveTargets();
            });
        };
        $scope.removeTarget = function(i) {
            $scope.aTargets.splice(i, 1);
            $scope.saveTargets();
        };
        $scope.labelTarget = function(target) {
            var labels = [];
            angular.forEach(target, function(v, k) {
                if (k !== '$$hashKey' && v && v.length) {
                    labels.push(srvRecordConverter.value2Html(v, $scope.app._schemasById[k]));
                }
            });
            return labels.join(',');
        };
        $scope.saveTargets = function() {
            $scope.activeRound.targets = $scope.aTargets;
            $scope.updateRound('targets');
        };
    }]);
    ngApp.provider.controller('ctrlPlayers', ['$scope', 'srvPlayer', function($scope, srvPlayer) {
        var players;
        $scope.players = players = [];
        srvPlayer.init(players).then(function() {
            $scope.$watch('activeRound', function(round) {
                if (round !== undefined) {
                    $scope.list();
                    $scope.tableReady = 'Y';
                }
            });
        });
        $scope.list = function() {
            srvPlayer.list($scope.activeRound).then(function() {
                if ($scope.activeRound) {
                    $scope.activeTab(players.length ? 1 : 0);
                }
            });
        };
        $scope.editPlayer = function(player) {
            srvPlayer.edit(player).then(function(updated) {
                srvPlayer.update(player, updated.player);
            });
        };
        $scope.addPlayer = function() {
            srvPlayer.edit({ tags: '' }).then(function(updated) {
                srvPlayer.add(updated.player);
            });
        };
        $scope.removePlayer = function(player) {
            if (window.confirm('确认删除？')) {
                srvPlayer.remove(player);
            }
        };
        $scope.empty = function() {
            srvPlayer.empty();
        };
        // 当前选中的行
        $scope.rows = {
            allSelected: 'N',
            selected: {},
            players: [],
            reset: function() {
                this.allSelected = 'N';
                this.selected = {};
                this.players = [];
            }
        };
        $scope.selectPlayer = function(player) {
            var players = $scope.rows.players,
                i = players.indexOf(player);
            i === -1 ? players.push(player) : players.splice(i, 1);
        };
        // 选中或取消选中所有行
        $scope.selectAllRows = function(checked) {
            var index = 0;
            if (checked === 'Y') {
                $scope.rows.players = [];
                while (index < $scope.players.length) {
                    $scope.rows.players.push($scope.players[index]);
                    $scope.rows.selected[index++] = true;
                }
            } else if (checked === 'N') {
                $scope.rows.reset();
            }
        };
        $scope.quitGroup = function(players) {
            if ($scope.activeRound && players.length) {
                srvPlayer.quitGroup($scope.activeRound, players).then(function() {
                    $scope.rows.reset();
                });
            }
        };
        $scope.joinGroup = function(round, players) {
            if (round && players.length) {
                srvPlayer.joinGroup(round, players).then(function() {
                    $scope.rows.reset();
                });
            }
        };
        // 表格定义是否准备完毕
        $scope.tableReady = 'N';
    }]);
});
