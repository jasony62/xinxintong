'use strict';
require('./repos.css');

var ngApp = require('./main.js');
ngApp.factory('Round', ['http2', '$q', function(http2, $q) {
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
        http2.get(url).then(function(rsp) {
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
ngApp.controller('ctrlRepos', ['$scope', 'http2', 'Round', function($scope, http2, srvRound) {
    var oApp, facRound, page, criteria, schemas;
    $scope.schemaCount = 0;
    $scope.page = page = { at: 1, size: 12 };
    $scope.criteria = criteria = { owner: 'all' };
    $scope.schemas = schemas = {};
    $scope.repos = [];
    $scope.clickAdvCriteria = function(event) {
        event.preventDefault();
        event.stopPropagation();
    };
    $scope.list4Schema = function(pageAt) {
        var url;
        if (pageAt) {
            page.at = pageAt;
        }
        url = '/rest/site/fe/matter/enroll/repos/list4Schema?site=' + oApp.siteid + '&app=' + oApp.id;
        url += '&page=' + page.at + '&size=' + page.size;
        http2.post(url, criteria).then(function(result) {
            $scope.repos = result.data.records;
            page.total = result.data.total;
        });
    }
    $scope.gotoRemark = function(oRecordData) {
        var url;
        url = '/rest/site/fe/matter/enroll?site=' + oApp.siteid + '&app=' + oApp.id + '&page=remark';
        url += '&ek=' + oRecordData.enroll_key;
        url += '&schema=' + oRecordData.schema_id;
        location.href = url;
    };
    $scope.shiftRound = function() {
        if (criteria.rid === 'more') {
            facRound.oPage.at++;
            facRound.list().then(function(result) {
                result.rounds.forEach(function(round) {
                    $scope.rounds.push(round);
                })
            });
        } else {
            $scope.list4Schema();
        }
    };
    $scope.shiftAgreed = function() {
        $scope.list4Schema();
    };
    $scope.shiftOwner = function() {
        $scope.list4Schema();
    };
    $scope.shiftSchema = function() {
        $scope.list4Schema();
    };
    $scope.likeRecordData = function(oRecord) {
        var url;
        url = '/rest/site/fe/matter/enroll/record/like';
        url += '?site=' + oApp.siteid;
        url += '&ek=' + oRecord.enroll_key;
        url += '&schema=' + oRecord.schema_id;
        http2.get(url).then(function(rsp) {
            oRecord.like_log = rsp.data.like_log;
            oRecord.like_num = rsp.data.like_num;
        });
    };
    $scope.$on('xxt.app.enroll.ready', function(event, params) {
        oApp = params.app;
        oApp.dataSchemas.forEach(function(schema) {
            if (schema.shareable === 'Y') {
                schemas[schema.id] = schema;
                $scope.schemaCount++;
            }
        });
        $scope.list4Schema();
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
