'use strict';

require('./ui.history.js');
require('../../../../../../../asset/js/xxt.ui.schema.js');

var ngMod = angular.module('record.repos.ui.enroll', ['schema.ui.xxt']);
ngMod.directive('tmsReposRecord', ['$templateCache', function ($templateCache) {
    return {
        restrict: 'A',
        template: require('./repos-record-schema.html'),
        scope: {
            schemas: '=',
            rec: '=record',
            schemaCounter: '='
        },
        controller: ['$scope', '$sce', '$location', 'tmsLocation', 'http2', 'tmsSchema', 'enlHistory', function ($scope, $sce, $location, LS, http2, tmsSchema, enlHistory) {
            $scope.open = function (file) {
                var url, appID, data;
                appID = $location.search().app;
                data = {
                    name: file.name,
                    size: file.size,
                    url: file.oUrl,
                    type: file.type
                }
                url = '/rest/site/fe/matter/enroll/attachment/download?app=' + appID;
                url += '&file=' + JSON.stringify(data);
                window.open(url);
            };
            $scope.showHistory222 = function (event, oSchema, oRecord) {
                console.log('xxxxxxx', event);
                event.stopPropagation();
                event.preventDefault();
                (new enlHistory()).show(oSchema);
                return;
            };

            $scope.$watch('rec', function (oRecord) {
                if (!oRecord) {
                    return;
                }
                $scope.$watch('schemas', function (schemas) {
                    if (!schemas) {
                        return;
                    }
                    var oSchema, schemaData;
                    for (var schemaId in $scope.schemas) {
                        oSchema = $scope.schemas[schemaId];
                        if (schemaData = oRecord.data[oSchema.id]) {
                            switch (oSchema.type) {
                                case 'longtext':
                                    oRecord.data[oSchema.id] = tmsSchema.txtSubstitute(schemaData);
                                    break;
                                case 'url':
                                    schemaData._text = tmsSchema.urlSubstitute(schemaData);
                                    break;
                                case 'file':
                                case 'voice':
                                    schemaData.forEach(function (oFile) {
                                        if (oFile.url && !angular.isObject(oFile.url)) {
                                            oFile.oUrl = oFile.url;
                                            oFile.url = $sce.trustAsResourceUrl(oFile.url);
                                        }
                                    });
                                    break;
                            }
                        }

                    }
                });
            });
        }]
    };
}]);