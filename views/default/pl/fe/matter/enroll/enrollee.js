define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlEnrollee', ['$scope', 'http2', 'srvEnrollRecord', '$q', '$uibModal', 'tmsSchema', 'facListFilter', 'tmsRowPicker', function($scope, http2, srvEnrollRecord, $q, $uibModal, tmsSchema, facListFilter, tmsRowPicker) {
        function _fnAbsent() {
            http2.get('/rest/pl/fe/matter/enroll/user/undone?app=' + $scope.app.id + '&rid=' + _oCriteria.rid).then(function(rsp) {
                var schemasById;
                $scope.absentUsers = rsp.data.users;
                if (rsp.data.app) {
                    $scope.absentApp = rsp.data.app;
                    if ($scope.absentApp.dataSchemas) {
                        schemasById = {};
                        $scope.absentApp.dataSchemas.forEach(function(oSchema) {
                            schemasById[oSchema.id] = oSchema;
                        });
                        $scope.absentUsers.forEach(function(oUser) {
                            tmsSchema.forTable(oUser, schemasById);
                        });
                    }
                }
            });
        }

        var _oCriteria, _oRows, _oPage;
        $scope.category = 'enrollee';
        $scope.page = _oPage = { size: 20 };
        $scope.criteria = _oCriteria = {
            orderby: 'enroll_num',
            onlyEnrolled: 'Y',
            rid: '',
            turn_title: '全部轮次',
            filter: {}
        };
        $scope.tmsTableWrapReady = 'N';
        $scope.rows = _oRows = new tmsRowPicker();
        $scope.$watch('rows.allSelected', function(nv) {
            if ($scope.enrollees) {
                _oRows.setAllSelected(nv, $scope.enrollees.length);
            }
        });
        $scope.editCause = function(user) {
            $uibModal.open({
                templateUrl: 'editCause.html',
                controller: ['$scope', '$uibModalInstance', 'http2', function($scope2, $mi, http2) {
                    $scope2.cause = '';
                    $scope2.app = $scope.app;
                    $scope2.cancel = function() {
                        $mi.dismiss();
                    };
                    $scope2.ok = function() {
                        var url, params = {};
                        params[user.userid] = {
                            rid: user.absent_cause.rid,
                            cause: $scope2.cause
                        }
                        url = '/rest/pl/fe/matter/enroll/update?site=' + $scope.app.siteid + '&app=' + $scope.app.id;
                        http2.post(url, { 'absent_cause': params }).then(function(rsp) {
                            $mi.close($scope2.cause);
                        });
                    };
                }],
                backdrop: 'static'
            }).result.then(function(result) {
                user.absent_cause.cause = result;
            });
        };
        $scope.chooseOrderby = function(orderby) {
            _oCriteria.orderby = orderby;
            $scope.searchEnrollee(1);
        };
        $scope.export = function() {
            var url = '/rest/pl/fe/matter/enroll/user/export';
            url += '?app=' + $scope.app.id;
            url += '&rid=' + _oCriteria.rid;
            window.open(url);
        };
        $scope.notify = function(isBatch) {
            srvEnrollRecord.notify(isBatch ? _oRows : null);
        };
        $scope.filter = facListFilter.init(function() {
            $scope.searchEnrollee(1);
        }, _oCriteria.filter);
        $scope.advFilter = function() {
            $uibModal.open({
                templateUrl: '/views/default/pl/fe/matter/enroll/component/enrolleeFilter.html?_=2',
                controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                    $scope2.app = $scope.app;
                    $scope2.criteria = _oCriteria;
                    $scope2.page = { size: 5 };
                    $scope2.doSearchRound = function() {
                        http2.get('/rest/pl/fe/matter/enroll/round/list?site=' + $scope.app.siteid + '&app=' + $scope.app.id, { page: $scope2.page }).then(function(rsp) {
                            $scope2.rounds = rsp.data.rounds;
                        });
                    };
                    $scope2.ok = function() {
                        $scope2.rounds.forEach(function(round) {
                            if ($scope2.criteria.rid == round.rid) {
                                $scope2.criteria.turn_title = round.title;
                            } else if ($scope2.criteria.rid == '') {
                                $scope2.criteria.turn_title = '全部轮次';
                            }
                        });
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
                _fnAbsent();
            });
        };
        $scope.searchEnrollee = function(pageAt) {
            var url;

            _oRows.reset();
            pageAt && (_oPage.at = pageAt);
            url = '/rest/pl/fe/matter/enroll/user/enrollee?app=' + $scope.app.id;
            http2.post(url, _oCriteria, { page: _oPage }).then(function(rsp) {
                srvEnrollRecord.init($scope.app, _oPage, _oCriteria, rsp.data.users);
                $scope.enrollees = rsp.data.users;
            });
        };
        $scope.repairEnrollee = function() {
            var url = '/rest/pl/fe/matter/enroll/user/repair?site=' + $scope.app.siteid;
            url += '&app=' + $scope.app.id;
            http2.get(url).then(function(rsp) {
                $scope.searchEnrollee(1);
            });
        };
        $scope.repairGroup = function() {
            var url = '/rest/pl/fe/matter/enroll/user/repairGroup?site=' + $scope.app.siteid;
            url += '&app=' + $scope.app.id;
            http2.get(url).then(function(rsp) {
                $scope.searchEnrollee(1);
            });
        };
        $scope.toggleAbsent = function() {
            $scope.category = $scope.category === 'absent' ? 'enrollee' : 'absent';
        };
        $scope.$watch('app.entryRule', function(oRule) {
            if (!oRule) return;
            $scope.rule = oRule;
            $scope.tmsTableWrapReady = 'Y';
            $scope.searchEnrollee(1);
            _fnAbsent();
        });
    }]);
});