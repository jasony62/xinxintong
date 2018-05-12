'use strict';

var ngMod = angular.module('repos.ui.enroll', []);
var oUtilSchema = require('../../_module/schema.util.js');
ngMod.directive('tmsReposRecordData', ['$templateCache', function($templateCache) {
    return {
        restrict: 'A',
        replace: true,
        template: require('./repos-record-data.html'),
        scope: {
            schemas: '=',
            rec: '=record'
        },
        controller: ['$scope', '$sce', function($scope, $sce) {
            var oRecord, oSchema, schemaData;
            if (oRecord = $scope.rec) {
                if ($scope.schemas) {
                    for (var schemaId in $scope.schemas) {
                        oSchema = $scope.schemas[schemaId];
                        if (schemaData = oRecord.data[oSchema.id]) {
                            switch (oSchema.type) {
                                case 'longtext':
                                    oRecord.data[oSchema.id] = oUtilSchema.txtSubstitute(schemaData);
                                    break;
                                case 'url':
                                    schemaData._text = oUtilSchema.urlSubstitute(schemaData);
                                    break;
                                case 'file':
                                case 'voice':
                                    schemaData.forEach(function(oFile) {
                                        if (oFile.url) {
                                            oFile.url = $sce.trustAsResourceUrl(oFile.url);
                                        }
                                    });
                                    break;
                                case 'single':
                                case 'multiple':
                                    oRecord.data[oSchema.id] = $sce.trustAsHtml(oUtilSchema.optionsSubstitute(oSchema, schemaData));
                                    break;
                            }
                        }

                    }
                }
            }
        }]
    };
}]);