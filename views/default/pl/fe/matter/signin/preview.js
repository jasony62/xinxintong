define(['frame'], function (ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlPreview', ['$scope', 'srvSigninApp', function ($scope, srvSigninApp) {
        function refresh() {
            $scope.previewURL = previewURL + '&openAt=' + params.openAt + '&page=' + params.page.name + '&_=' + (new Date() * 1);
        }

        var previewURL, params;
        $scope.params = params = {
            openAt: 'ontime'
        };
        $scope.showPage = function (page) {
            params.page = page;
        };
        srvSigninApp.get().then(function (app) {
            if (app.pages && app.pages.length) {
                $scope.gotoPage = function (page) {
                    var url = "/rest/pl/fe/matter/signin/page";
                    url += "?site=" + app.siteid;
                    url += "&id=" + app.id;
                    url += "&page=" + page.name;
                    location.href = url;
                };
                previewURL = '/rest/site/fe/matter/signin/preview?site=' + app.siteid + '&app=' + app.id;
                params.page = app.pages[0];
                $scope.$watch('params', function () {
                    refresh();
                }, true);
                $scope.$watch('app.use_site_header', function (nv, ov) {
                    nv !== ov && refresh();
                });
                $scope.$watch('app.use_site_footer', function (nv, ov) {
                    nv !== ov && refresh();
                });
                $scope.$watch('app.use_mission_header', function (nv, ov) {
                    nv !== ov && refresh();
                });
                $scope.$watch('app.use_mission_header', function (nv, ov) {
                    nv !== ov && refresh();
                });
            }
        });
    }]);
});