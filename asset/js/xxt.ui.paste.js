'use strict';
var ngMod = angular.module('paste.ui.xxt', ['ngSanitize', 'notice.ui.xxt']);
ngMod.service('tmsPaste', ['$timeout', '$q', 'noticebox', function($timeout, $q, noticebox) {
    this.onpaste = function(originalText, dom) {
        var defer, actions, cleanEmptyText, cleanHtmlText, newText;
        defer = $q.defer();
        actions = [
            { label: '跳过', value: 'cancel' }
        ];
        /* 是否存在空字符 */
        cleanEmptyText = originalText.replace(/\s/gm, '');
        if (cleanEmptyText.length !== originalText.length) {
            actions.splice(0, 0, { label: '清除空字符', value: 'cleanEmpty' });
        }
        cleanHtmlText = originalText.replace(/<(style|script|iframe)[^>]*?>[\s\S]+?<\/\1\s*>/gi, '').replace(/<[^>]+?>/g, '').replace(/\s+/g, ' ').replace(/ /g, ' ').replace(/>/g, ' ');
        if (cleanHtmlText.length !== originalText.length) {
            actions.splice(0, 0, { label: '清除HTML', value: 'cleanHtml' });
        }
        if (actions.length > 1) {
            noticebox.confirm('清理粘贴内容格式？', actions).then(function(confirmValue) {
                switch (confirmValue) {
                    case 'cleanHtml':
                        newText = cleanHtmlText;
                        break;
                    case 'cleanEmpty':
                        newText = cleanEmptyText;
                        break;
                    default:
                        newText = originalText;
                }
                document.execCommand("insertHTML", false, newText);
                defer.resolve(newText);
            });
        } else {
            document.execCommand("insertHTML", false, originalText);
            defer.resolve(originalText);
        }
        return defer.promise;
    }
}]);