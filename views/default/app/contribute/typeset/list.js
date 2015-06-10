xxtApp = angular.module('xxtApp', ['ui.tms']);
xxtApp.config(['$locationProvider', '$controllerProvider', function ($locationProvider, $controllerProvider) {
    $locationProvider.html5Mode(true);
    xxtApp.register = { controller: $controllerProvider.register };
}]);
xxtApp.controller('myArticleCtrl', ['$scope', '$location', function ($scope, $location) {
    $scope.phases = {'I':'投稿','R':'审核','T':'版面'};
    $scope.open = function (article) {
        location.href = '/rest/app/contribute/typeset/article?mpid=' + $scope.mpid + '&id=' + article.id;
    };
    $scope.titleOfChannels = function (article) {
        var titles = [];
        angular.forEach(article.channels, function (data) { titles.push(data.title); });
        return titles.join(',');
    };
    $scope.$watch('jsonParams', function (nv) {
        if (nv && nv.length) {
            var params = JSON.parse(decodeURIComponent(nv.replace(/\+/, '%20')));
            console.log('ready', params);
            $scope.mpid = params.mpid;
            $scope.articles = params.articles;
        }
    });
}]);
