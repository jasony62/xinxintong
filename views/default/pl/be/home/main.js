define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlMain', ['$scope', 'http2', '$uibModal', function($scope, http2, $uibModal) {
        function createPage(pageType, template) {
            var url = '/rest/pl/be/home/pageCreate?name=' + pageType;
            template && (url += '&template=' + template);
            http2.get(url, function(rsp) {
                $scope.platform[pageType + '_page_name'] = rsp.data.name;
                location.href = '/rest/pl/fe/code?site=platform&name=' + rsp.data.name;
            });
        }

        function resetPage(pageType, template) {
            var name = $scope.platform[pageType + '_page_name'],
                url;
            if (name && name.length) {
                url = '/rest/pl/be/home/pageReset?name=' + pageType;
                template && (url += '&template=' + template);
                http2.get(url, function(rsp) {
                    location.href = '/rest/pl/fe/code?site=platform&name=' + name;
                });
            } else {
                url = '/rest/pl/be/home/pageCreate?name=' + pageType;
                template && (url += '&template=' + template);
                http2.get(url, function(rsp) {
                    $scope.platform[pageType + '_page_name'] = rsp.data.name;
                    location.href = '/rest/pl/fe/code?site=platform&name=' + rsp.data.name;
                });
            }
        }

        function chooseTemplate() {
            return $uibModal.open({
                templateUrl: 'homePageTemplate.html',
                dropback: 'static',
                controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                    $scope2.data = { template: 'siteuser' };
                    $scope2.ok = function() {
                        $mi.close($scope2.data);
                    };
                    $scope2.cancel = function() {
                        $mi.dismiss();
                    };
                }]
            }).result;
        }
        $scope.editPage = function(pageType) {
            var name = $scope.platform[pageType + '_page_name'];
            if (name && name.length) {
                location.href = '/rest/pl/fe/code?site=platform&name=' + name;
            } else {
                if (pageType === 'home') {
                    chooseTemplate().then(function(selected) {
                        createPage(pageType, selected.template);
                    });
                } else {
                    http2.get('/rest/pl/be/home/pageCreate?name=' + pageType, function(rsp) {
                        $scope.platform[pageType + '_page_name'] = rsp.data.name;
                        location.href = '/rest/pl/fe/code?site=platform&name=' + rsp.data.name;
                    });
                }
            }
        };
        $scope.resetPage = function(pageType) {
            if (window.confirm('重置操作将覆盖已经做出的修改，确定重置？')) {
                if (pageType === 'home') {
                    chooseTemplate().then(function(selected) {
                        resetPage(pageType, selected.template);
                    });
                } else {
                    resetPage(pageType);
                }
            }
        };
    }]);
    ngApp.provider.controller('ctrlHomeCarousel', ['$scope', 'http2', 'mediagallery', function($scope, http2, mediagallery) {
        var slides;
        $scope.add = function() {
            var options = {
                callback: function(url) {
                    slides.push({
                        picUrl: url + '?_=' + (new Date() * 1)
                    });
                    $scope.update('home_carousel');
                }
            };
            mediagallery.open('platform', options);
        };
        $scope.remove = function(homeChannel, index) {
            slides.splice(index, 1);
            $scope.update('home_carousel');
        };
        $scope.$watch('platform', function(platform) {
            if (platform === undefined) return;
            if (!platform.home_carousel) platform.home_carousel = [];
            slides = platform.home_carousel;
            $scope.slides = slides;
        });
    }]);
});
