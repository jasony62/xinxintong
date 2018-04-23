define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlLog', ['$scope', 'http2', 'srvLog', 'facListFilter', function($scope, http2, srvLog, facListFilter) {
        var read, spread, favor;
        $scope.read = read = {
            page: {},
            list: function() {
                var _this = this;
                srvLog.list($scope.editing, this.page, 'log').then(function(data) {
                    _this.logs = data.logs;
                    _this.page.total = data.total;
                });
            }
        };
        $scope.spread = spread = {
            page: {},
            criteria: {
               start: '',
               end: '',
               byUser: ''
            },
            list: function() {
                var _this = this;
                srvLog.list($scope.editing, this.page, 'spread', this.criteria).then(function(spreaders) {
                    _this.spreaders = spreaders.data;
                    _this.page.total = spreaders.total;
                });
            },
            cleanFilter: function() {
                var _this = this;
                this.criteria.byUser = '';
                srvLog.list($scope.editing, this.page, 'spread', this.criteria).then(function(spreaders) {
                    _this.spreaders = spreaders.data;
                    _this.page.total = spreaders.total;
                });
            }
        };
        $scope.open = function(id) {
            spread.page.at = 1;
            spread.criteria.shareby = id;
            spread.list($scope.editing, spread.page, 'spread', spread.criteria).then(function(user) {

            });
        };
        $scope.export = function() {

        };
        $scope.favor = favor = {
            page: {},
            list: function() {
                var _this = this;
                srvLog.list($scope.editing, this.page, 'favor').then(function(favorers) {
                    _this.favorers = favorers.data;
                    _this.page.total = favorers.total;
                });
            }
        };
        $scope.$watch('editing', function(nv) {
            if(!nv) return;
            read.list();
            spread.list();
            favor.list();
        });
    }]);
});
