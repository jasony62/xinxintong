define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlEnrollee', ['$scope', 'http2', 'srvEnrollRecord', '$q', '$uibModal', function($scope, http2, srvEnrollRecord, $q, $uibModal) {
        function _searchByMschema(mschema) {
            if (mschema) {
                http2.post('/rest/pl/fe/matter/enroll/user/byMschema?site=' + $scope.app.siteid + '&app=' + $scope.app.id + '&mschema=' + mschema.id + page.j(), oCriteria, function(rsp) {
                    srvEnrollRecord.init($scope.app, $scope.page, $scope.criteria, rsp.data.members);
                    $scope.enrollees = rsp.data.members;
                    rsp.data.members.forEach(function(member) {
                        if (member.tmplmsg && member.tmplmsg.status) {
                            member._tmpStatus = member.tmplmsg.status.split(':');
                            member._tmpStatus[0] = member._tmpStatus[0] === 'success' ? '成功' : '失败';
                        }
                    });
                    $scope.enrollees = rsp.data.members;
                    $scope.page.total = rsp.data.total;
                });
            } else {
                $scope.enrollees = [];
                $scope.page.total = 0;
            }
        }

        function _absent() {
            http2.get('/rest/pl/fe/matter/enroll/user/absent?site=' + $scope.app.siteid + '&app=' + $scope.app.id, function(rsp) {
                $scope.absentUsers = rsp.data.users;
            });
        }
        var mschemas, _oCriteria, _oRows, rounds, page;
        $scope.category = 'enrollee';
        $scope.mschemas = mschemas = [];
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
            if ($scope.rule.scope !== 'member') {
                url += '&rid=' + _oCriteria.rid;
            } else {
                url += '&rid=' + _oCriteria.rid + '&mschema=' + _oCriteria.mschema.id;
            }
            window.open(url);
        };
        $scope.notify = function(isBatch) {
            srvEnrollRecord.notify(isBatch ? $scope.criteria : undefined);
        };
        $scope.gotoMschema = function(oMschema) {
            if (oMschema.matter_id) {
                if (oMschema.matter_type === 'mission') {
                    location.href = '/rest/pl/fe/matter/mission/mschema?id=' + oMschema.matter_id + '&site=' + $scope.app.siteid + '#' + oMschema.id;
                } else {
                    location.href = '/rest/pl/fe/site/mschema?site=' + $scope.app.siteid + '#' + oMschema.id;
                }
            } else {
                location.href = '/rest/pl/fe?view=main&scope=user&sid=' + $scope.app.siteid + '&mschema=' + oMschema.id;
            }
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
            if (pageAt) {
                page.at = pageAt;
            }

            if ($scope.rule.scope === 'member') {
                _searchByMschema(_oCriteria.mschema);
            } else {
                http2.post('/rest/pl/fe/matter/enroll/user/enrollee?app=' + $scope.app.id + page.j(), _oCriteria, function(rsp) {
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
            }
        };
        $scope.toggleAbsent = function() {
            $scope.category = $scope.category === 'absent' ? 'enrollee' : 'absent';
        };
        $scope.$watch('app.entry_rule', function(oRule) {
            if (!oRule) return;
            $scope.rule = oRule;
            if (oRule.scope === 'member') {
                var mschemaIds = Object.keys(oRule.member);
                if (mschemaIds.length) {
                    http2.get('/rest/pl/fe/site/member/schema/overview?site=' + $scope.app.siteid + '&mschema=' + mschemaIds.join(','), function(rsp) {
                        Object.keys(rsp.data).forEach(function(schemaId) {
                            mschemas.push(rsp.data[schemaId]);
                        });
                        if (mschemas.length) {
                            _oCriteria.mschema = mschemas[0];
                        }
                    });
                }
            }
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