require(['matterService'], function() {
    'use strict';
    var siteId, missionId, ngApp;
    siteId = location.search.match('site=([^&]*)')[1];
    missionId = location.search.match('mission=([^&]*)')[1];
    ngApp = angular.module('app', ['service.matter']);
    ngApp.controller('ctrlMain', ['$scope', '$http', 'srvRecordConverter', function($scope, $http, srvRecordConverter) {
        $scope.gotoMatter = function(matter) {
            if (matter.entryUrl) {
                location.href = matter.entryUrl;
            }
        };
        $http.get('/rest/site/fe/matter/mission/recordList?site=' + siteId + '&mission=' + missionId).success(function(rsp) {
            rsp.data.forEach(function(matter) {
                if (matter.type === 'enroll') {
                    matter.dataSchemas = {};
                    JSON.parse(matter.data_schemas).forEach(function(schema) {
                        if (schema.type !== 'html') {
                            matter.dataSchemas[schema.id] = schema;
                        }
                    });
                    if (matter.records && matter.records.length) {
                        matter.records.forEach(function(record) {
                            srvRecordConverter.forTable(record, matter.dataSchemas);
                        });
                    }
                } else if (matter.type === 'signin') {
                    if (matter.record.signin_log) {
                        matter.rounds.forEach(function(round) {
                            var record = matter.record,
                                signinLog = record.signin_log;
                            record._signinLate = {};
                            if (signinLog && signinLog[round.rid]) {
                                record._signinLate[round.rid] = round.late_at && round.late_at < signinLog[round.rid] - 60;
                            }
                        });
                    }
                }
            });
            $scope.matters = rsp.data;
        });
        /* end app loading */
        window.loading.finish();
    }]);
    /* bootstrap angular app */
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
});