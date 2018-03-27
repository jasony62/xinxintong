define(['frame'], function(ngApp) {
    ngApp.provider.controller('ctrlUser', ['$scope', '$q', '$uibModal', 'http2', 'noticebox', 'cstApp', 'srvGroupApp', 'srvGroupRound', 'srvGroupPlayer', 'srvMemberPicker', function($scope, $q, $uibModal, http2, noticebox, cstApp, srvGroupApp, srvGroupRound, srvGroupPlayer, srvMemberPicker) {
        $scope.syncByApp = function(data) {
            srvGroupApp.syncByApp().then(function(count) {
                $scope.list();
            });
        };
        $scope.chooseAppUser = function() {
            var oSourceApp;
            if (oSourceApp = $scope.app.sourceApp) {
                switch (oSourceApp.type) {
                    case 'enroll':
                        break;
                    case 'signin':
                        break;
                    case 'mschema':
                        var matter = {
                            id: $scope.app.id,
                            siteid: $scope.app.siteid,
                            type: 'group'
                        }
                        srvMemberPicker.open(matter, oSourceApp).then(function() {
                            $scope.list();
                        });
                        break;
                }
            }
        };
        $scope.export = function() {
            srvGroupApp.export();
        };
        $scope.execute = function() {
            srvGroupPlayer.execute();
        };
        $scope.list = function() {
            if (_oCriteria.round.round_id === 'all') {
                srvGroupPlayer.list(null);
            } else if (_oCriteria.round.round_id === 'pending') {
                srvGroupPlayer.list(false);
            } else {
                srvGroupPlayer.list(_oCriteria.round);
            }
        };
        $scope.editPlayer = function(player) {
            srvGroupPlayer.edit(player).then(function(updated) {
                srvGroupPlayer.update(player, updated.player);
                srvGroupApp.update('tags');
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
            srvGroupPlayer.notify(isBatch ? $scope.rows : undefined);
        };
        $scope.filter = {
            show: function(event) {
                var eleKw;
                this.target = event.target;
                while (this.target.tagName !== 'TH') {
                    this.target = this.target.parentNode;
                }
                if (!this.target.dataset.filterBy) {
                    alert('没有指定过滤字段【data-filter-by】');
                    return;
                }
                $(this.target).trigger('show');
            },
            close: function() {
                if (this.keyword) {
                    this.target.classList.add('active');
                } else {
                    this.target.classList.remove('active');
                }
                $(this.target).trigger('hide');
            },
            cancel: function() {
                _oCriteria.round.round_id = 'all';
                $scope.list();
                this.close();
            },
            exec: function() {
                $scope.list();
                this.close();
            }
        };
        var players, _oCriteria;
        $scope.players = players = [];
        // 表格定义是否准备完毕
        $scope.tableReady = 'N';
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
        $scope.criteria = _oCriteria = {
            round: { round_id: 'all' }
        };
        $scope.$watch('app', function(oApp) {
            if (oApp) {
                if (oApp.assignedNickname) {
                    $scope.bRequireNickname = oApp.assignedNickname.valid !== 'Y' || !oApp.assignedNickname.schema;
                }
                srvGroupPlayer.init(players).then(function() {
                    $scope.list();
                    $scope.tableReady = 'Y';
                });
            }
        });
        srvGroupRound.list().then(function(rounds) {
            $scope.rounds = rounds;
        });
    }]);
});