define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlMain', ['$scope', '$anchorScroll', 'http2', '$uibModal', 'noticebox', 'srvSite', 'srvEnrollApp', 'srvTag', function($scope, $anchorScroll, http2, $uibModal, noticebox, srvSite, srvEnrollApp, srvTag) {
        $scope.assignMission = function() {
            srvEnrollApp.assignMission().then(function(mission) {});
        };
        $scope.quitMission = function() {
            if (window.confirm('确定将[' + $scope.app.title + ']从项目中移除？')) {
                srvEnrollApp.quitMission().then(function() {});
            }
        };
        $scope.tagMatter = function(subType) {
            var oTags;
            oTags = $scope.oTag;
            srvTag._tagMatter($scope.app, oTags, subType);
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
            $scope.bCountLimited = oApp.count_limit !== '0';
            $('#main-view').height($('#pl-layout-main').height());
            $('#main-view').scrollspy({ target: '#mainScrollspy' });
            $('#mainScrollspy>ul').affix({
                offset: {
                    top: 0
                }
            });
        });
    }]);
    ngApp.provider.controller('ctrlAccess', ['$scope', '$uibModal', 'http2', 'srvSite', 'srvEnrollApp', 'srvEnrollSchema', function($scope, $uibModal, http2, srvSite, srvEnrollApp, srvEnrollSchema) {
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
                    entry: 'Y'
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

        var _oApp, _oAppRule;
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
            srvEnrollApp.changeUserScope($scope.rule.scope, $scope.sns);
        };
        $scope.chooseMschema = function() {
            srvSite.chooseMschema(_oApp).then(function(result) {
                if (setMschemaEntry(result.chosen.id)) {
                    $scope.update('entryRule');
                }
            });
        };
        $scope.chooseGroupApp = function() {
            chooseGroupApp().then(function(result) {
                if (setGroupEntry(result)) {
                    $scope.update('entryRule');
                }
            });
        };
        $scope.removeGroupApp = function() {
            delete _oAppRule.group;
            $scope.update('entryRule');
        };
        $scope.removeMschema = function(mschemaId) {
            var bSchemaChanged = false;
            if (_oAppRule.member[mschemaId]) {
                /* 取消题目和通信录的关联 */
                _oApp.dataSchemas.forEach(function(oSchema) {
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
                    srvEnrollSchema.submitChange(_oApp.pages);
                }
                delete _oAppRule.member[mschemaId];
                $scope.update('entryRule');
            }
        };
        $scope.addExclude = function() {
            if (!_oAppRule.exclude) {
                _oAppRule.exclude = [];
            }
            _oAppRule.exclude.push('');
        };
        $scope.removeExclude = function(index) {
            _oAppRule.exclude.splice(index, 1);
            $scope.configExclude();
        };
        $scope.configExclude = function() {
            $scope.update('entryRule');
        };
        srvEnrollApp.get().then(function(app) {
            $scope.jumpPages = srvEnrollApp.jumpPages();
            _oApp = app;
            $scope.rule = _oAppRule = app.entryRule;
        }, true);
    }]);
});