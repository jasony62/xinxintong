define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlEnrollee', ['$scope', '$filter', 'http2', 'facListFilter', 'noticebox', function($scope, $filter, http2, facListFilter, noticebox) {
        function fnReadableElapse(e) {
            var result, h, m, s, time = e * 1;
            h = Math.floor(time / 3600);
            m = Math.floor((time / 60 % 60));
            s = Math.floor((time % 60));
            return result = h + ":" + m + ":" + s;
        }

        var _oMission, _oCriteria;
        $scope.criteria = _oCriteria = {
            orderBy: '',
            filter: {}
        };
        $scope.enrolleeList = function() {
            var url;
            url = '/rest/pl/fe/matter/mission/user/enrolleeList?mission=' + _oMission.id;
            http2.post(url, _oCriteria).then(function(rsp) {
                var enrollees, dateFormat;
                dateFormat = 'MM-dd HH:mm';
                $scope.enrollees = enrollees = rsp.data.enrollees;
                enrollees.forEach(function(oEnrollee) {
                    oEnrollee.score = $filter('number')(oEnrollee.score, 2);
                    ['last_entry_at', 'last_enroll_at', 'last_do_remark_at', 'last_do_like_at', 'last_agree_at'].forEach(function(prop) {
                        oEnrollee[prop] = oEnrollee[prop] > 0 ? $filter('date')(oEnrollee[prop] * 1000, dateFormat) : '';
                    });
                    oEnrollee.total_elapse = oEnrollee.total_elapse > 0 ? fnReadableElapse(oEnrollee.total_elapse) : '';
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
        $scope.renewScore = function() {
            var url;
            url = '/rest/pl/fe/matter/mission/user/renewScore';
            url += '?mission=' + _oMission.id;
            http2.get(url).then(function(rsp) {
                $scope.enrolleeList();
                noticebox.success('更新完成');
            });
        };
        $scope.tmsTableWrapReady = 'N';
        $scope.filter = facListFilter.init(function() {
            $scope.enrolleeList();
        }, _oCriteria.filter);
        $scope.$watch('mission', function(oMission) {
            if (!oMission) return;
            _oMission = oMission;
            $scope.tmsTableWrapReady = 'Y';
            $scope.enrolleeList();
        });
    }]);
});