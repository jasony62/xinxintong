define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlEnrollee', ['$scope', 'http2', function($scope, http2) {
        var mschemas, oCriteria;
        $scope.mschemas = mschemas = [];
        $scope.criteria = oCriteria = {};
        $scope.seachEnrollee = function() {
            http2.post('/rest/pl/fe/matter/enroll/user/byMschema?site=' + $scope.app.siteid + '&app=' + $scope.app.id + '&mschema=' + oCriteria.mschema.id, {}, function(rsp) {
                $scope.members = rsp.data.members;
            });
        };
        $scope.$watch('app.entry_rule', function(oRule) {
            if (!oRule) return;
            if (oRule.scope !== 'member') {
                return;
            }
            var mschemaIds = Object.keys(oRule.member);
            if (mschemaIds.length) {
                http2.get('/rest/pl/fe/site/member/schema/overview?site=' + $scope.app.siteid + '&mschema=' + mschemaIds.join(','), function(rsp) {
                    var schemaId, oMschema;
                    for (schemaId in rsp.data) {
                        oMschema = rsp.data[schemaId];
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
