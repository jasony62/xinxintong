define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlMain', ['$scope', 'http2', 'srvSigninApp', 'srvTag', function($scope, http2, srvSigApp, srvTag) {
        $scope.$on('xxt.tms-datepicker.change', function(event, data) {
            $scope.app[data.state] = data.value;
            srvSigApp.update(data.state);
        });
        $scope.assignMission = function() {
            srvSigApp.assignMission();
        };
        $scope.quitMission = function() {
            srvSigApp.quitMission();
        };
        $scope.remove = function() {
            if (window.confirm('确定删除？')) {
                http2.get('/rest/pl/fe/matter/signin/remove?site=' + $scope.app.siteid + '&app=' + $scope.app.id).then(function(rsp) {
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
        srvSigApp.get().then(function(oApp) {
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
    ngApp.provider.controller('ctrlAccess', ['$scope', 'srvSigninApp', 'tkEntryRule', function($scope, srvSigApp, tkEntryRule) {
        srvSigApp.get().then(function(oApp) {
            $scope.jumpPages = srvSigApp.jumpPages();
            $scope.rule = oApp.entryRule;
            $scope.tkEntryRule = new tkEntryRule(oApp, $scope.sns);
        }, true);
        $scope.$watch('app.entryRule', function(nv, ov) {
            if (nv && nv !== ov) {
                srvSigApp.renew(['enrollApp', 'groupApp']);
            }
        });
    }]);
});