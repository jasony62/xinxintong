'use strict';

var ngMod = angular.module('round.ui.enroll', []);
ngMod.factory('enlRound', ['http2', '$q', '$uibModal', 'tmsLocation', function(http2, $q, $uibModal, LS) {
    var Round;
    Round = function(oApp) {
        this.app = oApp;
        this.page = {};
    };
    Round.prototype.get = function(aRids) {
        var defer;
        defer = $q.defer();
        if (aRids.indexOf('ALL') !== -1) {
            defer.resolve({ rid: 'ALL', title: '全部轮次' });
        } else {
            http2.get(LS.j('round/get', 'site', 'app') + '&rid=' + aRids).then(function(rsp) {
                defer.resolve(rsp.data);
            });
        }

        return defer.promise;
    };
    Round.prototype.list = function() {
        var deferred = $q.defer();
        http2.get(LS.j('round/list', 'site', 'app'), { page: this.page }).then(function(rsp) {
            deferred.resolve(rsp.data);
        });
        return deferred.promise;
    };
    Round.prototype.getRoundTitle = function(aRids) {
        var defer;
        defer = $q.defer();
        if (aRids.indexOf('ALL') !== -1) {
            defer.resolve('全部轮次');
        } else {
            var titles;
            http2.get(LS.j('round/get', 'site', 'app') + '&rid=' + aRids).then(function(rsp) {
                if (rsp.data.length === 1) {
                    titles = rsp.data[0].title;
                } else if (rsp.data.length === 2) {
                    titles = rsp.data[0].title + ',' + rsp.data[1].title;
                } else if (rsp.data.length > 2) {
                    titles = rsp.data[0].title + '-' + rsp.data[rsp.data.length - 1].title;
                }
                defer.resolve(titles);
            });
        }

        return defer.promise;
    };
    Round.prototype.pick = function(aCheckedRounds, oOptions) {
        var _self = this;
        return $uibModal.open({
            template: require('./pick-round.html'),
            backdrop: 'static',
            controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                var oCheckedRounds;
                $scope2.pageOfRound = _self.page;
                $scope2.checkedRounds = oCheckedRounds = {};
                $scope2.countOfChecked = 0;
                $scope2.options = {};
                if (oOptions) angular.extend($scope2.options, oOptions);
                $scope2.toggleCheckedRound = function(rid) {
                    if (rid === 'ALL') {
                        if (oCheckedRounds.ALL) {
                            $scope2.checkedRounds = oCheckedRounds = { ALL: true };
                        } else {
                            $scope2.checkedRounds = oCheckedRounds = {};
                        }
                    } else {
                        if (oCheckedRounds[rid]) {
                            delete oCheckedRounds.ALL;
                        } else {
                            delete oCheckedRounds[rid];
                        }
                    }
                    $scope2.countOfChecked = Object.keys(oCheckedRounds).length;
                };
                $scope2.clean = function() {
                    $scope2.checkedRounds = oCheckedRounds = {};
                };
                $scope2.ok = function() {
                    var checkedRoundIds = [];
                    if (Object.keys(oCheckedRounds).length) {
                        angular.forEach(oCheckedRounds, function(v, k) {
                            if (v) {
                                checkedRoundIds.push(k);
                            }
                        });
                    }
                    _self.getRoundTitle(checkedRoundIds).then(function(titles) {
                        $mi.close({ ids: checkedRoundIds, titles: titles });
                    });
                };
                $scope2.cancel = function() {
                    $mi.dismiss('cancel');
                };
                $scope2.doSearch = function() {
                    _self.list().then(function(result) {
                        $scope2.activeRound = result.active;
                        if ($scope2.activeRound) {
                            var otherRounds = [];
                            result.rounds.forEach(function(oRound) {
                                if (oRound.rid !== $scope2.activeRound.rid) {
                                    otherRounds.push(oRound);
                                }
                            });
                            $scope2.rounds = otherRounds;
                        } else {
                            $scope2.rounds = result.rounds;
                        }

                    });
                };
                if (angular.isArray(aCheckedRounds)) {
                    if (aCheckedRounds.length) {
                        aCheckedRounds.forEach(function(rid) {
                            oCheckedRounds[rid] = true;;
                        });
                    }
                }
                $scope2.countOfChecked = Object.keys(oCheckedRounds).length;
                $scope2.doSearch();
            }]
        }).result;
    };

    return Round;
}]);