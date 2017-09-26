define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlCoworker', ['$scope', 'http2', 'noticebox', function($scope, http2, noticebox) {
        $scope.label = '';
        $scope.newOwner = '';
        $scope.transfer = function() {
            var url = '/rest/pl/fe/matter/mission/coworker/transferMission?site=' + $scope.mission.siteid;
            url += '&mission=' + $scope.mission.id + '&label=' + $scope.newOwner;
            http2.get(url, function(rsp) {
                noticebox.success('完成移交');
                if (rsp.data == 1) {
                    $scope.status = true;
                }
                $scope.newOwner = '';
            });
        };
        $scope.openMyCoworkers = function() {
            if ($scope.myCoworkers && $scope.myCoworkers.length) {
                $('#popoverMyCoworker').trigger('show');
            }
        };
        $scope.closeMyCoworkers = function() {
            $('#popoverMyCoworker').trigger('hide');
        };
        $scope.chooseMyCoworker = function(coworker) {
            $scope.label = coworker.coworker_label;
            $('#popoverMyCoworker').trigger('hide');
        };
        $scope.add = function() {
            var url = '/rest/pl/fe/matter/mission/coworker/add?mission=' + $scope.mission.id;
            url += '&label=' + $scope.label;
            http2.get(url, function(rsp) {
                $scope.coworkers.splice(0, 0, rsp.data);
                if ($scope.myCoworkers && $scope.myCoworkers.length) {
                    for (var i = 0, ii = $scope.myCoworkers.length; i < ii; i++) {
                        if ($scope.label === $scope.myCoworkers[i].coworker_label) {
                            $scope.myCoworkers.splice(i, 1);
                            break;
                        }
                    }
                }
                $scope.label = '';
            });
        };
        $scope.remove = function(acl) {
            http2.get('/rest/pl/fe/matter/mission/coworker/remove?mission=' + $scope.mission.id + '&coworker=' + acl.coworker, function(rsp) {
                var index = $scope.coworkers.indexOf(acl);
                $scope.coworkers.splice(index, 1);
            });
        };
        $scope.makeInvite = function() {
            http2.get('/rest/pl/fe/matter/mission/coworker/makeInvite?mission=' + $scope.mission.id, function(rsp) {
                var host, url;
                host = $scope.mission.opUrl.match(/\/\/(\S+?)\//);
                host = host.length === 2 ? host[1] : location.host;
                url = 'http://' + host + rsp.data;
                $scope.inviteURL = url;
                $('#shareMission').trigger('show');
            });
        };
        $scope.closeInvite = function() {
            $scope.inviteURL = '';
            $('#shareMission').trigger('hide');
        };
        $scope.$watch('mission', function(mission) {
            if (mission) {
                http2.get('/rest/pl/fe/matter/mission/coworker/list?mission=' + $scope.mission.id, function(rsp) {
                    $scope.coworkers = rsp.data;
                });
                http2.get('/rest/pl/fe/matter/mission/coworker/mine?mission=' + $scope.mission.id, function(rsp) {
                    $scope.myCoworkers = rsp.data;
                });
            }
        });
    }]);
});