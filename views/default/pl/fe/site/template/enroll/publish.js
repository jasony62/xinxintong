define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlPublish', ['$scope', 'http2', 'mediagallery', '$timeout',  'srvEnrollApp', 'srvTempApp', '$controller', function($scope,  http2, mediagallery, $timeout,  srvEnrollApp, srvTempApp, controller) {
        $scope.shareUser = {};
        $scope.setPic = function() {
            var options = {
                callback: function(url) {
                    $scope.app.pic = url + '?_=' + (new Date() * 1);
                    srvTempApp.update('pic');
                }
            };
            mediagallery.open($scope.app.siteid, options);
        };
        $scope.removePic = function() {
            $scope.app.pic = '';
            srvTempApp.update('pic');
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
    ngApp.provider.controller('ctrlPreview', ['$scope', 'srvEnrollApp', 'srvTempApp', function($scope, srvEnrollApp, srvTempApp) {
        var previewURL, params, args, param;
        $scope.params = params = {
            openAt: 'ontime',
        };
        $scope.args = args = {
            pageAt: -1,
            hasPrev: false,
            hasNext: false,
        }
        $scope.nextPage = function() {
            args.pageAt++;
            args.hasPrev = true;
            args.hasNext = args.pageAt < $scope.app.pages.length - 1;
        };
        $scope.prevPage = function() {
            args.pageAt--;
            args.hasNext = true;
            args.hasPrev = args.pageAt > 0;
        };
        $scope.showPage = function(page) {
            params.page = page;
        };
        function refresh() {
            $scope.previewURL = previewURL + '&page=' + params.page.name + '&_=' + (new Date() * 1);
        }
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
                args.pageAt = 0;
                args.hasPrev = false;
                $scope.args = args;
                args.hasNext = !!app.pages.length;

                previewURL = '/rest/site/fe/matter/template/enroll/preview?site=' + app.siteid + '&tid=' + app.id + '&vid=' + app.vid;
                params.page = app.pages[0];
                $scope.$watch('params', function() {
                    refresh();
                }, true);
                $scope.$watch('args', function(args) {
                    if (args) {
                        $scope.previewURL = previewURL + '&page=' + app.pages[args.pageAt].name;
                    }
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
        $scope.$on('to-child',function(event,data)  {
            $scope.app.pub_status = data[1].pub_status;
            $scope.args = args = data[2];
            $scope.nextPage = function() {
                args.pageAt++;
                args.hasPrev = true;
                args.hasNext = args.pageAt < data[1].pages.length - 1;
            };
            $scope.prevPage = function() {
                args.pageAt--;
                args.hasNext = true;
                args.hasPrev = args.pageAt > 0;
            };
            $scope.$watch('args', function(param) {
                if (param) {
                    $scope.previewURL = data[0]  + '&page=' + data[1].pages[args.pageAt].name;
                }
            }, true);
        });
    }]);
});
