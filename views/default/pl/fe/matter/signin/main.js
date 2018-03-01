define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlMain', ['$scope', 'http2', 'srvSigninApp', '$uibModal', 'srvTag', function($scope, http2, srvSigninApp, $uibModal, srvTag) {
        $scope.$on('xxt.tms-datepicker.change', function(event, data) {
            $scope.app[data.state] = data.value;
            srvSigninApp.update(data.state);
        });
        $scope.assignMission = function() {
            srvSigninApp.assignMission();
        };
        $scope.quitMission = function() {
            srvSigninApp.quitMission();
        };
        $scope.remove = function() {
            if (window.confirm('确定删除？')) {
                http2.get('/rest/pl/fe/matter/signin/remove?site=' + $scope.app.siteid + '&app=' + $scope.app.id, function(rsp) {
                    if ($scope.app.mission) {
                        location = "/rest/pl/fe/matter/mission?site=" + $scope.app.siteid + "&id=" + $scope.app.mission.id;
                    } else {
                        location = '/rest/pl/fe/site/console?site=' + $scope.app.siteid;
                    }
                });
            }
        };
        $scope.tagMatter = function(subType) {
            var oTags;
            oTags = $scope.oTag;
            srvTag._tagMatter($scope.app, oTags, subType);
        };
        srvSigninApp.get().then(function(oApp) {
            $scope.defaultTime = {
                start_at: oApp.start_at > 0 ? oApp.start_at : (function() {
                    var t;
                    t = new Date;
                    t.setHours(8);
                    t.setMinutes(0);
                    t.setMilliseconds(0);
                    t.setSeconds(0);
                    t = parseInt(t / 1000);
                    return t;
                })()
            };
        });
    }]);
    ngApp.provider.controller('ctrlReceiver', ['$scope', 'http2', '$interval', '$uibModal', 'srvSigninApp', function($scope, http2, $interval, $uibModal, srvSigninApp) {
        function listReceivers(app) {
            http2.get(baseURL + 'list?site=' + app.siteid + '&app=' + app.id, function(rsp) {
                var map = { wx: '微信', yx: '易信', qy: '企业号' };
                rsp.data.forEach(function(receiver) {
                    if (receiver.sns_user) {
                        receiver.snsUser = JSON.parse(receiver.sns_user);
                        map[receiver.snsUser.src] && (receiver.snsUser.snsName = map[receiver.snsUser.src]);
                    }
                });
                $scope.receivers = rsp.data;
            });
        }

        var baseURL = '/rest/pl/fe/matter/signin/receiver/';
        $scope.qrcodeShown = false;
        $scope.qrcode = function(snsName) {
            if ($scope.qrcodeShown === false) {
                var url = '/rest/pl/fe/site/sns/' + snsName + '/qrcode/createOneOff';
                url += '?site=' + $scope.app.siteid;
                url += '&matter_type=signinreceiver';
                url += '&matter_id=' + $scope.app.id;
                http2.get(url, function(rsp) {
                    var qrcode = rsp.data,
                        eleQrcode = $("#" + snsName + "Qrcode");
                    eleQrcode.trigger('show');
                    $scope.qrcodeURL = qrcode.pic;
                    $scope.qrcodeShown = true;
                    (function() {
                        var fnCheckQrcode, url2;
                        url2 = '/rest/pl/fe/site/sns/' + snsName + '/qrcode/get';
                        url2 += '?site=' + qrcode.siteid;
                        url2 += '&id=' + rsp.data.id;
                        url2 += '&cascaded=N';
                        fnCheckQrcode = $interval(function() {
                            http2.get(url2, function(rsp) {
                                if (rsp.data == false) {
                                    $interval.cancel(fnCheckQrcode);
                                    eleQrcode.trigger('hide');
                                    $scope.qrcodeShown = false;
                                    (function() {
                                        var fnCheckReceiver;
                                        fnCheckReceiver = $interval(function() {
                                            http2.get('/rest/pl/fe/matter/signin/receiver/afterJoin?site=' + $scope.app.siteid + '&app=' + $scope.app.id + '&timestamp=' + qrcode.create_at, function(rsp) {
                                                if (rsp.data.length) {
                                                    $interval.cancel(fnCheckReceiver);
                                                    $scope.receivers = $scope.receivers.concat(rsp.data);
                                                }
                                            });
                                        }, 2000);
                                    })();
                                }
                            });
                        }, 2000);
                    })();
                });
            } else {
                $("#yxQrcode").trigger('hide');
                $scope.qrcodeShown = false;
            }
        };
        $scope.remove = function(receiver) {
            http2.get(baseURL + 'remove?site=' + $scope.app.siteid + '&app=' + $scope.app.id + '&receiver=' + receiver.userid, function(rsp) {
                $scope.receivers.splice($scope.receivers.indexOf(receiver), 1);
            });
        };
        srvSigninApp.get().then(function(app) {
            listReceivers(app);
        });
    }]);
    ngApp.provider.controller('ctrlAccess', ['$scope', '$uibModal', 'http2', 'srvSite', 'srvSigninApp', 'srvEnrollSchema', function($scope, $uibModal, http2, srvSite, srvSigninApp, srvEnrollSchema) {
        var oEntryRule;
        $scope.rule = {};
        $scope.changeUserScope = function(scopeProp) {
            switch (scopeProp) {
                case 'sns':
                    if ($scope.rule.scope[scopeProp] === 'Y') {
                        if (!$scope.rule.sns) {
                            $scope.rule.sns = {};
                        }
                        if ($scope.snsCount === 1) {
                            $scope.rule.sns[Object.keys($scope.sns)[0]] = { 'entry': 'Y' };
                        }
                    }
                    break;
            }
            srvSigninApp.changeUserScope($scope.rule.scope, $scope.sns);
        };
        $scope.chooseMschema = function() {
            srvSite.chooseMschema($scope.app).then(function(result) {
                var rule = {};
                if (!oEntryRule.member[result.chosen.id]) {
                    rule.entry = 'Y';
                    oEntryRule.member[result.chosen.id] = rule;
                    $scope.update('entryRule');
                }
            });
        };
        $scope.editMschema = function(oMschema) {
            if (oMschema.matter_id) {
                if (oMschema.matter_type === 'mission') {
                    location.href = '/rest/pl/fe/matter/mission/mschema?id=' + oMschema.matter_id + '&site=' + $scope.app.siteid + '#' + oMschema.id;
                } else {
                    location.href = '/rest/pl/fe/site/mschema?site=' + $scope.app.siteid + '#' + oMschema.id;
                }
            } else {
                location.href = '/rest/pl/fe?view=main&scope=user&sid=' + $scope.app.siteid + '#' + oMschema.id;
            }
        };
        $scope.removeMschema = function(mschemaId) {
            var bSchemaChanged;
            if (oEntryRule.member[mschemaId]) {
                /* 取消题目和通信录的关联 */
                $scope.app.dataSchemas.forEach(function(oSchema) {
                    var _oBeforeState;
                    if (oSchema.type === 'member') {
                        _oBeforeState = angular.copy(oSchema);
                        oSchema.type = 'shorttext';
                        delete oSchema.schema_id;
                        srvEnrollSchema.update(oSchema, _oBeforeState);
                        bSchemaChanged = true;
                    }
                });
                if (bSchemaChanged) {
                    srvEnrollSchema.submitChange($scope.app.pages);
                }
                delete oEntryRule.member[mschemaId];
                $scope.update('entryRule');
            }
        };
        srvSigninApp.get().then(function(app) {
            $scope.jumpPages = srvSigninApp.jumpPages();
            $scope.rule = oEntryRule = app.entryRule;
        }, true);
    }]);
});