xxtApp=angular.module('xxtApp', ['ui.tms','matters.xxt']);
xxtApp.controller('myArticleCtrl',['$scope','$http', function($scope,$http){
    $scope.editing = {
        pic:'',
        title:'aaaa',
        body:'aaa'
    };
}]);
