define(['require', 'angular'], function(require, angular) {
    'use strict';

    var siteId = location.search.match('site=(.*)')[1];
    var ngApp = angular.module('app', ['ui.bootstrap']);
    ngApp.controller('ctrlCoin', ['$scope', '$http', '$uibModal', function($scope, $http, $uibModal) {
        var page, _oCriteria, _oSite={};
        $scope.frameSid = '';
        $scope.unionType = '';
        $scope.criteria = _oCriteria = {
            sid: '',
            type: ''
        };
        $scope.page = page = {
            at: 1,
            size: 10,
            join: function() {
                return '&page=' + this.at + '&size=' + this.size;
            }
        };
        $scope.view = function(sid, type, id) {
            $uibModal.open({
                templateUrl: 'detaiLog.html',
                controller:['$uibModalInstance', '$scope', '$http', function($mi, $scope2, $http) {
                    $scope2.oSite = angular.copy(_oSite);
                    $scope2.page = {
                        at: 1,
                        size: 20,
                        j: function() {
                            return '&page=' + this.at + '&size=' + this.size;
                        }
                    };
                    $scope2.doSearch = function() {
                        var url;
                        url = '/rest/site/fe/user/coin/logs?site=' + sid;
                        url += '&user=' + $scope2.oSite[sid].userid + '&matterType=' + type + '&matterId=' + id;
                        url +=  '&groupByMatter=true' + page.join();

                        $http.get(url).success(function(rsp) {
                            if(rsp.data.logs.length !== 0) {
                                $scope2.logs = rsp.data.logs;
                                $scope2.page.total = rsp.data.total;
                            }
                        });
                    };
                    $scope2.doSearch();
                }],
                backdrop: 'static'
            })
        };
        $scope.list = function(pageAt) {
            pageAt && (page.at = pageAt);

            var url;
            url = '/rest/site/fe/user/coin/logs?site=' + _oCriteria.sid;
            url += '&user=' + _oSite[_oCriteria.sid].userid;
            url +=  '&groupByMatter=true' + page.join();
            if(_oCriteria.type) {
                url += '&matterType=' + _oCriteria.type;
            }
            $http.get(url).success(function(rsp) {
                if(rsp.data.logs.length!==0) {
                    $scope.matters = rsp.data;
                    $scope.page.total = rsp.data.total;
                }
            });
        };
        $http.get('/rest/site/fe/get?site=' + siteId).success(function(rsp) {
            $scope.site = rsp.data;
            $http.get('/rest/site/fe/user/coin/sites?site=' + siteId).success(function(rsp) {
                if(rsp.data) {
                    rsp.data.forEach(function(data) {
                        _oSite[data.siteid] = data;
                    });
                    $scope.sites = rsp.data;
                    $scope.frameSid = rsp.data[0].siteid;
                }
                window.loading.finish();
            });
        });
        $scope.$watchGroup(['frameSid', 'unionType'], function(nv) {
            if(!nv[0] && !nv[1]) return;
            _oCriteria.sid = nv[0];
            _oCriteria.type = nv[1];
            $scope.list(1);
        }, true);
    }]);
});
