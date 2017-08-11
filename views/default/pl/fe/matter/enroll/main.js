define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlMain', ['$scope', 'http2', '$uibModal', 'noticebox', 'srvSite', 'srvEnrollApp', 'srvTag', function($scope, http2, $uibModal, noticebox, srvSite, srvEnrollApp, srvTag) {
        $scope.assignMission = function() {
            srvEnrollApp.assignMission().then(function(mission) {});
        };
        $scope.tagMatter = function(subType){
            var oTags;
            oTags = $scope.oTag;
            srvTag._tagMatter($scope.app, oTags, subType);
        };
        $scope.quitMission = function() {
            srvEnrollApp.quitMission().then(function() {});
        };
        $scope.choosePhase = function() {
            srvEnrollApp.choosePhase();
        };
        $scope.remove = function() {
            if (window.confirm('确定删除活动？')) {
                srvEnrollApp.remove().then(function() {
                    if ($scope.app.mission) {
                        location = "/rest/pl/fe/matter/mission?site=" + $scope.app.siteid + "&id=" + $scope.app.mission.id;
                    } else {
                        location = '/rest/pl/fe/site/console?site=' + $scope.app.siteid;
                    }
                });
            }
        };
        $scope.exportAsTemplate = function() {
            var url;
            url = '/rest/pl/fe/matter/enroll/exportAsTemplate?site=' + $scope.app.siteid + '&app=' + $scope.app.id;
            window.open(url);
        };
        $scope.applyToHome = function() {
            var url = '/rest/pl/fe/matter/home/apply?site=' + $scope.app.siteid + '&type=enroll&id=' + $scope.app.id;
            http2.get(url, function(rsp) {
                noticebox.success('完成申请！');
            });
        };
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
        srvEnrollApp.summary().then(function(data) {
            if (data.length) {
                $scope.summary = data[0];
            } else {
                $scope.summary = data;
            }
        });
    }]);
});
