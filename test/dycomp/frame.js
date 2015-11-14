app = angular.module('app', ['ngSanitize']);
app.config(['$controllerProvider', function($cp) {
    app.register = {
        controller: $cp.register
    };
}]);
app.directive('dynaComponent', ['$compile', '$http', function($compile, $http) {
    return {
        restrict: 'EA',
        replace: true,
        compile: function(ele, attrs) {
            console.log('compile...');
            var html = ele.html();
            ele.html('');
            return {
                post:function(scope, ele, attrs) {
                    console.log('post...');
                    scope.$watch(attrs.url, function(url){
                        if (url && url.length) {
                            $http.get(url).success(function(component) {
                                if (component.css && component.css.length) {
                                    var style = document.createElement('style');
                                    style.type = 'text/css';
                                    style.innerHTML = component.css;
                                    document.querySelector('head').appendChild(style);
                                }
                                if (component.js && component.js.length) {
                                    (function loadjs() {
                                        eval(component.js);
                                    })();
                                }
                                if (component.html && component.html.length) {
                                    ele.html(component.html);
                                    $compile(ele.contents())(scope);
                                } else {
                                    ele.html(html);
                                    $compile(ele.contents())(scope);
                                }
                            });
                        } else {
                            ele.html(html);
                            $compile(ele.contents())(scope);
                        }
                    });
                }
            }
        }
    };
}]);
app.controller('ctrl', ['$scope', '$http', function($scope, $http) {
    console.log('controller...');
    $scope.data = 'xyz';
    $scope.test = function() {
        console.log('test...');
        return 'test';
    };
    $scope.test2 = function() {
        console.log('test2...');
        return 'test2';
    };
}]);