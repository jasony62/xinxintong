'use strict';

require('../../../../../../../asset/js/xxt.ui.schema.js');

var ngMod = angular.module('repos.ui.enroll', ['schema.ui.xxt']);
ngMod.directive('tmsReposRecordData', ['$templateCache', function ($templateCache) {
    return {
        restrict: 'A',
        template: require('./repos-record-data.html'),
        scope: {
            schemas: '=',
            rec: '=record',
            task: '=task',
            pendingVotes: "=",
            currentTab: '='
        },
        controller: ['$scope', '$sce', '$location', 'tmsLocation', 'http2', 'noticebox', 'tmsSchema', function ($scope, $sce, $location, LS, http2, noticebox, tmsSchema) {
            var fnVote = function (oRecData, voteAt, remainder) {
                if (oRecData.voteResult) {
                    oRecData.voteResult.vote_num++;
                    oRecData.voteResult.vote_at = voteAt;
                } else {
                    oRecData.vote_num++;
                    oRecData.vote_at = voteAt;
                }
                if (undefined !== remainder) {
                    if (remainder > 0) {
                        noticebox.success('还需要投出【' + remainder + '】票');
                    } else {
                        noticebox.success('已完成全部投票');
                    }
                }
            };
            var fnUnvote = function (oRecData, remainder) {
                if (oRecData.voteResult) {
                    oRecData.voteResult.vote_num--;
                    oRecData.voteResult.vote_at = 0;
                } else {
                    oRecData.vote_num--;
                    oRecData.vote_at = 0;
                }
                if (undefined !== remainder) {
                    if (remainder > 0) {
                        noticebox.success('还需要投出【' + remainder + '】票');
                    } else {
                        noticebox.success('已完成全部投票');
                    }
                }
            };
            $scope.vote = function (oRecData, event) {
                event.preventDefault();
                event.stopPropagation();

                if ($scope.task) {
                    if ($scope.pendingVotes && angular.isArray($scope.pendingVotes)) {
                        fnVote(oRecData, new Date() * 1);
                        if (-1 === $scope.pendingVotes.indexOf(oRecData))
                            $scope.pendingVotes.push(oRecData);
                    } else {
                        http2.get(LS.j('task/vote', 'site') + '&data=' + oRecData.id + '&task=' + $scope.task.id).then(function (rsp) {
                            fnVote(oRecData, rsp.data[0].vote_at, rsp.data[1][0] - rsp.data[1][1]);
                        });
                    }
                }
            };
            $scope.unvote = function (oRecData, event) {
                event.preventDefault();
                event.stopPropagation();

                if ($scope.task) {
                    if ($scope.pendingVotes && angular.isArray($scope.pendingVotes)) {
                        fnUnvote(oRecData);
                        if (-1 === $scope.pendingVotes.indexOf(oRecData))
                            $scope.pendingVotes.push(oRecData);
                    } else {
                        http2.get(LS.j('task/unvote', 'site') + '&data=' + oRecData.id + '&task=' + $scope.task.id).then(function (rsp) {
                            fnUnvote(oRecData, rsp.data[1][0] - rsp.data[1][1]);
                        });
                    }
                }
            };
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
            }
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