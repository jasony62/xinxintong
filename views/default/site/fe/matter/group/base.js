'use strict';
require('../../../../../../asset/js/xxt.ui.share.js');
if (/MicroMessenger/i.test(navigator.userAgent) && window.signPackage && window.wx) {
    window.wx.ready(function() {
        window.wx.showOptionMenu();
    });
}

require('../../../../../../asset/js/xxt.ui.trace.js');
require('../../../../../../asset/js/xxt.ui.notice.js');
require('../../../../../../asset/js/xxt.ui.http.js');
require('../../../../../../asset/js/xxt.ui.siteuser.js');

/* 公共加载的模块 */
var angularModules = ['ngSanitize', 'ui.bootstrap', 'notice.ui.xxt', 'http.ui.xxt', 'trace.ui.xxt', 'snsshare.ui.xxt', 'siteuser.ui.xxt'];
/* 加载指定的模块 */
if (window.moduleAngularModules) {
    window.moduleAngularModules.forEach(function(m) {
        angularModules.push(m);
    });
}

var ngApp = angular.module('app', angularModules);
ngApp.config(['$locationProvider', '$uibTooltipProvider', function($locationProvider, $uibTooltipProvider) {
    $locationProvider.html5Mode(true);
    $uibTooltipProvider.setTriggers({ 'show': 'hide' });
}]);
ngApp.factory('facGroupApp', ['$q', 'http2', 'tmsLocation', function($q, http2, LS) {
    var _oInstance = {};
    _oInstance.get = function() {
        var oDeferred;
        oDeferred = $q.defer();
        http2.get('/rest/site/fe/matter/group/get?' + LS.s('site', 'app')).then(function(rsp) {
            oDeferred.resolve(rsp.data);
        });
        return oDeferred.promise;
    };

    return _oInstance;
}]);
ngApp.factory('facGroupTeam', ['$q', 'http2', 'tmsLocation', function($q, http2, LS) {
    var _oInstance = {};
    _oInstance.get = function() {
        var oDeferred;
        oDeferred = $q.defer();
        http2.get('/rest/site/fe/matter/group/team/get?' + LS.s('site', 'app', 'team')).then(function(rsp) {
            oDeferred.resolve(rsp.data);
        });
        return oDeferred.promise;
    };
    _oInstance.list = function() {
        var oDeferred;
        oDeferred = $q.defer();
        http2.get('/rest/site/fe/matter/group/team/list?' + LS.s('site', 'app')).then(function(rsp) {
            oDeferred.resolve(rsp.data);
        });
        return oDeferred.promise;
    };
    _oInstance.create = function(oTeam, oMember) {
        var oDeferred;
        oDeferred = $q.defer();
        http2.post('/rest/site/fe/matter/group/team/add?' + LS.s('site', 'app'), { team: oTeam, member: oMember }).then(function(rsp) {
            oDeferred.resolve(rsp.data);
        });
        return oDeferred.promise;
    };
    _oInstance.update = function(oUpdated) {
        var oDeferred;
        oDeferred = $q.defer();
        http2.post('/rest/site/fe/matter/group/team/update?' + LS.s('site', 'app', 'team'), oUpdated).then(function(rsp) {
            oDeferred.resolve(rsp.data);
        });
        return oDeferred.promise;
    };
    _oInstance.join = function(oMember) {
        var oDeferred;
        oDeferred = $q.defer();
        http2.post('/rest/site/fe/matter/group/invite/join?' + LS.s('site', 'app', 'team'), oMember).then(function(rsp) {
            oDeferred.resolve(rsp.data);
        });
        return oDeferred.promise;
    };

    return _oInstance;
}]);
ngApp.factory('facGroupRecord', ['$q', 'http2', 'tmsLocation', function($q, http2, LS) {
    var _oInstance = {};
    _oInstance.list = function() {
        var oDeferred;
        oDeferred = $q.defer();
        http2.get('/rest/site/fe/matter/group/record/list?' + LS.s('site', 'app', 'team')).then(function(rsp) {
            oDeferred.resolve(rsp.data);
        });
        return oDeferred.promise;
    };

    return _oInstance;
}]);
ngApp.controller('ctrlBase', ['$scope', '$q', '$parse', 'http2', '$timeout', 'tmsLocation', 'tmsSnsShare', 'tmsSiteUser', function($scope, $q, $parse, http2, $timeout, LS, tmsSnsShare, tmsSiteUser) {
    $scope.isSmallLayout = false;
    if (window.screen && window.screen.width < 992) {
        $scope.isSmallLayout = true;
    }
    var eleLoading;
    if (eleLoading = document.querySelector('.loading')) {
        eleLoading.parentNode.removeChild(eleLoading);
    }
}]);
module.exports = ngApp;