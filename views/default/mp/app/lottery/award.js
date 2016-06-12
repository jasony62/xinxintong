(function() {
    xxtApp.register.controller('awardCtrl', ['$scope', '$uibModal', 'http2', function($scope, $uibModal, http2) {
        $scope.$parent.subView = 'award';
        $scope.addAward = function() {
            http2.get('/rest/mp/app/lottery/award/add?lottery=' + $scope.lid + '&mpid=' + $scope.lottery.mpid, function(rsp) {
                $scope.lottery.awards.push(rsp.data);
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
                http2.post('/rest/mp/app/lottery/award/batch?lottery=' + $scope.lid + '&mpid=' + $scope.lottery.mpid, option, function(rsp) {
                    var i, l, award;
                    for (i = 0, l = rsp.data.length; i < l; i++) {
                        award = rsp.data[i];
                        award._type = $scope.awardTypes[award.type].n;
                        award._period = $scope.awardPeriods[award.period].n;
                        $scope.lottery.awards.push(award);
                    }
                });
            });
        };
        $scope.openAward = function(award) {
            $uibModal.open({
                templateUrl: 'awardEditor.html',
                windowClass: 'auto-height',
                controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                    $scope2.award = angular.copy(award);
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
                delete updatedAward.mpid;
                delete updatedAward.lid;
                delete updatedAward.aid;
                http2.post('/rest/mp/app/lottery/award/update?award=' + award.aid, updatedAward, function(rsp) {
                    angular.extend(award, updatedAward);
                    award._type = $scope.awardTypes[award.type].n;
                    award._period = $scope.awardPeriods[award.period].n;
                });
            });
        };
        $scope.removeAward = function(award) {
            if (confirm('确定删除？')) {
                http2.get('/rest/mp/app/lottery/award/remove?award=' + award.aid, function(rsp) {
                    var i = $scope.lottery.awards.indexOf(award);
                    $scope.lottery.awards.splice(i, 1);
                });
            }
        };
        $scope.setPic = function(award) {
            var options = {
                callback: function(url) {
                    award.pic = url + '?_=' + (new Date()) * 1;
                    $scope.update(award, 'pic');
                }
            };
            $scope.$broadcast('mediagallery.open', options);
        };
        $scope.removePic = function(award) {
            award.pic = '';
            $scope.update(award, 'pic');
        };
    }]);
})();