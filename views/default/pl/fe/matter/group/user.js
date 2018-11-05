define(['frame'], function(ngApp) {
    ngApp.provider.controller('ctrlUser', ['$scope', 'noticebox', 'srvGroupApp', 'srvGroupRound', 'srvGroupPlayer', 'srvMemberPicker', 'facListFilter', function($scope, noticebox, srvGroupApp, srvGroupRound, srvGrpUsr, srvMemberPicker, facListFilter) {
        $scope.syncByApp = function(data) {
            srvGroupApp.syncByApp().then(function(count) {
                $scope.list('round');
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
                            $scope.list('round');
                        });
                        break;
                }
            }
        };
        $scope.export = function() {
            srvGroupApp.export();
        };
        $scope.execute = function() {
            srvGrpUsr.execute();
        };
        $scope.list = function(arg) {
            if (_oCriteria[arg].round_id === 'all') {
                srvGrpUsr.list(null, arg);
            } else if (_oCriteria[arg].round_id === 'pending') {
                srvGrpUsr.list(false, arg);
            } else {
                srvGrpUsr.list(_oCriteria[arg], arg);
            }
        };
        $scope.editUser = function(oUser) {
            srvGrpUsr.edit(oUser).then(function(updated) {
                srvGrpUsr.update(oUser, updated.player);
                srvGroupApp.update('tags');
            });
        };
        $scope.addUser = function() {
            srvGrpUsr.edit({ tags: '', role_rounds: [] }).then(function(updated) {
                srvGrpUsr.add(updated.player);
            });
        };
        $scope.removeUser = function(oUser) {
            if (window.confirm('确认删除？')) {
                srvGrpUsr.remove(oUser);
            }
        };
        $scope.empty = function() {
            srvGrpUsr.empty();
        };
        $scope.selectUser = function(oUser) {
            var players = $scope.rows.players,
                i = players.indexOf(oUser);
            i === -1 ? players.push(oUser) : players.splice(i, 1);
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
        $scope.quitGroup = function(users) {
            if (users.length) {
                srvGrpUsr.quitGroup(users).then(function() {
                    $scope.rows.reset();
                });
            }
        };
        $scope.joinGroup = function(oRound, users) {
            if (users.length && oRound) {
                srvGrpUsr.joinGroup(oRound, users).then(function() {
                    $scope.rows.reset();
                });
            }
        };
        $scope.notify = function(isBatch) {
            srvGrpUsr.notify(isBatch ? $scope.rows : undefined);
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
            round: { round_id: 'all' },
            roleRound: { round_id: 'all' }
        };
        $scope.filter = facListFilter.init(function(oFilterData, filterByProp, filterByKeyword) {
            if (/round|roleRound/.test(filterByProp)) {
                $scope.list(filterByProp);
            } else if ('nickname' === filterByProp) {
                srvGrpUsr.list(null, 'round', { by: filterByProp, kw: filterByKeyword });
            }
        }, _oCriteria);

        srvGroupApp.get().then(function(oApp) {
            if (oApp.assignedNickname) {
                $scope.bRequireNickname = oApp.assignedNickname.valid !== 'Y' || !oApp.assignedNickname.schema;
            }
            srvGrpUsr.init(players).then(function() {
                $scope.list('round');
                $scope.tableReady = 'Y';
            });
        });
        srvGroupRound.list().then(function(rounds) {
            $scope.teamRounds = [];
            $scope.roleRounds = [];
            rounds.forEach(function(oRound) {
                switch (oRound.round_type) {
                    case 'T':
                        $scope.teamRounds.push(oRound);
                        break;
                    case 'R':
                        $scope.roleRounds.push(oRound);
                        break;
                }
            });
        });
    }]);
});