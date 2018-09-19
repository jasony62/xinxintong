require(['matterService'], function() {
    'use strict';
    var siteId, missionId, ngApp;
    siteId = location.search.match('site=([^&]*)')[1];
    missionId = location.search.match('mission=([^&]*)')[1];
    ngApp = angular.module('app', ['ui.tms', 'service.matter']);
    ngApp.controller('ctrlMain', ['$scope', 'http2', function($scope, http2) {
        var _oMission;
        $scope.siteid = siteId;
        /* end app loading */
        http2.get('/rest/site/fe/matter/mission/get?site=' + siteId + '&mission=' + missionId, function(rsp) {
            var groupUsers;
            $scope.mission = _oMission = rsp.data;
        });
        window.loading.finish();
    }]);
    ngApp.controller('ctrlDoc', ['$scope', 'http2', function($scope, http2) {
        http2.get('/rest/site/fe/matter/mission/matter/docList?site=' + siteId + '&mission=' + missionId, function(rsp) {
            $scope.docs = rsp.data;
        });
    }]);
    ngApp.controller('ctrlRecommend', ['$scope', '$sce', 'http2', function($scope, $sce, http2) {
        $scope.value2Label = function(oSchema, value) {
            var val, aVal, aLab = [];

            if (val = value) {
                if (oSchema.ops && oSchema.ops.length) {
                    aVal = val.split(',');
                    oSchema.ops.forEach(function(op) {
                        aVal.indexOf(op.v) !== -1 && aLab.push(op.l);
                    });
                    val = aLab.join(',');
                }
            } else {
                val = '';
            }
            return $sce.trustAsHtml(val);
        };
        $scope.likeRecommend = function(oRecommend) {
            var oRecord, oRecData;
            if (oRecommend.obj_unit === 'R') {
                oRecord = oRecommend.obj;
                http2.get('/rest/site/fe/matter/enroll/record/like?site=' + siteId + '&ek=' + oRecommend.obj_key, function(rsp) {
                    oRecord.like_log = rsp.data.like_log;
                    oRecord.like_num = rsp.data.like_num;
                });
            } else {
                oRecData = oRecommend.obj;
                http2.get('/rest/site/fe/matter/enroll/data/like?site=' + siteId + '&ek=' + oRecommend.obj_key + '&schema=' + oRecommend.obj.schema_id, function(rsp) {
                    oRecData.like_log = rsp.data.like_log;
                    oRecData.like_num = rsp.data.like_num;
                });
            }
        };
        $scope.remarkRecommend = function(oRecommend) {
            var url;
            url = '/rest/site/fe/matter/enroll?site=' + siteId;
            url += '&app=' + oRecommend.matter.id;
            url += '&page=remark';
            url += '&ek=' + oRecommend.obj_key;
            if (oRecommend.obj_unit === 'D') {
                url += '&schema=' + oRecommend.obj.schema_id;
            }
            location.href = url;
        };
        http2.get('/rest/site/fe/matter/mission/matter/agreedList?site=' + siteId + '&mission=' + missionId, function(rsp) {
            $scope.recommends = rsp.data.agreed;
        });
    }]);
    ngApp.controller('ctrlRank', ['$scope', 'http2', function($scope, http2) {
        http2.get('/rest/site/fe/matter/mission/user/rank?site=' + siteId + '&mission=' + missionId, function(rsp) {
            $scope.users = rsp.data.users;
        });
    }]);
    /* bootstrap angular app */
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
});