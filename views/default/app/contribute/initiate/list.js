xxtApp=angular.module('xxtApp', ['ui.tms']);
xxtApp.config(['$locationProvider', '$controllerProvider', function ($locationProvider, $controllerProvider) {
    $locationProvider.html5Mode(true);
    xxtApp.register = { controller: $controllerProvider.register };
}]);
xxtApp.controller('myArticleCtrl',['$scope','$location','http2',function($scope,$location,http2){
    $scope.phases = {'I':'投稿','R':'审核','T':'版面'};
    $scope.create = function() {
        var url, params = $location.search();
        url = '/rest/app/contribute/initiate/create';
        url += '?mpid='+params.mpid+'&entry='+params.entry;
        http2.get(url, function success(rsp){
            location.href = '/rest/app/contribute/initiate/article?mpid='+params.mpid+'&id='+rsp.data.id;
        });
    };
    $scope.open = function(article) {
        location.href = '/rest/app/contribute/initiate/article?mpid='+$scope.mpid+'&id='+article.id;
    };
    $scope.$watch('jsonParams', function(nv) {
        if (nv && nv.length) {
            var params = JSON.parse(decodeURIComponent(nv.replace(/\+/, '%20')));
            console.log('ready', params);
            $scope.mpid = params.mpid;
            $scope.needReview = params.needReview;
            $scope.articles = params.articles;
        }
    });
}]);
