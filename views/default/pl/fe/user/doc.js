define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlDoc', ['$scope', 'http2', '$uibModal', function($scope, http2, $uibModal) {
        var _oPage, _oCriteria;
        $scope.criteria = _oCriteria = {
            start: '',
            end: ''
        };
        $scope.page = _oPage = {
            at: 1,
            size: 30,
            j: function() {
                return '&page=' + this.at + '&size=' + this.size;
            }
        };
        //分页
        $scope.doSearch = function() {
            var url;
            url = '/rest/pl/fe/user/readList?site='+ $scope.siteId +'&uid=' + $scope.userId;
            url += '&startAt=' + _oCriteria.start + '&endAt=' + _oCriteria.end;
            url + _oPage.j();
            http2.get(url, function(rsp) {
                $scope.matters = rsp.data.logs;
                _oPage.total = rsp.data.total || 0;
            });
        };
        $scope.cancle = function() {
            _oCriteria.start = _oCriteria.end = '';
            $scope.doSearch();
        };
        $scope.detail = function(matter, type) {
            $uibModal.open({
                templateUrl: '/views/default/pl/fe/_module/statDetail.html?_=1',
                controller: ['$scope', '$uibModalInstance', 'http2', function($scope, $mi, http2) {
                    var criteria = {
                        byOp: type,
                        byUserId: matter.userid,
                        start: _oCriteria.start,
                        end: _oCriteria.end
                    }
                    $scope.page = {
                        at: 1,
                        size: 15,
                        j: function() {
                            return '&page=' + this.at + '&size=' + this.size;
                        }
                    };
                    $scope.list = function() {
                        var url;
                        url = '/rest/pl/fe/user/userDetailLogs?matterId=' + matter.matter_id + '&matterType=' + matter.matter_type + $scope.page.j();
                        http2.post(url, criteria, function(rsp) {
                            $scope.logs = rsp.data.logs;
                            $scope.page.total = rsp.data.total;
                        });
                    };
                    $scope.cancle = function() {
                        $mi.dismiss();
                    };
                    $scope.list();
                }],
                backdrop: 'static'
            })
        };
        $scope.$on('xxt.tms-datepicker.change', function(event, data) {
            _oCriteria[data.state] = data.value;
            if(_oCriteria.start || _oCriteria.end) {
                $scope.doSearch();
            }
        });
        $scope.doSearch();
    }])

});