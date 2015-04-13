xxtApp.controller('linkCtrl',['$scope','$http','$location',function($scope,$http,$location){
    $scope.id = $location.search().id;
    $scope.back = function() {
        window.location.href = '/page/mp/matter/links';
    };
}])
.controller('editCtrl',['$scope','$http','$location',function($scope,$http,$location){
    $scope.mpid = $location.search().mpid;
    $scope.urlsrcs = {
        '0':'外部链接',
        '1':'多图文',
        '2':'频道',
    };
    $scope.linkparams = {
        '{{openid}}':'用户标识(openid)',
        '{{mpid}}':'公众号标识',
        '{{src}}':'用户来源（易信/微信）',
        '{{authed_identity}}':'用户绑定ID',
    };
    var getInitData = function() {
        $http.get('/rest/mp/matter/link?id='+$scope.id).
        success(function(rsp) {
            editLink(rsp.data);
            $scope.picGalleryUrl = '/kcfinder/browse.php?lang=zh-cn&type=图片&mpid='+rsp.data.mpid;
        });
        $http.get('/rest/mp/mpaccount/authapis').
        success(function(rsp) {
            $scope.authapis = rsp.data;
        });
        $http.get('/rest/mp/matter/news?cascade=n').
        success(function(rsp) {
            $scope.news = rsp.data;
        });
        $http.get('/rest/mp/matter/channel?cascade=n').
        success(function(rsp) {
            $scope.channels = rsp.data;
        });
    };
    var editLink = function(link) {
        if (link.params) {
            var p;
            for(var i in link.params) {
                p = link.params[i];
                p.customValue = $scope.linkparams[p.pvalue] ? false : true;
            }
        }
        $scope.editingAuthapis = angular.copy($scope.authapis);
        if (link.authapis) {
            for (var i in $scope.editingAuthapis)
                $scope.editingAuthapis[i].checked = link.authapis.indexOf($scope.editingAuthapis[i].authid) !== -1 ? 'Y':'N';
        }
        $scope.editing = link;
        $scope.persisted = angular.copy(link);
        $('[ng-model="editing.title"]').focus();
    };
    $scope.update = function(n) {
        if (!angular.equals($scope.editing, $scope.persisted)) {
            var nv = {};
            nv[n] = $scope.editing[n];
            if (n === 'urlsrc' && $scope.editing.urlsrc != 0){
                $scope.editing.open_directly = 'N';
                nv.open_directly = 'N';
            } else if (n === 'method' && $scope.editing.method === 'POST') {
                $scope.editing.open_directly = 'N';
                nv.open_directly = 'N';
            } else if (n === 'open_directly' && $scope.editing.open_directly == 'Y') {
                $scope.editing.access_control = 'N';
                nv.access_control = 'N';
                nv.authapis = '';
            } else if (n === 'access_control' && $scope.editing.access_control == 'N') {
                var p;
                for(var i in $scope.editing.params) {
                    p = $scope.editing.params[i];
                    if (p.pvalue == '{{authed_identity}}') {
                        window.alert('只有在进行访问控制的情况下，才可以指定和用户身份相关的信息！');
                        $scope.editing.access_control = 'Y';
                        nv.access_control = 'Y';
                        return false;
                    }
                }
                nv.authapis = '';
            } else if ($scope.authapis.length===1 && n==='access_control')
                nv.authapis = nv[n] === 'Y' ? $scope.authapis[0].authid : '';
            $http.post('/rest/mp/matter/link/update?id='+$scope.editing.id, nv).
            success(function(rsp){
                $scope.persisted = angular.copy($scope.editing);
            });
        }
    };
    $scope.updateAuthapi = function(api) {
        var eapis = $scope.editing.authapis,p={};
        api.checked === 'Y' ? eapis.push(api.authid) : eapis.splice(eapis.indexOf(api.authid),1);
        p.authapis = eapis.join();
        $http.post('/rest/mp/matter/link/update?id='+$scope.editing.id, p).
        success(function() {});
    };

    $scope.setPic = function(){
        $scope.$broadcast('picgallery.open', function(url){
            var t=(new Date()).getTime(),url=url+'?_='+t,nv={pic:url};
            $http.post('/rest/mp/matter/link/update?id='+$scope.editing.id, nv).
            success(function() {
                $scope.editing.pic = url;
            });
        }, false);
    }; 
    $scope.removePic = function(){
        var nv = {pic:''};
        $http.post('/rest/mp/matter/link/update?id='+$scope.editing.id, nv).
        success(function() {
            $scope.editing.pic = '';
        });
    };
    $scope.addParam = function() {
        $http.post('/rest/mp/matter/link/addParam?linkid='+$scope.editing.id).
        success(function(rsp) {
            var oNewParam = {id:rsp.data, pname:'newparam', pvalue:''};
            $scope.editing.params.push(oNewParam);
        });
    };
    $scope.updateParam = function(updated, name) {
        if (updated.pvalue==='{{authed_identity}}' && $scope.editing.access_control==='N') {
            window.alert('只有在进行访问控制的情况下，才可以指定和用户身份相关的信息！');
            updated.pvalue = '';
        }
        if (updated.pvalue !== '{{authed_identity}}')
            updated.authapi_id = 0;
        // 参数中有额外定义，需清除
        var p = {
            pname:updated.pname,
            pvalue:updated.pvalue,
            authapi_id:updated.authapi_id
        };
        $http.post('/rest/mp/matter/link/updateParam?id='+updated.id, p);
    };
    $scope.removeParam = function(removed) {
        $http.post('/rest/mp/matter/link/removeParam?id='+removed.id).
        success(function(rsp) {
            var i = $scope.editing.params.indexOf(removed);
            $scope.editing.params.splice(i, 1);
        });
    };
    $scope.changePValueMode = function(p) {
        p.pvalue = '';
    };
    $http.get('/rest/mp/mpaccount/features?fields=matter_visible_to_creater').
    success(function(rsp) {
        $scope.features = rsp.data;
    });
    getInitData();
}]);
