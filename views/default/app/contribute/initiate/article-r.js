xxtApp.config(['$routeProvider', function ($routeProvider) {
    $routeProvider.when('/rest/app/contribute/initiate/article', {
        templateUrl: '/views/default/app/contribute/initiate/edit-r.html',
        controller: 'editCtrl',
    }).when('/rest/app/contribute/initiate/reviewlog', {
        templateUrl: '/views/default/app/contribute/initiate/reviewlog.html',
        controller: 'reviewlogCtrl',
    });
}]);
xxtApp.controller('initiateCtrl', ['$location', '$scope', 'Article', function ($location, $scope, Article) {
    $scope.subView = '';
    $scope.mpid = $location.search().mpid;
    $scope.id = $location.search().id;
    $scope.Article = new Article('initiate', $scope.mpid, '');
    $scope.back = function (event) {
        event.preventDefault();
        history.back();
    };
}]);
xxtApp.controller('editCtrl', ['$scope', '$modal', 'http2', 'Article', function ($scope, $modal, http2, Article) {
    $scope.$parent.subView = 'edit';
    $scope.Article.get($scope.id).then(function (data) {
        $scope.editing = data;
        var ele = document.querySelector('#content');
        if (ele.contentDocument && ele.contentDocument.body) {
            ele.contentDocument.body.innerHTML = $scope.editing.body;
        }
    });
}]);
xxtApp.controller('reviewlogCtrl', ['$scope', '$modal', 'http2', 'Article', function ($scope, $modal, http2, Article) {
    $scope.$parent.subView = 'reviewlog';
}]);