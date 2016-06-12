xxtApp.controller('pageCtrl', ['$rootScope', '$scope', 'http2', '$timeout', '$uibModal', function($rootScope, $scope, http2, $timeout, $uibModal) {
    htmlEditor = ace.edit("htmlEditor");
    htmlEditor.setTheme("ace/theme/twilight");
    htmlEditor.getSession().setMode("ace/mode/html");
    cssEditor = ace.edit("cssEditor");
    cssEditor.setTheme("ace/theme/twilight");
    cssEditor.getSession().setMode("ace/mode/css");
    jsEditor = ace.edit("jsEditor");
    jsEditor.setTheme("ace/theme/twilight");
    jsEditor.getSession().setMode("ace/mode/javascript");
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
        p[name] = encodeURIComponent(window[name + 'Editor'].getValue());
        http2.post('/rest/code/update?id=' + $scope.page.id, p, function(rsp) {
            $rootScope.infomsg = '保存成功';
            $scope[name + 'Changed'] = false;
        });
    };
    $scope.update = function(name) {
        var p = {};
        p[name] = $scope.page[name];
        http2.post('/rest/code/update?id=' + $scope.page.id, p);
    };
    $scope.zoom = function(editor) {
        var curr = $scope.maxed;
        $scope.maxed = false;
        if (curr !== editor)
            $scope.maxed = editor;
        $timeout(function() {
            window[editor + 'Editor'].resize();
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
            http2.post('/rest/code/addExternal?id=' + $scope.page.id, rst, function(rsp) {
                if (type === 'J')
                    $scope.page.ext_js.push(rsp.data);
                else if (type === 'C')
                    $scope.page.ext_css.push(rsp.data);
            });
        });
    };
    $scope.removeExternal = function(ext, index) {
        http2.get('/rest/code/delExternal?id=' + ext.id, function(rsp) {
            if (ext.type === 'J')
                $scope.page.ext_js.splice(index, 1);
            else if (ext.type === 'C')
                $scope.page.ext_css.splice(index, 1);
        });
    };
    $scope.$watch('jsonPage', function(nv) {
        $scope.page = JSON.parse(decodeURIComponent(nv.replace(/\+/g, '%20')));
        htmlEditor.setValue($scope.page.html);
        htmlEditor.getSession().setUndoManager(new ace.UndoManager());
        htmlEditor.on('input', function() {
            if (htmlEditor.session.getUndoManager().hasUndo()) {
                $scope.$apply(function() {
                    $scope.htmlChanged = true;
                });
            } else {
                $scope.$apply(function() {
                    $scope.htmlChanged = false;
                });
            }
        });
        cssEditor.setValue($scope.page.css);
        cssEditor.getSession().setUndoManager(new ace.UndoManager());
        cssEditor.on('input', function() {
            if (cssEditor.session.getUndoManager().hasUndo()) {
                $scope.$apply(function() {
                    $scope.cssChanged = true;
                });
            } else {
                $scope.$apply(function() {
                    $scope.cssChanged = false;
                });
            }
        });
        jsEditor.setValue($scope.page.js);
        jsEditor.getSession().setUndoManager(new ace.UndoManager());
        jsEditor.on('input', function() {
            if (jsEditor.session.getUndoManager().hasUndo()) {
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