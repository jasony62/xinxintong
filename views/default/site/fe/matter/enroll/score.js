'use strict';
require('./score.css');

var ngApp = require('./main.js');
ngApp.controller('ctrlScore', ['$scope', '$sce', function($scope, $sce) {
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
        oApp.dataSchemas.forEach(function(oSchema) {
            if (oSchema.requireScore && oSchema.requireScore === 'Y') {
                quizSchemas.push(oSchema);
                quizSchemasById[oSchema.id] = oSchema;
                if (oSchema.type === 'multiple' && params.record.data[oSchema.id]) {
                    params.record.data[oSchema.id] = params.record.data[oSchema.id].split(',');
                }
            }
        });
        $scope.quizSchemas = quizSchemas;
        $scope.record = params.record;
    });
}]);
