(function() {
    ngApp.provider.controller('ctrlAward', ['$scope', '$uibModal', 'http2', function($scope, $uibModal, http2) {
        $scope.awardTypes = {
            '0': {
                n: '未中奖',
                v: '0'
            },
            '1': {
                n: '应用积分',
                v: '1'
            },
            '2': {
                n: '奖励重玩',
                v: '2'
            },
            '3': {
                n: '完成任务',
                v: '3'
            },
            '99': {
                n: '实体奖品',
                v: '99'
            }
        };
        $scope.awardPeriods = {
            'A': {
                n: '总计',
                v: 'A'
            },
            'D': {
                n: '每天',
                v: 'D'
            },
        };
        $scope.addAward = function() {
            http2.get('/rest/pl/fe/matter/lottery/award/add?site=' + $scope.siteId + '&app=' + $scope.id, function(rsp) {
                $scope.app.awards.push(rsp.data);
                $scope.openAward(rsp.data);
            });
        };
        $scope.batchAward = function() {
            $uibModal.open({
                templateUrl: 'batchAward.html',

                controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                    $scope2.option = {
                        quantity: 1,
                        award: {
                            title: '奖项',
                            type: '99',
                            period: 'A',
                            quantity: 1,
                            prob: 1,
                            greeting: '恭喜中奖！'
                        }
                    };
                    $scope2.close = function() {
                        $mi.dismiss()
                    };
                    $scope2.ok = function() {
                        $mi.close($scope2.option);
                    };
                }]
            }).result.then(function(option) {
                http2.post('/rest/pl/fe/matter/lottery/award/batch?site=' + $scope.siteId + '&app=' + $scope.id, option, function(rsp) {
                    var i, l, award;
                    for (i = 0, l = rsp.data.length; i < l; i++) {
                        award = rsp.data[i];
                        award._type = $scope.awardTypes[award.type].n;
                        award._period = $scope.awardPeriods[award.period].n;
                        $scope.app.awards.push(award);
                    }
                });
            });
        };
        $scope.openAward = function(award) {
            $uibModal.open({
                templateUrl: 'awardEditor.html',
                resolve: {
                    siteId: function() {
                        return $scope.siteId;
                    }
                },
                windowClass: 'auto-height',
                controller: ['$scope', '$uibModalInstance', 'siteId', 'mediagallery', function($scope2, $mi, siteId, mediagallery) {
                    $scope2.award = angular.copy(award);
                    $scope2.setPic = function(award) {
                        var options = {
                            callback: function(url) {
                                award.pic = url + '?_=' + (new Date()) * 1;
                            }
                        };
                        mediagallery.open(siteId, options);
                    };
                    $scope2.removePic = function(award) {
                        award.pic = '';
                    };
                    $scope2.close = function() {
                        $mi.dismiss();
                    }
                    $scope2.ok = function() {
                        $mi.close($scope2.award);
                    };
                }],
            }).result.then(function(updatedAward) {
                delete updatedAward._type;
                delete updatedAward._period;
                delete updatedAward.siteid;
                delete updatedAward.lid;
                delete updatedAward.aid;
                http2.post('/rest/pl/fe/matter/lottery/award/update?site=' + $scope.siteId + '&app=' + $scope.id + '&award=' + award.aid, updatedAward, function(rsp) {
                    angular.extend(award, updatedAward);
                    award._type = $scope.awardTypes[award.type].n;
                    award._period = $scope.awardPeriods[award.period].n;
                });
            });
        };
        $scope.removeAward = function(award) {
            if (confirm('确定删除？')) {
                http2.get('/rest/pl/fe/matter/lottery/award/remove?site=' + $scope.siteId + '&app=' + $scope.id + '&award=' + award.aid, function(rsp) {
                    var i = $scope.app.awards.indexOf(award);
                    $scope.app.awards.splice(i, 1);
                });
            }
        };
    }]);
})();