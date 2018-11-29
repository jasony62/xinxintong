define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlVote', ['$scope', '$parse', 'http2', 'noticebox', 'srvEnrollApp', function($scope, $parse, http2, noticebox, srvEnlApp) {
        function fnWatchWrap(oWrap) {
            var $wrapScope;
            $wrapScope = $scope.$new(true);
            $wrapScope.wrap = oWrap;
            $wrapScope.$watch('wrap', function(nv, ov) {
                if (nv && nv !== ov) {
                    nv.modified = true;
                }
            }, true);
        }
        var _aWraps;
        $scope.wraps = _aWraps = [];
        $scope.addConfig = function() {
            var oNewWrap;
            oNewWrap = { data: {}, modified: true };
            _aWraps.push(oNewWrap);
            fnWatchWrap(oNewWrap);
        };
        $scope.delConfig = function(oWrap) {
            noticebox.confirm('删除投票环节，确定？').then(function() {
                if (oWrap.data.id) {
                    http2.post('/rest/pl/fe/matter/enroll/updateVoteConfig?app=' + $scope.app.id, { method: 'delete', data: oWrap.data }).then(function() {
                        _aWraps.splice(_aWraps.indexOf(oWrap), 1);
                    });
                } else {
                    _aWraps.splice(_aWraps.indexOf(oWrap), 1);
                }
            });
        };
        $scope.addVoteGroup = function(oWrap) {
            if (!$parse('data.role.groups')(oWrap)) {
                $parse('data.role.groups').assign(oWrap, [{}]);
            } else {
                $parse('data.role.groups')(oWrap).push({});
            }
        };
        $scope.delVoteGroup = function(oWrap, oVoteGroup) {
            oWrap.data.role.groups.splice(oWrap.data.role.groups.indexOf(oVoteGroup), 1);
        };
        $scope.save = function(oWrap) {
            http2.post('/rest/pl/fe/matter/enroll/updateVoteConfig?app=' + $scope.app.id, { method: 'save', data: oWrap.data }).then(function(rsp) {
                http2.merge(oWrap.data, rsp.data);
                oWrap.modified = false;
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
                    var oWrap;
                    oWrap = { data: angular.copy(oConfig), index: index };
                    _aWraps.push(oWrap);
                    fnWatchWrap(oWrap);
                });
            }
        });
    }]);
});