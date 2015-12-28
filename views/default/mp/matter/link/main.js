xxtApp.controller('linkCtrl', ['$scope', 'http2', '$location', function($scope, http2, $location) {
    $scope.id = $location.search().id;
    $scope.back = function() {
        window.location.href = '/page/mp/matter/links';
    };
}]).controller('editCtrl', ['$scope', 'http2', function($scope, http2) {
    $scope.urlsrcs = {
        '0': '外部链接',
        '1': '多图文',
        '2': '频道',
        '3': '内置回复',
    };
    $scope.linkparams = {
        '{{openid}}': '用户标识(openid)',
        '{{mpid}}': '公众号标识',
        '{{src}}': '用户来源（易信/微信）',
        '{{authed_identity}}': '用户绑定ID',
    };
    var getInitData = function() {
        http2.get('/rest/mp/feature/get?fields=matter_visible_to_creater', function(rsp) {
            $scope.features = rsp.data;
        });
        http2.get('/rest/mp/matter/link?id=' + $scope.id, function(rsp) {
            editLink(rsp.data);
        });
    };
    var editLink = function(link) {
        if (link.params) {
            var p;
            for (var i in link.params) {
                p = link.params[i];
                p.customValue = $scope.linkparams[p.pvalue] ? false : true;
            }
        }
        $scope.editing = link;
        $scope.persisted = angular.copy(link);
        $('[ng-model="editing.title"]').focus();
    };
    $scope.update = function(n) {
        if (!angular.equals($scope.editing, $scope.persisted)) {
            var nv = {};
            nv[n] = $scope.editing[n];
            if (n === 'urlsrc' && $scope.editing.urlsrc != 0) {
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
                for (var i in $scope.editing.params) {
                    p = $scope.editing.params[i];
                    if (p.pvalue == '{{authed_identity}}') {
                        window.alert('只有在进行访问控制的情况下，才可以指定和用户身份相关的信息！');
                        $scope.editing.access_control = 'Y';
                        nv.access_control = 'Y';
                        return false;
                    }
                }
                nv.authapis = '';
            }
            http2.post('/rest/mp/matter/link/update?id=' + $scope.editing.id, nv, function(rsp) {
                $scope.persisted = angular.copy($scope.editing);
            });
        }
    };
    $scope.setPic = function() {
        var options = {
            callback: function(url) {
                $scope.editing.pic = url + '?_=' + (new Date()) * 1;
                $scope.update('pic');
            }
        };
        $scope.$broadcast('mediagallery.open', options);
    };
    $scope.removePic = function() {
        $scope.editing.pic = '';
        $scope.update('pic');
    };
    $scope.addParam = function() {
        http2.get('/rest/mp/matter/link/addParam?linkid=' + $scope.editing.id, function(rsp) {
            var oNewParam = {
                id: rsp.data,
                pname: 'newparam',
                pvalue: ''
            };
            if ($scope.editing.urlsrc === '3' && $scope.editing.url === '9') oNewParam.pname = 'channelid';
            $scope.editing.params.push(oNewParam);
        });
    };
    $scope.updateParam = function(updated, name) {
        if (updated.pvalue === '{{authed_identity}}' && $scope.editing.access_control === 'N') {
            window.alert('只有在进行访问控制的情况下，才可以指定和用户身份相关的信息！');
            updated.pvalue = '';
        }
        if (updated.pvalue !== '{{authed_identity}}')
            updated.authapi_id = 0;
        // 参数中有额外定义，需清除
        var p = {
            pname: updated.pname,
            pvalue: encodeURIComponent(updated.pvalue),
            authapi_id: updated.authapi_id
        };
        http2.post('/rest/mp/matter/link/updateParam?id=' + updated.id, p);
    };
    $scope.removeParam = function(removed) {
        http2.get('/rest/mp/matter/link/removeParam?id=' + removed.id, function(rsp) {
            var i = $scope.editing.params.indexOf(removed);
            $scope.editing.params.splice(i, 1);
        });
    };
    $scope.changePValueMode = function(p) {
        p.pvalue = '';
    };
    http2.get('/rest/mp/mpaccount/get', function(rsp) {
        $scope.mpaccount = rsp.data;
        getInitData();
    });
    $scope.$watch('editing.urlsrc', function(nv) {
        switch (nv) {
            case '1':
                if ($scope.news === undefined) {
                    http2.get('/rest/mp/matter/news/get?cascade=N', function(rsp) {
                        $scope.news = rsp.data;
                    });
                }
                break;
            case '2':
                if ($scope.channels === undefined) {
                    http2.get('/rest/mp/matter/channel/get?cascade=N', function(rsp) {
                        $scope.channels = rsp.data;
                    });
                }
                break;
            case '3':
                if ($scope.inners === undefined) {
                    http2.get('/rest/mp/matter/inner', function(rsp) {
                        $scope.inners = rsp.data;
                    });
                }
                if ($scope.channels === undefined) {
                    http2.get('/rest/mp/matter/channel/get?cascade=N', function(rsp) {
                        $scope.channels = rsp.data;
                    });
                }
                break;
        }
    });
}]);