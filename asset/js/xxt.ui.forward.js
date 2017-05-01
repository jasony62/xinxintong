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
        template = '<div class="modal-header"><span class="modal-title">转发到指定团队主页</span></div>';
        template += '<div class="modal-body">';
        template += '<div ng-repeat="site in mySites">';
        template += '<span>{{site.name}}</span>';
        template += '<div class="checkbox" ng-repeat="chn in site.homeChannels">';
        template += '<label>';
        template += '<input type=\'checkbox\' ng-true-value="\'Y\'" ng-false-value="\'N\'" ng-model=\'chn._selected\' ng-change="choose(site,chn)">';
        template += '<span>{{chn.title}}</span>';
        template += '</label>';
        template += '</div>'
        template += '</div>'
        template += '<div ng-if="mySites.length===0">创建团队和主页频道，转发内容到团队主页</div>';
        template += '</div>';
        template += '<div class="modal-footer"><button ng-click="cancel()">关闭</button><button ng-click="ok()">确定</button></div>';
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
                    tmsDynaPage.openPlugin('http://' + location.host + '/rest/site/fe/user/login?site=' + oMatter.siteid).then(function(data) {
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
