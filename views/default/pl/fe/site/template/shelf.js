define(['frame'], function(ngApp) {
    ngApp.provider.controller('ctrlShelf', ['$scope', 'http2', function($scope, http2) {
        var criteria;
        $scope.criteria = criteria = {
            scope: 'S'
        };
        $scope.page = page = {
            size: 21,
            at: 1,
            total: 0,
            j: function() {
                return '&page=' + this.at + '&size=' + this.size;
            }
        };
        $scope.changeScope = function(scope) {
            criteria.scope = scope;
            if (scope === 'share2Me') {
                $scope.searchShare2Me()
            } else if (scope === 'M') {
                $scope.listLatest();
                $scope.listPublish();
            } else {
                $scope.searchTemplate();
            }
        };
        $scope.use = function(template) {
            var templateId, url;
            templateId = template.template_id || template.id;
            url = '/rest/pl/fe/template/purchase?template=' + templateId;
            url += '&site=' + $scope.siteId;
            http2.get(url, function(rsp) {
                http2.get('/rest/pl/fe/matter/enroll/createByOther?site=' + $scope.siteId + '&template=' + templateId, function(rsp) {
                    location.href = '/rest/pl/fe/matter/enroll?id=' + rsp.data.id + '&site=' + $scope.siteId;
                });
            });
        };
        $scope.favor = function(template) {
            var url = '/rest/pl/fe/template/favor?template=' + template.id;
            url += '&site=' + $scope.siteId;
            http2.get(url, function(rsp) {
                template._favored = 'Y';
            });
        };
        $scope.unfavor = function(template, index) {
            var url = '/rest/pl/fe/template/unfavor?template=' + template.id;
            url += '&site=' + $scope.siteId;
            http2.get(url, function(rsp) {
                $scope.templates.splice(index, 1);
                $scope.page.total--;
            });
        };
        $scope.searchTemplate = function() {
            var url = '/rest/pl/fe/template/site/list?matterType=enroll&scope=' + criteria.scope;
            url += '&site=' + $scope.siteId;

            http2.get(url, function(rsp) {
                $scope.templates = rsp.data.templates;
                $scope.page.total = rsp.data.total;
            });
        };
        $scope.searchShare2Me = function() {
            var url = '/rest/pl/fe/template/platform/share2Me?matterType=enroll';
            url += '&site=' + $scope.siteId;

            http2.get(url, function(rsp) {
                $scope.templates = rsp.data.templates;
                $scope.page.total = rsp.data.total;
            });
        };
        $scope.listLatest = function(pageAt) {
            var url = '/rest/pl/fe/template/enroll/list?matterType=enroll';
            url += '&site=' + $scope.siteId;
            url += '&pub=N';
            url += page.j();
            if (pageAt) {
                page.at = pageAt;
            }
            http2.get(url, function(rsp) {
                $scope.latests = rsp.data.templates;
                $scope.page.total = rsp.data.total;
            });
        };
        $scope.listPublish = function(pageAt) {
            var url = '/rest/pl/fe/template/enroll/list?matterType=enroll';
            url += '&site=' + $scope.siteId;
            url += '&pub=Y';
            url += page.j();
            if (pageAt) {
                page.at = pageAt;
            }
            http2.get(url, function(rsp) {
                $scope.publishs = rsp.data.templates;
                $scope.page.total = rsp.data.total;
            });
        };
        $scope.message = function(template) {
            location.href = '/rest/pl/fe/template/enroll?site=' + $scope.siteId + '&id=' + template.id + '&vid=' + template.lastVersion.id;
        }
        $scope.createEnrollTemplate = function(matter) {
            http2.get('/rest/pl/fe/template/create?site=' + $scope.siteId + '&matterType=' + matter, function(rsp) {
                location.href = '/rest/pl/fe/template/enroll?site=' + $scope.siteId + '&id=' + rsp.data.id;
            });
        }
        $scope.searchTemplate();
    }]);
});
