'use strict';

var ngMod = angular.module('url.ui.xxt', ['http.ui.xxt']);
ngMod.service('tmsUrl', ['$q', '$uibModal', function($q, $uibModal) {
    function validateUrl(url) {
        return true;
    }
    this.fetch = function(oBeforeUrlData, oOptions) {
        var defer;
        defer = $q.defer();
        $uibModal.open({
            template: require('../html/ui-url.html'),
            controller: ['$scope', '$uibModalInstance', 'http2', function($scope, $mi, http2) {
                var _oData;
                $scope.data = _oData = {
                    text: '结果预览'
                };
                if (oBeforeUrlData) {
                    _oData.summary = {
                        title: oBeforeUrlData.title,
                        description: oBeforeUrlData.description,
                        url: oBeforeUrlData.url
                    };
                    _oData.url = oBeforeUrlData.url;
                }
                $scope.options = oOptions;
                $scope.cancel = function() {
                    $mi.dismiss();
                };
                $scope.ok = function() {
                    $mi.close(_oData);
                };
                $scope.crawlUrl = function(event) {
                    var url;
                    if (event && event.clipboardData) {
                        url = event.clipboardData.getData('Text');
                    } else {
                        url = _oData.url;
                    }
                    if (validateUrl(url)) {
                        http2.post('/rest/site/fe/matter/enroll/url', { url: url }).then(function(rsp) {
                            _oData.summary = rsp.data;
                        });
                    }
                };
                $scope.$watch('data.summary', function(nv) {
                    if (nv) {
                        var text;
                        text = '';
                        if (nv.title) {
                            text += '【' + nv.title + '】';
                        }
                        if (nv.description) {
                            text += nv.description;
                        }
                        text += '<a href="' + _oData.url + '">网页链接</a>';
                        _oData.text = text;
                    }
                }, true);
            }],
            backdrop: 'static'
        }).result.then(function(data) {
            defer.resolve(data);
        });

        return defer.promise;
    };
}]);