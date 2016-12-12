angular.module('service.article', ['ui.bootstrap', 'ui.xxt']).provider('srvLog', function() {
    this.$get = ['$q', 'http2', function($q, http2) {
        return {
            list: function(articleId, page) {
                var defer = $q.defer(),
                    url;
                if (!page || !page._j) {
                    angular.extend(page, {
                        at: 1,
                        size: 30,
                        orderBy: 'time',
                        _j: function() {
                            var p;
                            p = '&page=' + this.at + '&size=' + this.size;
                            p += '&orderby=' + this.orderBy;
                            return p;
                        }
                    });
                }
                url = '/rest/pl/fe/matter/article/log/list?id=' + articleId + page._j();
                http2.get(url, function(rsp) {
                    rsp.data.total && (page.total = rsp.data.total);
                    defer.resolve(rsp.data.logs);
                });

                return defer.promise;
            }
        };
    }];
});