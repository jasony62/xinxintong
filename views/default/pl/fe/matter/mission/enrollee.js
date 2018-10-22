define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlEnrollee', ['$scope', '$filter', 'http2', 'facListFilter', function($scope, $filter, http2, facListFilter) {
        var _oMission, _oCriteria;
        $scope.criteria = _oCriteria = {
            orderBy: '',
            filter: {}
        };
        $scope.enrolleeList = function() {
            var url;
            url = '/rest/pl/fe/matter/mission/user/enrolleeList?site=' + _oMission.siteid + '&mission=' + _oMission.id;
            http2.post(url, _oCriteria).then(function(rsp) {
                var enrollees, dateFormat;
                dateFormat = 'MM-dd HH:mm';
                $scope.enrollees = enrollees = rsp.data.enrollees;
                enrollees.forEach(function(oEnrollee) {
                    oEnrollee.last_enroll_at = oEnrollee.last_enroll_at > 0 ? $filter('date')(oEnrollee.last_enroll_at * 1000, dateFormat) : '';
                    oEnrollee.last_do_remark_at = oEnrollee.last_do_remark_at > 0 ? $filter('date')(oEnrollee.last_do_remark_at * 1000, dateFormat) : '';
                    oEnrollee.last_do_like_at = oEnrollee.last_do_like_at > 0 ? $filter('date')(oEnrollee.last_do_like_at * 1000, dateFormat) : '';
                    oEnrollee.last_agree_at = oEnrollee.last_agree_at > 0 ? $filter('date')(oEnrollee.last_agree_at * 1000, dateFormat) : '';
                    if (oEnrollee.members && oEnrollee.members.length) {
                        oEnrollee.isMember = '是';
                    }
                });
            });
        }
        $scope.chooseOrderBy = function(orderBy) {
            _oCriteria.orderBy = orderBy;
            $scope.enrolleeList();
        };
        $scope.createEnlAppByUser = function() {
            var url;
            url = '/rest/pl/fe/matter/enroll/createByMissionUser?mission=' + _oMission.id;
            http2.get(url).then(function(rsp) {
                location.href = '/rest/pl/fe/matter/enroll/preview?site=' + rsp.data.siteid + '&id=' + rsp.data.id;
            });
        };
        $scope.filter = facListFilter.init(function() {
            $scope.enrolleeList();
        }, _oCriteria.filter);
        $scope.$watch('mission', function(oMission) {
            if (!oMission) return;
            _oMission = oMission;
            $scope.enrolleeList();
        });
    }]);
});