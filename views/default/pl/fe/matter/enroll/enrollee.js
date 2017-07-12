define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlEnrollee', ['$scope', 'http2', 'srvEnrollRecord', function($scope, http2, srvEnrollRecord) {
        var mschemas, oCriteria, page;
        $scope.mschemas = mschemas = [];
        $scope.page = page = {
            at: 1,
            size: 20,
            j: function() {
                return '&page=' + this.at + '&size=' + this.size;
            }
        };
        $scope.criteria = oCriteria = {
            allSelected: 'N',
            selected: {},
            reset: function() {
                this.allSelected = 'N';
                this.selected = {};
            }
        };
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
        $scope.export = function() {

        };
        $scope.notify = function(isBatch) {
            srvEnrollRecord.notify(isBatch ? $scope.criteria : undefined);
        };
        $scope.seachEnrollee = function() {
            http2.post('/rest/pl/fe/matter/enroll/user/byMschema?site=' + $scope.app.siteid + '&app=' + $scope.app.id + '&mschema=' + oCriteria.mschema.id + page.j(), {}, function(rsp) {
                srvEnrollRecord.init($scope.app, $scope.page, $scope.criteria, rsp.data.members);
                $scope.members = rsp.data.members;
                $scope.page.total = rsp.data.total;
            });
        };
        $scope.$watch('app.entry_rule', function(oRule) {
            if (!oRule) return;
            if (oRule.scope !== 'member') {
                http2.get('/rest/pl/fe/matter/enroll/user/enrollee?app=' + $scope.app.id, function(rsp) {
                    $scope.members = rsp.data.users;
                    $scope.page.total = rsp.data.total;
                });
                return;
            }
            var mschemaIds = Object.keys(oRule.member);
            if (mschemaIds.length) {
                http2.get('/rest/pl/fe/site/member/schema/overview?site=' + $scope.app.siteid + '&mschema=' + mschemaIds.join(','), function(rsp) {
                    var schemaId, oMschema;
                    for (schemaId in rsp.data) {
                        oMschema = rsp.data[schemaId];
                        if(oMschema.is_qy_fan=='Y'||oMschema.is_yx_fan=='Y'||oMschema.is_wx_fan=='Y') {
                            oMschema.sns = 'Y';
                        }
                        mschemas.push(oMschema);
                    }
                    if (mschemas.length) {
                        oCriteria.mschema = mschemas[0];
                        $scope.seachEnrollee();
                    }
                });
            }
        });
    }]);
});
