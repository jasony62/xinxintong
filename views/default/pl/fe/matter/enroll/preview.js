define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlPreview', ['$scope', '$location', '$anchorScroll', '$uibModal', 'http2', 'srvEnrollApp', function($scope, $location, $anchorScroll, $uibModal, http2, srvEnrollApp) {
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
        $scope.popupQrcode = function() {
            $uibModal.open({
                templateUrl: 'popupQrcode.html',
                controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                    var _oApp, _oQrcode;
                    _oApp = $scope.app;
                    $scope2.qrcode = _oQrcode = {};
                    if (_oApp.entryRule.scope && _oApp.entryRule.scope.sns === 'Y' && _oApp.entryRule.sns.wx) {
                        http2.get('/rest/pl/fe/matter/enroll/wxQrcode?site=' + _oApp.siteid + '&app=' + _oApp.id).then(function(rsp) {
                            var qrcodes = rsp.data;
                            _oQrcode.pic = qrcodes.length ? qrcodes[0].pic : false;
                            _oQrcode.src = 'wx';
                        });
                    } else {
                        _oQrcode.pic = '/rest/site/fe/matter/enroll/qrcode?site=' + _oApp.siteid + '&url=' + encodeURIComponent(_oApp.entryUrl);
                    }
                    $scope2.createWxQrcode = function() {
                        var url;
                        url = '/rest/pl/fe/site/sns/wx/qrcode/create?site=' + _oApp.siteid;
                        url += '&matter_type=enroll&matter_id=' + _oApp.id;
                        url += '&expire=864000';
                        http2.get(url).then(function(rsp) {
                            _oQrcode.pic = rsp.data.pic;
                        });
                    };
                    $scope2.cancel = function() {
                        $mi.dismiss();
                    };
                }],
                backdrop: 'static'
            });
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
        /*overview*/
        var status;
        $scope.status = status = {
            schema: { required: 0, shareable: 0 },
            page: { submitAfter: null, addRecord: 'N', removeRecord: 'N', browseHistory: null, whenEnrolled: null, repos: 'N', rank: 'N' },
            user: { member: [], sns: [] }
        };
        $scope.$watch('app.dataSchemas', function(dataSchemas) {
            if (!dataSchemas) return;
            dataSchemas.forEach(function(oSchema) {
                if (oSchema.required === 'Y') {
                    status.schema.required++;
                }
                if (oSchema.shareable === 'Y') {
                    status.schema.shareable++;
                }
            });
        }, true);
        $scope.$watch('app.pages', function(pages) {
            if (!pages) return;
            var pagesByName = {};
            srvEnrollApp.jumpPages().all.forEach(function(oPage) {
                pagesByName[oPage.name] = oPage;
            });
            status.page.whenEnrolled = pagesByName[$scope.app.enrolled_entry_page];
            pages.forEach(function(oPage) {
                if (oPage.type === 'I') {
                    if (oPage.actSchemas && oPage.actSchemas.length) {
                        for (var i = 0, ii = oPage.actSchemas.length; i < ii; i++) {
                            if (oPage.actSchemas[i].name === 'submit') {
                                status.page.submitAfter = pagesByName[oPage.actSchemas[i].next] ? pagesByName[oPage.actSchemas[i].next] : { title: '未指定' };
                                break;
                            }
                        }
                    }
                } else if (oPage.type === 'V') {
                    if (oPage.actSchemas && oPage.actSchemas.length) {
                        for (var i = 0, ii = oPage.actSchemas.length; i < ii; i++) {
                            if (oPage.actSchemas[i].name === 'addRecord') {
                                status.page.addRecord = 'Y';
                            }
                            if (oPage.actSchemas[i].name === 'removeRecord') {
                                status.page.removeRecord = 'Y';
                           }
                            if (oPage.actSchemas[i].next === 'repos') {
                                status.page.repos = 'Y';
                            }
                            if (oPage.actSchemas[i].next === 'rank') {
                                status.page.rank = 'Y';
                            }
                        }
                    }
                }
            });
        }, true);
        $scope.$watch('app.entryRule', function(oRule) {
            if (!oRule) return;
            if (oRule.scope.member === 'Y' && oRule.member) {
                var mschemaIds = Object.keys(oRule.member);
                if (mschemaIds.length) {
                    http2.get('/rest/pl/fe/site/member/schema/overview?site=' + $scope.app.siteid + '&mschema=' + mschemaIds.join(',')).then(function(rsp) {
                        var oMschema;
                        for (var schemaId in rsp.data) {
                            oMschema = rsp.data[schemaId];
                            status.user.member.push(oMschema);
                        }
                    });
                }
            }
            if (oRule.scope.sns === 'Y') {
                if (oRule.sns && $scope.sns) {
                    if (oRule.sns.wx && oRule.sns.wx.entry && $scope.sns.wx) {
                        status.user.sns.push({ title: $scope.sns.wx.title });
                    }
                    if (oRule.sns.yx && oRule.sns.yx.entry && $scope.sns.yx) {
                        status.user.sns.push({ title: $scope.sns.yx.title });
                    }
                    if (oRule.sns.qy && oRule.sns.qy.entry && $scope.sns.qy) {
                        status.user.sns.push({ title: $scope.sns.qy.title });
                    }
                }
            }
        });
        srvEnrollApp.opData().then(function(data) {
            if (data.length) {
                $scope.opData = data[0];
            }
        });
        $('#preview-view').height($('#pl-layout-main').height());
        $('#preview-view').scrollspy({ target: '#previewScrollspy' }).scrollspy('refresh');
        $('#previewScrollspy>ul').affix({
            offset: {
                top: 0
            }
        });
        //$location.hash("status5");
        //$anchorScroll();
    }]);
});