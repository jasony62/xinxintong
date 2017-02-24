define(["angular"], function(angular) {
    require(['tms-siteuser'], function() {
        'use strict';
        var siteId, missionId, ngApp;
        siteId = location.search.match('site=([^&]*)')[1];
        missionId = location.search.match('mission=([^&]*)')[1];
        ngApp = angular.module('app', ['siteuser.ui.xxt']);
        ngApp.controller('ctrlMain', ['$scope', '$http', 'tmsSiteUser', function($scope, $http, tmsSiteUser) {
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
                var recordsByApp = {};
                $scope.myRecords = rsp.data;
                if (rsp.data.enroll && rsp.data.enroll.records && rsp.data.enroll.records.length) {
                    recordsByApp.enroll = {};
                    rsp.data.enroll.records.forEach(function(record) {
                        //srvRecordConverter.forTable(record, $scope.enrollAppSchemas[record.aid]);
                        recordsByApp.enroll[record.aid] === undefined && (recordsByApp.enroll[record.aid] = []);
                        recordsByApp.enroll[record.aid].push(record);
                    });
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
