define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlAnalysis', ['$scope', function($scope) {
        var catelogs;
        $scope.catelogs = catelogs = [];
        catelogs.splice(0, catelogs.length, { l: '单图文', v: 'article' }, { l: '用户', v: 'user' });
        $scope.catelog = catelogs[0];
    }]);
    ngApp.provider.controller('ctrlArticle', ['$scope', 'http2', 'facListFilter', function($scope, http2, facListFilter) {
        var current, startAt, endAt, _oCriteria, _siteid;
        current = new Date();
        startAt = {
            year: current.getFullYear(),
            month: current.getMonth() + 1,
            mday: current.getDate(),
            getTime: function() {
                var d = new Date(this.year, this.month - 1, this.mday, 0, 0, 0, 0);
                return d.getTime();
            }
        };
        endAt = {
            year: current.getFullYear(),
            month: current.getMonth() + 1,
            mday: current.getDate(),
            getTime: function() {
                var d = new Date(this.year, this.month - 1, this.mday, 23, 59, 59, 0);
                return d.getTime();
            }
        };
        _siteid = location.search.match(/[\?&]site=([^&]*)/)[1];
        $scope.startAt = startAt.getTime() / 1000;
        $scope.endAt = endAt.getTime() / 1000;
        $scope.page = {
            at: 1,
            size: 30,
            total: 0,
            param: function() {
                return 'page=' + this.at + '&size=' + this.size;
            }
        };
        $scope.criteria = _oCriteria = {
            orderby: "",
            filter: {}
        }
        $scope.filter = facListFilter.init(null, _oCriteria.filter);
        $scope.orderby = 'read';
        $scope.fetch = function(page) {
            var url;
            page && ($scope.page.at = page);
            url = '/rest/pl/fe/site/analysis/matterActions';
            url += '?site=' + _siteid;
            url += '&type=article';
            url += '&orderby=' + _oCriteria.orderby;
            url += '&startAt=' + $scope.startAt;
            url += '&endAt=' + $scope.endAt;
            url += '&' + $scope.page.param();
            http2.post(url, {byUser: _oCriteria.filter.keyword}).then(function(rsp) {
                $scope.matters = rsp.data.matters;
                $scope.page.total = rsp.data.total;
            });
        };
        $scope.export = function(url) {
            var url;
            url = '/rest/pl/fe/site/analysis/exportMatterActions';
            url += '?site=' + $scope.site.id;
            url += '&type=article';
            url += '&orderby=' + _oCriteria.orderby;
            url += '&startAt=' + $scope.startAt;
            url += '&endAt=' + $scope.endAt;
            window.open(url);
        };
        $scope.$on('xxt.tms-datepicker.change', function(event, data) {
            $scope[data.state] = data.value;
            $scope.fetch(1);
        });
        $scope.$watch('criteria', function(nv) {
            if (!nv) return;
            $scope.fetch(1);
        }, true);
    }]);
    ngApp.provider.controller('ctrlUserAction', ['$scope', 'http2', 'facListFilter', function($scope, http2, facListFilter) {
        var current, startAt, endAt, _oCriteria, _siteid;
        current = new Date();
        startAt = {
            year: current.getFullYear(),
            month: current.getMonth() + 1,
            mday: current.getDate(),
            getTime: function() {
                var d = new Date(this.year, this.month - 1, this.mday, 0, 0, 0, 0);
                return d.getTime();
            }
        };
        endAt = {
            year: current.getFullYear(),
            month: current.getMonth() + 1,
            mday: current.getDate(),
            getTime: function() {
                var d = new Date(this.year, this.month - 1, this.mday, 23, 59, 59, 0);
                return d.getTime();
            }
        };
        _siteid = location.search.match(/[\?&]site=([^&]*)/)[1];
        $scope.startAt = startAt.getTime() / 1000;
        $scope.endAt = endAt.getTime() / 1000;
        $scope.page = {
            at: 1,
            size: 30,
            total: 0,
            param: function() {
                return 'page=' + this.at + '&size=' + this.size;
            }
        };
        $scope.criteria = _oCriteria = {
            orderby: 'read',
            filter: {}
        };
        $scope.userType = { 'Y': "管理员", 'N': "非管理员" };
        $scope.filter = facListFilter.init(null, _oCriteria.filter);
        $scope.fetch = function(page) {
            var url;
            page && ($scope.page.at = page);
            url = '/rest/pl/fe/site/analysis/userActions';
            url += '?site=' + _siteid;
            url += '&orderby=' + _oCriteria.orderby;
            url += '&startAt=' + $scope.startAt;
            url += '&endAt=' + $scope.endAt;
            url += '&' + $scope.page.param();
            http2.post(url, {byType: _oCriteria.filter.keyword}).then(function(rsp) {
                $scope.users = rsp.data.users;
                $scope.page.total = rsp.data.total;
            });
        };
        $scope.export = function(url) {
            var url;
            url = '/rest/pl/fe/site/analysis/exportUserActions';
            url += '?site=' + $scope.site.id;
            url += '&type=article';
            url += '&orderby=' + $scope.orderby;
            url += '&startAt=' + $scope.startAt;
            url += '&endAt=' + $scope.endAt;
            window.open(url);
        };
        $scope.viewUser = function(openid) {
            location.href = '/rest/mp/user?openid=' + openid;
        };
        $scope.$on('xxt.tms-datepicker.change', function(event, data) {
            $scope[data.state] = data.value;
            $scope.fetch(1);
        });
        $scope.$watch('criteria', function(nv) {
            if (!nv) return;
            $scope.fetch(1);
        }, true);
    }]);
});