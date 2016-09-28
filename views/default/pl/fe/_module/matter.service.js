angular.module('service.matter', ['ui.bootstrap', 'ui.xxt']).
provider('srvQuickEntry', function() {
    var siteId;
    this.setSiteId = function(id) {
        siteId = id;
    };
    this.$get = ['$q', 'http2', 'noticebox', function($q, http2, noticebox) {
        return {
            get: function(taskUrl) {
                var defer = $q.defer(),
                    url;

                url = '/rest/pl/fe/q/get?site=' + siteId;
                http2.post(url, {
                    url: encodeURI(taskUrl)
                }, function(rsp) {
                    defer.resolve(rsp.data);
                });

                return defer.promise;
            },
            add: function(taskUrl) {
                var defer = $q.defer(),
                    url;

                url = '/rest/pl/fe/q/create?site=' + siteId;
                http2.post(url, {
                    url: encodeURI(taskUrl)
                }, function(rsp) {
                    defer.resolve(rsp.data);
                });

                return defer.promise;
            },
            remove: function(taskUrl) {
                var defer = $q.defer(),
                    url;

                url = '/rest/pl/fe/q/remove?site=' + siteId;
                http2.post(url, {
                    url: encodeURI(taskUrl)
                }, function(rsp) {
                    defer.resolve(rsp.data);
                });

                return defer.promise;
            },
            config: function(taskUrl, config) {
                var defer = $q.defer(),
                    url;

                url = '/rest/pl/fe/q/config?site=' + siteId;
                http2.post(url, {
                    url: encodeURI(taskUrl),
                    config: config
                }, function(rsp) {
                    defer.resolve(rsp.data);
                });

                return defer.promise;
            }
        };
    }];
});