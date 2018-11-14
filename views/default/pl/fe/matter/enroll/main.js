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
            http2.get(url).then(function(rsp) {
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
    ngApp.provider.controller('ctrlAccess', ['$scope', '$uibModal', 'srvSite', 'srvEnrollApp', 'srvEnrollSchema', 'tkEntryRule', 'tkGroupApp', function($scope, $uibModal, srvSite, srvEnlApp, srvEnrollSchema, tkEntryRule, tkGroupApp) {
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
        srvEnlApp.get().then(function(oApp) {
            $scope.jumpPages = srvEnlApp.jumpPages();
            _oApp = oApp;
            $scope.tkEntryRule = new tkEntryRule(oApp, $scope.sns);
            $scope.rule = _oAppRule = oApp.entryRule;
            // $scope.$watch('app.entryRule', function(nv, ov) {
            //     if (nv && nv !== ov) {
            //         $scope.update('entryRule');
            //     }
            // }, true);
        });
    }]);
});