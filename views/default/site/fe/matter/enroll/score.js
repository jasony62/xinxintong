'use strict';
require('./score.css');

var ngApp = require('./main.js');
ngApp.controller('ctrlScore', ['$scope', '$sce', 'tmsLocation', 'http2', function($scope, $sce, LS, http2) {
    var oApp, quizSchemas, quizSchemasById;
    $scope.value2Label = function(schemaId) {
        var val, schema, aVal, aLab = [];

        if ((schema = oApp._schemasById[schemaId]) && $scope.record.data) {
            if (val = $scope.record.data[schemaId]) {
                if (schema.ops && schema.ops.length) {
                    if (schema.type === 'single') {
                        aVal = val.split(',');
                    } else {
                        aVal = val;
                    }
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
        quizSchemas = [];
        quizSchemasById = {};
        http2.get(LS.j('record/get', 'site', 'app', 'ek')).then(function(rsp) {
            var oRecord;
            oRecord = rsp.data;
            oApp.dynaDataSchemas.forEach(function(oSchema) {
                if (oSchema.requireScore && oSchema.requireScore === 'Y') {
                    quizSchemas.push(oSchema);
                    quizSchemasById[oSchema.id] = oSchema;
                    if (oSchema.type === 'multiple' && oRecord.data[oSchema.id]) {
                        oRecord.data[oSchema.id] = oRecord.data[oSchema.id].split(',');
                    }
                }
            });
            $scope.quizSchemas = quizSchemas;
            $scope.record = oRecord;
            /*设置页面分享信息*/
            $scope.setSnsShare(oRecord);
            /*设置页面操作*/
            $scope.setPopAct(['addRecord'], 'score');
            /*设置页面导航*/
            $scope.setPopNav(['repos', 'rank', 'event'], 'score');
            /*页面阅读日志*/
            $scope.logAccess();
        });
    });
}]);