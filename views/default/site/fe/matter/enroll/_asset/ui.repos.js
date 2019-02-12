'use strict';

require('../../../../../../../asset/js/xxt.ui.schema.js');

var ngMod = angular.module('repos.ui.enroll', ['schema.ui.xxt']);
ngMod.directive('tmsReposRecordData', ['$templateCache', function($templateCache) {
    return {
        restrict: 'A',
        template: require('./repos-record-data.html'),
        scope: {
            schemas: '=',
            rec: '=record',
            task: '=task',
            currentTab: '='
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
            $scope.vote = function(oRecData, event) {
                event.preventDefault();
                event.stopPropagation();

                if ($scope.task) {
                    http2.get(LS.j('task/vote', 'site') + '&data=' + oRecData.id + '&task=' + $scope.task.id).then(function(rsp) {
                        if (oRecData.voteResult) {
                            oRecData.voteResult.vote_num++;
                            oRecData.voteResult.vote_at = rsp.data[0].vote_at;
                        } else {
                            oRecData.vote_num++;
                            oRecData.vote_at = rsp.data[0].vote_at;
                        }
                        var remainder = rsp.data[1][0] - rsp.data[1][1];
                        if (remainder > 0) {
                            noticebox.success('还需要投出【' + remainder + '】票');
                        } else {
                            noticebox.success('已完成全部投票');
                        }
                    });
                }
            };
            $scope.unvote = function(oRecData, event) {
                event.preventDefault();
                event.stopPropagation();
                
                if ($scope.task) {
                    http2.get(LS.j('task/unvote', 'site') + '&data=' + oRecData.id + '&task=' + $scope.task.id).then(function(rsp) {
                        if (oRecData.voteResult) {
                            oRecData.voteResult.vote_num--;
                            oRecData.voteResult.vote_at = 0;
                        } else {
                            oRecData.vote_num--;
                            oRecData.vote_at = 0;
                        }
                        var remainder = rsp.data[0] - rsp.data[1];
                        if (remainder > 0) {
                            noticebox.success('还需要投出【' + remainder + '】票');
                        } else {
                            noticebox.success('已完成全部投票');
                        }
                    });
                }
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
                                        if (oFile.url && !angular.isObject(oFile.url)) {
                                            oFile.oUrl = oFile.url;
                                            oFile.url = $sce.trustAsResourceUrl(oFile.url);
                                        }
                                    });
                                    break;
                                case 'single':
                                case 'multiple':
                                case 'score':
                                    var _result = tmsSchema.optionsSubstitute(oSchema, schemaData);
                                    oRecord.data[oSchema.id] = angular.isObject(_result) ? _result : $sce.trustAsHtml(_result);
                                    break;
                            }
                        }

                    }
                });
            });
        }]
    };
}]);