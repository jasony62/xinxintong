'use strict';
require('../css/xxt.ui.modal.css');

require('./xxt.ui.modal.js');

var ngMod = angular.module('contribute.ui.xxt', ['modal.ui.xxt']);
ngMod.service('tmsContribute', ['$http', 'tmsModal', function($http, tmsModal) {
    this.open = function(oUser, oSite) {
        var template;
        template = '<div class="modal-header"><span class="modal-title">投稿，将自己团队中的内容发给当前团队</span></div>';
        template += '<div class="modal-body">';
        template += '<dl>';
        template += '<dd ng-repeat="m in selectedMatters" ng-click="unchooseMatter(m)"><span>{{m.title}}</span></dd>';
        template += '</dl>';
        template += '<select ng-options="site.id as site.name for site in mySites" ng-model="data.chooseSite" ng-change="chooseSite()"></select>';
        template += '<dl>';
        template += '<dd ng-repeat="m in matters" ng-click="chooseMatter(m)"><span>{{m.title}}</span></dd>';
        template += '</dl>';
        template += '</div>';
        template += '<div class="modal-footer"><button class="btn btn-default" ng-click="cancel()">关闭</button><button class="btn btn-success" ng-click="ok()">确定</button></div>';
        tmsModal.open({
            template: template,
            controller: ['$scope', '$tmsModalInstance', function($scope2, $mi) {
                $http.get('/rest/pl/fe/site/list?site=' + oSite.id + '&_=' + (new Date() * 1)).success(function(rsp) {
                    if (rsp.err_code != 0) {
                        return;
                    }
                    var mySites = rsp.data;
                    mySites.forEach(function(site) {
                        site._selected = site._subscribed;
                    });
                    $scope2.mySites = mySites;
                });
                var data, selectedMatters;
                $scope2.data = data = {};
                $scope2.selectedMatters = selectedMatters = [];
                $scope2.chooseSite = function() {
                    $http.get('/rest/pl/fe/matter/article/list?site=' + data.chooseSite).success(function(rsp) {
                        $scope2.matters = rsp.data.docs;
                    });
                };
                $scope2.chooseMatter = function(matter) {
                    if (selectedMatters.indexOf(matter) === -1) {
                        selectedMatters.push(matter);
                    }
                };
                $scope2.unchooseMatter = function(matter) {
                    selectedMatters.splice(selectedMatters.indexOf(matter), 1);
                };
                $scope2.ok = function() {
                    if (selectedMatters.length) {
                        $http.post('/rest/pl/fe/site/contribute/do?site=' + oSite.id, selectedMatters).success(function(rsp) {
                            $mi.close();
                        });
                    } else {
                        $mi.close();
                    }
                };
                $scope2.cancel = function() {
                    $mi.dismiss();
                };
            }]
        });
    };
}]);