(function () {
    xxtApp.register.controller('settingCtrl', ['$scope', 'http2', 'matterTypes', '$modal', function ($scope, http2, matterTypes, $modal) {
        $scope.$parent.subView = 'setting';
        $scope.matterTypes = matterTypes;
        $scope.setPic = function () {
            $scope.$broadcast('picgallery.open', function (url) {
                var t = (new Date()).getTime(), url = url + '?_=' + t, nv = { pic: url };
                http2.post('/rest/mp/app/enroll/update?aid=' + $scope.aid, nv, function () {
                    $scope.editing.pic = url;
                });
            }, false);
        };
        $scope.removePic = function () {
            var nv = { pic: '' };
            http2.post('/rest/mp/app/enroll/update?aid=' + $scope.aid, nv, function () {
                $scope.editing.pic = '';
            });
        };
        $scope.setSuccessReply = function () {
            $scope.$broadcast('mattersgallery.open', function (aSelected, matterType) {
                if (aSelected.length === 1) {
                    var p = { mt: matterType, mid: aSelected[0].id };
                    http2.post('/rest/mp/app/enroll/setSuccessReply?aid=' + $scope.aid, p, function (rsp) {
                        $scope.editing.successMatter = aSelected[0];
                    });
                }
            });
        };
        $scope.setFailureReply = function () {
            $scope.$broadcast('mattersgallery.open', function (aSelected, matterType) {
                if (aSelected.length === 1) {
                    var p = { mt: matterType, mid: aSelected[0].id };
                    http2.post('/rest/mp/app/enroll/setFailureReply?aid=' + $scope.aid, p, function (rsp) {
                        $scope.editing.failureMatter = aSelected[0];
                    });
                }
            });
        };
        $scope.removeSuccessReply = function () {
            var p = { mt: '', mid: '' };
            http2.post('/rest/mp/app/enroll/setSuccessReply?aid=' + $scope.aid, p, function (rsp) {
                $scope.editing.successMatter = null;
            });
        };
        $scope.removeFailureReply = function () {
            var p = { mt: '', mid: '' };
            http2.post('/rest/mp/app/enroll/setFailureReply?aid=' + $scope.aid, p, function (rsp) {
                $scope.editing.failureMatter = null;
            });
        };
        $scope.addRound = function () {
            $modal.open({
                templateUrl: 'roundEditor.html',
                backdrop: 'static',
                resolve: {
                    roundState: function () { return $scope.roundState; }
                },
                controller: ['$scope', '$modalInstance', 'roundState', function ($scope, $modalInstance, roundState) {
                    $scope.round = { state: 0 };
                    $scope.roundState = roundState;
                    $scope.close = function () { $modalInstance.dismiss(); };
                    $scope.ok = function () { $modalInstance.close($scope.round); };
                    $scope.start = function () {
                        $scope.round.state = 1;
                        $modalInstance.close($scope.round);
                    };
                }]
            }).result.then(function (newRound) {
                http2.post('/rest/mp/app/enroll/addRound?aid=' + $scope.aid, newRound, function (rsp) {
                    if ($scope.editing.rounds.length > 0 && rsp.data.state == 1)
                        $scope.editing.rounds[1].state = 2;
                    $scope.editing.rounds.splice(0, 0, rsp.data);
                });
            });
        };
        $scope.openRound = function (round) {
            $modal.open({
                templateUrl: 'roundEditor.html',
                backdrop: 'static',
                resolve: {
                    roundState: function () { return $scope.roundState; }
                },
                controller: ['$scope', '$modalInstance', 'roundState', function ($scope, $modalInstance, roundState) {
                    $scope.round = angular.copy(round);
                    $scope.roundState = roundState;
                    $scope.close = function () { $modalInstance.dismiss(); };
                    $scope.ok = function () { $modalInstance.close({ action: 'update', data: $scope.round }); };
                    $scope.remove = function () { $modalInstance.close({ action: 'remove' }); };
                    $scope.start = function () {
                        $scope.round.state = 1;
                        $modalInstance.close({ action: 'update', data: $scope.round });
                    };
                }]
            }).result.then(function (rst) {
                if (rst.action === 'update') {
                    var url = '/rest/mp/app/enroll/updateRound';
                    url += '?aid=' + $scope.aid;
                    url += '&rid=' + round.rid;
                    http2.post(url, rst.data, function (rsp) {
                        if ($scope.editing.rounds.length > 1 && rst.data.state == 1)
                            $scope.editing.rounds[1].state = 2;
                        angular.extend(round, rst.data);
                    });
                } else if (rst.action === 'remove') {
                    var url = '/rest/mp/app/enroll/removeRound';
                    url += '?aid=' + $scope.aid;
                    url += '&rid=' + round.rid;
                    http2.get(url, function (rsp) {
                        var i = $scope.editing.rounds.indexOf(round);
                        $scope.editing.rounds.splice(i, 1);
                    });
                }
            });
        };
    }]);
})();
