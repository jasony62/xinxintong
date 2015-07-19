xxtApp.controller('typesetCtrl', ['$scope', '$location', function ($scope, $location) {
    $scope.mpid = $location.search().mpid;
    $scope.entry = $location.search().entry;
    $scope.phases = { 'I': '投稿', 'R': '审核', 'T': '版面' };
}]);
xxtApp.controller('articleCtrl', ['$scope', 'Article', 'News', function ($scope, Article, News) {
    $scope.checkedArticles = [];
    $scope.open = function (event, article) {
        if (event.target.tagName !== 'INPUT')
            location.href = '/rest/app/contribute/typeset/article?mpid=' + $scope.mpid + '&id=' + article.id;
    };
    $scope.checkArticle = function (o) {
        if (o.__checked) {
            delete o.__checked;
            $scope.checkedArticles.splice($scope.checkedArticles.indexOf(o), 1);
        } else {
            o.__checked = true;
            $scope.checkedArticles.push(o);
        }
    };
    $scope.createNews = function () {
        if ($scope.checkedArticles.length === 0) {
            alert('没有选择放入版面的文稿');
            return;
        }
        var i, articleIds = [];
        for (i = 0; i < $scope.checkedArticles.length; i++) {
            articleIds.push($scope.checkedArticles[i].id);
        }
        $scope.News.create(articleIds).then(function (data) {
            location.href = '/rest/app/contribute/typeset/news?mpid=' + $scope.mpid + '&entry=' + $scope.entry + '&id=' + data;
        });
    };
    $scope.titleOfChannels = function (article) {
        var titles = [];
        angular.forEach(article.channels, function (data) { titles.push(data.title); });
        return titles.join(',');
    };
    $scope.Article = new Article('typeset', $scope.mpid, $scope.entry);
    $scope.News = new News('typeset', $scope.mpid, $scope.entry);
    $scope.Article.list().then(function (data) {
        $scope.articles = data;
    });
}]);
xxtApp.controller('newsCtrl', ['$scope', 'News', function ($scope, News) {
    $scope.News = new News('typeset', $scope.mpid, $scope.entry);
    $scope.open = function (news) {
        location.href = '/rest/app/contribute/typeset/news?mpid=' + $scope.mpid + '&entry=' + $scope.entry + '&id=' + news.id;
    };
    $scope.News.list().then(function (data) {
        $scope.news = data;
    });
}]);
