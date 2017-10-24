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
            if (window.confirm('确定将[' + $scope.app.title + ']从项目中移除？')) {
                srvEnrollApp.quitMission().then(function() {});
            }
        };
        $scope.choosePhase = function() {
            srvEnrollApp.choosePhase();
        };
        $scope.remove = function() {
            if (window.confirm('确定删除[' + $scope.app.title + ']？')) {
                srvEnrollApp.remove().then(function() {
                    if ($scope.app.mission) {
                        location = "/rest/pl/fe/matter/mission?site=" + $scope.app.siteid + "&id=" + $scope.app.mission.id;
                    } else {
                        location = '/rest/pl/fe?view=main&scope=activity&type=enroll&sid=' + $scope.app.siteid;
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
            $('#main-view').height($('#pl-layout-main').height());
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
        function chooseGroupApp() {
            return $uibModal.open({
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
            }).result;
        }

        function setMschemaEntry(mschemaId) {
            if (!_oAppRule.member) {
                _oAppRule.member = {};
            }
            if (!_oAppRule.member[mschemaId]) {
                _oAppRule.member[mschemaId] = {
                    entry: $scope.jumpPages.defaultInput ? $scope.jumpPages.defaultInput.name : ''
                };
                return true;
            }
            return false;
        }

        function setGroupEntry(oResult) {
            if (oResult.app) {
                _oAppRule.group = { id: oResult.app.id, title: oResult.app.title };
                if (oResult.round) {
                    _oAppRule.group.round = { id: oResult.round.round_id, title: oResult.round.title };
                }
                return true;
            }
            return false;
        }

        var _oApp, _oAppRule, _oBeforeRule;
        $scope.rule = {};
        $scope.isInputPage = function(pageName) {
            if (!$scope.app) {
                return false;
            }
            for (var i in _oApp.pages) {
                if (_oApp.pages[i].name === pageName && _oApp.pages[i].type === 'I') {
                    return true;
                }
            }
            return false;
        };
        $scope.reset = function() {
            srvEnrollApp.resetEntryRule();
        };
        $scope.changeUserScope = function() {
            if ($scope.rule.scope === 'member' && (!_oAppRule.member || Object.keys(_oAppRule.member).length === 0)) {
                srvSite.chooseMschema(_oApp).then(function(result) {
                    setMschemaEntry(result.chosen.id);
                    srvEnrollApp.changeUserScope($scope.rule.scope, $scope.sns, $scope.jumpPages.defaultInput).then(function(rsp) {
                        _oBeforeRule = angular.copy($scope.rule);
                    });
                }, function(reason) {
                    $scope.rule.scope = _oBeforeRule.scope;
                });
            } else if ($scope.rule.scope === 'group' && (!_oAppRule.group || !_oAppRule.group.id)) {
                chooseGroupApp().then(function(result) {
                    if (setGroupEntry(result)) {
                        srvEnrollApp.changeUserScope($scope.rule.scope, $scope.sns, $scope.jumpPages.defaultInput).then(function(rsp) {
                            _oBeforeRule = angular.copy($scope.rule);
                        });
                    }
                }, function(reason) {
                    $scope.rule.scope = _oBeforeRule.scope;
                });
            } else {
                srvEnrollApp.changeUserScope($scope.rule.scope, $scope.sns, $scope.jumpPages.defaultInput).then(function(rsp) {
                    _oBeforeRule = angular.copy($scope.rule);
                });
            }
        };
        $scope.chooseMschema = function() {
            srvSite.chooseMschema(_oApp).then(function(result) {
                if (setMschemaEntry(result.chosen.id)) {
                    $scope.update('entry_rule');
                }
            });
        };
        $scope.chooseGroupApp = function() {
            chooseGroupApp().then(function(result) {
                if (setGroupEntry(result)) {
                    $scope.update('entry_rule');
                }
            });
        };
        $scope.removeGroupApp = function() {
            delete _oAppRule.group;
            $scope.update('entry_rule');
        };
        $scope.editMschema = function(oMschema) {
            if (oMschema.matter_id) {
                if (oMschema.matter_type === 'mission') {
                    location.href = '/rest/pl/fe/matter/mission/mschema?id=' + oMschema.matter_id + '&site=' + _oApp.siteid + '#' + oMschema.id;
                } else {
                    location.href = '/rest/pl/fe/site/mschema?site=' + _oApp.siteid + '#' + oMschema.id;
                }
            } else {
                location.href = '/rest/pl/fe?view=main&scope=user&sid=' + _oApp.siteid + '&mschema=' + oMschema.id;
            }
        };
        $scope.removeMschema = function(mschemaId) {
            if (_oAppRule.member[mschemaId]) {
                delete _oAppRule.member[mschemaId];
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
            _oApp.entry_rule.exclude = $scope.rule.exclude;
            $scope.update('entry_rule').then(function(rsp) {
                _oBeforeRule = angular.copy($scope.rule);
            });
        };
        srvEnrollApp.get().then(function(app) {
            $scope.jumpPages = srvEnrollApp.jumpPages();
            _oApp = app;
            _oAppRule = app.entry_rule;
            $scope.rule.scope = _oAppRule.scope || 'none';
            $scope.rule.exclude = _oAppRule.exclude;
            _oBeforeRule = angular.copy($scope.rule);
        }, true);
    }]);
});