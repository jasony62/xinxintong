define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlEnrollee', ['$scope', 'http2', 'srvEnrollRecord', function($scope, http2, srvEnrollRecord) {
        var mschemas, oCriteria, rounds, page;
        $scope.mschemas = mschemas = [];
        $scope.page = page = {
            at: 1,
            size: 20,
            j: function() {
                return '&page=' + this.at + '&size=' + this.size;
            }
        };
        $scope.criteria = oCriteria = {
            rid: '',
            allSelected: 'N',
            selected: {},
            reset: function() {
                this.allSelected = 'N';
                this.selected = {};
            }
        };
        $scope.export = function() {
            var url = '/rest/pl/fe/matter/enroll/user/export?site=' + $scope.app.siteid;
                url += '&app=' + $scope.app.id;
            if($scope.rule.scope !== 'member') {
                url += '&rid=' + oCriteria.rid;
            } else {
                url += '&rid=' + oCriteria.rid + '&mschema=' + oCriteria.mschema.id;
            }
            window.open(url);
        };
        $scope.notify = function(isBatch) {
            srvEnrollRecord.notify(isBatch ? $scope.criteria : undefined);
        };
        $scope.fetchRound = function() {
            http2.get('/rest/pl/fe/matter/enroll/round/list?site=' + $scope.app.siteid + '&app=' + $scope.app.id + page.j(), function(rsp) {
                $scope.rounds = rounds = rsp.data.rounds;
            });
        };
        $scope.searchEnrollee = function() {
            if($scope.rule.scope === 'member') {
                var mschemaIds = Object.keys($scope.rule.member);
                if (mschemaIds.length) {
                    http2.get('/rest/pl/fe/site/member/schema/overview?site=' + $scope.app.siteid + '&mschema=' + mschemaIds.join(','), function(rsp) {
                        var schemaId, oMschema;
                        for (schemaId in rsp.data) {
                            oMschema = rsp.data[schemaId];
                            mschemas.push(oMschema);
                        }
                        if (mschemas.length) {
                            oCriteria.mschema = mschemas[0];
                            http2.post('/rest/pl/fe/matter/enroll/user/byMschema?site=' + $scope.app.siteid + '&app=' + $scope.app.id + '&mschema=' + oCriteria.mschema.id + '&rid=' + oCriteria.rid +page.j(), {}, function(rsp) {
                                srvEnrollRecord.init($scope.app, $scope.page, $scope.criteria, rsp.data.members);
                                $scope.members = rsp.data.members;
                                $scope.page.total = rsp.data.total;
                            });
                        }
                    });
                }
            } else {
                http2.get('/rest/pl/fe/matter/enroll/user/enrollee?app=' + $scope.app.id + '&rid=' + oCriteria.rid + page.j(), function(rsp) {
                    srvEnrollRecord.init($scope.app, $scope.page, $scope.criteria, rsp.data.users);
                    $scope.members = rsp.data.users;
                    $scope.page.total = rsp.data.total;
                });
            }
        };
        $scope.$watch('app.scenarioConfig', function(oConfig) {
            if(!oConfig) return;
            $scope.config = oConfig;
            oConfig.can_rounds == 'Y' && $scope.fetchRound();
        });
        $scope.$watch('app.entry_rule', function(oRule) {
            if (!oRule) return;
            $scope.rule = oRule;
            $scope.searchEnrollee();
        });
        $scope.$watch('criteria.allSelected', function(nv) {
            var index = 0;
            if(nv == 'Y') {
                while (index < $scope.members.length) {
                    $scope.criteria.selected[index++] = true;
                }
            }else if(nv == 'N') {
                $scope.criteria.selected = {};
            }
        });
        $scope.$watch('criteria.rid', function(nv) {
            if(!$scope.rule) return;
            $scope.searchEnrollee();
        });
    }]);
});
