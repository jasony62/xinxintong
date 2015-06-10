xxtApp=angular.module('xxtApp', ['ui.tms']);
xxtApp.config(['$locationProvider', '$controllerProvider', function ($locationProvider, $controllerProvider) {
    $locationProvider.html5Mode(true);
    xxtApp.register = { controller: $controllerProvider.register };
}]);
xxtApp.controller('myArticleCtrl',['$scope','$location',function($scope,$location){
    $scope.phases = {'I':'投稿','R':'审核','T':'版面'};
    $scope.open = function(article) {
        location.href = '/rest/app/contribute/review/article?mpid='+$scope.mpid+'&id='+article.id;
    };
    $scope.$watch('jsonParams', function(nv) {
        if (nv && nv.length) {
            var params = JSON.parse(decodeURIComponent(nv.replace(/\+/, '%20')));
            console.log('ready', params);
            $scope.mpid = params.mpid;
            $scope.articles = params.articles;
        }
    });
}]);
