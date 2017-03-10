define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlPublish', ['$scope', 'http2', 'mediagallery', '$timeout',  'srvEnrollApp', 'srvTempApp', '$controller', function($scope,  http2, mediagallery, $timeout,  srvEnrollApp, srvTempApp, controller) {
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
        $scope.shareUser = function() {
            console.log(3);
        }
    }]);
    ngApp.provider.controller('ctrlPreview', ['$scope', 'srvEnrollApp', 'srvTempApp', function($scope, srvEnrollApp, srvTempApp) {
        function refresh() {
            $scope.previewURL = previewURL + '&page=' + params.page.name + '&_=' + (new Date() * 1);
        }
        $scope.$on('to-child',function(event,data)  {
            $scope.app.pub_status = data[1].pub_status;
            $scope.param = data[2];
            $scope.nextPage = function() {
                $scope.param.pageAt++;
                $scope.param.hasPrev = true;
                $scope.param.hasNext = $scope.param.pageAt < data[1].pages.length - 1;
            };
            $scope.prevPage = function() {
                $scope.param.pageAt--;
                $scope.param.hasNext = true;
                $scope.param.hasPrev = $scope.param.pageAt > 0;
            };
            $scope.$watch('param', function(param) {
                if (param) {
                    $scope.previewURL = data[0]  + '&page=' + data[1].pages[param.pageAt].name;
                }
            }, true);
        })
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
