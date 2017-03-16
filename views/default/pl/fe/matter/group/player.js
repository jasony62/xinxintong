define(['frame'], function(ngApp) {
    ngApp.provider.controller('ctrlPlayer', ['$scope', 'srvGroupApp', 'srvGroupRound', 'srvGroupPlayer', function($scope, srvGroupApp, srvGroupRound, srvGroupPlayer) {
        $scope.activeRound = null;
        srvGroupRound.list().then(function(rounds) {
            $scope.rounds = rounds;
        });
        $scope.importByApp = function() {
            srvGroupApp.importByApp().then(function() {
                $scope.openRound(null);
            });
        };
        $scope.cancelSourceApp = function() {
            srvGroupApp.cancelSourceApp();
        };
        $scope.syncByApp = function(data) {
            srvGroupApp.syncByApp().then(function(count) {
                if (count > 0) {
                    srvGroupPlayer.list();
                }
            });
        };
        $scope.configRule = function() {
            srvGroupRound.config();
        };
        $scope.emptyRule = function() {
            srvGroupRound.empty().then(function() {
                if ($scope.activeRound !== null) {
                    $scope.openRound(null);
                } else {
                    srvGroupPlayer.list(null);
                }
            });
        };
        $scope.openRound = function(round) {
            $scope.activeRound = round;
        };
        $scope.addRound = function() {
            srvGroupRound.add();
        };
        $scope.removeRound = function() {
            srvGroupRound.remove($scope.activeRound).then(function() {
                $scope.activeRound = null;
            });
        };
        $scope.export = function() {
            srvGroupApp.export();
        };
        $scope.execute = function() {
            srvGroupPlayer.execute();
        };
    }]);
    ngApp.provider.controller('ctrlRound', ['$scope', 'srvGroupRound', function($scope, srvGroupRound) {
        $scope.activeTabIndex = 0;
        $scope.activeTab = function(index) {
            $scope.activeTabIndex = index;
        };
        $scope.updateRound = function(name) {
            srvGroupRound.update($scope.activeRound, name);
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
    ngApp.provider.controller('ctrlPlayers', ['$scope', 'srvGroupPlayer', function($scope, srvGroupPlayer) {
        var players;
        $scope.players = players = [];
        srvGroupPlayer.init(players).then(function() {
            $scope.$watch('activeRound', function(round) {
                if (round !== undefined) {
                    $scope.list();
                    $scope.tableReady = 'Y';
                }
            });
        });
        $scope.list = function() {
            srvGroupPlayer.list($scope.activeRound).then(function() {
                if ($scope.activeRound) {
                    $scope.activeTab(players.length ? 1 : 0);
                }
            });
        };
        $scope.editPlayer = function(player) {
            srvGroupPlayer.edit(player).then(function(updated) {
                srvGroupPlayer.update(player, updated.player);
            });
        };
        $scope.addPlayer = function() {
            srvGroupPlayer.edit({ tags: '' }).then(function(updated) {
                srvGroupPlayer.add(updated.player);
            });
        };
        $scope.removePlayer = function(player) {
            if (window.confirm('确认删除？')) {
                srvGroupPlayer.remove(player);
            }
        };
        $scope.empty = function() {
            srvGroupPlayer.empty();
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
                srvGroupPlayer.quitGroup($scope.activeRound, players).then(function() {
                    $scope.rows.reset();
                });
            }
        };
        $scope.joinGroup = function(round, players) {
            if (round && players.length) {
                srvGroupPlayer.joinGroup(round, players).then(function() {
                    $scope.rows.reset();
                });
            }
        };
        $scope.notify = function(isBatch) {
            srvGroupPlayer.notify(isBatch ? $scope.rows.players : undefined);
        };
        // 表格定义是否准备完毕
        $scope.tableReady = 'N';
    }]);
});
