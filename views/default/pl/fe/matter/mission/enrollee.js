define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlEnrollee', ['$scope', '$filter', '$uibModal', 'http2', function($scope, $filter, $uibModal, http2) {
        function enrolleeList() {
            var url;
            url = '/rest/pl/fe/matter/mission/user/enrolleeList?site=' + _oMission.siteid + '&mission=' + _oMission.id;
            http2.post(url, _oCriteria, function(rsp) {
                var enrollees, dateFormat;
                dateFormat = 'MM-dd HH:mm';
                $scope.enrollees = enrollees = rsp.data.enrollees;
                enrollees.forEach(function(oEnrollee) {
                    oEnrollee.last_enroll_at = oEnrollee.last_enroll_at > 0 ? $filter('date')(oEnrollee.last_enroll_at * 1000, dateFormat) : '';
                    oEnrollee.last_remark_other_at = oEnrollee.last_remark_other_at > 0 ? $filter('date')(oEnrollee.last_remark_other_at * 1000, dateFormat) : '';
                    oEnrollee.last_like_other_at = oEnrollee.last_like_other_at > 0 ? $filter('date')(oEnrollee.last_like_other_at * 1000, dateFormat) : '';
                });
            });
        }
        var _oMission, _oCriteria;
        $scope.criteria = _oCriteria = {
            orderBy: '',
            filter: { by: '', keyword: '' }
        };
        $scope.$watch('mission', function(oMission) {
            if (!oMission) return;
            _oMission = oMission;
            enrolleeList();
        });
        $scope.chooseOrderBy = function(orderBy) {
            _oCriteria.orderBy = orderBy;
            enrolleeList();
        };
        $scope.filter = {
            keyword: '',
            target: null,
            show: function(event) {
                this.target = event.target;
                this.keyword = _oCriteria.filter.keyword || '';
                $(this.target).trigger('show');
            },
            close: function() {
                $(this.target).trigger('hide');
            },
            cancel: function() {
                _oCriteria.filter.keyword = this.keyword = '';
                _oCriteria.filter.by = '';
                this.close();
                enrolleeList();
            },
            exec: function(filterBy) {
                _oCriteria.filter.keyword = this.keyword;
                _oCriteria.filter.by = this.keyword ? filterBy : '';
                this.close();
                enrolleeList();
            }
        };
    }]);
});