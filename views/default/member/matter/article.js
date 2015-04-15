xxtApp=angular.module('xxtApp', ['ui.tms','matters.xxt']);
xxtApp.factory('Article', function($q,http2){
    var Article = function(mpid) {
        this.mpid = mpid;
        this.baseUrl ='/rest/member/matter/article/';
    };
    Article.prototype.create = function() {
        var deferred = $q.defer();
        var promise = deferred.promise;
        var url = this.baseUrl + 'create';
        url += '?mpid='+this.mpid;
        http2.get(url, function success(rsp){
            deferred.resolve(rsp.data);
        });

        return promise;
    };
    return Article;
});
xxtApp.controller('myArticleCtrl',['$scope','Article',function($scope,Article){
    $scope.create = function() {
        $scope.Article.create()
        .then(function success(newArticle){
            $scope.editing = newArticle;
            $scope.articles.splice(0,0,newArticle);
        });
    };
    $scope.$watch('jsonParams', function(nv) {
        if (nv && nv.length) {
            var params = JSON.parse(decodeURIComponent(nv.replace(/\+/, '20%')));
            $scope.articles = params.articles;
            $scope.Article = new Article(params.mpid);
        }
    });
}]);
