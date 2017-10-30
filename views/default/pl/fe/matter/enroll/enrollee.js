define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlEnrollee', ['$scope', 'http2', 'srvEnrollRecord', '$q', '$uibModal', function($scope, http2, srvEnrollRecord, $q, $uibModal) {
        function _absent() {
            http2.get('/rest/pl/fe/matter/enroll/user/absent?site=' + $scope.app.siteid + '&app=' + $scope.app.id, function(rsp) {
                $scope.absentUsers = rsp.data.users;
            });
        }

        var _oCriteria, _oRows, rounds, page;
        $scope.category = 'enrollee';
        $scope.page = page = {
            at: 1,
            size: 20,
            j: function() {
                return '&page=' + this.at + '&size=' + this.size;
            }
        };
        $scope.criteria = _oCriteria = {
            orderby: 'enroll_num',
            rid: ''
        };
        $scope.rows = _oRows = {
            allSelected: 'N',
            selected: {},
            count: 0,
            change: function(index) {
                this.selected[index] ? this.count++ : this.count--;
            },
            reset: function() {
                this.allSelected = 'N';
                this.selected = {};
                this.count = 0;
            }
        };
        $scope.chooseOrderby = function(orderby) {
            _oCriteria.orderby = orderby;
            $scope.searchEnrollee(1);
        };
        $scope.export = function() {
            var url = '/rest/pl/fe/matter/enroll/user/export?site=' + $scope.app.siteid;
            url += '&app=' + $scope.app.id;
            url += '&rid=' + _oCriteria.rid;
            window.open(url);
        };
        $scope.notify = function(isBatch) {
            srvEnrollRecord.notify(isBatch ? $scope.criteria : undefined);
        };
        $scope.filter = function() {
            $uibModal.open({
                templateUrl: '/views/default/pl/fe/matter/enroll/component/enrolleeFilter.html?_=1',
                controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                    $scope2.app = $scope.app;
                    $scope2.criteria = _oCriteria;
                    $scope2.page = {
                        at: 1,
                        size: 5,
                        j: function() {
                            return '&page=' + this.at + '&size=' + this.size;
                        }
                    };
                    $scope2.doSearchRound = function() {
                        http2.get('/rest/pl/fe/matter/enroll/round/list?site=' + $scope.app.siteid + '&app=' + $scope.app.id + $scope2.page.j(), function(rsp) {
                            $scope2.rounds = rsp.data.rounds;
                            $scope2.page.total = rsp.data.total;
                        });
                    }
                    $scope2.ok = function() {
                        $mi.close($scope2.criteria);
                    };
                    $scope2.cancel = function() {
                        $mi.dismiss();
                    };
                    $scope2.doSearchRound();
                }],
                windowClass: 'auto-height',
                backdrop: 'static',
            }).result.then(function(_oCriteria) {
                $scope.searchEnrollee(1);
            });
        };
        $scope.searchEnrollee = function(pageAt) {
            var url;

            pageAt && (page.at = pageAt);
            url = '/rest/pl/fe/matter/enroll/user/enrollee?app=' + $scope.app.id + page.j();
            http2.post(url, _oCriteria, function(rsp) {
                srvEnrollRecord.init($scope.app, $scope.page, $scope.criteria, rsp.data.users);
                rsp.data.users.forEach(function(user) {
                    if (user.tmplmsg && user.tmplmsg.status) {
                        user._tmpStatus = user.tmplmsg.status.split(':');
                        user._tmpStatus[0] = user._tmpStatus[0] === 'success' ? '成功' : '失败';
                    }
                });
                $scope.enrollees = rsp.data.users;
                $scope.page.total = rsp.data.total;
            });
        };
        $scope.toggleAbsent = function() {
            $scope.category = $scope.category === 'absent' ? 'enrollee' : 'absent';
        };
        $scope.$watch('app.entry_rule', function(oRule) {
            if (!oRule) return;
            $scope.rule = oRule;
            $scope.searchEnrollee(1);
            _absent();
        });
        $scope.$watch('rows.allSelected', function(nv) {
            var index = 0;
            if (nv == 'Y') {
                while (index < $scope.enrollees.length) {
                    _oRows.selected[index++] = true;
                }
                _oRows.count = $scope.enrollees.length;
            } else if (nv == 'N') {
                _oRows.reset();
            }
        });
    }]);
});