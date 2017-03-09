define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlPublish', ['$scope', 'noticebox', '$q', 'http2', 'mediagallery', '$timeout', '$uibModal', 'srvEnrollApp', 'srvTempApp', function($scope, noticebox, $q, http2, mediagallery, $timeout, $uibModal, srvEnrollApp, srvTempApp) {
        $scope.subView = false;
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
            console.log(1);
        };
        $scope.lookView = function(num) {
            $scope.subView = true;
            console.log(1);
        }
        $scope.lookDetail = function(id) {
            srvTempApp.lookDetail(id);
        }
        $scope.createVersion = function() {
           srvTempApp.createVersion();
        }
        $scope.shareUser = function() {
            console.log(3);
        }
    }]);
    ngApp.provider.controller('ctrlPreview', ['$scope', 'srvEnrollApp', 'srvTempApp', function($scope, srvEnrollApp, srvTempApp) {
        function refresh() {
            $scope.previewURL = previewURL + '&page=' + params.page.name + '&_=' + (new Date() * 1);
        }
        var previewURL, params;
        $scope.params = params = {
            openAt: 'ontime',
        };
        $scope.showPage = function(page) {
            params.page = page;
        };
        srvTempApp.tempEnrollGet().then(function(app) {
            if (app.pages && app.pages.length) {
                $scope.gotoPage = function(page) {
                    var url = "/rest/pl/fe/template/enroll/page";
                    url += "?site=" + app.siteid;
                    url += "&id=" + app.id;
                    url += "&vid=" + app.vid;
                    url += "&page=" + page.name;
                    location.href = url;
                };
                previewURL = '/rest/site/fe/matter/template/enroll/preview?site=' + app.siteid + '&tid=' + app.id + '&vid=' + app.vid;
                params.page = app.pages[0];
                $scope.$watch('params', function() {
                    refresh();
                }, true);
                $scope.$watch('app.use_site_header', function(nv, ov) {
                    nv !== ov && refresh();
                });
                $scope.$watch('app.use_site_footer', function(nv, ov) {
                    nv !== ov && refresh();
                });
                $scope.$watch('app.use_mission_header', function(nv, ov) {
                    nv !== ov && refresh();
                });
                $scope.$watch('app.use_mission_header', function(nv, ov) {
                    nv !== ov && refresh();
                });
            }
        });
    }]);
});
