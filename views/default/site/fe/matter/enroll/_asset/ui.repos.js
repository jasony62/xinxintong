'use strict';

var ngMod = angular.module('repos.ui.enroll', []);
var oUtilSchema = require('../../_module/schema.util.js');
ngMod.directive('tmsReposRecordData', ['$templateCache', function($templateCache) {
    return {
        restrict: 'A',
        template: require('./repos-record-data.html'),
        scope: {
            schemas: '=',
            rec: '=record'
        },
        controller: ['$scope', '$sce', 'tmsLocation', '$location',function($scope, $sce, LS, $location) {
            $scope.coworkRecord = function(oRecord) {
                var url;
                url = LS.j('', 'site', 'app');
                url += '&ek=' + oRecord.enroll_key;
                url += '&page=cowork';
                url += '#cowork';
                location.href = url;
            };
            $scope.open = function(file) {
                var url, appID;
                appID = $location.search().app;
                url = '/rest/site/fe/matter/enroll/attachment/download?app=' + appID;
                url += '&file=' + JSON.stringify(file);
                window.open(url);
            }
            $scope.$watch('rec', function(oRecord) {
                if (!oRecord) { return; }
                $scope.$watch('schemas', function(schemas) {
                    if (!schemas) { return; }
                    var oSchema, schemaData;
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
                });
            });
        }]
    };
}]);