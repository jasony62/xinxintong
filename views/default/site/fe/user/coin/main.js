define(['require', 'angular'], function (require, angular) {
    'use strict';

    var siteId = location.search.match('site=(.*)')[1];
    var ngApp = angular.module('app', ['ui.bootstrap']);
    ngApp.controller('ctrlCoin', ['$scope', '$http', '$uibModal', function ($scope, $http, $uibModal) {
        var page, _oCriteria, _oSite;
        $scope.frameSid = '';
        $scope.unionType = '';
        $scope.byTitle = '';
        $scope.oSite = _oSite = {};
        $scope.matterTypes = {
            'article': '单图文',
            'enroll': '记录活动',
            'signin': '签到活动'
        };
        $scope.criteria = _oCriteria = {
            sid: '',
            type: ''
        };
        $scope.page = page = {
            at: 1,
            size: 20,
            join: function () {
                return '&page=' + this.at + '&size=' + this.size;
            }
        };
        $scope.view = function (matter) {
            if ($scope.unionType == '') {
                return false;
            }
            $uibModal.open({
                templateUrl: 'detaiLog.html',
                controller: ['$uibModalInstance', '$scope', '$http', function ($mi, $scope2, $http) {
                    $scope2.type = $scope.unionType;
                    $scope2.page = {
                        at: 1,
                        size: 20,
                        j: function () {
                            return '&page=' + this.at + '&size=' + this.size;
                        }
                    };
                    $scope2.doSearch = function (pageAt) {
                        pageAt && ($scope2.page.at = pageAt);
                        var url;

                        if ($scope2.type === 'mission') {
                            $scope2.logs = matter.modify_log;
                        } else {
                            url = '/rest/site/fe/user/coin/logs?site=' + _oCriteria.sid + '&user=' + _oSite[_oCriteria.sid].userid;
                            url += '&matterType=' + matter.matter_type + '&matterId=' + matter.matter_id;
                            url += page.join();

                            $http.get(url).success(function (rsp) {
                                $scope2.logs = rsp.data.logs;
                                $scope2.page.total = rsp.data.total;
                            });
                        }
                    };
                    $scope2.cancel = function () {
                        $mi.close();
                    }
                    $scope2.doSearch();
                }],
                backdrop: 'static'
            })
        };
        $scope.list = function (pageAt) {
            pageAt && (page.at = pageAt);

            var url;
            if (_oCriteria.type == 'mission') {
                url = '/rest/site/fe/user/coin/missions?site=' + _oCriteria.sid + '&user=' + _oSite[_oCriteria.sid].userid;
            } else {
                url = '/rest/site/fe/user/coin/logs?site=' + _oCriteria.sid;
                url += '&user=' + _oSite[_oCriteria.sid].userid;
                url += '&matterType=' + _oCriteria.type;
            }
            url += page.join();
            $http.post(url, {
                'byName': $scope.byTitle
            }).success(function (rsp) {
                $scope.matters = rsp.data.logs;
                $scope.page.total = rsp.data.total;
            });
        };
        $scope.cleanFilter = function () {
            $scope.byTitle = '';
            $scope.list();
        };
        $http.get('/rest/site/fe/get?site=' + siteId).success(function (rsp) {
            $scope.site = rsp.data;
            $http.get('/rest/site/fe/user/coin/sites?site=' + siteId).success(function (rsp) {
                if (rsp.data) {
                    rsp.data.forEach(function (data) {
                        _oSite[data.siteid] = data;
                    });
                    $scope.sites = rsp.data;
                    $scope.frameSid = rsp.data[0].siteid;
                }
                window.loading.finish();
            });
        });
        $scope.$watchGroup(['frameSid', 'unionType'], function (nv) {
            if (!nv[0] && !nv[1]) return;
            _oCriteria.sid = nv[0];
            _oCriteria.type = nv[1];
            $scope.list(1);
        }, true);
    }]);
});