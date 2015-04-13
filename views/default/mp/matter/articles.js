xxtApp.controller('ArticleCtrl', ['$scope','http2',function($scope,http2){
    var getArticles = function() {
        var page = $scope.page.at,
        size = $scope.page.size,
        tag = $scope.selectedTagsId,
        order = $scope.order,
        url = '/rest/mp/matter/article?page='+page+'&size='+size+'&tag='+tag+'&order='+order;
        if ($scope.fromParent && $scope.fromParent==='Y')
            url += '&src=p';
        http2.get(url, function(rsp) {
            $scope.articles = rsp.data[0];
            rsp.data[1] && ($scope.page.total = rsp.data[1]);
        });
    };
    var getInitData = function() {
        http2.get('/rest/mp/matter/tag?resType=article', function(rsp) {
            $scope.tags = rsp.data;
            getArticles();
        });
    };
    $scope.selectedTags = [];
    $scope.selectedTagsId = [];
    $scope.order = 'time';
    $scope.page = {at:1,size:30};
    $scope.create = function() {
        http2.get('/rest/mp/matter/article/create', function(rsp) {
            location.href = '/page/mp/matter/article?id='+rsp.data.id;
        });
    };
    $scope.edit = function(article) {
        location.href = '/page/mp/matter/article?id='+article.id;
    };
    $scope.remove = function(event, article, index){
        event.preventDefault();
        event.stopPropagation();
        http2.get('/rest/mp/matter/article/remove?id='+article.id, function(rsp) {
            $scope.articles.splice(index, 1);
        });
    };
    $scope.doSearch = function() {
        getArticles();
    };
    $scope.$on('xxt.combox.done', function(event, aSelected){
        for (var i in aSelected) {
            if ($scope.selectedTags.indexOf(aSelected[i].title) === -1) {
                $scope.selectedTags.push(aSelected[i].title);
                $scope.selectedTagsId.push(aSelected[i].id);
            }
        }
        getArticles();
    });
    $scope.$on('xxt.combox.del', function(event, removed){
        var i = $scope.selectedTags.indexOf(removed);
        $scope.selectedTags.splice(i, 1);
        $scope.selectedTagsId.splice(i, 1);
        getArticles();
    });
    getInitData();
}]);
