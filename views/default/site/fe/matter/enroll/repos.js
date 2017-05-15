'use strict';
require('./repos.css');

var ngApp = require('./main.js');
ngApp.factory('Round', ['$http', '$q', function($http, $q) {
    var Round, _ins;
    Round = function(oApp) {
        this.oApp = oApp;
        this.oPage = {
            at: 1,
            size: 10,
            j: function() {
                return '&page=' + this.at + '&size=' + this.size;
            }
        };
    };
    Round.prototype.list = function() {
        var _this = this,
            deferred = $q.defer(),
            url;

        url = '/rest/site/fe/matter/enroll/round/list?site=' + this.oApp.siteid + '&app=' + this.oApp.id;
        url += this.oPage.j();
        $http.get(url).success(function(rsp) {
            if (rsp.err_code != 0) {
                alert(rsp.data);
                return;
            }
            _this.oPage.total = rsp.data.total;
            deferred.resolve(rsp.data);
        });
        return deferred.promise;
    };
    return {
        ins: function(oApp) {
            _ins = _ins ? _ins : new Round(oApp);
            return _ins;
        }
    };
}]);
ngApp.controller('ctrlRepos', ['$scope', '$http', 'Round', function($scope, $http, srvRound) {
    var oApp, facRound, criteria, opened = {},
        schemas = [];

    $scope.criteria = criteria = { owner: 'all' };
    $scope.schemas = schemas;
    $scope.repos = {};
    $scope.list4Schema = function(schema) {
        var url, page;
        page = schema._page;
        url = '/rest/site/fe/matter/enroll/repos/list4Schema?site=' + oApp.siteid + '&app=' + oApp.id;
        url += '&schema=' + schema.id;
        url += '&page=' + page.at + '&size=' + page.size;
        criteria.rid && (url += '&rid=' + criteria.rid);
        criteria.owner && criteria.owner !== 'all' && (url += '&owner=' + criteria.owner);
        $http.get(url).success(function(result) {
            $scope.repos[schema.id] = result.data.records;
            page.total = result.data.total;
        });
    }
    $scope.schemaExpanded = function(schema) {};
    $scope.switchSchema = function(schema) {
        schema._open = !schema._open;
        if (schema._open) {
            schema._page.at = 1;
            opened.schema = schema;
            $scope.list4Schema(schema);
        }
    };
    $scope.gotoRemark = function(ek, schema) {
        var url;
        url = '/rest/site/fe/matter/enroll?site=' + oApp.siteid + '&app=' + oApp.id + '&page=remark';
        url += '&ek=' + ek;
        schema && (url += '&schema=' + schema.id);
        location.href = url;
    };
    $scope.shiftRound = function() {
        if (criteria.rid === 'more') {
            if (opened.schema) {
                opened.schema._open = false;
                opened = {};
            }
            facRound.oPage.at++;
            facRound.list().then(function(result) {
                result.rounds.forEach(function(round) {
                    $scope.rounds.push(round);
                })
            });
        } else {
            if (opened.schema) {
                opened.schema._page.at = 1;
                $scope.list4Schema(opened.schema);
            }
        }
    };
    $scope.shiftOwner = function() {
        $scope.list4Schema(opened.schema);
    };
    $scope.likeRecordData = function(oRecord, oSchema) {
        var url;
        url = '/rest/site/fe/matter/enroll/record/like';
        url += '?site=' + oApp.siteid;
        url += '&ek=' + oRecord.enroll_key;
        url += '&schema=' + oSchema.id;
        $http.get(url).success(function(rsp) {
            oRecord.like_log = rsp.data.like_log;
            oRecord.like_num = rsp.data.like_num;
        });
    };
    $scope.$on('xxt.app.enroll.ready', function(event, params) {
        oApp = params.app;
        oApp.dataSchemas.forEach(function(schema) {
            if (schema.shareable === 'Y') {
                schema._open = false;
                schema._page = { at: 1, size: 10 };
                schemas.push(schema);
            }
        });
        if (schemas.length === 1) {
            $scope.switchSchema(schemas[0]);
        }
        $scope.facRound = facRound = srvRound.ins(oApp);
        facRound.list().then(function(result) {
            if (result.active) {
                for (var i = 0, ii = result.rounds.length; i < ii; i++) {
                    if (result.rounds[i].rid === result.active.rid) {
                        criteria.rid = result.active.rid;
                        break;
                    }
                }
            }
            $scope.rounds = result.rounds;
        });
    });
}]);
