define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlMain', ['$scope', '$anchorScroll', 'http2', '$uibModal', 'noticebox', 'srvSite', 'srvEnrollApp', 'srvTag', function($scope, $anchorScroll, http2, $uibModal, noticebox, srvSite, srvEnrollApp, srvTag) {
        $scope.assignMission = function() {
            srvEnrollApp.assignMission().then(function(mission) {});
        };
        $scope.tagMatter = function(subType) {
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
        $scope.$on('xxt.tms-datepicker.change', function(event, data) {
            $scope.app[data.state] = data.value;
            srvEnrollApp.update(data.state);
        });
        srvEnrollApp.get().then(function(oApp) {
            $scope.bCountLimited = oApp.count_limit !== '0';
            $('#main-view').scrollspy({ target: '#mainScrollspy' });
            $('#mainScrollspy>ul').affix({
                offset: {
                    top: 0
                }
            });
            $anchorScroll();
        });
    }]);
    ngApp.provider.controller('ctrlAccess', ['$scope', '$uibModal', 'http2', 'srvSite', 'srvEnrollApp', function($scope, $uibModal, http2, srvSite, srvEnrollApp) {
        var _oApp, _oEntryRule;
        $scope.rule = {};
        $scope.isInputPage = function(pageName) {
            if (!$scope.app) {
                return false;
            }
            for (var i in $scope.app.pages) {
                if ($scope.app.pages[i].name === pageName && $scope.app.pages[i].type === 'I') {
                    return true;
                }
            }
            return false;
        };
        $scope.reset = function() {
            srvEnrollApp.resetEntryRule();
        };
        $scope.changeUserScope = function() {
            srvEnrollApp.changeUserScope($scope.rule.scope, $scope.sns, $scope.memberSchemas, $scope.jumpPages.defaultInput);
        };
        $scope.chooseMschema = function() {
            srvSite.chooseMschema($scope.app).then(function(result) {
                var rule = {};
                if (!_oEntryRule.member[result.chosen.id]) {
                    if ($scope.jumpPages.defaultInput) {
                        rule.entry = $scope.jumpPages.defaultInput.name;
                    } else {
                        rule.entry = '';
                    }
                    _oEntryRule.member[result.chosen.id] = rule;
                    $scope.update('entry_rule');
                }
            });
        };
        $scope.chooseGroupApp = function() {
            $uibModal.open({
                templateUrl: 'chooseGroupApp.html',
                controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                    $scope2.app = _oApp;
                    $scope2.data = {
                        app: null,
                        round: null
                    };
                    _oApp.mission && ($scope2.data.sameMission = 'Y');
                    $scope2.cancel = function() {
                        $mi.dismiss();
                    };
                    $scope2.ok = function() {
                        $mi.close($scope2.data);
                    };
                    var url = '/rest/pl/fe/matter/group/list?site=' + _oApp.siteid + '&size=999&cascaded=Y';
                    _oApp.mission && (url += '&mission=' + _oApp.mission.id);
                    http2.get(url, function(rsp) {
                        $scope2.apps = rsp.data.apps;
                    });
                }],
                backdrop: 'static'
            }).result.then(function(result) {
                if (result.app) {
                    _oEntryRule.group = { id: result.app.id, title: result.app.title };
                    if (result.round) {
                        _oEntryRule.group.round = { id: result.round.round_id, title: result.round.title };
                    }
                    $scope.update('entry_rule');
                }
            });
        };
        $scope.removeGroupApp = function() {
            delete _oEntryRule.group;
            $scope.update('entry_rule');
        };
        $scope.editMschema = function(oMschema) {
            if (oMschema.matter_id) {
                if (oMschema.matter_type === 'mission') {
                    location.href = '/rest/pl/fe/matter/mission/mschema?id=' + oMschema.matter_id + '&site=' + $scope.app.siteid + '#' + oMschema.id;
                } else {
                    location.href = '/rest/pl/fe/site/mschema?site=' + $scope.app.siteid + '#' + oMschema.id;
                }
            } else {
                location.href = '/rest/pl/fe?view=main&scope=user&sid=' + $scope.app.siteid + '&mschema=' + oMschema.id;
            }
        };
        $scope.removeMschema = function(mschemaId) {
            if (_oEntryRule.member[mschemaId]) {
                delete _oEntryRule.member[mschemaId];
                $scope.update('entry_rule');
            }
        };
        $scope.$watch('memberSchemas', function(nv) {
            if (!nv) return;
            $scope.mschemasById = {};
            $scope.memberSchemas.forEach(function(mschema) {
                $scope.mschemasById[mschema.id] = mschema;
            });
        }, true);
        $scope.addExclude = function() {
            var rule = $scope.rule;
            if (!rule.exclude) {
                rule.exclude = [];
            }
            rule.exclude.push('');
        };
        $scope.removeExclude = function(index) {
            $scope.rule.exclude.splice(index, 1);
            $scope.configExclude();
        };
        $scope.configExclude = function() {
            $scope.app.entry_rule.exclude = $scope.rule.exclude;
            $scope.update('entry_rule');
        };
        $scope.bCountLimited = false;
        srvEnrollApp.get().then(function(app) {
            $scope.jumpPages = srvEnrollApp.jumpPages();
            $scope.rule.scope = app.entry_rule.scope || 'none';
            $scope.rule.exclude = app.entry_rule.exclude;
            _oEntryRule = app.entry_rule;
            _oApp = app;
        }, true);
    }]);
});