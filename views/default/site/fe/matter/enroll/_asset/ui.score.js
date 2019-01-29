'use strict';

var ngMod = angular.module('score.ui.enroll', []);
ngMod.directive('tmsScoreRecordData', [function() {
    return {
        restrict: 'A',
        template: require('./score-app.html'),
        scope: {
            task: '='
        },
        controller: ['$scope', '$parse', 'tmsLocation', 'http2', 'noticebox', function($scope, $parse, LS, http2, noticebox) {
            var _oData, _oScoreRecord;
            $scope.data = _oData = {};
            $scope.score = function(oSchema, opIndex, number) {
                var oOption;

                if (!(oOption = oSchema.ops[opIndex])) return;

                if (_oData[oSchema.id] === undefined) {
                    _oData[oSchema.id] = {};
                    oSchema.ops.forEach(function(oOp) {
                        _oData[oSchema.id][oOp.v] = 0;
                    });
                }
                _oData[oSchema.id][oOption.v] = number;
            };
            $scope.lessScore = function(oSchema, opIndex, number) {
                var oOption;

                if (!(oOption = oSchema.ops[opIndex])) return false;
                if (_oData[oSchema.id] === undefined) {
                    return false;
                }
                return _oData[oSchema.id][oOption.v] >= number;
            };
            $scope.submit = function() {
                var url;
                url = LS.j('record/submit', 'site') + '&app=' + _oScoreApp.id + '&task=' + $scope.task.id;
                if (_oScoreRecord)
                    url += '&ek=' + _oScoreRecord.enroll_key;
                http2.post(url, { data: _oData }, { autoBreak: false }).then(function(rsp) {
                    http2.post(LS.j('marks/renewReferScore', 'site') + '&app=' + _oScoreApp.id, {}).then(function() {
                        noticebox.success('提交成功！');
                    });
                });
            };
            var _oScoreApp;
            _oScoreApp = $scope.task.scoreApp;
            http2.get(LS.j('get', 'site') + '&app=' + _oScoreApp.id + '&task=' + $scope.task.id).then(function(rsp) {
                _oScoreApp = rsp.data.app;
                $scope.schemas = _oScoreApp.dynaDataSchemas;
                http2.get(LS.j('record/get', 'site') + '&app=' + _oScoreApp.id + '&task=' + $scope.task.id).then(function(rsp) {
                    if (rsp.data.enroll_key) {
                        _oScoreRecord = rsp.data;
                        http2.merge(_oData, _oScoreRecord.data);
                    }
                });
            });
        }]
    };
}]);