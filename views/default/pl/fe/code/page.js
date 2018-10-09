'use strict';
var ngApp = angular.module('app', ['ui.tms', 'ui.bootstrap', 'http.ui.xxt', 'notice.ui.xxt']);
ngApp.config(['$locationProvider', '$controllerProvider', function($locationProvider, $controllerProvider) {
    $locationProvider.html5Mode(true);
    ngApp.register = {
        controller: $controllerProvider.register
    };
}]);
ngApp.controller('codeCtrl', ['$rootScope', function($rootScope) {
    $rootScope.$on('xxt.notice-box.timeout', function(event, name) {
        if (name === 'info') $rootScope.infomsg = '';
        else if (name === 'err') $rootScope.errmsg = '';
    });
}]);
ngApp.controller('pageCtrl', ['$rootScope', '$scope', '$location', 'http2', '$timeout', '$uibModal', function($rootScope, $scope, $location, http2, $timeout, $uibModal) {
    var oEditors = {};
    oEditors.html = ace.edit("htmlEditor");
    oEditors.html.setTheme("ace/theme/twilight");
    oEditors.html.getSession().setMode("ace/mode/html");
    oEditors.css = ace.edit("cssEditor");
    oEditors.css.setTheme("ace/theme/twilight");
    oEditors.css.getSession().setMode("ace/mode/css");
    oEditors.js = ace.edit("jsEditor");
    oEditors.js.setTheme("ace/theme/twilight");
    oEditors.js.getSession().setMode("ace/mode/javascript");
    $scope.htmlChanged = false;
    $scope.cssChanged = false;
    $scope.jsChanged = false;
    window.onbeforeunload = function(e) {
        var message;
        if ($scope.htmlChanged || $scope.cssChanged || $scope.jsChanged) {
            message = '已经修改的代码还没有保存';
            e = e || window.event;
            if (e) {
                e.returnValue = message;
            }
            return message;
        }
    };
    $scope.save = function(name) {
        var p = {};
        p[name] = encodeURIComponent(oEditors[name].getValue());
        http2.post('/rest/pl/fe/code/update?id=' + $scope.page.id, p).then(function(rsp) {
            $rootScope.infomsg = '保存成功';
            $scope[name + 'Changed'] = false;
        });
    };
    $scope.update = function(name) {
        var p = {};
        p[name] = $scope.page[name];
        http2.post('/rest/pl/fe/code/update?id=' + $scope.page.id, p);
    };
    $scope.zoom = function(editor) {
        var curr = $scope.maxed;
        $scope.maxed = false;
        if (curr !== editor)
            $scope.maxed = editor;
        $timeout(function() {
            oEditors[editor].resize();
        }, 100);
    };
    $scope.addExternal = function(type) {
        $uibModal.open({
            templateUrl: 'external.html',
            controller: ['$scope', '$uibModalInstance', function($scope, $mi) {
                $scope.external = {
                    url: '',
                    type: type
                };
                $scope.close = function() {
                    $mi.dismiss('cancel');
                };
                $scope.confirm = function() {
                    $mi.close($scope.external);
                };
            }]
        }).result.then(function(rst) {
            http2.post('/rest/pl/fe/code/addExternal?id=' + $scope.page.id, rst).then(function(rsp) {
                if (type === 'J')
                    $scope.page.ext_js.push(rsp.data);
                else if (type === 'C')
                    $scope.page.ext_css.push(rsp.data);
            });
        });
    };
    $scope.removeExternal = function(ext, index) {
        http2.get('/rest/pl/fe/code/delExternal?id=' + ext.id).then(function(rsp) {
            if (ext.type === 'J')
                $scope.page.ext_js.splice(index, 1);
            else if (ext.type === 'C')
                $scope.page.ext_css.splice(index, 1);
        });
    };
    http2.get('/rest/pl/fe/code/get?site=' + $location.search().site + '&name=' + $location.search().name).then(function(rsp) {
        $scope.page = rsp.data;
        oEditors.html.setValue($scope.page.html);
        oEditors.html.getSession().setUndoManager(new ace.UndoManager());
        oEditors.html.on('input', function() {
            if (oEditors.html.session.getUndoManager().hasUndo()) {
                $scope.$apply(function() {
                    $scope.htmlChanged = true;
                });
            } else {
                $scope.$apply(function() {
                    $scope.htmlChanged = false;
                });
            }
        });
        oEditors.css.setValue($scope.page.css);
        oEditors.css.getSession().setUndoManager(new ace.UndoManager());
        oEditors.css.on('input', function() {
            if (oEditors.css.session.getUndoManager().hasUndo()) {
                $scope.$apply(function() {
                    $scope.cssChanged = true;
                });
            } else {
                $scope.$apply(function() {
                    $scope.cssChanged = false;
                });
            }
        });
        oEditors.js.setValue($scope.page.js);
        oEditors.js.getSession().setUndoManager(new ace.UndoManager());
        oEditors.js.on('input', function() {
            if (oEditors.js.session.getUndoManager().hasUndo()) {
                $scope.$apply(function() {
                    $scope.jsChanged = true;
                });
            } else {
                $scope.$apply(function() {
                    $scope.jsChanged = false;
                });
            }
        });
    });
}]);