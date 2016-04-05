ngApp.controller('ctrlReview', ['$scope', '$location', function($scope, $location) {
    $scope.siteId = $location.search().site;
    $scope.entry = $location.search().entry;
    $scope.phases = {
        'I': '投稿',
        'R': '审核',
        'T': '版面'
    };
    $scope.approved = {
        'Y': '通过',
        'N': '未通过'
    };
}]);
ngApp.controller('ctrlArticle', ['$scope', 'Article', function($scope, Article) {
    $scope.open = function(article) {
        location.href = '/rest/site/fe/matter/contribute/review/article?site=' + $scope.siteId + '&entry=' + $scope.entry + '&id=' + article.id;
    };
    $scope.Article = new Article('review', $scope.siteId, $scope.entry);
    $scope.Article.list().then(function(data) {
        $scope.articles = data;
    });
}]);
ngApp.controller('newsCtrl', ['$scope', 'News', function($scope, News) {
    $scope.open = function(news) {
        location.href = '/rest/site/fe/matter/contribute/review/news?site=' + $scope.siteId + '&entry=' + $scope.entry + '&id=' + news.id;
    };
    $scope.News = new News('review', $scope.siteId, $scope.entry);
    $scope.News.list().then(function(data) {
        $scope.news = data;
    });
}]);