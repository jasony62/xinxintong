xxtApp = angular.module('xxtApp', ['ngRoute', 'ui.tms', 'matters.xxt']);
xxtApp.config(['$locationProvider', '$controllerProvider', function($locationProvider, $controllerProvider) {
    $locationProvider.html5Mode(true);
    xxtApp.register = {
        controller: $controllerProvider.register
    };
}]);
xxtApp.factory('Article', function($q, http2) {
    var Article = function(phase, mpid, entry) {
        this.phase = phase;
        this.mpid = mpid;
        this.entry = entry;
        this.baseUrl = '/rest/app/contribute/' + phase + '/';
    };
    Article.prototype.get = function(id) {
        var deferred = $q.defer(),
            promise = deferred.promise;
        var url = this.baseUrl + 'articleGet';
        url += '?mpid=' + this.mpid;
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
        url += '?mpid=' + this.mpid;
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
        url += '?mpid=' + this.mpid + '&entry=' + this.entry;
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
        url += '?mpid=' + this.mpid;
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
        url += '?mpid=' + this.mpid;
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
        url += '?mpid=' + this.mpid;
        url += '&id=' + obj.id;
        url += '&phase=' + phase;
        url += '&mid=' + mid;
        http2.get(url, function success(rsp) {
            deferred.resolve(rsp.data);
        });
        return promise;
    };
    Article.prototype.update = function(obj, prop) {
        var deferred = $q.defer();
        var promise = deferred.promise;
        var url, nv = {};
        if (prop === 'body')
            nv[prop] = encodeURIComponent(obj[prop]);
        else
            nv[prop] = obj[prop];
        url = this.baseUrl + 'articleUpdate';
        url += '?mpid=' + this.mpid;
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
        url += '?mpid=' + this.mpid;
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
        url += '?mpid=' + this.mpid;
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
        url += '?mpid=' + this.mpid;
        http2.post(url, params, function success(rsp) {
            deferred.resolve(rsp.data);
        });
        return promise;
    };
    Article.prototype.delChannel = function(id, channelId) {
        var deferred = $q.defer(),
            promise = deferred.promise;
        var url = this.baseUrl + 'articleRemoveChannel';
        url += '?mpid=' + this.mpid;
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
        url += '?mpid=' + this.mpid;
        http2.get(url, function success(rsp) {
            deferred.resolve(rsp.data);
        });
        return promise;
    };
    return Article;
});
xxtApp.factory('News', function($q, http2) {
    var News = function(phase, mpid, entry) {
        this.phase = phase;
        this.mpid = mpid;
        this.entry = entry;
        this.baseUrl = '/rest/app/contribute/' + phase + '/';
    };
    News.prototype.get = function(id) {
        var deferred = $q.defer(),
            promise = deferred.promise;
        var url = this.baseUrl + 'newsGet';
        url += '?mpid=' + this.mpid;
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
        url += '?mpid=' + this.mpid;
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
        url += '?mpid=' + this.mpid + '&entry=' + this.entry;
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
        url += '?mpid=' + this.mpid;
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
        url += '?mpid=' + this.mpid;
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
        url += '?mpid=' + this.mpid;
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
        url += '?mpid=' + this.mpid;
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
        url += '?mpid=' + this.mpid;
        url += '&id=' + obj.id;
        http2.get(url, function success(rsp) {
            deferred.resolve(rsp.data);
        });
        return promise;
    };
    return News;
});
xxtApp.factory('Entry', function($q, http2) {
    var Entry = function(mpid, entry) {
        this.mpid = mpid;
        entry = entry.split(',');
        this.type = entry[0];
        this.id = entry[1];
    };
    Entry.prototype.get = function() {
        var deferred = $q.defer(),
            promise = deferred.promise;
        var url = '/rest/app/contribute/entryGet';
        url += '?mpid=' + this.mpid;
        url += '&type=' + this.type;
        url += '&id=' + this.id;
        http2.get(url, function success(rsp) {
            deferred.resolve(rsp.data);
        });
        return promise;
    };
    return Entry;
});
xxtApp.factory('Reviewlog', function($q, http2) {
    var Reviewlog = function(phase, mpid, matter) {
        this.mpid = mpid;
        this.matter = matter;
        this.baseUrl = '/rest/app/contribute/reviewlog/';
    };
    Reviewlog.prototype.list = function(id) {
        var deferred = $q.defer(),
            promise = deferred.promise;
        var url = this.baseUrl + 'list';
        url += '?mpid=' + this.mpid;
        url += '&matterId=' + this.matter.id;
        url += '&matterType=' + this.matter.type;
        http2.get(url, function success(rsp) {
            deferred.resolve(rsp.data);
        });
        return promise;
    };
    return Reviewlog;
});