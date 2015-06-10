xxtApp.controller('myArticleCtrl', ['$location', '$scope', 'Article', function ($location, $scope, Article) {
    $scope.mpid = $location.search().mpid;
    $scope.id = $location.search().id;
    $scope.Article = new Article('initiate', $scope.mpid, '');
    $scope.Article.get($scope.id).then(function (data) {
        $scope.editing = data;
        var ele = document.querySelector('#content');
        if (ele.contentDocument && ele.contentDocument.body) {
            ele.contentDocument.body.innerHTML = $scope.editing.body;
        }
    });
    $scope.back = function (event) {
        event.preventDefault();
        history.back();
    };
}]);