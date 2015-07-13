(function () {
    xxtApp.register.controller('settingCtrl', ['$scope', 'http2', 'matterTypes', '$modal', function ($scope, http2, matterTypes, $modal) {
        $scope.$parent.subView = 'setting';
        $scope.pages4OutAcl = [];
        $scope.pages4Unauth = [];
        $scope.pages4Nonfan = [];
        $scope.$watch('editing.pages', function (nv) {
            var newPage;
            if (!nv) return;
            $scope.pages4OutAcl = $scope.editing.access_control === 'Y' ? [{ name: '$authapi_outacl', title: '提示白名单' }] : [];
            $scope.pages4Unauth = $scope.editing.access_control === 'Y' ? [{ name: '$authapi_auth', title: '提示认证' }] : [];
            $scope.pages4Nonfan = [{ name: '$mp_follow', title: '提示关注' }];
            for (var p in nv) {
                newPage = {
                    name: p,
                    title: nv[p].title
                };
                $scope.pages4OutAcl.push(newPage);
                $scope.pages4Unauth.push(newPage);
                $scope.pages4Nonfan.push(newPage);
            }
        }, true);
        $scope.matterTypes = matterTypes;
        $scope.updateEntryRule = function () {
            var p = { entry_rule: encodeURIComponent(JSON.stringify($scope.editing.entry_rule)) };
            http2.post('/rest/mp/app/enroll/update?aid=' + $scope.aid, p, function (rsp) {
                $scope.persisted = angular.copy($scope.editing);
            });
        };
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
        $scope.$on('xxt.tms-datepicker.change', function (event, data) {
            $scope.editing[data.state] = data.value;
            $scope.update(data.state);
        });
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
                var url;
                if (rst.action === 'update') {
                    url = '/rest/mp/app/enroll/updateRound';
                    url += '?aid=' + $scope.aid;
                    url += '&rid=' + round.rid;
                    http2.post(url, rst.data, function (rsp) {
                        if ($scope.editing.rounds.length > 1 && rst.data.state == 1)
                            $scope.editing.rounds[1].state = 2;
                        angular.extend(round, rst.data);
                    });
                } else if (rst.action === 'remove') {
                    url = '/rest/mp/app/enroll/removeRound';
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
