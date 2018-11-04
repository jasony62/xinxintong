'use strict';
var ngApp;
ngApp = angular.module('app', ['ui.bootstrap', 'http.ui.xxt', 'trace.ui.xxt', 'nav.ui.xxt']);
ngApp.config(['$locationProvider', '$uibTooltipProvider', function($locationProvider, $uibTooltipProvider) {
    $uibTooltipProvider.setTriggers({
        'show': 'hide'
    });
    $locationProvider.html5Mode(true);
}]);
ngApp.controller('ctrlMain', ['$scope', '$parse', 'tmsLocation', 'http2', function($scope, $parse, LS, http2) {
    var _oMission;
    $scope.siteid = LS.s().site;
    /* end app loading */
    http2.get(LS.j('get', 'site', 'mission')).then(function(rsp) {
        var groupUsers;
        $scope.mission = _oMission = rsp.data;
        http2.get(LS.j('user/get', 'site', 'mission')).then(function(rsp) {
            var oMisUser, oCustom;
            oMisUser = rsp.data;
            if (oMisUser) {
                oCustom = $parse('board.nav')(oMisUser.custom);
            }
            if (!oCustom) {
                oCustom = { stopTip: false };
            }
            /* 设置页面导航 */
            $scope.popNav = {
                navs: [{ name: 'main', title: '项目活动', url: LS.j('', 'site', 'mission') + '&page=main' }],
                custom: oCustom
            };
            $scope.$watch('popNav.custom', function(nv, ov) {
                if (nv !== ov) {
                    http2.post(LS.j('user/updateCustom', 'site', 'mission'), { board: { nav: $scope.popNav.custom } }).then(function(rsp) {});
                }
            }, true);
        });
    });
    var eleLoading, eleStyle;
    eleLoading = document.querySelector('.loading');
    eleLoading.parentNode.removeChild(eleLoading);
}]);
ngApp.controller('ctrlDoc', ['$scope', 'tmsLocation', 'http2', function($scope, LS, http2) {
    http2.get(LS.j('matter/docList', 'site', 'mission')).then(function(rsp) {
        $scope.docs = rsp.data;
    });
}]);
ngApp.controller('ctrlRecommend', ['$scope', '$sce', 'tmsLocation', 'http2', function($scope, $sce, LS, http2) {
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
            http2.get('/rest/site/fe/matter/enroll/record/like?site=' + LS.s().site + '&ek=' + oRecommend.obj_key).then(function(rsp) {
                oRecord.like_log = rsp.data.like_log;
                oRecord.like_num = rsp.data.like_num;
            });
        } else {
            oRecData = oRecommend.obj;
            http2.get('/rest/site/fe/matter/enroll/data/like?site=' + LS.s().site + '&ek=' + oRecommend.obj_key + '&schema=' + oRecommend.obj.schema_id).then(function(rsp) {
                oRecData.like_log = rsp.data.like_log;
                oRecData.like_num = rsp.data.like_num;
            });
        }
    };
    $scope.remarkRecommend = function(oRecommend) {
        var url;
        url = '/rest/site/fe/matter/enroll?site=' + LS.s().site;
        url += '&app=' + oRecommend.matter.id;
        url += '&page=remark';
        url += '&ek=' + oRecommend.obj_key;
        if (oRecommend.obj_unit === 'D') {
            url += '&schema=' + oRecommend.obj.schema_id;
        }
        location.href = url;
    };
    http2.get(LS.j('matter/agreedList', 'site', 'mission')).then(function(rsp) {
        $scope.recommends = rsp.data.agreed;
    });
}]);
ngApp.controller('ctrlRank', ['$scope', 'tmsLocation', 'http2', function($scope, LS, http2) {
    http2.get(LS.j('user/rank', 'site', 'mission')).then(function(rsp) {
        $scope.users = rsp.data.users;
    });
}]);
/* bootstrap angular app */
angular.bootstrap(document, ["app"]);