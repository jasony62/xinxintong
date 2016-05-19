ngApp = angular.module('xxtApp', ['ngRoute', 'ui.tms', 'tinymce.ui.xxt', 'matters.xxt']);
ngApp.config(['$locationProvider', '$controllerProvider', function($locationProvider, $controllerProvider) {
    $locationProvider.html5Mode(true);
    ngApp.register = {
        controller: $controllerProvider.register
    };
}]);
ngApp.factory('Article', function($q, http2) {
    var Article = function(phase, siteId, entry) {
        this.phase = phase;
        this.siteId = siteId;
        this.entry = entry;
        this.baseUrl = '/rest/site/fe/matter/contribute/' + phase + '/';
    };
    Article.prototype.get = function(id) {
        var deferred = $q.defer(),
            promise = deferred.promise;
        var url = this.baseUrl + 'articleGet';
        url += '?site=' + this.siteId;
        url += '&id=' + id;
        http2.get(url, function success(rsp) {
            deferred.resolve(rsp.data);
        });

        return promise;
    };
    Article.prototype.list = function() {
        var deferred = $q.defer(),
            promise = deferred.promise;
        var url = this.baseUrl + 'articleList';
        url += '?site=' + this.siteId;
        url += '&entry=' + this.entry;
        http2.get(url, function success(rsp) {
            deferred.resolve(rsp.data);
        });

        return promise;
    };
    Article.prototype.create = function() {
        var deferred = $q.defer(),
            promise = deferred.promise;
        var url = this.baseUrl + 'articleCreate';
        url += '?site=' + this.siteId + '&entry=' + this.entry;
        http2.get(url, function success(rsp) {
            deferred.resolve(rsp.data);
        });

        return promise;
    };
    Article.prototype.return = function(obj, msg) {
        var deferred = $q.defer();
        var promise = deferred.promise;
        var url;
        url = this.baseUrl + 'articleReturn';
        url += '?site=' + this.siteId;
        url += '&id=' + obj.id;
        url += '&msg=' + msg;
        http2.get(url, function success(rsp) {
            deferred.resolve(rsp.data);
        });
        return promise;
    };
    Article.prototype.pass = function(obj) {
        var deferred = $q.defer();
        var promise = deferred.promise;
        var url;
        url = this.baseUrl + 'articlePass';
        url += '?site=' + this.siteId;
        url += '&id=' + obj.id;
        http2.get(url, function success(rsp) {
            deferred.resolve(rsp.data);
        });
        return promise;
    };
    Article.prototype.forward = function(obj, mid, phase) {
        var deferred = $q.defer(),
            promise = deferred.promise;
        var url = this.baseUrl + 'articleForward';
        url += '?site=' + this.siteId;
        url += '&id=' + obj.id;
        url += '&phase=' + phase;
        url += '&mid=' + mid;
        http2.get(url, function success(rsp) {
            deferred.resolve(rsp.data);
        });
        return promise;
    };
    Article.prototype.update = function(obj, prop) {
        var deferred = $q.defer(),
            promise = deferred.promise,
            nv = {},
            url;
        if (prop === 'body') {
            nv[prop] = encodeURIComponent(obj[prop]);
        } else {
            nv[prop] = obj[prop];
        }
        url = this.baseUrl + 'articleUpdate';
        url += '?site=' + this.siteId;
        url += '&id=' + obj.id;
        http2.post(url, nv, function success(rsp) {
            deferred.resolve(rsp.data);
        });
        return promise;
    };
    Article.prototype.remove = function(obj) {
        var deferred = $q.defer();
        var promise = deferred.promise;
        var url;
        url = this.baseUrl + 'articleRemove';
        url += '?site=' + this.siteId;
        url += '&id=' + obj.id;
        http2.get(url, function success(rsp) {
            deferred.resolve(rsp.data);
        });
        return promise;
    };
    Article.prototype.channels = function(id) {
        var deferred = $q.defer(),
            promise = deferred.promise;
        var url = this.baseUrl + 'channelGet';
        url += '?site=' + this.siteId;
        url += '&acceptType=article';
        http2.get(url, function success(rsp) {
            deferred.resolve(rsp.data);
        });
        return promise;
    };
    Article.prototype.addChannels = function(params) {
        var deferred = $q.defer(),
            promise = deferred.promise;
        var url = this.baseUrl + 'articleAddChannel';
        url += '?site=' + this.siteId;
        http2.post(url, params, function success(rsp) {
            deferred.resolve(rsp.data);
        });
        return promise;
    };
    Article.prototype.delChannel = function(id, channelId) {
        var deferred = $q.defer(),
            promise = deferred.promise;
        var url = this.baseUrl + 'articleRemoveChannel';
        url += '?site=' + this.siteId;
        url += '&id=' + id;
        url += '&channelId=' + channelId;
        http2.get(url, function success(rsp) {
            deferred.resolve(rsp.data);
        });
        return promise;
    };
    Article.prototype.mpaccounts = function(id) {
        var deferred = $q.defer(),
            promise = deferred.promise;
        var url = this.baseUrl + 'mpaccountGet';
        url += '?site=' + this.siteId;
        http2.get(url, function success(rsp) {
            deferred.resolve(rsp.data);
        });
        return promise;
    };
    return Article;
});
ngApp.factory('News', function($q, http2) {
    var News = function(phase, site, entry) {
        this.phase = phase;
        this.siteId = site;
        this.entry = entry;
        this.baseUrl = '/rest/app/contribute/' + phase + '/';
    };
    News.prototype.get = function(id) {
        var deferred = $q.defer(),
            promise = deferred.promise;
        var url = this.baseUrl + 'newsGet';
        url += '?site=' + this.siteId;
        url += '&entry=' + this.entry;
        url += '&id=' + id;
        http2.get(url, function success(rsp) {
            deferred.resolve(rsp.data);
        });
        return promise;
    };
    News.prototype.list = function(id) {
        var deferred = $q.defer(),
            promise = deferred.promise;
        var url = this.baseUrl + 'newsList';
        url += '?site=' + this.siteId;
        url += '&entry=' + this.entry;
        http2.get(url, function success(rsp) {
            deferred.resolve(rsp.data);
        });
        return promise;
    };
    News.prototype.create = function(articleIds) {
        var deferred = $q.defer(),
            promise = deferred.promise;
        var url = this.baseUrl + 'newsCreate';
        url += '?site=' + this.siteId + '&entry=' + this.entry;
        http2.post(url, articleIds, function success(rsp) {
            deferred.resolve(rsp.data);
        });
        return promise;
    };
    News.prototype.return = function(obj) {
        var deferred = $q.defer();
        var promise = deferred.promise;
        var url;
        url = this.baseUrl + 'newsReturn';
        url += '?site=' + this.siteId;
        url += '&id=' + obj.id;
        http2.get(url, function success(rsp) {
            deferred.resolve(rsp.data);
        });
        return promise;
    };
    News.prototype.pass = function(obj) {
        var deferred = $q.defer();
        var promise = deferred.promise;
        var url;
        url = this.baseUrl + 'pass';
        url += '?site=' + this.siteId;
        url += '&id=' + obj.id;
        http2.get(url, function success(rsp) {
            deferred.resolve(rsp.data);
        });
        return promise;
    };
    News.prototype.forward = function(obj, who, phase) {
        var deferred = $q.defer(),
            promise = deferred.promise;
        var url = this.baseUrl + 'newsForward';
        url += '?site=' + this.siteId;
        url += '&id=' + obj.id;
        url += '&phase=' + phase;
        http2.post(url, who, function success(rsp) {
            deferred.resolve(rsp.data);
        });
        return promise;
    };
    News.prototype.update = function(obj, prop) {
        var deferred = $q.defer();
        var promise = deferred.promise;
        var url, nv = {};
        if (prop === 'body')
            nv[prop] = encodeURIComponent(obj[prop]);
        else
            nv[prop] = obj[prop];
        url = this.baseUrl + 'update';
        url += '?site=' + this.siteId;
        url += '&id=' + obj.id;
        http2.post(url, nv, function success(rsp) {
            deferred.resolve(rsp.data);
        });
        return promise;
    };
    News.prototype.remove = function(obj) {
        var deferred = $q.defer();
        var promise = deferred.promise;
        var url;
        url = this.baseUrl + 'remove';
        url += '?site=' + this.siteId;
        url += '&id=' + obj.id;
        http2.get(url, function success(rsp) {
            deferred.resolve(rsp.data);
        });
        return promise;
    };
    return News;
});
ngApp.factory('Entry', function($q, http2) {
    var Entry = function(site, entry) {
        this.siteId = site;
        entry = entry.split(',');
        this.type = entry[0];
        this.id = entry[1];
    };
    Entry.prototype.get = function() {
        var deferred = $q.defer(),
            promise = deferred.promise;
        var url = '/rest/site/fe/matter/contribute/entry/get';
        url += '?site=' + this.siteId;
        url += '&type=' + this.type;
        url += '&id=' + this.id;
        http2.get(url, function success(rsp) {
            deferred.resolve(rsp.data);
        });
        return promise;
    };
    return Entry;
});
ngApp.factory('Reviewlog', function($q, http2) {
    var Reviewlog = function(phase, site, matter) {
        this.siteId = site;
        this.matter = matter;
        this.baseUrl = '/rest/site/fe/matter/contribute/reviewlog/';
    };
    Reviewlog.prototype.list = function(id) {
        var deferred = $q.defer(),
            promise = deferred.promise;
        var url = this.baseUrl + 'list';
        url += '?site=' + this.siteId;
        url += '&matterId=' + this.matter.id;
        url += '&matterType=' + this.matter.type;
        http2.get(url, function success(rsp) {
            deferred.resolve(rsp.data);
        });
        return promise;
    };
    return Reviewlog;
});