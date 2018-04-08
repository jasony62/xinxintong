define(['require', 'angular'], function(require, angular) {
    'use strict';

    var siteId = location.search.match('site=(.*)')[1];
    var ngApp = angular.module('app', []);
    ngApp.controller('ctrlCoin', ['$scope', '$http', function($scope, $http) {
        var filter, criteria, page, _oSite = {};
        $scope.filter = filter = {
            sid: '',
            type: ''
        }
        $scope.criteria = criteria = {
            bySite: '',
            byType: '',
            byMatterId: ''
        };
        $scope.page = page = {
            at: 1,
            size: 10,
            join: function() {
                return 'page=' + this.at + '&size=' + this.size;
            }
        };
        $scope.doSearch = function(pageAt) {
            if(pageAt) {page.at = pageAt;}
            var url = '/rest/site/fe/user/coin/list?site=' + siteId;
                url += '&' + page.join();
            $http.post(url, {'byTitle': criteria.byTitle}).success(function(rsp) {
                if (rsp.err_code != 0) {
                    $scope.$root.errmsg = rsp.err_msg;
                    return;
                }
                $scope.logs = rsp.data.logs;
                $scope.page.total = rsp.data.total;
            });
        };
        $scope.list = function() {
            var url;
            if(filter.sid && !filter.type) {
                url = '/rest/site/fe/user/coin/matters?site=' + filter.sid;
                url += '&user=' + _oSite[filter.sid].userid;
                $http.get(url).success(function(rsp) {
                    $scope.logs = rsp.data;
                    $scope.page.total = rsp.data.total;
                });
            }
        };
        $scope.$watch('filter', function(nv) {
            if(!nv) return;
            criteria.bySite = nv.sid;
            criteria.type = nv.type;
            $scope.list();
        });
        $http.get('/rest/site/fe/get?site=' + siteId).success(function(rsp) {
            $scope.site = rsp.data;
            $http.get('/rest/site/fe/user/coin/sites?site=' + siteId).success(function(rsp) {
                $scope.sites = rsp.data;
                /*filter.sid = rsp.data[0].siteid;*/
                angular.forEach(rsp.data, function(site) {
                    _oSite[site.siteid] = site;
                });
                $scope.list();
                window.loading.finish();
            });
        });
    }]);
});
