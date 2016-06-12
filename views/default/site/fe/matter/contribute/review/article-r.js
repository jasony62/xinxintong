ngApp.controller('ctrlReview', ['$location', '$scope', '$uibModal', 'http2', 'Article', 'Entry', 'Reviewlog', function($location, $scope, $uibModal, http2, Article, Entry, Reviewlog) {
    var siteId, id;
    siteId = $location.search().site;
    id = $location.search().id;
    $scope.phases = {
        'I': '投稿',
        'R': '审核',
        'T': '版面'
    };
    $scope.entry = $location.search().entry;
    $scope.Article = new Article('review', siteId, $scope.entry);
    $scope.Entry = new Entry(siteId, $scope.entry);
    $scope.Reviewlog = new Reviewlog('initiate', siteId, {
        type: 'article',
        id: id
    });
    $scope.back = function(event) {
        event.preventDefault();
        location.href = '/rest/site/fe/matter/contribute/review?site=' + siteId + '&entry=' + $scope.entry;
    };
    $scope.downloadUrl = function(att) {
        return '/rest/site/fe/matter/article/attachmentGet?site=' + siteId + '&articleid=' + $scope.editing.id + '&attachmentid=' + att.id;
    };
    $scope.Article.get(id).then(function(data) {
        $scope.editing = data;
        var ele = document.querySelector('#content>iframe');
        if (ele.contentDocument && ele.contentDocument.body) {
            ele.contentDocument.body.innerHTML = data.body;
        }
    }).then(function() {
        $scope.Entry.get().then(function(data) {
            var i, j, ch, mapSubChannels = {};
            $scope.editing.subChannels = [];
            $scope.entryApp = data;
            if (data.subChannels)
                for (i = 0, j = data.subChannels.length; i < j; i++) {
                    ch = data.subChannels[i];
                    mapSubChannels[ch.id] = ch;
                }
            if ($scope.editing.channels)
                for (i = 0, j = $scope.editing.channels.length; i < j; i++) {
                    ch = $scope.editing.channels[i];
                    mapSubChannels[ch.id] && $scope.editing.subChannels.push(ch);
                }
        });
    });
    $scope.return = function() {
        $uibModal.open({
            templateUrl: 'replyBox.html',
            controller: ['$scope', '$uibModalInstance', 'http2', function($scope, $mi, http2) {
                $scope.data = {
                    message: ''
                };
                $scope.close = function() {
                    $mi.dismiss();
                };
                $scope.ok = function() {
                    $mi.close($scope.data);
                };
            }],
            backdrop: 'static',
        }).result.then(function(data) {
            $scope.Article.return($scope.editing, data.message).then(function() {
                location.href = '/rest/site/fe/matter/contribute/review?site=' + siteId + '&entry=' + $scope.editing.entry;
            });
        });
    };
    $scope.forward2Review = function() {
        $uibModal.open({
            templateUrl: 'review-list.html',
            controller: ['$scope', '$uibModalInstance', 'reviewers', function($scope, $mi, reviewers) {
                $scope.reviewers = reviewers;
                $scope.data = {
                    selected: '0'
                };
                $scope.cancel = function() {
                    $mi.dismiss();
                };
                $scope.ok = function() {
                    $scope.data.selected ? $mi.close(reviewers[$scope.data.selected]) : $mi.dismiss();
                };
            }],
            resolve: {
                reviewers: function() {
                    return $scope.entryApp.reviewers;
                }
            },
            backdrop: 'static',
        }).result.then(function(who) {
            $scope.Article.forward($scope.editing, who.identity, 'R').then(function() {
                location.href = '/rest/site/fe/matter/contribute/review?site=' + siteId + '&entry=' + $scope.entry;
            });
        });
    };
    $scope.pass = function() {
        $scope.Article.pass($scope.editing).then(function() {
            location.href = '/rest/site/fe/matter/contribute/review?site=' + siteId + '&entry=' + $scope.editing.entry;
        });
    };
    $scope.refuse = function() {

    };
    $scope.preview = function() {
        location.href = '/rest/mi/matter?mode=preview&type=article&tpl=std&mpid=' + siteId + '&id=' + id;
    };
    $scope.Reviewlog.list().then(function(data) {
        $scope.logs = data;
    });
}]);