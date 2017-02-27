define(['angular'], function(angular) {
    require(['tmsSiteuser', 'matterService'], function() {
        'use strict';
        var siteId, missionId, ngApp;
        siteId = location.search.match('site=([^&]*)')[1];
        missionId = location.search.match('mission=([^&]*)')[1];
        ngApp = angular.module('app', ['siteuser.ui.xxt', 'service.matter']);
        ngApp.controller('ctrlMain', ['$scope', '$http', 'tmsSiteUser', 'srvRecordConverter', function($scope, $http, tmsSiteUser, srvRecordConverter) {
            $scope.gotoApp = function(app) {
                if (app.entryUrl) {
                    location.href = app.entryUrl;
                }
            };
            $http.get('/rest/site/fe/matter/mission/get?site=' + siteId + '&mission=' + missionId).success(function(rsp) {
                $scope.mission = rsp.data;
                if (!document.querySelector('.tms-switch-siteuser')) {
                    tmsSiteUser.showSwitch(siteId, true);
                }
            });
            $http.get('/rest/site/fe/matter/mission/recordList?site=' + siteId + '&mission=' + missionId).success(function(rsp) {
                var recordsByApp = {},
                    schemasByApp = {};

                if (rsp.data.enroll) {
                    if (rsp.data.enroll.apps && rsp.data.enroll.apps.length) {
                        schemasByApp.enroll = {};
                        rsp.data.enroll.apps.forEach(function(app) {
                            schemasByApp.enroll[app.id] === undefined && (schemasByApp.enroll[app.id] = {});
                            app.data_schemas.forEach(function(schema) {
                                schemasByApp.enroll[app.id][schema.id] = schema;
                            });
                        });
                    }
                    if (rsp.data.enroll.records && rsp.data.enroll.records.length) {
                        recordsByApp.enroll = {};
                        rsp.data.enroll.records.forEach(function(record) {
                            srvRecordConverter.forTable(record, schemasByApp.enroll[record.aid]);
                            recordsByApp.enroll[record.aid] === undefined && (recordsByApp.enroll[record.aid] = []);
                            recordsByApp.enroll[record.aid].push(record);
                        });
                    }
                }
                if (rsp.data.signin && rsp.data.signin.records && rsp.data.signin.records.length) {
                    recordsByApp.signin = {};
                    rsp.data.signin.records.forEach(function(record) {
                        recordsByApp.signin[record.aid] === undefined && (recordsByApp.signin[record.aid] = []);
                        recordsByApp.signin[record.aid].push(record);
                    });
                }
                if (rsp.data.group && rsp.data.group.records && rsp.data.group.records.length) {
                    recordsByApp.group = {};
                    rsp.data.group.records.forEach(function(record) {
                        recordsByApp.group[record.aid] === undefined && (recordsByApp.group[record.aid] = []);
                        recordsByApp.group[record.aid].push(record);
                    });
                }

                $scope.myRecords = rsp.data;
                $scope.schemasByApp = schemasByApp;
                $scope.recordsByApp = recordsByApp;
            });
            /* end app loading */
            window.loading.finish();
        }]);
        /* bootstrap angular app */
        require(['domReady!'], function(document) {
            angular.bootstrap(document, ["app"]);
        });
    });
});
