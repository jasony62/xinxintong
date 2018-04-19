'use strict';
require('../css/xxt.ui.modal.css');

require('./xxt.ui.page.js');
require('./xxt.ui.modal.js');

var ngMod = angular.module('forward.ui.xxt', ['page.ui.xxt', 'modal.ui.xxt']);
ngMod.service('tmsForward', ['$rootScope', '$http', '$q', 'tmsDynaPage', 'tmsModal', function($rootScope, $http, $q, tmsDynaPage, tmsModal) {
    function bySite(oMatter) {
        var url, defer;
        defer = $q.defer();
        url = '/rest/pl/fe/site/forward/sitesByUser?site=' + oMatter.siteid + '&id=' + oMatter.id + '&type=' + oMatter.type + '&_=' + (new Date() * 1);
        $http.get(url).success(function(rsp) {
            if (rsp.err_code != 0) {
                return;
            }
            defer.resolve(rsp.data);
        });
        return defer.promise;
    }
    this.open = function(oMatter) {
        var template;
        template = '<div class="modal-header"><span class="modal-title">转发到哪个团队和频道</span></div>';
        template += '<div class="modal-body">';
        template += '<div ng-repeat="site in mySites">';
        template += '<span>{{site.name}}</span>';
        template += '<div class="checkbox" ng-repeat="chn in site.homeChannels">';
        template += '<label>';
        template += '<input type=\'checkbox\' ng-true-value="\'Y\'" ng-false-value="\'N\'" ng-model=\'chn._selected\' ng-change="choose(site,chn)">';
        template += '<span>{{chn.title}}</span>';
        template += '</label>';
        template += '</div>'
        template += '<div ng-if="site.homeChannels.length===0"><a href="" ng-click="createChannel(site)">创建</a>团队主页频道，转发内容到团队主页</div>';
        template += '</div>'
        template += '<div ng-if="mySites.length===0"><a href="" ng-click="createSite()">创建</a>团队，转发内容到团队主页</div>';
        template += '</div>';
        template += '<div class="modal-footer"><button class="btn btn-default" ng-click="cancel()">关闭</button><button class="btn btn-success" ng-click="ok()">确定</button></div>';
        tmsModal.open({
            template: template,
            controller: ['$http', '$scope', '$tmsModalInstance', function($http, $scope2, $mi) {
                var aSelected = [];
                bySite(oMatter).then(function(sites) {
                    var mySites = sites;
                    mySites.forEach(function(site) {
                        site._selected = site._recommended;
                    });
                    $scope2.mySites = mySites;
                });
                $scope2.createChannel = function(site) {
                    $http.post('/rest/pl/fe/matter/channel/create?site=' + site.id, {}).success(function(rsp) {
                        var oChannel = rsp.data;
                        $http.post('/rest/pl/fe/site/setting/page/addHomeChannel?site=' + site.id, oChannel).success(function(rsp) {
                            site.homeChannels.push(rsp.data);
                        });
                    });
                };
                $scope2.createSite = function() {
                    $http.get('/rest/pl/fe/site/create').success(function(rsp) {
                        var site = rsp.data;
                        site._selected = 'N';
                        site.homeChannels = [];
                        $scope2.mySites = [site];
                    });
                };
                $scope2.choose = function(oSite, oChannel) {
                    if (oChannel._selected === 'Y') {
                        oChannel.siteid = oSite.id;
                        aSelected.push(oChannel);
                    } else {
                        aSelected.splice(aSelected.indexOf(oChannel), 1);
                    }
                };
                $scope2.ok = function() {
                    var aTargets = [];
                    if (aSelected.length) {
                        aSelected.forEach(function(oChannel) {
                            aTargets.push({ siteid: oChannel.siteid, channelId: oChannel.channel_id });
                        });
                        $http.post('/rest/pl/fe/site/forward/push?id=' + oMatter.id + '&type=' + oMatter.type, aTargets).success(function() {
                            $mi.close();
                        });
                    }
                };
                $scope2.cancel = function() {
                    $mi.dismiss();
                };
            }]
        });
    };
    this.showSwitch = function(oUser, oMatter) {
        var _this = this,
            eSwitch;
        eSwitch = document.createElement('div');
        eSwitch.classList.add('tms-switch', 'tms-switch-forward');
        eSwitch.addEventListener('click', function(event) {
            event.preventDefault();
            event.stopPropagation();
            $rootScope.$apply(function() {
                if (!oUser.loginExpire) {
                    tmsDynaPage.openPlugin(location.protocol + '//' + location.host + '/rest/site/fe/user/access?site=platform#login').then(function(data) {
                        oUser.loginExpire = data.loginExpire;
                        _this.open(oMatter);
                    });
                } else {
                    _this.open(oMatter);
                }
            });
        }, true);
        document.body.appendChild(eSwitch);
    };
}]);
