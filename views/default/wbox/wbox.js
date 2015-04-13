angular.module('wbox.xxt',['ui.tms','matters.xxt']).
config(['$locationProvider',function($locationProvider){
    $locationProvider.html5Mode(true);
}]).
controller('WboxCtrl',['$scope','$http',function($scope,$http){
    $scope.errorMsg = '';
    $scope.getTags = function() {
        $http.get('/rest/wbox/article/tag').
        success(function(rsp) {
            $scope.tags = rsp.data;
        });
    };
}]).
controller('AuthCtrl',['$scope','$http',function($scope,$http){
    $scope.auth = function(){
        var t = (new Date()).getTime();
        $http.post('/rest/wbox/auth?_='+t,{'ac':$scope.auth_code}).
        success(function(rsp){
            if (rsp.err_code != 0) {
                $scope.$parent.errorMsg = rsp.err_msg;
                return;
            }
            window.location.href='/rest/wbox/list?_='+t;
        });
    };
    $scope.$watch('auth_mode', function(nv,ov){
        if (nv == 1)
            $('[ng-model="auth_code"]').focus();
    });
    $scope.keypress = function(event) {
        if (event.keyCode === 13) {
            $scope.auth();
        }
    };
}]).
controller('ListCtrl', ['$scope','$http',function($scope,$http){
    var getArticles = function() {
        var page = $scope.page.current,
        size = $scope.page.size,
        fields = 'id,title,summary,modify_at,approved,tag',
        tag = $scope.selectedTagsId,
        order = $scope.order;
        $http.get('/rest/wbox/list?page='+page+'&size='+size+'&fields='+fields+'&tag='+tag+'&order='+order,{
            headers:{'ACCEPT':'application/json'}
        }).
        success(function(rsp) {
            $scope.articles = rsp.data[0];
            rsp.data[1] && ($scope.page.total = rsp.data[1]);
        });
    };
    $scope.selectedTags = [];
    $scope.selectedTagsId = [];
    $scope.order = 'time';
    $scope.page = {current:1,size:30};
    $scope.create = function() {
        $http.post('/rest/wbox/article/create').
        success(function(rsp) {
            window.location.href = '/page/wbox/article?id='+rsp.data.id;
        });
    };
    $scope.edit = function(article) {
        window.location.href = '/page/wbox/article?id='+article.id;
    };
    $scope.remove = function(article, index){
        $http.post('/rest/wbox/remove?id='+article.id).
        success(function(rsp) {
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
    $scope.getTags();
    getArticles();
}]);
