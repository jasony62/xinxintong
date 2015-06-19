xxtApp.controller('initiateCtrl', ['$scope', '$location', 'Article', function ($scope, $location, Article) {
    $scope.phases = { 'I': '投稿', 'R': '审核', 'T': '版面' };
    $scope.approved = { 'Y': '通过', 'N': '未通过' };
    $scope.create = function () {
        $scope.Article.create().then(function (data) {
            location.href = '/rest/app/contribute/initiate/article?mpid=' + $scope.mpid + '&entry=' + $scope.entry + '&id=' + data.id;
        });
    };
    $scope.open = function (article) {
        location.href = '/rest/app/contribute/initiate/article?mpid=' + $scope.mpid + '&entry=' + $scope.entry + '&id=' + article.id;
    };
    $scope.mpid = $location.search().mpid;
    $scope.entry = $location.search().entry;
    $scope.Article = new Article('initiate', $scope.mpid, $scope.entry);
    $scope.Article.list().then(function (data) {
        $scope.articles = data;
    });
}]);
