ngApp.controller('ctrlInitiate', ['$location', '$scope', 'http2', 'Article', 'Entry', 'Reviewlog', function($location, $scope, http2, Article, Entry, Reviewlog) {
    var siteId, id;
    $scope.phases = {
        'I': '投稿',
        'R': '审核',
        'T': '版面'
    };
    siteId = $location.search().site;
    id = $location.search().id;
    $scope.entry = $location.search().entry;
    $scope.Article = new Article('initiate', siteId, '');
    $scope.Entry = new Entry(siteId, $scope.entry);
    $scope.Reviewlog = new Reviewlog('initiate', siteId, {
        type: 'article',
        id: id
    });
    $scope.downloadUrl = function(att) {
        return '/rest/site/fe/matter/article/attachmentGet?site=' + siteId + '&articleid=' + $scope.editing.id + '&attachmentid=' + att.id;
    };
    $scope.back = function(event) {
        event.preventDefault();
        location.href = '/rest/site/fe/matter/contribute/initiate?site=' + siteId + '&entry=' + $scope.entry;
    };
    $scope.shift2pc = function() {
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
        $('#pagePopup').show();
    };
    $scope.preview = function() {
        var url;
        url = '/rest/site/fe/matter?mode=preview&type=article&tpl=std&site=' + siteId + '&id=' + id;
        location.href = url;
    };
    $scope.Article.get(id).then(function(data) {
        $scope.editing = data;
        var ele = document.querySelector('#content>iframe');
        if (ele.contentDocument && ele.contentDocument.body) {
            ele.contentDocument.body.innerHTML = $scope.editing.body;
        }
    }).then(function() {
        $scope.Entry.get().then(function(data) {
            var i, j, ch, mapSubChannels = {};
            $scope.editing.subChannels = [];
            $scope.entryApp = data;
            for (i = 0, j = data.subChannels.length; i < j; i++) {
                ch = data.subChannels[i];
                mapSubChannels[ch.id] = ch;
            }
            for (i = 0, j = $scope.editing.channels.length; i < j; i++) {
                ch = $scope.editing.channels[i];
                mapSubChannels[ch.id] && $scope.editing.subChannels.push(ch);
            }
        });
    });
    $scope.Reviewlog.list().then(function(data) {
        $scope.logs = data;
    });
}]);