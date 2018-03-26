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
                            type: 'group',
                            appid: $scope.app.id
                        }
                        srvMemberPicker.open(matter, oSourceApp).then(function() {
                            $scope.list();
                        });
                        $uibModal.open({
                            templateUrl: '/views/default/pl/fe/_module/memberPicker.html',
                            resolve: {
                                mschema: function() {
                                    return oSourceApp;
                                },
                                action: function() {
                                    return {
                                        label: '加入',
                                        execute: function(members) {
                                            var ids, defer;
                                            defer = $q.defer();
                                            if (members.length) {
                                                ids = [];
                                                members.forEach(function(oMember) {
                                                    ids.push(oMember.id);
                                                });
                                                http2.post('/rest/pl/fe/matter/group/player/addByApp?app=' + $scope.app.id, ids, function(rsp) {
                                                    noticebox.success('加入【' + rsp.data + '】个用户');
                                                    defer.resolve(rsp.data);
                                                });
                                            }
                                            return defer.promise;
                                        }
                                    }
                                }
                            },
                            controller: ['$scope', '$uibModalInstance', 'http2', 'tmsSchema', 'mschema', 'action', function($scope2, $mi, http2, tmsSchema, _oMschema, _oAction) {
                                var _oPage, _oRows, _bAdded;
                                $scope2.mschema = _oMschema;
                                $scope2.action = _oAction;
                                $scope2.page = _oPage = {
                                    at: 1,
                                    size: 30,
                                    keyword: '',
                                    //searchBy: $scope2.searchBys[0].v
                                };
                                // 选中的记录
                                $scope2.rows = _oRows = {
                                    selected: {},
                                    count: 0,
                                    change: function(index) {
                                        this.selected[index] ? this.count++ : this.count--;
                                    },
                                    reset: function() {
                                        this.selected = {};
                                        this.count = 0;
                                    }
                                };
                                $scope2.doSearch = function(pageAt) {
                                    pageAt && (_oPage.at = pageAt);
                                    var url, filter = '';
                                    if (_oPage.keyword !== '') {
                                        filter = '&kw=' + _oPage.keyword;
                                        filter += '&by=' + _oPage.searchBy;
                                    }
                                    url = '/rest/pl/fe/site/member/list?site=' + _oMschema.siteid + '&schema=' + _oMschema.id;
                                    url += '&page=' + _oPage.at + '&size=' + _oPage.size + filter
                                    url += '&contain=total';
                                    http2.get(url, function(rsp) {
                                        var members;
                                        members = rsp.data.members;
                                        if (members.length) {
                                            if (_oMschema.extAttrs.length) {
                                                members.forEach(function(oMember) {
                                                    oMember._extattr = tmsSchema.member.getExtattrsUIValue(_oMschema.extAttrs, oMember);
                                                });
                                            }
                                        }
                                        $scope2.members = members;
                                        _oPage.total = rsp.data.total;
                                        _oRows.reset();
                                    });
                                };
                                $scope2.cancel = function() {
                                    _bAdded ? $mi.close() : $mi.dismiss();
                                };
                                $scope2.execute = function(bClose) {
                                    var pickedMembers;
                                    if (_oRows.count) {
                                        pickedMembers = [];
                                        for (var i in _oRows.selected) {
                                            pickedMembers.push($scope2.members[i]);
                                        }
                                        _oAction.execute(pickedMembers).then(function() {
                                            if (bClose) {
                                                $mi.close();
                                            } else {
                                                _bAdded = true;
                                            }
                                        });
                                    }
                                };
                                $scope2.doSearch(1);
                            }],
                            backdrop: 'static',
                            size: 'lg',
                            windowClass: 'auto-height'
                        }).result.then(function() {
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