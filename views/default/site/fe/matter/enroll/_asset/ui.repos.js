'use strict';

require('../../../../../../../asset/js/xxt.ui.schema.js');

var ngMod = angular.module('repos.ui.enroll', ['schema.ui.xxt']);
ngMod.directive('tmsReposRecordData', ['$templateCache', function($templateCache) {
    return {
        restrict: 'A',
        template: require('./repos-record-data.html'),
        scope: {
            schemas: '=',
            rec: '=record'
        },
        controller: ['$scope', '$sce', '$location', 'tmsLocation', 'http2', 'noticebox', 'tmsSchema', function($scope, $sce, $location, LS, http2, noticebox, tmsSchema) {
            $scope.coworkRecord = function(oRecord) {
                var url;
                url = LS.j('', 'site', 'app');
                url += '&ek=' + oRecord.enroll_key;
                url += '&page=cowork';
                url += '#cowork';
                location.href = url;
            };
            $scope.vote = function(oRecData) {
                http2.get(LS.j('data/vote', 'site') + '&data=' + oRecData.id).then(function(rsp) {
                    oRecData.vote_num++;
                    oRecData.vote_at = rsp.data.vote_at;
                    noticebox.success('完成投票！');
                });
            };
            $scope.unvote = function(oRecData) {
                http2.get(LS.j('data/unvote', 'site') + '&data=' + oRecData.id).then(function(rsp) {
                    oRecData.vote_num--;
                    oRecData.vote_at = 0;
                    noticebox.success('撤销投票！');
                });
            };
            $scope.open = function(file) {
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
                                    oRecord.data[oSchema.id] = tmsSchema.txtSubstitute(schemaData);
                                    break;
                                case 'url':
                                    schemaData._text = tmsSchema.urlSubstitute(schemaData);
                                    break;
                                case 'file':
                                case 'voice':
                                    schemaData.forEach(function(oFile) {
                                        if (oFile.url) {
                                            oFile.oUrl = oFile.url;
                                            oFile.url = $sce.trustAsResourceUrl(oFile.url);
                                        }
                                    });
                                    break;
                                case 'single':
                                case 'multiple':
                                case 'score':
                                    oRecord.data[oSchema.id] = $sce.trustAsHtml(tmsSchema.optionsSubstitute(oSchema, schemaData));
                                    break;
                            }
                        }

                    }
                });
            });
        }]
    };
}]);