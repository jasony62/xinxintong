xxtApp.controller('ArticleCtrl',['$scope','$location',function($scope,$location){
    $scope.id = $location.search().id;
    $scope.back = function() {
        location.href = '/page/mp/matter/articles';
    };
}]).
controller('EditCtrl',['$scope','http2',function($scope,http2){
    $scope.innerlinkTypes = [
        {value:'article',title:'单图文'},
        {value:'news',title:'多图文'},
        {value:'channel',title:'频道'}
    ];
    var getInitData = function() {
        http2.get('/rest/mp/matter/article?id='+$scope.id, function(rsp) {
            $scope.editing = rsp.data;
            $scope.entryUrl = 'http://'+location.host+'/rest/mi/matter?mpid='+$scope.editing.mpid+'&id='+$scope.id+'&type=article';
            $scope.picGalleryUrl = '/kcfinder/browse.php?lang=zh-cn&type=图片&mpid='+$scope.editing.mpid;
            if (!$scope.editing.creater)
                $scope.bodyEditable = false;
            else
                $scope.bodyEditable = true;
        });
        http2.get('/rest/mp/matter/tag?resType=article', function(rsp) {
            $scope.tags = rsp.data;
        });
        http2.get('/rest/mp/matter/channel?cascade=n', function(rsp) {
            $scope.channels = rsp.data;
        });
    };
    $scope.update = function(name) {
        var nv = {};
        nv[name] = $scope.editing[name];
        http2.post('/rest/mp/matter/article/update?id='+$scope.editing.id, nv);
    };
    $scope.setPic = function(){
        $scope.$broadcast('picgallery.open', function(url){
            var t=(new Date()).getTime(),url=url+'?_='+t,nv={'pic':url};
            http2.post('/rest/mp/matter/article/update?id='+$scope.editing.id, nv, function() {
                $scope.editing.pic = url;
            });
        }, false);
    }; 
    $scope.removePic = function(){
        var nv = {'pic': ''};
        http2.post('/rest/mp/matter/article/update?id='+$scope.editing.id, nv, function() {
            $scope.editing.pic = '';
        });
    }; 
    $scope.gotoCode = function() {
        if ($scope.editing.page_id != 0)
            location.href = '/rest/code?pid='+$scope.editing.page_id;
        else {
            http2.get('/rest/code/create', function(rsp){
                var nv = {'page_id': rsp.data.id};
                http2.post('/rest/mp/matter/article/update?id='+$scope.editing.id, nv, function() {
                    $scope.editing.page_id = rsp.data.id;
                    location.href = '/rest/code?pid='+rsp.data.id;
                });
            });
        }
    };
    $scope.$on('tinymce.innerlink_dlg.open', function(event, callback){
        $scope.$broadcast('mattersgallery.open', callback);
    });
    $scope.$on('tinymce.multipleimage.open', function(event, callback){
        $scope.$broadcast('picgallery.open', callback, true, true);
    });
    $scope.$on('tag.xxt.combox.done', function(event, aSelected){
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
        http2.post('/rest/mp/matter/article/addTag?id='+$scope.editing.id, aNewTags, function(rsp){
            $scope.editing.tags = $scope.editing.tags.concat(aNewTags);
        });
    });
    $scope.$on('tag.xxt.combox.add', function(event, newTag){
        var oNewTag = {title:newTag};
        http2.post('/rest/mp/matter/article/addTag?id='+$scope.editing.id, [oNewTag], function(rsp){
            $scope.editing.tags.push(oNewTag);
        });
    });
    $scope.$on('tag.xxt.combox.del', function(event, removed){
        http.post('/rest/mp/matter/article/removeTag?id='+$scope.editing.id, [removed], function(rsp){
            $scope.editing.tags.splice($scope.editing.tags.indexOf(removed), 1);
        });
    });
    http2.get('/rest/mp/mpaccount/feature?fields=matter_visible_to_creater', function(rsp) {
        $scope.features = rsp.data;
    });
    getInitData();
}]).
controller('RemarkCtrl',['$scope','http2',function($scope,http2){
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
        http2.get('/rest/mp/matter/article/remarks?id='+$scope.id+'&'+page, function(rsp){
            $scope.remarks = rsp.data[0];
            $scope.page.total = rsp.data[1]; 
        });
    };
    $scope.doSearch();
}]).
controller('StatCtrl',['$scope','http2',function($scope,http2){
    http2.get('/rest/mp/matter/article/stat?id='+$scope.id, function(rsp){
        $scope.stat = rsp.data;
    });
}]).
controller('ReadCtrl',['$scope','http2',function($scope,http2){
    http2.get('/rest/mp/matter/article/read?id='+$scope.id, function(rsp){
        $scope.reads = rsp.data;
    });
}]);
