define(['frame'], function(ngApp) {
    ngApp.provider.controller('ctrlReocrd', ['$scope', 'http2', 'mediagallery', '$timeout',  'srvEnrollApp', 'srvTempApp', '$controller', function($scope,  http2, mediagallery, $timeout,  srvEnrollApp, srvTempApp, controller) {
        $scope.shareUser = {};
        $scope.setPic = function() {
            var options = {
                callback: function(url) {
                    $scope.app.pic = url + '?_=' + (new Date() * 1);
                    srvEnrollApp.update('pic');
                }
            };
            mediagallery.open($scope.app.siteid, options);
        };
        $scope.removePic = function() {
            $scope.app.pic = '';
            srvEnrollApp.update('pic');
        };
        /**
         * 访问控制规则
         */
        $scope.isInputPage = function(pageName) {
            if (!$scope.app) {
                return false;
            }
            for (var i in $scope.app.pages) {
                if ($scope.app.pages[i].name === pageName && $scope.app.pages[i].type === 'I') {
                    return true;
                }
            }
            return false;
        };
        $scope.shareAsTemplate = function() {
            srvTempApp.shareAsTemplate();
        };
        $scope.applyToHome = function(matter) {
            srvTempApp.applyToHome(matter);
        };
        $scope.cancelAsTemplate = function() {
            srvTempApp.cancelAsTemplate();
        };
        $scope.removeAsTemplate = function() {
            if (window.confirm('确定删除模板？')) {
                srvTempApp.removeAsTemplate().then(function() {
                    location = '/rest/pl/fe/template/site?site=' + $scope.app.siteid;
                });
            }
        };
        $scope.lookView = function(num) {
            var previewURL,
                params = {
                    pageAt: -1,
                    hasPrev: false,
                    hasNext: false,
                };
            srvTempApp.lookView(num).then(function(data) {
                params.pageAt = 0;
                params.hasPrev = false;
                params.hasNext = !!data.pages.length;
                $scope.params = params;

                previewURL = '/rest/site/fe/matter/template/enroll/preview?site=' + data.siteid;
                previewURL += '&tid=' + data.id + '&vid=' + num;
                $scope.$broadcast('to-child', {0:previewURL,1:data,2:$scope.params});
            });
        }
        $scope.lookDetail = function(id) {
            srvTempApp.lookDetail(id);
        }
        $scope.createVersion = function() {
           srvTempApp.createVersion();
        }
        $scope.addReceiver = function(user) {
            srvTempApp.addReceiver($scope.shareUser).then(function(){
                $scope.shareUser.label = '';
            });
        };
        $scope.removeReceiver = function(acl) {
            srvTempApp.removeReceiver(acl);
        };
    }]);
});