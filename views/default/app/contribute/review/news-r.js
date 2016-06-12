xxtApp.controller('myNewsCtrl', ['$location', '$scope', 'http2', 'News', function ($location, $scope, http2, News) {
    $scope.back = function (event) {
        event.preventDefault();
        history.back();
    };
    $scope.return = function () {
        $scope.News.return($scope.editing).then(function () {
            location.href = '/rest/app/contribute/review?mpid=' + $scope.mpid + '&entry=' + $scope.entry;
        });
    };
    $scope.mpid = $location.search().mpid;
    $scope.entry = $location.search().entry;
    $scope.id = $location.search().id;
    $scope.News = new News('review', $scope.mpid, $scope.entry);
    $scope.News.get($scope.id).then(function (data) {
        $scope.editing = data;
    });
}]);
