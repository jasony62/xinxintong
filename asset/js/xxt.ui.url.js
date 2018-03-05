'use strict';

var ngMod = angular.module('url.ui.xxt', ['http.ui.xxt']);
ngMod.service('tmsUrl', ['$q', '$uibModal', function($q, $uibModal) {
    function validateUrl(url) {
        return true;
    }
    var HtmlTemplate;
    HtmlTemplate = '<div class="modal-header">';
    HtmlTemplate += '<h5 class="modal-title">上传链接</h5>';
    HtmlTemplate += '</div>';
    HtmlTemplate += '<div class="modal-body">';
    HtmlTemplate += '<form>';
    HtmlTemplate += '<div class="form-group">';
    HtmlTemplate += '<div class="input-group">';
    HtmlTemplate += '<input type="text" ng-paste="crawlUrl($event)" class="form-control" placeholder="1、请将链接粘贴到这里或输入" ng-model="data.url">';
    HtmlTemplate += '<div class="input-group-btn"><button class="btn btn-default" ng-click="crawlUrl()">刷新</button></div>';
    HtmlTemplate += '</div>';
    HtmlTemplate += '</div>';
    HtmlTemplate += '<div class="form-group">';
    HtmlTemplate += '<input type="text" class="form-control" placeholder="2、复制链接后这里将显示页面的标题，可进行修改" ng-model="data.summary.title">';
    HtmlTemplate += '</div>'
    HtmlTemplate += '<div class="form-group">';
    HtmlTemplate += '<textarea class="form-control" placeholder="3、复制链接后这里将显示页面的摘要描述（如果提供），可进行修改" ng-model="data.summary.description" rows="4"></textarea>';
    HtmlTemplate += '</div>';
    HtmlTemplate += '<div class="form-group">';
    HtmlTemplate += '<div class="form-control" ng-bind-html="data.text" style="height:auto;min-height:34px;"></div>';
    HtmlTemplate += '</div>';
    HtmlTemplate += '</form>';
    HtmlTemplate += '</div>';
    HtmlTemplate += '<div class="modal-footer">';
    HtmlTemplate += '<button class="btn btn-default" ng-click="cancel()">关闭</button>';
    HtmlTemplate += '<button class="btn btn-default" ng-click="ok()">完成</button>';
    HtmlTemplate += '</div>';
    this.fetch = function(oBeforeUrlData) {
        var defer;
        defer = $q.defer();
        $uibModal.open({
            template: HtmlTemplate,
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
                        console.log('ttt', text);
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