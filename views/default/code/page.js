xxtApp.controller('pageCtrl',['$rootScope','$scope','http2','$timeout',function($rootScope,$scope, http2,$timeout){
    htmlEditor = ace.edit("htmlEditor");
    htmlEditor.setTheme("ace/theme/twilight");
    htmlEditor.getSession().setMode("ace/mode/html");
    cssEditor = ace.edit("cssEditor");
    cssEditor.setTheme("ace/theme/twilight");
    cssEditor.getSession().setMode("ace/mode/css");
    jsEditor = ace.edit("jsEditor");
    jsEditor.setTheme("ace/theme/twilight");
    jsEditor.getSession().setMode("ace/mode/javascript");

    $scope.save = function(name) {
        var p = {};
        switch (name) {
            case 'html':
                p[name] = htmlEditor.getValue();
                break;
            case 'css':
                p[name] = cssEditor.getValue();
                break;
            case 'js':
                p[name] = jsEditor.getValue();
                break;
        }
        http2.post('/rest/code/update?id='+$scope.page.id, p, function(rsp){
            $rootScope.infomsg = '保存成功';
        });
    };
    $scope.update = function(name){
        var p = {};
        p[name] = $scope.page[name];
        http2.post('/rest/code/update?id='+$scope.page.id, p);
    };
    $scope.zoom = function(editor) {
        var curr = $scope.maxed;
        $scope.maxed = false; 
        if (curr !== editor)
            $scope.maxed = editor; 
        $timeout(function(){window[editor+'Editor'].resize();},100);
    };
    $scope.$watch('jsonPage', function(nv){
        $scope.page = JSON.parse(decodeURIComponent(nv.replace(/\+/g,'%20')));
        htmlEditor.setValue($scope.page.html);
        cssEditor.setValue($scope.page.css);
        jsEditor.setValue($scope.page.js);
    });
}]);
