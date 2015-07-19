xxtApp.controller('feaCtrl',['$scope','http2',function($scope,http2){
    $scope.update = function(name){
        var p = {};
        p[name] = $scope.features[name];
        http2.post('/rest/mp/mpaccount/updateFeature', p);
    };
    $scope.setPic = function(){
        $scope.$broadcast('picgallery.open', function(url){
            url += '?_=' + (new Date()).getTime();
            $scope.features.heading_pic = url;
            $scope.update('heading_pic');
        }, false);
    }; 
    $scope.removePic = function(){
        $scope.features.heading_pic = '';
        $scope.update('heading_pic');
    }; 
    $scope.editPage = function(event, prop) {
        event.preventDefault();
        event.stopPropagation();
        var pageid = $scope.features[prop];
        if (pageid === '0') {
            http2.get('/rest/code/create', function(rsp){
                $scope.features[prop] = rsp.data.id;   
                $scope.update(prop);
                window.open('/rest/code?pid='+rsp.data.id);
            })
        } else {
            window.open('/rest/code?pid='+pageid);
        }
    };
    $scope.$watch('jsonParams', function(nv){
        if (nv && nv.length) {
            var params = JSON.parse(decodeURIComponent(nv.replace(/\+/g,'%20')));
            console.log('params', params);
            $scope.features = params.features;
            $scope.picGalleryUrl = '/kcfinder/browse.php?lang=zh-cn&type=图片&mpid='+$scope.features.mpid;
        }
    });
}]);
