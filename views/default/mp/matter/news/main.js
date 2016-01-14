xxtApp.config(['$routeProvider', function($routeProvider) {
    $routeProvider.when('/rest/mp/matter/news/edit', {
        templateUrl: '/views/default/mp/matter/news/edit.html?_=1',
        controller: 'editCtrl',
    }).when('/rest/mp/matter/news/read', {
        templateUrl: '/views/default/mp/matter/news/read.html?_=1',
        controller: 'readCtrl',
    }).when('/rest/mp/matter/news/stat', {
        templateUrl: '/views/default/mp/matter/news/stat.html?_=1',
        controller: 'statCtrl'
    }).otherwise({
        templateUrl: '/views/default/mp/matter/news/edit.html?_=1',
        controller: 'editCtrl'
    });
}]);
xxtApp.controller('newsCtrl', ['$location', '$scope', 'http2', function($location, $scope, http2) {
    $scope.subView = '';
    $scope.id = $location.search().id;
    $scope.back = function() {
        location.href = '/page/mp/matter/newses';
    };
    http2.get('/rest/mp/mpaccount/get', function(rsp) {
        $scope.mpaccount = rsp.data;
        $scope.hasParent = rsp.data.parent_mpid && rsp.data.parent_mpid.length;
        http2.get('/rest/mp/matter/news/get?id=' + $scope.id, function(rsp) {
            $scope.editing = rsp.data;
            $scope.entryUrl = 'http://' + location.host + '/rest/mi/matter?mpid=' + $scope.mpaccount.mpid + '&id=' + $scope.id + '&type=news';
        });
    });
}]);
xxtApp.directive('sortable', function() {
    return {
        link: function(scope, el, attrs) {
            el.sortable({
                revert: 50
            });
            el.disableSelection();
            el.on("sortdeactivate", function(event, ui) {
                var from = angular.element(ui.item).scope().$index;
                var to = el.children('li').index(ui.item);
                if (to >= 0) {
                    scope.$apply(function() {
                        if (from >= 0) {
                            scope.$emit('my-sorted', {
                                from: from,
                                to: to
                            });
                        }
                    });
                }
            });
        }
    };
});
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
    }, {
        value: 'enroll',
        title: '通用活动',
        url: '/rest/mp/matter'
    }, {
        value: 'lottery',
        title: '抽奖活动',
        url: '/rest/mp/matter'
    }, {
        value: 'wall',
        title: '讨论组',
        url: '/rest/mp/matter'
    }, ];
    var updateMatters = function() {
        http2.post('/rest/mp/matter/news/updateMatter?id=' + $scope.editing.id, $scope.editing.matters);
    };
    $scope.update = function(prop) {
        var nv = {};
        nv[prop] = $scope.editing[prop];
        http2.post('/rest/mp/matter/news/update?id=' + $scope.editing.id, nv);
    };
    $scope.assign = function() {
        $scope.$broadcast('mattersgallery.open', function(aSelected, matterType) {
            for (var i in aSelected) {
                aSelected[i].type = matterType;
            }
            $scope.editing.matters = $scope.editing.matters.concat(aSelected);
            updateMatters();
        });
    };
    $scope.removeMatter = function(index) {
        $scope.editing.matters.splice(index, 1);
        updateMatters();
    };
    $scope.setEmptyReply = function() {
        $scope.$broadcast('mattersgallery.open', function(aSelected, matterType) {
            if (aSelected.length === 1) {
                var p = {
                    mt: matterType,
                    mid: aSelected[0].id
                };
                http2.post('/rest/mp/matter/news/setEmptyReply?id=' + $scope.editing.id, p, function(rsp) {
                    $scope.editing.emptyReply = aSelected[0];
                });
            }
        });
    };
    $scope.removeEmptyReply = function() {
        var p = {
            mt: '',
            mid: ''
        };
        http2.post('/rest/mp/matter/news/setEmptyReply?id=' + $scope.editing.id, p, function(rsp) {
            $scope.editing.emptyReply = null;
        });
    };
    $scope.$on('my-sorted', function(ev, val) {
        // rearrange $scope.items
        $scope.editing.matters.splice(val.to, 0, $scope.editing.matters.splice(val.from, 1)[0]);
        for (var i = 0; i < $scope.editing.matters.length; i++) {
            $scope.editing.matters.seq = i;
        }
        updateMatters();
    });
}]);
xxtApp.controller('statCtrl', ['$scope', 'http2', function($scope, http2) {
    $scope.$parent.subView = 'stat';
}])
xxtApp.controller('readCtrl', ['$scope', 'http2', function($scope, http2) {
    $scope.$parent.subView = 'read';
    http2.get('/rest/mp/matter/news/readGet?id=' + $scope.id, function(rsp) {
        $scope.reads = rsp.data;
    });
}]);