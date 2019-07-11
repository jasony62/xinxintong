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
        $scope.page = {
            at: 1,
            size: 30,
            total: 0,
            param: function() {
                return 'page=' + this.at + '&size=' + this.size;
            }
        };
        $scope.criteria = _oCriteria = {
            orderby: "read",
            filter: {},
            isAdmin: "",
            startAt: startAt.getTime() / 1000,
            endAt: endAt.getTime() / 1000
        }
        function cbFilter(obj, key, value) {
            switch(key) {
                case 'creator':
                    _oCriteria.byCreator = value;
                break;
            }
        };
        $scope.filter = facListFilter.init(cbFilter, _oCriteria.filter);
        $scope.fetch = function(page) {
            var url;
            page && ($scope.page.at = page);
            url = '/rest/pl/fe/site/analysis/matterActions?site=' + _siteid + '&type=article&' + $scope.page.param();
            http2.post(url, _oCriteria).then(function(rsp) {
                $scope.matters = rsp.data.matters;
                $scope.page.total = rsp.data.total;
            });
        };
        $scope.export = function(url) {
            var url;
            url = '/rest/pl/fe/site/analysis/exportMatterActions?site=' + _siteid + '&type=article';
            url += '&orderby=' + _oCriteria.orderby;
            url += '&isAdmin=' + _oCriteria.isAdmin;
            url += '&startAt=' + _oCriteria.startAt;
            url += '&endAt=' + _oCriteria.endAt;
            window.open(url);
        };
        $scope.$on('xxt.tms-datepicker.change', function(event, data) {
            _oCriteria[data.state] = data.value;
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
            isAdmin: '',
            startAt: startAt.getTime() / 1000,
            endAt: endAt.getTime() / 1000
        };
        $scope.fetch = function(page) {
            var url;
            page && ($scope.page.at = page);
            url = '/rest/pl/fe/site/analysis/userActions?site=' + _siteid + '&' + $scope.page.param();
            http2.post(url, _oCriteria).then(function(rsp) {
                $scope.users = rsp.data.users;
                $scope.page.total = rsp.data.total;
            });
        };
        $scope.export = function(url) {
            var url;
            url = '/rest/pl/fe/site/analysis/exportUserActions?site=' + _siteid;
            url += '&orderby=' + _oCriteria.orderby;
            url += '&isAdmin=' + _oCriteria.isAdmin;
            url += '&startAt=' + _oCriteria.startAt;
            url += '&endAt=' + _oCriteria.endAt;
            window.open(url);
        };
        $scope.viewUser = function(openid) {
            location.href = '/rest/mp/user?openid=' + openid;
        };
        $scope.$on('xxt.tms-datepicker.change', function(event, data) {
            _oCriteria[data.state] = data.value;
        });
        $scope.$watch('criteria', function(nv) {
            if (!nv) return;
            $scope.fetch(1);
        }, true);
    }]);
});