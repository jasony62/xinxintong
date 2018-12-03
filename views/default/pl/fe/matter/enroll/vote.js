define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlVote', ['$scope', '$parse', 'http2', 'noticebox', 'srvEnrollApp', function($scope, $parse, http2, noticebox, srvEnlApp) {
        function fnWatchConfig(oConfig) {
            var $configScope;
            $configScope = $scope.$new(true);
            $configScope.config = oConfig;
            if (oConfig.id)
                _oConfigsModified[oConfig.id] = false;
            $configScope.$watch('config', function(nv, ov) {
                if (nv && nv !== ov && nv.id) {
                    _oConfigsModified[nv.id] = true;
                }
            }, true);
        }
        var _aConfigs, _oConfigsModified;
        $scope.configs = _aConfigs = [];
        $scope.configsModified = _oConfigsModified = {};
        $scope.addConfig = function() {
            _aConfigs.push({});
        };
        $scope.delConfig = function(oConfig) {
            noticebox.confirm('删除投票环节，确定？').then(function() {
                if (oConfig.id) {
                    http2.post('/rest/pl/fe/matter/enroll/updateVoteConfig?app=' + $scope.app.id, { method: 'delete', data: oConfig }).then(function() {
                        _aConfigs.splice(_aConfigs.indexOf(oConfig), 1);
                        delete _oConfigsModified[oConfig.id];
                    });
                } else {
                    _aConfigs.splice(_aConfigs.indexOf(oConfig), 1);
                }
            });
        };
        $scope.addVoteGroup = function(oConfig) {
            if (!$parse('role.groups')(oConfig)) {
                $parse('role.groups').assign(oConfig, [{}]);
            } else {
                $parse('role.groups')(oConfig).push({});
            }
        };
        $scope.delVoteGroup = function(oConfig, oVoteGroup) {
            oConfig.role.groups.splice(oConfig.role.groups.indexOf(oVoteGroup), 1);
        };
        $scope.save = function(oConfig) {
            http2.post('/rest/pl/fe/matter/enroll/updateVoteConfig?app=' + $scope.app.id, { method: 'save', data: oConfig }).then(function(rsp) {
                http2.merge(oConfig, rsp.data);
                fnWatchConfig(oConfig);
                noticebox.success('保存成功！');
            });
        };
        srvEnlApp.get().then(function(oApp) {
            $scope.votingSchemas = [];
            oApp.dataSchemas.forEach(function(oSchema) {
                if (!/html|single|multiplue|score/.test(oSchema.type)) {
                    $scope.votingSchemas.push(oSchema);
                }
            });
            if (oApp.voteConfig && oApp.voteConfig.length) {
                oApp.voteConfig.forEach(function(oConfig, index) {
                    var oCopied;
                    oCopied = angular.copy(oConfig);
                    _aConfigs.push(oCopied);
                    fnWatchConfig(oCopied);
                });
            }
        });
    }]);
});