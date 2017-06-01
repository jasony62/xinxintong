'use strict';
require('../css/xxt.ui.modal.css');

require('./xxt.ui.modal.js');

var ngMod = angular.module('subscribe.ui.xxt', ['modal.ui.xxt']);
ngMod.service('tmsSubscribe', ['$http', 'tmsModal', function($http, tmsModal) {
    this.open = function(oUser, oSite) {
        var template;
        template = '<div class="modal-header"><span class="modal-title">关注团队，接收该团队发布的内容</span></div>';
        template += '<div class="modal-body">';
        template += '<div class="checkbox">';
        template += '<label>';
        template += '<input type=\'checkbox\' ng-true-value="\'Y\'" ng-false-value="\'N\'" ng-model=\'atSite._selected\'>';
        template += '<span>个人账户</span>';
        template += '<span ng-if="atSite._subscribed===\'Y\'">（已关注）</span>';
        template += '</label>';
        template += '</div>';
        template += '<div class="checkbox" ng-repeat="site in mySites">';
        template += '<label>';
        template += '<input type=\'checkbox\' ng-true-value="\'Y\'" ng-false-value="\'N\'" ng-model=\'site._selected\'>';
        template += '<span>{{site.name}}</span>';
        template += '<span ng-if="site._subscribed===\'Y\'">（已关注）</span>';
        template += '</label>';
        template += '</div>'
        template += '<div ng-if="mySites.length===0"><a href="" ng-click="createSite()">创建</a>团队进行关注，方便团队内共享信息</div>';
        template += '</div>';
        template += '<div class="modal-footer"><button class="btn btn-default" ng-click="cancel()">关闭</button><button class="btn btn-success" ng-click="ok()">确定</button></div>';
        tmsModal.open({
            template: template,
            controller: ['$scope', '$tmsModalInstance', function($scope2, $mi) {
                $http.get('/rest/site/home/get?site=' + oSite.id + '&_=' + (new Date() * 1)).success(function(rsp) {
                    var atSite = rsp.data;
                    atSite._selected = atSite._subscribed;
                    $scope2.atSite = atSite;
                });
                $http.get('/rest/pl/fe/site/subscribe/sitesByUser?site=' + oSite.id + '&_=' + (new Date() * 1)).success(function(rsp) {
                    if (rsp.err_code != 0) {
                        return;
                    }
                    var mySites = rsp.data;
                    mySites.forEach(function(site) {
                        site._selected = site._subscribed;
                    });
                    $scope2.mySites = mySites;
                });
                $scope2.createSite = function() {
                    $http.get('/rest/pl/fe/site/create').success(function(rsp) {
                        var site = rsp.data;
                        site._subscribed = site._selected = 'N';
                        $scope2.mySites = [site];
                    })
                };
                $scope2.ok = function() {
                    var result;
                    result = {
                        atSite: $scope2.atSite,
                        mySites: $scope2.mySites
                    }
                    $mi.close(result);
                };
                $scope2.cancel = function() {
                    $mi.dismiss();
                };
            }]
        }).result.then(function(result) {
            var url, atSite, mySites;
            atSite = result.atSite;
            if (atSite && atSite._selected !== atSite._subscribed) {
                if (atSite._selected === 'Y') {
                    url = '/rest/site/fe/user/site/subscribe?site=' + oSite.id + '&target=' + atSite.id;
                } else {
                    url = '/rest/site/fe/user/site/unsubscribe?site=' + oSite.id + '&target=' + atSite.id;
                }
                $http.get(url);
            }
            mySites = result.mySites;
            if (mySites) {
                var subscribed = [],
                    unsubscribed = [];
                mySites.forEach(function(site) {
                    if (site._selected !== site._subscribed) {
                        if (site._selected === 'Y') {
                            subscribed.push(site.id);
                        } else {
                            unsubscribed.push(site.id);
                        }
                    }
                });
                if (subscribed.length) {
                    var url = '/rest/pl/fe/site/subscribe/do?site=' + oSite.id;
                    url += '&subscriber=' + subscribed.join(',');
                    $http.get(url);
                }
            }
        });
    };
}]);
