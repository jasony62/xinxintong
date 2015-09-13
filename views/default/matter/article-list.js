angular.module('xxt', []).controller('ctrl', ['$scope', '$http', '$q', function($scope, $http, $q) {
    var ls, mpid, tagid;
    ls = location.search;
    mpid = ls.match(/mpid=([^&]*)/)[1];
    tagid = ls.match(/tagid=([^&]*)/)[1];
    var getArticles = function() {
        var deferred = $q.defer();
        $http.get('/rest/mi/article/list?mpid=' + mpid + '&tagid=' + tagid).success(function(rsp) {
            var articles;
            articles = rsp.data.articles;
            angular.forEach(articles, function(a) {
                if (a.pic) {
                    a._style = {
                        backgroundImage: 'url(' + a.pic + ')'
                    };
                }
            });
            $scope.articles = articles;
            deferred.resolve();
        });
        return deferred.promise;
    };
    $scope.open = function(opened) {
        location.href = opened.url;
    };
    $scope.loading = true;
    getArticles().then(function() {
        $scope.loading = false;
    });
}]);