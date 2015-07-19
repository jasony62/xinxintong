xxtApp.controller('pageCtrl', ['$rootScope', '$scope', 'http2', '$timeout','$modal', function ($rootScope, $scope, http2, $timeout,$modal) {
    htmlEditor = ace.edit("htmlEditor");
    htmlEditor.setTheme("ace/theme/twilight");
    htmlEditor.getSession().setMode("ace/mode/html");
    cssEditor = ace.edit("cssEditor");
    cssEditor.setTheme("ace/theme/twilight");
    cssEditor.getSession().setMode("ace/mode/css");
    jsEditor = ace.edit("jsEditor");
    jsEditor.setTheme("ace/theme/twilight");
    jsEditor.getSession().setMode("ace/mode/javascript");

    $scope.save = function (name) {
        var p = {};
        switch (name) {
            case 'html':
                p[name] = encodeURIComponent(htmlEditor.getValue());
                break;
            case 'css':
                p[name] = encodeURIComponent(cssEditor.getValue());
                break;
            case 'js':
                p[name] = encodeURIComponent(jsEditor.getValue());
                break;
        }
        http2.post('/rest/code/update?id=' + $scope.page.id, p, function (rsp) {
            $rootScope.infomsg = '保存成功';
        });
    };
    $scope.update = function (name) {
        var p = {};
        p[name] = $scope.page[name];
        http2.post('/rest/code/update?id=' + $scope.page.id, p);
    };
    $scope.zoom = function (editor) {
        var curr = $scope.maxed;
        $scope.maxed = false;
        if (curr !== editor)
            $scope.maxed = editor;
        $timeout(function () { window[editor + 'Editor'].resize(); }, 100);
    };
    $scope.addExternal = function(type) {
        $modal.open({
            templateUrl:'external.html',
            controller:function($scope, $modalInstance) {
                $scope.external = {url:'', type:type};
                $scope.close = function() {
                    $modalInstance.dismiss('cancel');
                };
                $scope.confirm = function() {
                    $modalInstance.close($scope.external);
                };
            }
        }).result.then(function(rst){
            http2.post('/rest/code/addExternal?id=' + $scope.page.id, rst, function (rsp) {
                if (type === 'J') 
                    $scope.page.ext_js.push(rsp.data);
                else if (type === 'C')
                    $scope.page.ext_css.push(rsp.data);    
            });
        });
    };
    $scope.removeExternal = function(ext, index) {
        http2.get('/rest/code/delExternal?id=' + ext.id, function (rsp) {
            if (ext.type === 'J')
                $scope.page.ext_js.splice(index, 1);
            else if (ext.type === 'C')
                $scope.page.ext_css.splice(index, 1);
        });
    };
    $scope.$watch('jsonPage', function (nv) {
        $scope.page = JSON.parse(decodeURIComponent(nv.replace(/\+/g, '%20')));
        htmlEditor.setValue($scope.page.html);
        cssEditor.setValue($scope.page.css);
        jsEditor.setValue($scope.page.js);
    });
}]);
