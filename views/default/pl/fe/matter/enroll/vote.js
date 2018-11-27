define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlVote', ['$scope', 'http2', 'noticebox', 'srvEnrollApp', function($scope, http2, noticebox, srvEnlApp) {
        var _aConfigs;
        $scope.voteConfig = _aConfigs = [];
        $scope.addConfig = function() {
            _aConfigs.push({ data: {} });
        };
        $scope.delConfig = function(oConfig) {
            noticebox.confirm('删除投票环节，确定？').then(function() {
                if (oConfig.data.id) {
                    http2.post('/rest/pl/fe/matter/enroll/updateVoteConfig?app=' + $scope.app.id, { method: 'delete', data: oConfig.data }).then(function() {
                        _aConfigs.splice(_aConfigs.indexOf(oConfig), 1);
                    });
                } else {
                    _aConfigs.splice(_aConfigs.indexOf(oConfig), 1);
                }
            });
        };
        $scope.save = function(oConfig) {
            http2.post('/rest/pl/fe/matter/enroll/updateVoteConfig?app=' + $scope.app.id, { method: 'save', data: oConfig.data }).then(function() {});
        };
        srvEnlApp.get().then(function(oApp) {
            $scope.votingSchemas = [];
            oApp.dataSchemas.forEach(function(oSchema) {
                if (!/html|single|multiplue|score/.test(oSchema.type)) {
                    $scope.votingSchemas.push(oSchema);
                }
            });
            if (oApp.voteConfig && oApp.voteConfig.length) {
                oApp.voteConfig.forEach(function(oConfig) {
                    _aConfigs.push({ data: angular.copy(oConfig) });
                });
            }
        });
    }]);
});