define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlDoc', ['$scope', 'http2', function($scope, http2) {
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
        }
        $scope.$on('xxt.tms-datepicker.change', function(event, data) {
            _oCriteria[data.state] = data.value;
            if(_oCriteria.start&&_oCriteria.end) {
                $scope.doSearch();
            }
        });
        $scope.doSearch();
    }])

});