define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlEntry', ['$scope', 'mediagallery', '$timeout', 'srvEnrollApp', function($scope, mediagallery, $timeout, srvEnrollApp) {
        $timeout(function() {
            new ZeroClipboard(document.querySelectorAll('.text2Clipboard'));
        });
        srvEnrollApp.get().then(function(app) {
            var oEntry;
            oEntry = {
                url: app.entryUrl,
                qrcode: '/rest/site/fe/matter/enroll/qrcode?site=' + app.siteid + '&url=' + encodeURIComponent(app.entryUrl),
                pages: []
            };
            $scope.entry = oEntry;
        });
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
        $scope.downloadQrcode = function(url) {
            $('<a href="' + url + '" download="登记二维码.png"></a>')[0].click();
        };
        srvEnrollApp.summary().then(function(data) {
            $scope.summary = data;
        });
    }]);
    ngApp.provider.controller('ctrlOpUrl', ['$scope', 'srvQuickEntry', 'srvEnrollApp', function($scope, srvQuickEntry, srvEnrollApp) {
        var targetUrl, opEntry;
        $scope.opEntry = opEntry = {};
        srvEnrollApp.get().then(function(app) {
            targetUrl = 'http://' + location.host + '/rest/site/op/matter/enroll?site=' + $scope.app.siteid + '&app=' + $scope.app.id;
            srvQuickEntry.get(targetUrl).then(function(entry) {
                if (entry) {
                    opEntry.url = 'http://' + location.host + '/q/' + entry.code;
                    opEntry.password = entry.password;
                    opEntry.code = entry.code;
                    opEntry.can_favor = entry.can_favor;
                }
            });
        });
        $scope.makeOpUrl = function() {
            srvQuickEntry.add(targetUrl, $scope.app.title).then(function(task) {
                $scope.app.op_short_url_code = task.code;
                srvEnrollApp.update('op_short_url_code');
                opEntry.url = 'http://' + location.host + '/q/' + task.code;
                opEntry.code = task.code;
            });
        };
        $scope.closeOpUrl = function() {
            srvQuickEntry.remove(targetUrl).then(function(task) {
                opEntry.url = '';
                opEntry.code = '';
                opEntry.can_favor = 'N';
                opEntry.password = '';
                $scope.app.op_short_url_code = '';
                srvEnrollApp.update('op_short_url_code');
            });
        };
        $scope.configOpUrl = function(event, prop) {
            event.preventDefault();
            srvQuickEntry.config(targetUrl, {
                password: opEntry.password
            });
        };
        $scope.updCanFavor = function() {
            srvQuickEntry.update(opEntry.code, { can_favor: opEntry.can_favor });
        };
    }]);
    /**
     * 微信二维码
     */
    ngApp.provider.controller('ctrlWxQrcode', ['$scope', 'http2', function($scope, http2) {
        $scope.create = function() {
            var url;
            url = '/rest/pl/fe/site/sns/wx/qrcode/create?site=' + $scope.app.siteid;
            url += '&matter_type=enroll&matter_id=' + $scope.app.id;
            url += '&expire=864000';
            http2.get(url, function(rsp) {
                $scope.qrcode = rsp.data;
            });
        };
        $scope.download = function() {
            $('<a href="' + $scope.qrcode.pic + '" download="微信登记二维码.jpeg"></a>')[0].click();
        };
        http2.get('/rest/pl/fe/matter/enroll/wxQrcode?site=' + $scope.app.siteid + '&app=' + $scope.app.id, function(rsp) {
            var qrcodes = rsp.data;
            $scope.qrcode = qrcodes.length ? qrcodes[0] : false;
        });
    }]);
    ngApp.provider.controller('ctrlPreview', ['$scope', 'srvEnrollApp', function($scope, srvEnrollApp) {
        function refresh() {
            $scope.previewURL = previewURL + '&openAt=' + params.openAt + '&page=' + params.page.name + '&_=' + (new Date() * 1);
        }
        var previewURL, params;
        $scope.params = params = {
            openAt: 'ontime',
        };
        $scope.showPage = function(page) {
            params.page = page;
        };
        srvEnrollApp.get().then(function(app) {
            if (app.pages && app.pages.length) {
                $scope.gotoPage = function(page) {
                    var url = "/rest/pl/fe/matter/enroll/page";
                    url += "?site=" + app.siteid;
                    url += "&id=" + app.id;
                    url += "&page=" + page.name;
                    location.href = url;
                };
                previewURL = '/rest/site/fe/matter/enroll/preview?site=' + app.siteid + '&app=' + app.id + '&start=Y';
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
