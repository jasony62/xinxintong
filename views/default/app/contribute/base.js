xxtApp = angular.module('xxtApp', ['ui.tms', 'matters.xxt']);
xxtApp.config(['$locationProvider', '$controllerProvider', function ($locationProvider, $controllerProvider) {
    $locationProvider.html5Mode(true);
    xxtApp.register = { controller: $controllerProvider.register };
}]);
xxtApp.factory('Article', function ($q, http2) {
    var Article = function (phase, mpid, entry) {
        this.phase = phase;
        this.mpid = mpid;
        this.entry = entry;
        this.baseUrl = '/rest/app/contribute/' + phase + '/';
    };
    Article.prototype.get = function (id) {
        var deferred = $q.defer(), promise = deferred.promise;
        var url = this.baseUrl + 'articleGet';
        url += '?mpid=' + this.mpid;
        url += '&id=' + id;
        http2.get(url, function success(rsp) {
            deferred.resolve(rsp.data);
        });

        return promise;
    };
    Article.prototype.create = function () {
        var deferred = $q.defer(), promise = deferred.promise;
        var url = this.baseUrl + 'create';
        url += '?mpid=' + this.mpid + '&entry=' + this.entry;
        http2.get(url, function success(rsp) {
            deferred.resolve(rsp.data);
        });

        return promise;
    };
    Article.prototype.return = function (obj) {
        var deferred = $q.defer();
        var promise = deferred.promise;
        var url;
        url = this.baseUrl + 'return';
        url += '?mpid=' + this.mpid;
        url += '&id=' + obj.id;
        http2.get(url, function success(rsp) {
            deferred.resolve(rsp.data);
        });
        return promise;
    };
    Article.prototype.forward = function (obj, who, phase) {
        var deferred = $q.defer(), promise = deferred.promise;
        var url = this.baseUrl + 'forward';
        url += '?mpid=' + this.mpid;
        url += '&id=' + obj.id;
        url += '&phase=' + phase;
        http2.post(url, who, function success(rsp) {
            deferred.resolve(rsp.data);
        });
        return promise;
    };
    Article.prototype.update = function (obj, prop) {
        var deferred = $q.defer();
        var promise = deferred.promise;
        var url, nv = {};
        if (prop === 'body')
            nv[prop] = encodeURIComponent(obj[prop]);
        else
            nv[prop] = obj[prop];
        url = this.baseUrl + 'update';
        url += '?mpid=' + this.mpid;
        url += '&id=' + obj.id;
        http2.post(url, nv, function success(rsp) {
            deferred.resolve(rsp.data);
        });
        return promise;
    };
    Article.prototype.remove = function (obj) {
        var deferred = $q.defer();
        var promise = deferred.promise;
        var url;
        url = this.baseUrl + 'remove';
        url += '?mpid=' + this.mpid;
        url += '&id=' + obj.id;
        http2.get(url, function success(rsp) {
            deferred.resolve(rsp.data);
        });
        return promise;
    };
    Article.prototype.channels = function (id) {
        var deferred = $q.defer(), promise = deferred.promise;
        var url = this.baseUrl + 'channelGet';
        url += '?mpid=' + this.mpid;
        url += '&acceptType=article';
        http2.get(url, function success(rsp) {
            deferred.resolve(rsp.data);
        });
        return promise;
    };
    Article.prototype.mpaccounts = function (id) {
        var deferred = $q.defer(), promise = deferred.promise;
        var url = this.baseUrl + 'mpaccountGet';
        url += '?mpid=' + this.mpid;
        http2.get(url, function success(rsp) {
            deferred.resolve(rsp.data);
        });
        return promise;
    };
    return Article;
});
xxtApp.controller('ReviewUserPickerCtrl', ['$scope', '$modalInstance', 'userSetAsParam', function ($scope, $mi, userSetAsParam) {
    $scope.userConfig = { userScope: ['M'] };
    $scope.userSet = {};
    $scope.cancel = function () {
        $mi.dismiss();
    };
    $scope.ok = function () {
        var data = {};
        data.userScope = $scope.userSet.userScope;
        data.userSet = userSetAsParam.convert($scope.userSet);
        $mi.close(data);
    };
}]);