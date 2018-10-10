define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlOverview', ['$scope', '$uibModal', 'http2', 'srvSite', function($scope, $uibModal, http2, srvSite) {
        var status;
        $scope.status = status = {
            user: { member: [] }
        };
        $scope.$watch('mission.entry_rule', function(oRule) {
            if (!oRule) return;
            if (oRule.scope === 'member') {
                var mschemaIds = Object.keys(oRule.member);
                if (mschemaIds.length) {
                    http2.get('/rest/pl/fe/site/member/schema/overview?site=' + $scope.mission.siteid + '&mschema=' + mschemaIds.join(',')).then(function(rsp) {
                        var oMschema;
                        for (var schemaId in rsp.data) {
                            oMschema = rsp.data[schemaId];
                            status.user.member.push(oMschema);
                        }
                    });
                }
            }
        });
        $scope.$watch('mission', function(oMission) {
            var url;
            url = '/rest/pl/fe/matter/mission/matter/list?id=' + oMission.id + '&verbose=N';
            http2.post(url, {}).then(function(rsp) {
                $scope.matters = rsp.data;
            });
        });
    }]);
});