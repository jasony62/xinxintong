var app = angular.module('app', ['ngRoute', 'ui.tms', 'matters.xxt']);
app.config(['$locationProvider', '$routeProvider', function($lp, $rp) {
    $lp.html5Mode(true);
    $rp.when('/rest/pl/fe/site/setting', {
        templateUrl: '/views/default/pl/fe/site/setting.html?_=1',
        controller: 'ctrlSet',
    }).when('/rest/pl/fe/site/console', {
        templateUrl: '/views/default/pl/fe/site/console.html?_=1',
        controller: 'ctrlConsole',
    }).when('/rest/pl/fe/site/matter', {
        templateUrl: '/views/default/pl/fe/site/matter.html?_=1',
        controller: 'ctrlMatter',
    }).otherwise({
        templateUrl: '/views/default/pl/fe/site/console.html?_=1',
        controller: 'ctrlConsole'
    });
}]);
app.controller('ctrlSite', ['$scope', '$location', 'http2', function($scope, $location, http2) {
    $scope.subView = 'console';
    $scope.$on('$routeChangeSuccess', function(evt, nextRoute, lastRoute) {
        $scope.subView = nextRoute.loadedTemplateUrl.match(/\/([^\/]+)\.html/)[1];
    });
    $scope.id = $location.search().id;
    http2.get('/rest/pl/fe/site/get?id=' + $scope.id, function(rsp) {
        $scope.site = rsp.data;
    });
}]);
app.controller('ctrlSet', ['$scope', 'http2', function($scope, http2) {
    $scope.sub = 'basic';
    $scope.gotoSub = function(name) {
        $scope.sub = name;
    };
    $scope.update = function(name) {
        var p = {};
        p[name] = $scope.site[name];
        http2.post('/rest/pl/fe/site/update?id=' + $scope.id, p, function(rsp) {});
    };
    $scope.setPic = function() {
        var options = {
            callback: function(url) {
                $scope.site.heading_pic = url + '?_=' + (new Date()) * 1;;
                $scope.update('heading_pic');
            }
        };
        $scope.$broadcast('mediagallery.open', options);
    };
    $scope.removePic = function() {
        $scope.features.heading_pic = '';
        $scope.update('heading_pic');
    };
    $scope.editPage = function(event, page) {
        event.preventDefault();
        event.stopPropagation();
        var pageid = $scope.site[page + '_page_id'];
        if (pageid === '0') {
            http2.get('/rest/pl/fe/site/pageCreate?id=' + $scope.id + '&page=' + page, function(rsp) {
                $scope.site[prop] = new String(rsp.data.id);
                location.href = '/rest/code?pid=' + rsp.data.id;
            });
        } else {
            location.href = '/rest/code?pid=' + pageid;
        }
    };
    $scope.resetPage = function(event, page) {
        event.preventDefault();
        event.stopPropagation();
        if (window.confirm('重置操作将覆盖已经做出的修改，确定重置？')) {
            var pageid = $scope.site[page + '_page_id'];
            if (pageid === '0') {
                http2.get('/rest/pl/fe/site/pageCreate?id=' + $scope.id + '&page=' + page, function(rsp) {
                    $scope.site[prop] = new String(rsp.data.id);
                    location.href = '/rest/code?pid=' + rsp.data.id;
                });
            } else {
                http2.get('/rest/pl/fe/site/pageReset?id=' + $scope.id + '&page=' + page, function(rsp) {
                    location.href = '/rest/code?pid=' + pageid;
                });
            }
        }
    };
}]);
app.controller('ctrlAdmin', ['$scope', '$modal', 'http2', function($scope, $modal, http2) {
    $scope.admins = [];
    $scope.isAdmin = true;
    $scope.add = function() {
        var url = '/rest/pl/fe/site/adminCreate';
        http2.get(url, function(rsp) {
            $scope.admins.push(rsp.data);
            $scope.select(rsp.data);
        });
    };
    $scope.remove = function(admin) {
        http2.get('/rest/pl/fe/site/adminRemove?uid=' + admin.uid, function(rsp) {
            var index = $scope.admins.indexOf(admin);
            $scope.admins.splice(index, 1);
            $scope.selected = false;
        });
    };
    $scope.select = function(admin) {
        $scope.selected = admin;
    };
}]);
app.controller('ctrlConsole', ['$scope', 'http2', function($scope, http2) {
    $scope.open = function(matter) {
        if (matter.matter_type === 'article') {
            //location.href = '/rest/mp/matter/article?id=' + matter.matter_id;
            location.href = 'http://localhost/rest/pl/fe/matter/article?id=' + matter.matter_id;
        } else if (matter.matter_type === 'enroll') {
            //location.href = '/rest/mp/app/enroll/detail?aid=' + matter.matter_id;
            location.href = 'http://localhost/rest/pl/fe/matter/enroll?id=' + matter.matter_id;
        } else if (matter.matter_type === 'mission') {
            location.href = '/rest/mp/mission/setting?id=' + matter.matter_id;
        }
    };
    $scope.addArticle = function() {
        http2.get('/rest/mp/matter/article/create?mpid=' + $scope.id, function(rsp) {
            location.href = '/rest/mp/matter/article?id=' + rsp.data;
        });
    };
    $scope.addEnroll = function() {
        var url;
        url = '/rest/mp/app/enroll/create?mpid=' + $scope.id;
        http2.post(url, {}, function(rsp) {
            location.href = '/rest/mp/app/enroll/detail?aid=' + rsp.data.id;
        });
    };
    $scope.addTask = function() {
        http2.get('/rest/mp/mission/create?mpid=' + $scope.id, function(rsp) {
            location.href = '/rest/mp/mission/setting?id=' + rsp.data.id;
        });
    };
    http2.get('/rest/pl/fe/site/console/recent?id=' + $scope.id, function(rsp) {
        $scope.matters = rsp.data.matters;
    });
}]);
app.controller('ctrlMatter', ['$scope', 'http2', function($scope, http2) {}]);