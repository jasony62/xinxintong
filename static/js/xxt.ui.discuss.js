angular.module('discuss.ui.xxt', []).
directive('tmsDiscuss', function() {
    return {
        restrict: 'A',
        scope: {
            tmsDiscussReady: '='
        },
        link: function(scope, elem, attrs) {
            scope.$watch('tmsDiscussReady', function(ready) {
                if (ready === 'Y') {
                    window.tmsDiscussConfig = {
                        domain: attrs.tmsDiscuss
                    };
                    /*begin*/
                    (function(win, doc) {
                        var eThread, eReset, eMeta, eComments, eReplybox, thread = {};

                        function elapsedTime(time) {
                            var c = new Date,
                                t = new Date,
                                a = (new Date / 1000) - time;

                            t.setTime(time * 1000);

                            return 10 > a ? "刚刚" : 60 > a ? Math.round(a) + "秒前" : 3600 > a ? Math.round(a / 60) + "分钟前" : 86400 > a ? Math.round(a / 3600) + "小时前" : (c.getFullYear() == t.getFullYear() ? "" : t.getFullYear() + "年") + (t.getMonth() + 1) + "月" + t.getDate() + "日"
                        }

                        function metaHtml() {
                            var html;
                            html = '<a class="ds-like-thread-button ds-rounded';
                            if (thread.user_vote === 'Y') {
                                html += ' ds-thread-liked';
                            }
                            html += '">';
                            html += '<span class="ds-icon ds-icon-heart"></span>';
                            html += '<span class="ds-thread-like-text">';
                            html += thread.user_vote === 'Y' ? '已喜欢' : '喜欢';
                            html += '</span>';
                            html += '<span class="ds-thread-cancel-like">取消喜欢</span>';
                            html += '</a>'
                            html += '<span class="ds-like-panel">';
                            if (thread.user_vote === 'Y') {
                                html += '<span class="ds-highlight">' + thread.likes + '</span> 人喜欢';
                            }
                            html += '</span>';
                            return html;
                        }

                        function commentHtml(post) {
                            var html;
                            html = '<li class="ds-post" data-post-id="' + post.id + '">';
                            html += '<div class="ds-post-self">';
                            html += '<div class="ds-comment-body">';
                            html += '<div class="ds-comment-header">';
                            html += '<a class="ds-user-name ds-highlight">' + post.author_name + '</a>';
                            html += '</div>';
                            html += '<p>' + post.message + '</p>';
                            html += '<div class="ds-comment-footer ds-comment-actions';
                            if (post.user_vote === 'Y') {
                                html += ' ds-post-liked';
                            }
                            html += '">';
                            html += '<span class="ds-time">' + elapsedTime(post.create_at) + '</span>';
                            html += '<a class="ds-post-reply" href="javascript:void(0);"><span class="ds-icon ds-icon-reply"></span>回复</a>';
                            html += '<a class="ds-post-like" href="javascript:void(0);"><span class="ds-icon ds-icon-like"></span>顶';
                            if (parseInt(post.likes)) {
                                html += '<span>(' + post.likes + ')</span>';
                            }
                            html += '</a>';
                            html += '</div>';
                            html += '</div>';
                            html += '</div>';
                            html += '</li>';
                            return html;
                        }

                        function commentsHtml(posts) {
                            var html = '';
                            posts.forEach(function(post) {
                                html += commentHtml(post);
                            });
                            return html;
                        }

                        function replyboxHtml(parentPostId) {
                            var html;
                            html = '<form data-thread-id="' + thread.id + '"';
                            parentPostId && (html += ' data-parent-id="' + parentPostId + '"');
                            html += '>';
                            html += '<div class="ds-textarea-wrapper ds-rounded-top">';
                            html += '<textarea></textarea>';
                            html += '</div>'
                            html += '<div class="ds-post-toolbar">';
                            html += '<div class="ds-post-options ds-gradient-gb"></div>';
                            html += '<button class="ds-post-button">发布</button>';
                            html += '</div>';
                            html += '</form>';
                            return html;
                        }

                        function inlineReplyboxHtml(parentPostId) {
                            var html;
                            html = '<div class="ds-replybox ds-inline-replybox">';
                            html += replyboxHtml(parentPostId);
                            html += '</div>';
                            return html;
                        }

                        function onCommentSubmit(event) {
                            var threadId, message;
                            event.preventDefault();
                            threadId = event.target.dataset.threadId;
                            parentId = event.target.dataset.parentId || 0;
                            message = event.target.querySelector('textarea').value;
                            doAjax('post', '/rest/discuss/post/create?domain=' + tmsDiscussConfig.domain, {
                                thread_id: threadId,
                                parent_id: parentId,
                                message: message
                            }, function(rsp) {
                                var html;
                                html = commentHtml(rsp.data);
                                eComments.innerHTML = html + eComments.innerHTML;
                            });
                        }


                        function doAjax(type, url, data, callback) {
                            var xhr = new XMLHttpRequest();
                            xhr.open(type, url, true);
                            xhr.setRequestHeader("Content-type", "application/json;charset=UTF-8");
                            xhr.setRequestHeader("Accept", "application/json");
                            xhr.onreadystatechange = function() {
                                if (xhr.readyState == 4) {
                                    if (xhr.status >= 200 && xhr.status < 400) {
                                        try {
                                            if (callback) {
                                                var rsp = xhr.responseText;
                                                var obj = eval("(" + rsp + ')');
                                                callback(obj);
                                            }
                                        } catch (e) {
                                            console.log('E2', e);
                                            alert('E2:' + e.toString());
                                        }
                                    } else {
                                        alert('E3:' + xhr.statusText);
                                    }
                                }
                            };
                            xhr.send(data ? JSON.stringify(data) : null);
                        }
                        var threadAction = {
                            like: function(threadId) {
                                doAjax('post', '/rest/discuss/thread/vote?domain=' + tmsDiscussConfig.domain, {
                                    thread_id: threadId,
                                    vote: 'Y'
                                }, function() {});
                            },
                            unlike: function(threadId) {
                                doAjax('post', '/rest/discuss/thread/vote?domain=' + tmsDiscussConfig.domain, {
                                    thread_id: threadId,
                                    vote: 'N'
                                }, function() {});
                            }
                        };
                        var postActions = {
                            like: function(postId) {
                                doAjax('post', '/rest/discuss/post/vote?domain=' + tmsDiscussConfig.domain, {
                                    post_id: postId,
                                    vote: 'Y'
                                }, function() {});
                            },
                            unlike: function(postId) {
                                doAjax('post', '/rest/discuss/post/vote?domain=' + tmsDiscussConfig.domain, {
                                    post_id: postId,
                                    vote: 'N'
                                }, function() {});
                            }
                        };

                        function injectStylesheet(url) {
                            var l = doc.createElement("link");
                            l.type = "text/css", l.rel = "stylesheet", l.href = url;
                            doc.getElementsByTagName('head')[0].appendChild(l);
                        }

                        function build(posts) {
                            injectStylesheet('/static/css/discuss.css');
                            /*评论的主题*/
                            eMeta = doc.createElement('div');
                            eMeta.classList.add('ds-meta');
                            eMeta.innerHTML = metaHtml();
                            eMeta.querySelector('a').addEventListener('click', function(event) {
                                var eAction = event.target;
                                while (eAction.tagName !== 'A') {
                                    eAction = eAction.parentNode;
                                }
                                if (eAction.classList.toggle('ds-thread-liked')) {
                                    eAction.querySelector('.ds-thread-like-text').innerHTML = '已喜欢';
                                    eMeta.querySelector('.ds-like-panel').innerHTML = '<span class="ds-highlight">' + (parseInt(thread.likes) + 1) + '</span> 人喜欢';
                                    threadAction.like(thread.id);
                                } else {
                                    eAction.querySelector('.ds-thread-like-text').innerHTML = '喜欢';
                                    eMeta.querySelector('.ds-like-panel').innerHTML = '';
                                    threadAction.unlike(thread.id);
                                }
                            }, true);
                            /*评论*/
                            eComments = doc.createElement('ul');
                            eComments.classList.add('ds-comments');
                            eComments.innerHTML = commentsHtml(posts);
                            eComments.addEventListener('click', function(event) {
                                var eAction = event.target,
                                    ePost, postId;
                                if (eAction.tagName === 'SPAN') {
                                    eAction = eAction.parentNode;
                                }
                                if (eAction.tagName === 'A') {
                                    ePost = eAction.parentNode;
                                    while (ePost.tagName !== 'LI') {
                                        ePost = ePost.parentNode;
                                    }
                                    postId = ePost.dataset.postId;
                                    if (eAction.classList.contains('ds-post-reply')) {
                                        var eCommentBody = eAction.parentNode.parentNode;
                                        if (eAction.parentNode.classList.toggle('ds-reply-active')) {
                                            var html = inlineReplyboxHtml(eCommentBody.parentNode.parentNode.dataset.postId);
                                            eCommentBody.innerHTML += html;
                                            eCommentBody.querySelector('form').addEventListener('submit', onCommentSubmit, true);
                                        } else {
                                            eCommentBody.removeChild(eCommentBody.querySelector('.ds-replybox'));
                                        }
                                    } else if (eAction.classList.contains('ds-post-like')) {
                                        var eLikeNum = eAction.querySelector('span:nth-child(2)'),
                                            num;
                                        if (eAction.parentNode.classList.toggle('ds-post-liked')) {
                                            if (eLikeNum) {
                                                eLikeNum.innerHTML = '(' + (parseInt(eLikeNum.innerHTML.slice(1, -1)) + 1) + ')';
                                            } else {
                                                eLikeNum = doc.createElement('span');
                                                eLikeNum.innerHTML = '(1)';
                                                eAction.appendChild(eLikeNum);
                                            }
                                            postActions.like(postId);
                                        } else {
                                            num = parseInt(eLikeNum.innerHTML.slice(1, -1)) - 1;
                                            if (num) {
                                                eLikeNum.innerHTML = '(' + num + ')';
                                            } else {
                                                eAction.removeChild(eLikeNum);
                                            }
                                            postActions.unlike(postId);
                                        }
                                    }
                                }
                            }, true);
                            /*发表评论框*/
                            eReplybox = doc.createElement('div');
                            eReplybox.classList.add('ds-replybox');
                            eReplybox.innerHTML = replyboxHtml();
                            eReplybox.querySelector('form').addEventListener('submit', onCommentSubmit, true);

                            eReset = doc.createElement('div');
                            eReset.setAttribute('id', 'ds-reset');
                            eReset.appendChild(eMeta);
                            eReset.appendChild(eComments);
                            eReset.appendChild(eReplybox);

                            eThread.appendChild(eReset);
                        }

                        eThread = doc.querySelector('.ds-thread');
                        eThread.setAttribute('id', 'ds-thread');
                        thread.id = eThread.dataset.threadKey;
                        thread.title = eThread.dataset.title;
                        doAjax('get', '/rest/discuss/thread/listPosts?domain=' + tmsDiscussConfig.domain + '&threadKey=' + thread.id + '&title=' + thread.title, null, function(rsp) {
                            var posts = rsp.data.posts;

                            thread = rsp.data.thread;
                            build(posts);
                        });
                    })(window, document);
                    /*end*/
                }
            });
        }
    }
});