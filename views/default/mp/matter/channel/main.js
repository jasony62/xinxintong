xxtApp.config(['$routeProvider', function($routeProvider) {
    $routeProvider.when('/rest/mp/matter/channel/edit', {
        templateUrl: '/views/default/mp/matter/channel/edit.html?_=1',
        controller: 'editCtrl',
    }).when('/rest/mp/matter/channel/read', {
        templateUrl: '/views/default/mp/matter/channel/read.html?_=1',
        controller: 'readCtrl',
    }).when('/rest/mp/matter/channel/stat', {
        templateUrl: '/views/default/mp/matter/channel/stat.html?_=1',
        controller: 'statCtrl'
    }).otherwise({
        templateUrl: '/views/default/mp/matter/channel/edit.html?_=1',
        controller: 'editCtrl'
    });
}]);
xxtApp.controller('channelCtrl', ['$location', '$scope', 'http2', function($location, $scope, http2) {
    $scope.id = $location.search().id;
    $scope.subView = '';
    $scope.back = function() {
        location.href = '/page/mp/matter/channels';
    };
    http2.get('/rest/mp/mpaccount/get', function(rsp) {
        $scope.mpaccount = rsp.data;
        $scope.hasParent = rsp.data.parent_mpid && rsp.data.parent_mpid.length;
        http2.get('/rest/mp/matter/channel/get?id=' + $scope.id, function(rsp) {
            $scope.editing = rsp.data;
            $scope.entryUrl = 'http://' + location.host + '/rest/mi/matter?mpid=' + $scope.mpaccount.mpid + '&id=' + $scope.id + '&type=channel';
        });
    });
}]);
xxtApp.controller('editCtrl', ['$scope', 'http2', function($scope, http2) {
    $scope.$parent.subView = 'edit';
    $scope.matterTypes = [{
        value: 'article',
        title: '单图文',
        url: '/rest/mp/matter'
    }, {
        value: 'link',
        title: '链接',
        url: '/rest/mp/matter'
    }];
    $scope.acceptMatterTypes = [{
        name: '',
        title: '任意'
    }, {
        name: 'article',
        title: '单图文'
    }, {
        name: 'link',
        title: '链接'
    }, {
        name: 'enroll',
        title: '登记活动'
    }, {
        name: 'lottery',
        title: '抽奖活动'
    }, {
        name: 'wall',
        title: '信息墙'
    }, {
        name: 'contribute',
        title: '投稿活动'
    }];
    $scope.volumes = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10'];
    var arrangeMatters = function() {
        $scope.matters = $scope.editing.matters;
        if ($scope.editing.top_type) {
            $scope.topMatter = $scope.matters[0];
            $scope.matters = $scope.matters.slice(1);
        } else
            $scope.topMatter = false;
        if ($scope.editing.bottom_type) {
            var l = $scope.matters.length;
            $scope.bottomMatter = $scope.matters[l - 1];
            $scope.matters = $scope.matters.slice(0, l - 1);
        } else
            $scope.bottomMatter = false;
    };
    var postFixed = function(pos, params) {
        http2.post('/rest/mp/matter/channel/setfixed?id=' + $scope.editing.id + '&pos=' + pos, params, function(rsp) {
            if (pos === 'top') {
                $scope.editing.top_type = params.t;
                $scope.editing.top_id = params.id;
            } else if (pos === 'bottom') {
                $scope.editing.bottom_type = params.t;
                $scope.editing.bottom_id = params.id;
            }
            $scope.editing.matters = rsp.data;
            arrangeMatters();
        });
    };
    $scope.update = function(name) {
        var nv = {};
        nv[name] = $scope.editing[name];
        http2.post('/rest/mp/matter/channel/update?id=' + $scope.editing.id, nv, function() {
            if (name === 'orderby') {
                http2.get('/rest/mp/matter/channel/get?id=' + $scope.id, function(rsp) {
                    $scope.$parent.editing = rsp.data;
                });
            }
        });
    };
    $scope.setFixed = function(pos, clean) {
        if (!clean) {
            $scope.$broadcast('mattersgallery.open', function(aSelected, matterType) {
                if (aSelected.length === 1) {
                    var params = {
                        t: matterType,
                        id: aSelected[0].id
                    };
                    postFixed(pos, params);
                }
            });
        } else {
            var params = {
                t: null,
                id: null
            };
            postFixed(pos, params);
        }
    };
    $scope.removeMatter = function(matter) {
        var removed = {
            id: matter.id,
            type: matter.type.toLowerCase()
        };
        http2.post('/rest/mp/matter/channel/removeMatter?reload=Y&id=' + $scope.editing.id, removed, function(rsp) {
            $scope.editing.matters = rsp.data;
            arrangeMatters();
        });
    };
    http2.get('/rest/mp/feature/get?fields=matter_visible_to_creater', function(rsp) {
        $scope.features = rsp.data;
    });
    $scope.$parent.$watch('editing', function(nv) {
        if (!nv) return;
        arrangeMatters();
    });
}]);
xxtApp.controller('statCtrl', ['$scope', 'http2', function($scope, http2) {
    $scope.$parent.subView = 'stat';
}])
xxtApp.controller('readCtrl', ['$scope', 'http2', function($scope, http2) {
    $scope.$parent.subView = 'read';
    http2.get('/rest/mp/matter/channel/readGet?id=' + $scope.id, function(rsp) {
        $scope.reads = rsp.data;
    });
}]);