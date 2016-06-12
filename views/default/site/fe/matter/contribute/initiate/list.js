ngApp.controller('ctrlInitiate', ['$scope', '$location', 'http2', '$uibModal', 'Article', 'Entry', function($scope, $location, http2, $uibModal, Article, Entry) {
    $scope.phases = {
        'I': '未送审',
        'R': '审核中',
        'T': '版面'
    };
    $scope.approved = {
        'Y': '通过',
        'N': '未通过'
    };
    $scope.create = function() {
        if ($scope.entryApp.pageShift2Pc && /MicroMessenger|Yixin/i.test(navigator.userAgent)) {
            var ele = document.querySelector('#pagePopup iframe'),
                css, js;
            if (ele.contentDocument && ele.contentDocument.body) {
                ele.contentDocument.body.innerHTML = $scope.entryApp.pageShift2Pc.html;
                css = document.createElement('style');
                css.innerHTML = $scope.entryApp.pageShift2Pc.css;
                ele.contentDocument.body.appendChild(css);
                js = document.createElement('script');
                js.innerHTML = $scope.entryApp.pageShift2Pc.js;
                ele.contentDocument.body.appendChild(js);
            }
            document.querySelector('#pagePopup').style.display = 'block';
        } else {
            $scope.Article.create().then(function(data) {
                location.href = '/rest/site/fe/matter/contribute/initiate/article?site=' + $scope.siteId + '&entry=' + $scope.entry + '&id=' + data.id;
            });
        }
    };
    $scope.upload = function() {
        $uibModal.open({
            templateUrl: 'uploadArticle.html',
            controller: ['$scope', '$uibModalInstance', 'site', 'entry', function($scope, $mi, site, entry) {
                $scope.cancel = function() {
                    $mi.dismiss();
                };
                $scope.ok = function() {
                    $scope.uploading = true;
                    var r = new Resumable({
                        target: '/rest/app/contribute/initiate/articleUpload?site=' + site,
                        testChunks: false,
                    });
                    r.on('fileAdded', function(file, event) {
                        console.log('file Added and begin upload.');
                        r.upload();
                    });
                    r.on('progress', function() {
                        console.log('progress.');
                    });
                    r.on('complete', function() {
                        console.log('complete.');
                        var f, lastModified, posted;
                        f = r.files[0].file;
                        lastModified = f.lastModified ? f.lastModified : (f.lastModifiedDate ? f.lastModifiedDate.getTime() : 0);
                        posted = {
                            file: {
                                uniqueIdentifier: r.files[0].uniqueIdentifier,
                                name: f.name,
                                size: f.size,
                                type: f.type,
                                lastModified: lastModified
                            }
                        };
                        http2.post('/rest/app/contribute/initiate/articleUpload?state=done&site=' + site + '&entry=' + entry, posted, function(rsp) {
                            $scope.uploading = false;
                            $mi.close(rsp.data);
                        });
                    });
                    r.addFile(document.querySelector('#fileUpload').files[0]);
                };
            }],
            resolve: {
                site: function() {
                    return $scope.siteId;
                },
                entry: function() {
                    return $scope.entry;
                }
            },
            backdrop: 'static',
        }).result.then(function(data) {
            location.href = '/rest/app/contribute/initiate/article?site=' + $scope.siteId + '&entry=' + $scope.entry + '&id=' + data;
        });
    };
    $scope.open = function(article) {
        location.href = '/rest/site/fe/matter/contribute/initiate/article?site=' + $scope.siteId + '&entry=' + $scope.entry + '&id=' + article.id;
    };
    $scope.siteId = $location.search().site;
    $scope.entry = $location.search().entry;
    $scope.Entry = new Entry($scope.siteId, $scope.entry);
    $scope.Article = new Article('initiate', $scope.siteId, $scope.entry);
    $scope.Entry.get().then(function(data) {
        $scope.entryApp = data;
    });
    $scope.Article.list().then(function(data) {
        $scope.articles = data;
    });
}]);