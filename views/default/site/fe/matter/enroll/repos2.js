'use strict';
require('./repos2.css');

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
ngApp.controller('ctrlRepos', ['$scope', '$sce', 'http2', 'tmsLocation', 'Round', function($scope, $sce, http2, LS, srvRound) {
    var oApp, facRound, _oPage, _oCriteria, _oShareableSchemas, userGroups, _items;
    _items = {};
    $scope.schemaCount = 0;
    $scope.page = _oPage = { at: 1, size: 12 };
    $scope.criteria = _oCriteria = { owner: 'all' };
    $scope.schemas = _oShareableSchemas = {};
    $scope.repos = [];
    $scope.recordList = function(pageAt) {
        var url;
        if (pageAt) {
            _oPage.at = pageAt;
        } else {
            _oPage.at++;
        }
        if (_oPage.at == 1) {
            $scope.repos = [];
        }
        url = LS.j('repos/recordList', 'site', 'app');
        url += '&page=' + _oPage.at + '&size=' + _oPage.size;
        http2.post(url, _oCriteria).then(function(result) {
            _oPage.total = result.data.total;
            if (result.data.records) {
                result.data.records.forEach(function(oRecord) {
                    $scope.repos.push(oRecord);
                });
            }
        });
    }
    $scope.likeRecord = function(oRecord) {
        var url;
        url = LS.j('record/like', 'site');
        url += '&ek=' + oRecord.enroll_key;
        http2.get(url).then(function(rsp) {
            oRecord.like_log = rsp.data.like_log;
            oRecord.like_num = rsp.data.like_num;
        });
    };
    $scope.remarkRecord = function(oRecord) {
        var url;
        url = LS.j('', 'site', 'app');
        url += '&ek=' + oRecord.enroll_key;
        url += '&page=remark';
        location.href = url;
    };
    $scope.recommend = function(oRecord, value) {
        var url;
        if (oRecord.agreed !== value) {
            url = LS.j('record/recommend', 'site');
            url += '&ek=' + oRecord.enroll_key;
            url += '&value=' + value;
            http2.get(url).then(function(rsp) {
                oRecord.agreed = value;
            });
        }
    };
    $scope.value2Label = function(value, schemaId) {
        var val, schema, aVal, aLab = [];

        if ((schema = $scope.app._schemasById[schemaId]) && value) {
            if (val = value) {
                if (schema.ops && schema.ops.length) {
                    aVal = val.split(',');
                    schema.ops.forEach(function(op) {
                        aVal.indexOf(op.v) !== -1 && aLab.push(op.l);
                    });
                    val = aLab.join(',');
                }
            } else {
                val = '';
            }
        }
        return $sce.trustAsHtml(val);
    };
    $scope.$on('xxt.app.enroll.ready', function(event, params) {
        oApp = params.app;
        oApp.dataSchemas.forEach(function(schema) {
            if (schema.shareable && schema.shareable === 'Y') {
                _oShareableSchemas[schema.id] = schema;
                $scope.schemaCount++;
            }
            if (schema.id === '_round_id' && schema.ops && schema.ops.length) {
                schema.ops.forEach(function(op) {
                    userGroups.push(op);
                });
            }
        });
        $scope.groupUser = params.groupUser;
        var groupOthersById = {};
        if (params.groupOthers && params.groupOthers.length) {
            params.groupOthers.forEach(function(oOther) {
                groupOthersById[oOther.userid] = oOther;
            });
        }
        $scope.groupOthers = groupOthersById;
        $scope.dataTags = oApp.dataTags;
        $scope.recordList(1);
        $scope.facRound = facRound = srvRound.ins(oApp);
        if (oApp.multi_rounds === 'Y') {
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
        }
    });
}]);