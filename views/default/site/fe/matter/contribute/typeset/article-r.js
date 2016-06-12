xxtApp.controller('myArticleCtrl', ['$location', '$scope', 'http2', 'Article', function($location, $scope, http2, Article) {
    $scope.back = function(event) {
        event.preventDefault();
        history.back();
    };
    $scope.titleOfChannels = function(article) {
        if (article === undefined) return;
        var titles = [];
        angular.forEach(article.channels, function(data) {
            titles.push(data.title);
        });
        return titles.join(',');
    };
    $scope.mpid = $location.search().mpid;
    $scope.id = $location.search().id;
    $scope.Article = new Article('typeset', $scope.mpid, '');
    $scope.Article.get($scope.id).then(function(data) {
        $scope.editing = data;
        var ele = document.querySelector('#content');
        if (ele.contentDocument && ele.contentDocument.body)
            ele.contentDocument.body.innerHTML = data.body;
        $scope.Article.mpaccounts().then(function(data) {
            var target_mps2 = [];
            if ($scope.editing.target_mps.indexOf('[') === 0) {
                var mps = JSON.parse($scope.editing.target_mps);
                angular.forEach(data, function(mpa) {
                    mps.indexOf(mpa.id) !== -1 && target_mps2.push(mpa.name);
                });
                $scope.targetMps = target_mps2.join(',');
            }
        });
    });
    $scope.Article.channels().then(function(data) {
        $scope.channels = data;
    });
}]);