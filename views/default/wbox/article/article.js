angular.module('wbox.xxt',['ui.tms','matters.xxt']).
config(['$locationProvider',function($locationProvider){
    $locationProvider.html5Mode(true);
}]).
controller('WboxCtrl',['$scope','$http',function($scope,$http){
    $scope.errorMsg = '';
    $scope.back = function(){
        window.location.href='/rest/wbox/list';
    };
}]).
controller('ArticleCtrl',['$scope','$http','$location',function($scope,$http,$location){
    $scope.id = $location.search().id;
    $scope.$parent.requireBack = true;
    $scope.getTags = function() {
        $http.get('/rest/wbox/article/tag').
        success(function(rsp) {
            $scope.tags = rsp.data;
        });
    };
}]).
controller('EditCtrl',['$scope','$http',function($scope,$http){
    $scope.update = function(n) {
        var nv = [n, $scope.editing[n]];
        $http.post('/rest/wbox/article/update?id='+$scope.editing.id, nv).
        success(function(rsp){
            $scope.modified = false;
        });
    };
    $scope.setPic = function(){
        $scope.$broadcast('picgallery.open', function(url){
            var t=(new Date()).getTime(),url=url+'?_='+t,nv=['pic',url];
            $http.post('/rest/wbox/article/update?id='+$scope.editing.id, nv).
            success(function() {
                $scope.editing.pic = url;
            });
        }, false);
    }; 
    $scope.removePic = function(){
        var nv = ['pic', ''];
        $http.post('/rest/wbox/article/update?id='+$scope.editing.id, nv).
        success(function() {
            $scope.editing.pic = '';
        });
    }; 
    $scope.$on('xxt.combox.done', function(event, aSelected){
        var aNewTags = [];
        for (var i in aSelected) {
            var existing = false;
            for (var j in $scope.editing.tags) {
                if (aSelected[i].title === $scope.editing.tags[j].title) {
                    existing = true;
                    break;
                }
            }
            !existing && aNewTags.push(aSelected[i]);
        }
        $http.post('/rest/wbox/article/addTag?id='+$scope.editing.id, aNewTags).
        success(function(rsp){
            $scope.editing.tags = $scope.editing.tags.concat(aNewTags);
        });
    });
    $scope.$on('xxt.combox.add', function(event, newTag){
        var oNewTag = {title:newTag};
        $http.post('/rest/wbox/article/addTag?id='+$scope.editing.id, [oNewTag]).
        success(function(rsp){
            $scope.editing.tags.push(oNewTag);
        });
    });
    $scope.$on('xxt.combox.del', function(event, removed){
        $http.post('/rest/wbox/article/removeTag?id='+$scope.editing.id, [removed]).
        success(function(rsp){
            $scope.editing.tags.splice($scope.editing.tags.indexOf(removed), 1);
        });
    });
    $scope.$on('tinymce.innerlink_dlg.open', function(event, callback){
        $scope.$broadcast('mattersgallery.open', callback);
    });
    $scope.$on('tinymce.multipleimage.open', function(event, callback){
        $scope.$broadcast('picgallery.open', callback, true);
    });
    $scope.$on('tinymce.instance.init', function(event) {
        $http.get('/rest/wbox/article?id='+$scope.id,{headers:{'ACCEPT':'application/json'}}).
        success(function(rsp) {
            $scope.editing = rsp.data;
            $scope.picGalleryUrl = '/kcfinder/browse.php?lang=zh-cn&type=图片&mpid='+$scope.editing.galleryId;
            tinymce.get('body').setContent($scope.editing.body || '');
        });
    });
    $scope.$on('tinymce.blur', function(event){
        $scope.editing.body = tinymce.get('body').getContent();
        $scope.modified = true;
        $scope.update('body');
    });
    $scope.$parent.getTags();
}]).
controller('RemarkCtrl',['$scope','$http',function($scope,$http){
    $scope.page = {current:1,size:30};
    $scope.nickname = function(remark) {
        if (remark.nickname && remark.nickname.length >0)
            return remark.nickname;
        else if (remark.email && remark.email.length > 0)
            return remark.email.slice(0, remark.email.indexOf('@'));
        else
            return '未知';
    };
    $scope.doSearch = function() {
        var page = 'page='+$scope.page.current+'&size='+$scope.page.size;
        $http.get('/rest/wbox/article/remarks?id='+$scope.id+'&'+page).
        success(function(rsp){
            $scope.remarks = rsp.data[0];
            $scope.page.total = rsp.data[1]; 
        });
    };
    $scope.doSearch();
}]).
controller('StatCtrl',['$scope','$http',function($scope,$http){
    $http.get('/rest/wbox/article/stat?id='+$scope.id).
    success(function(rsp){
        $scope.stat = rsp.data;
    });
}]);
