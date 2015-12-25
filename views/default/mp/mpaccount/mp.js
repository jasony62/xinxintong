xxtApp.factory('Authapi', function($q, http2) {
    var Authapi = function() {
        this.baseUrl = '/rest/mp/authapi/';
    };
    Authapi.prototype.get = function(own) {
        var deferred, url;
        deferred = $q.defer();
        own === undefined && (own === 'N');
        url = this.baseUrl + 'list?own=' + own;
        http2.get(url, function(rsp) {
            deferred.resolve(rsp.data);
        });
        return deferred.promise;
    }
    Authapi.prototype.update = function(api, updated) {
        var deferred, url;
        deferred = $q.defer();
        url = this.baseUrl + 'update?type=' + api.type;
        if (api.authid) url += '&id=' + api.authid;
        http2.post(url, updated, function(rsp) {
            deferred.resolve(rsp.data);
        });
        return deferred.promise;
    };
    return Authapi;
});
xxtApp.factory('Mp', function($q, http2) {
    var Mp = function() {};
    Mp.prototype.get = function() {
        var deferred;
        deferred = $q.defer();
        http2.get('/rest/mp/mpaccount/get', function(rsp) {
            deferred.resolve(rsp.data);
        });
        return deferred.promise;
    };
    Mp.prototype.relayGet = function() {
        var deferred;
        deferred = $q.defer();
        http2.get('/rest/mp/relay/get', function(rsp) {
            deferred.resolve(rsp.data);
        });
        return deferred.promise;
    };
    return Mp;
});