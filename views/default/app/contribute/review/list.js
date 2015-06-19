xxtApp.controller('reviewCtrl', ['$scope', '$location', function ($scope, $location) {
    $scope.mpid = $location.search().mpid;
    $scope.entry = $location.search().entry;
    $scope.phases = { 'I': '投稿', 'R': '审核', 'T': '版面' };
    $scope.approved = { 'Y': '通过', 'N': '未通过' };
}]);
xxtApp.controller('articleCtrl', ['$scope', 'Article', function ($scope, Article) {
    $scope.open = function (article) {
        location.href = '/rest/app/contribute/review/article?mpid=' + $scope.mpid + '&entry=' + $scope.entry + '&id=' + article.id;
    };
    $scope.Article = new Article('review', $scope.mpid, $scope.entry);
    $scope.Article.list().then(function (data) {
        $scope.articles = data;
    });
}]);
xxtApp.controller('newsCtrl', ['$scope', 'News', function ($scope, News) {
    $scope.open = function (news) {
        location.href = '/rest/app/contribute/review/news?mpid=' + $scope.mpid + '&entry=' + $scope.entry + '&id=' + news.id;
    };
    $scope.News = new News('review', $scope.mpid, $scope.entry);
    $scope.News.list().then(function (data) {
        $scope.news = data;
    });
}]);