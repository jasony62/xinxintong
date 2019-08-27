define(['frame'], function (ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlEnrollee', ['$scope', 'http2', 'noticebox', 'tkEnrollRound', 'srvEnrollRecord', '$uibModal', 'tmsSchema', 'facListFilter', 'tmsRowPicker', function ($scope, http2, noticebox, tkEnlRnd, srvEnrollRecord, $uibModal, tmsSchema, facListFilter, tmsRowPicker) {
        function _fnAbsent() {
            http2.post('/rest/pl/fe/matter/enroll/user/undone?app=' + $scope.app.id, {
                rids: _oCriteria.rids
            }).then(function (rsp) {
                var schemasById;
                $scope.absentUsers = rsp.data.users;
                $scope.absentRounds = rsp.data.rounds;
                if (rsp.data.app) {
                    $scope.absentApp = rsp.data.app;
                    if ($scope.absentApp.dataSchemas) {
                        schemasById = {};
                        $scope.absentApp.dataSchemas.forEach(function (oSchema) {
                            schemasById[oSchema.id] = oSchema;
                        });
                        $scope.absentUsers.forEach(function (oUser) {
                            tmsSchema.forTable(oUser, schemasById);
                        });
                    }
                }
            });
        }

        var _oCriteria, _oRows, _oPage;
        $scope.category = 'enrollee';
        $scope.page = _oPage = {
            size: 20
        };
        $scope.criteria = _oCriteria = {
            orderby: 'enroll_num',
            //onlyEnrolled: 'Y',
            filter: {}
        };
        $scope.tmsTableWrapReady = 'N';
        $scope.rows = _oRows = new tmsRowPicker();
        $scope.$watch('rows.allSelected', function (nv) {
            if ($scope.enrollees) {
                _oRows.setAllSelected(nv, $scope.enrollees.length);
            }
        });
        $scope.chooseOrderby = function (orderby) {
            _oCriteria.orderby = orderby;
            $scope.searchEnrollee(1);
        };
        $scope.export = function () {
            var url = '/rest/pl/fe/matter/enroll/export/user';
            url += '?app=' + $scope.app.id;
            if (_oCriteria.rids) url += '&rids=' + _oCriteria.rids;
            window.open(url);
        };
        $scope.notify = function (isBatch) {
            srvEnrollRecord.notify(isBatch ? _oRows : null);
        };
        $scope.filter = facListFilter.init(function () {
            $scope.searchEnrollee(1);
        }, _oCriteria.filter);
        $scope.advFilter = function () {
            http2.post('/rest/script/time', {
                html: {
                    'enrollee': '/views/default/pl/fe/matter/enroll/component/enrolleeFilter'
                }
            }).then(function (rsp) {
                $uibModal.open({
                    templateUrl: '/views/default/pl/fe/matter/enroll/component/enrolleeFilter.html?_=' + rsp.data.html.enrollee.time,
                    controller: ['$scope', '$uibModalInstance', function ($scope2, $mi) {
                        $scope2.app = $scope.app;
                        $scope2.criteria = _oCriteria;
                        $scope2.page = {
                            size: 7
                        };
                        $scope2.doSearchRound = function () {
                            http2.get('/rest/pl/fe/matter/enroll/round/list?site=' + $scope.app.siteid + '&app=' + $scope.app.id, {
                                page: $scope2.page
                            }).then(function (rsp) {
                                $scope2.rounds = rsp.data.rounds;
                            });
                        };
                        $scope2.ok = function () {
                            $mi.close($scope2.criteria);
                        };
                        $scope2.cancel = function () {
                            $mi.dismiss();
                        };
                        $scope2.doSearchRound();
                    }],
                    windowClass: 'auto-height',
                    backdrop: 'static',
                }).result.then(function (_oCriteria) {
                    $scope.searchEnrollee(1);
                    _fnAbsent();
                });
            });
        };
        $scope.searchEnrollee = function (pageAt) {
            var url;

            _oRows.reset();
            pageAt && (_oPage.at = pageAt);
            url = '/rest/pl/fe/matter/enroll/user/enrollee?app=' + $scope.app.id;
            http2.post(url, _oCriteria, {
                page: _oPage
            }).then(function (rsp) {
                srvEnrollRecord.init($scope.app, _oPage, _oCriteria, rsp.data.users);
                $scope.enrollees = rsp.data.users;
            });
        };
        $scope.repairEnrollee = function () {
            var url = '/rest/pl/fe/matter/enroll/repair/user';
            url += '?app=' + $scope.app.id;
            http2.get(url).then(function (rsp) {
                $scope.searchEnrollee(1);
            });
        };
        $scope.repairCoin = function () {
            tkEnlRnd.pick($scope.app, {
                single: false
            }).then(function (oResult) {
                function resetCoinByRound(i) {
                    if (i < rids.length) {
                        var url = '/rest/pl/fe/matter/enroll/repair/userCoin?site=' + $scope.app.siteid;
                        url += '&app=' + $scope.app.id;
                        url += '&rid=' + rids[i];
                        http2.get(url).then(function (rsp) {
                            resetCoinByRound(++i);
                        });
                    } else {
                        noticebox.success('完成【' + i + '】个轮次数据的更新');
                        $scope.searchEnrollee(1);
                    }
                }
                var rids = oResult.rid;
                rids.length && resetCoinByRound(0);
            });
        };
        $scope.repairGroup = function () {
            var url = '/rest/pl/fe/matter/enroll/repair/userGroup';
            url += '?app=' + $scope.app.id;
            http2.get(url).then(function (rsp) {
                $scope.searchEnrollee(1);
            });
        };
        $scope.toggleAbsent = function () {
            $scope.category = $scope.category === 'absent' ? 'enrollee' : 'absent';
        };
        $scope.$watch('app.entryRule', function (oRule) {
            if (!oRule) return;
            $scope.rule = oRule;
            $scope.tmsTableWrapReady = 'Y';
            $scope.searchEnrollee(1);
            _fnAbsent();
        });
    }]);
});