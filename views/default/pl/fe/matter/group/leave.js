define(['frame'], function (ngApp) {
    ngApp.provider.controller('ctrlLeave', ['$scope', 'http2', 'noticebox', '$uibModal', '$q', function ($scope, http2, noticebox, $uibModal, $q) {
        function _fnListLeaves() {
            http2.get('/rest/pl/fe/matter/group/leave/list?app=' + $scope.app.id).then(function (rsp) {
                $scope.leaves = _leaves = rsp.data;
            });
        }

        function _fnPickRecord() {
            var defer = $q.defer();
            $uibModal.open({
                templateUrl: '/views/default/pl/fe/matter/group/component/recordPicker.html?_=' + $scope.frameTemplates.compRecordPicker.time,
                controller: ['$uibModalInstance', '$scope', 'srvGroupRec', 'tmsRowPicker', function ($mi, $scope2, srvGrpRec, tmsRowPicker) {
                    var records = [];
                    $scope2.rows = _oRows = new tmsRowPicker();
                    srvGrpRec.init(records).then(function () {
                        srvGrpRec.all({}).then(function () {
                            $scope2.users = records;
                        });
                    });
                    $scope2.cancel = function () {
                        $mi.dismiss();
                    };
                    $scope2.execute = function () {
                        var picked;
                        if (_oRows.count) {
                            picked = [];
                            for (var i in _oRows.selected) {
                                picked.push($scope2.users[i]);
                            }
                            $mi.close(picked);
                        }
                    };
                }],
                backdrop: 'static',
            }).result.then(function (aResult) {
                defer.resolve(aResult);
            });
            return defer.promise;
        }
        var _leaves;
        $scope.add = function () {
            _fnPickRecord().then(function (aPickedUsers) {
                if (aPickedUsers.length) {
                    $uibModal.open({
                        templateUrl: '/views/default/pl/fe/matter/group/component/leaveEditor.html?_=' + $scope.frameTemplates.compLeaveEditor.time,
                        controller: ['$uibModalInstance', '$scope', function ($mi, $scope2) {
                            $scope2.data = {};
                            $scope2.cancel = function () {
                                $mi.dismiss();
                            };
                            $scope2.ok = function () {
                                $mi.close($scope2.data);
                            };
                        }],
                        backdrop: 'static',
                    }).result.then(function (oProto) {
                        var url = '/rest/pl/fe/matter/group/leave/create?app=' + $scope.app.id;
                        var eks = [];
                        aPickedUsers.forEach(function (oUser) {
                            eks.push(oUser.enroll_key)
                        });
                        url += '&ek=' + eks.join(',');
                        http2.post(url, oProto).then(function (rsp) {
                            _leaves.push(rsp.data);
                        });
                    });
                }
            });
        };
        $scope.edit = function (oLeave) {
            $uibModal.open({
                templateUrl: '/views/default/pl/fe/matter/group/component/leaveEditor.html?_=' + $scope.frameTemplates.compLeaveEditor.time,
                controller: ['$uibModalInstance', '$scope', function ($mi, $scope2) {
                    var data = {};
                    data.begin_at = oLeave.begin_at;
                    data.end_at = oLeave.end_at;
                    $scope2.data = data;
                    $scope2.cancel = function () {
                        $mi.dismiss();
                    };
                    $scope2.ok = function () {
                        $mi.close($scope2.data);
                    };
                }],
                backdrop: 'static',
            }).result.then(function (oProto) {
                var url = '/rest/pl/fe/matter/group/leave/update?app=' + $scope.app.id;
                url += '&id=' + oLeave.id;
                http2.post(url, oProto).then(function (rsp) {
                    http2.merge(oLeave, rsp.data, ['id']);
                });
            });
        };
        $scope.close = function (oLeave) {
            noticebox.confirm('删除选中的请假记录，确定？').then(function () {
                http2.get('/rest/pl/fe/matter/group/leave/close?app=' + $scope.app.id + '&id=' + oLeave.id).then(function (rsp) {
                    _leaves.splice(_leaves.indexOf(oLeave), 1);
                });
            });
        };
        $scope.$watch('app', function (oApp) {
            if (oApp) {
                _fnListLeaves();
            }
        });
    }]);
});