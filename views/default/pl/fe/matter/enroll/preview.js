define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlPreview', ['$scope', 'http2', 'srvEnrollApp', function($scope, http2, srvEnrollApp) {
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
        /*overview*/
        var status;
        $scope.status = status = {
            schema: { required: 0, remarkable: 0, shareable: 0 },
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
                if (oSchema.remarkable === 'Y') {
                    status.schema.remarkable++;
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
                    if (oPage.act_schemas && oPage.act_schemas.length) {
                        for (var i = 0, ii = oPage.act_schemas.length; i < ii; i++) {
                            if (oPage.act_schemas[i].name === 'submit') {
                                status.page.submitAfter = pagesByName[oPage.act_schemas[i].next] ? pagesByName[oPage.act_schemas[i].next] : { title: '未指定' };
                                break;
                            }
                        }
                    }
                } else if (oPage.type === 'V') {
                    if (oPage.act_schemas && oPage.act_schemas.length) {
                        for (var i = 0, ii = oPage.act_schemas.length; i < ii; i++) {
                            if (oPage.act_schemas[i].name === 'addRecord') {
                                status.page.addRecord = 'Y';
                            }
                            if (oPage.act_schemas[i].name === 'removeRecord') {
                                status.page.removeRecord = 'Y';
                            }
                            if (oPage.act_schemas[i].next === 'repos') {
                                status.page.repos = 'Y';
                            }
                            if (oPage.act_schemas[i].next === 'rank') {
                                status.page.rank = 'Y';
                            }
                        }
                    }
                } else if (oPage.type === 'L') {
                    if (oPage.data_schemas && oPage.data_schemas.length) {
                        status.page.browseHistory = 'Y';
                    }
                }
            });
        }, true);
        $scope.$watch('app.entry_rule', function(oRule) {
            if (!oRule) return;
            if (oRule.scope === 'member') {
                var mschemaIds = Object.keys(oRule.member);
                if (mschemaIds.length) {
                    http2.get('/rest/pl/fe/site/member/schema/overview?site=' + $scope.app.siteid + '&mschema=' + mschemaIds.join(','), function(rsp) {
                        var oMschema;
                        for (var schemaId in rsp.data) {
                            oMschema = rsp.data[schemaId];
                            status.user.member.push(oMschema);
                        }
                    });
                }
            } else if (oRule.scope === 'sns') {
                if (oRule.sns) {
                    if (oRule.sns.wx && oRule.sns.wx.entry) {
                        status.user.sns.push({ title: $scope.sns.wx.title });
                    }
                    if (oRule.sns.yx && oRule.sns.yx.entry) {
                        status.user.sns.push({ title: $scope.sns.yx.title });
                    }
                    if (oRule.sns.qy && oRule.sns.qy.entry) {
                        status.user.sns.push({ title: $scope.sns.qy.title });
                    }
                }
            }
        });
        $scope.$watch('app', function(oApp) {
            if (!oApp) return;
            http2.get('/rest/pl/fe/matter/enroll/receiver/list?site=' + oApp.siteid + '&app=' + oApp.id, function(rsp) {
                var map = { wx: '微信', yx: '易信', qy: '企业号' };
                rsp.data.forEach(function(receiver) {
                    if (receiver.sns_user) {
                        receiver.snsUser = JSON.parse(receiver.sns_user);
                        map[receiver.snsUser.src] && (receiver.snsUser.snsName = map[receiver.snsUser.src]);
                    }
                });
                $scope.admins = rsp.data;
            });
        });
        srvEnrollApp.opData().then(function(data) {
            if (data.length) {
                $scope.opData = data[0];
            }
        });
    }]);
});