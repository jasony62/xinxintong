'use strict';
var ngMod = angular.module('paste.ui.xxt', ['ngSanitize', 'notice.ui.xxt']);
ngMod.service('tmsPaste', ['$timeout', '$q', 'noticebox', function($timeout, $q, noticebox) {
    this.onpaste = function(originalText, oOptions) {
        function fnDoPaste(text) {
            if (oOptions.doc) {
                oOptions.doc.execCommand("insertHTML", false, text);
            } else {
                document.execCommand("insertHTML", false, text);
            }
            defer.resolve(text);
        }

        var defer, actions, cleanEmptyText, cleanHtmlText, newText;
        defer = $q.defer();
        actions = [
            { label: '跳过', value: 'cancel', execWait: 3000 }
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
                fnDoPaste(newText);
                defer.resolve(newText);
            }, function() {
                fnDoPaste(originalText);
            });
        } else {
            fnDoPaste(originalText);
        }
        return defer.promise;
    }
}]);