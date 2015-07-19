$.ajaxSetup({
    cache:false
    ,type: 'GET'
    ,beforeSend:function(jqXHR, settings) {
        $.tip.open('正在处理数据请等候...');
    }
    ,complete:function(jqXHR, textStatus) {
        if ($.tip._$tip.hasClass('alert-success')) {
            $.tip.msg('操作完成');
            $.tip.close(1500);
        }
    }
    ,success:function(rsp) {
        if (typeof(rsp) === 'string') {
            $.tip.error('操作失败：' + rsp);
        } else if (rsp.err_code != 0) {
            $.tip.error('操作失败：' + rsp.err_msg);
            return;
        }
        if (this.success2) {
            this.success2(rsp);
        }
    }
});
/**
 * Global TopTip.
 */
(function($){
    var cssBase = {
        'position': 'fixed',
        'font-size': '14px',
        'min-height': '30px',
        'padding': '5px 4px 5px 4px', 
        'width': 300,
        'overflow': 'hidden',
        'top': 10,
        'left': '50%',
        'margin-left': -150,
        'text-align': 'center',
        'z-index': 9999,
        'border-radius': '4px'
    };
    var btnClose = '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>';
    $.tip = {
        _$tip: undefined,
        open: function(msg) {
            if (this._$tip == undefined) {
                this._$tip = $('<div>').css(cssBase);
                $('body').append(this._$tip);
            }
            if (this._$tip.hasClass('alert-danger')) {
                this._$tip.removeClass('alert-danger');
            }
            this._$tip.addClass('alert-success');
            this._$tip.html(msg).show();
            return this._$tip;
        },
        error: function(msg) {
            if (this._$tip == undefined) {
                this._$tip = $('<div>').css(cssBase);
                $('body').append(this._$tip);
            }
            if (this._$tip.hasClass('alert-success')) {
                this._$tip.removeClass('alert-success');
            }
            this._$tip.addClass('alert-danger');
            this._$tip.html(btnClose+msg).show();
            this._$tip.off('click.close', '.close');
            this._$tip.on('click.close', '.close', function(){
                $.tip.close();
            });
            return this._$tip;
        },
        msg: function(msg) {
            this._$tip.html(msg);
        },
        close: function(delay) {
            if (this._$tip) {
                if (delay) {
                    this._$tip.hide(delay);
                } else {
                    this._$tip.hide();
                }
            }
        }
    }
})(jQuery);
/**
* Global Dialog
* options:
* title
* body
* footer
* fullHeight
* retainBody
*/
(function($){
    // dialog definition
    var DLG = '<div class="modal fade">';
    DLG += '<div class="modal-dialog">';
    DLG += '<div class="modal-content">';
    DLG += '<div class="modal-header">';
    DLG += '<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>';
    DLG += '<h4 class="modal-title">title</h4>';
    DLG += '</div>';
    DLG += '<div class="modal-body">';
    DLG += '</div>';
    DLG += '<div class="modal-footer">';
    DLG += '</div>';
    DLG += '</div>';
    DLG += '</div>';
    DLG += '</div>';
    //
    $.dlg = {
        _dlg: undefined,
        open: function(options) {
            !options && (options = {});
            if (this._dlg == undefined) {
                this._dlg = $(DLG);
                $(this._dlg).on('shown.bs.modal', function (e) {
                    if (options.bodyCss == undefined || options.bodyCss.height == undefined) {
                        var wh = $(window).height();
                        var dlgh = $.dlg._dlg.find('.modal-dialog').outerHeight(true);
                        var h = dlgh - wh;
                        if (h > 0 || options.fullHeight) {
                            var $body = $.dlg._dlg.find('.modal-body');
                            var bodyh = $body.height();
                            if (options.bodyCss == undefined || options.bodyCss.overflow == undefined) {
                                $body.css({'overflow':'auto'});
                            }
                            $body.height(bodyh - h);
                        }
                    }
                })
                $(this._dlg).on('hidden.bs.modal', function (e) {
                    if (options.retainBody) {
                        var $dlgBody = $.dlg._dlg.find('.modal-body').children();
                        $dlgBody.hide();
                        $('#' + $.dlg.bodyTmpId).replaceWith($dlgBody);
                        delete $.dlg.bodyTmpId;
                    }
                    $.dlg._dlg.remove();
                    $.dlg._dlg = undefined;
                })
            }
            options.dialogWidth && this._dlg.find('.modal-dialog').css('width', options.dialogWidth);
            options.title && this._dlg.find('.modal-title').html(options.title);
            if (options.body) {
                if (options.bodyCss) {
                    this._dlg.find('.modal-body').css(options.bodyCss);
                }
                if (options.retainBody) {
                    var $dlgBody = $('body').find(options.body);
                    if ($dlgBody.length > 0) {
                        this.bodyTmpId = '__' + (new Date).getTime() + '__';
                        $wrap = $('<div>').attr('id', this.bodyTmpId).hide();
                        $dlgBody.wrap($wrap);
                    }
                    $dlgBody.show();
                }
                this._dlg.find('.modal-body').html(options.body);
            }
            if (!options.footer) {
                this._dlg.find('.modal-footer').hide();
            } else {
                this._dlg.find('.modal-footer').html(options.footer);         
            }
            if (options.shown) {
                $(this._dlg).on('shown.bs.modal', function (e) {
                    options.shown.call($.dlg, $.dlg._dlg);
                });
            }
            if (options.hidden) {
                $(this._dlg).on('hidden.bs.modal', function (e) {
                    options.hidden.call($.dlg, $.dlg._dlg);
                });
            }
            $(this._dlg).modal();

            return this._dlg;
        },
        close: function() {
            this._dlg.modal('hide');
        }
    };
})(jQuery);
/**
* editable list plugin 
*/
(function($){
    var $list = null;
    var _settings = {
        eventName: 'list.change',
        placeholder: ''
    };
    var activeItem = function(content) {
        $input = $('<input>').attr({type:'text',placeholder:_settings.placeholder})
        .addClass('form-control input-sm')
        .blur(deactive);
        if (content && content.length > 0) {
            $input.val(content).data('oldContent', content);
        }
        return $input;
    };
    var staticItem = function(content) {
        $span = $('<span>').html(content).addClass('content').click(active);
        return $span;
    };
    var itemWrapper = function() {
        $li = $('<li>').addClass('list-group-item')
        .mouseenter(function(){
            if ($(this).find('input').length == 0) {
                $(this).append(
                    $('<button>').addClass('btn btn-default btn-xs pull-right delete')
                    .append($('<span>').addClass('glyphicon glyphicon-remove'))
                    .click(removeItem)
                );
            }
        })
        .mouseleave(function(){
            $(this).children('button.delete').remove();
        });
        return $li;
    };
    var deactive = function() {
        var state,newContent,oldContent;
        newContent = $(this).val();
        oldContent = $(this).data('oldContent');

        if (!oldContent && newContent.length > 0) {
            state = 'n';
        } else if (oldContent && newContent.length > 0 && oldContent != newContent) {
            state = 'm';
        } else if (oldContent && newContent.length == 0) {
            state = 'd';
        }
        if (newContent.length > 0) {
            $span = staticItem(newContent);
            $(this).replaceWith($span);
        } else {
            // empty, equal delete
            $(this).parent().remove();
        }
        if (state) {
            $list.trigger(_settings.eventName,[state, oldContent, newContent]);
        }
    };
    var active = function() {
        $(this).parent().children('button.delete').remove();
        var content = $(this).html();
        $input = activeItem(content);
        $(this).replaceWith($input);
        $input.focus();
    };
    var removeItem = function() {
        $wrapper = $(this).parent();
        var oldContent = $wrapper.children('span').html();
        $wrapper.remove();
        $list.trigger(_settings.eventName,['d', oldContent]);
    };
    var _methods = {
        init: function(options) {
            _settings = $.extend(_settings, options);
        },
        append: function() {
            var $wrapper = itemWrapper();
            var $item = activeItem();
            $(this).append($wrapper.append($item));
            $item.focus();
        },
        load: function(arr, fnLabel) {
            $(this).empty();
            for (var i in arr) {
                var $wrapper = itemWrapper();
                var label = fnLabel ? fnLabel(arr[i]) : arr[i];
                var $item = staticItem(label);
                $(this).append($wrapper.append($item));
            }
        },
        flush: function() {
            $(this).find('input').blur();
            var arr = [];
            $(this).find('li span').each(function(){
                arr.push($(this).html());
            });
            return arr;
        }
    };
    $.fn.EditableList = function(method) {
        $list = $(this);
        if (_methods[method]) {
            return _methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
        } else if (typeof method === 'object' || !method) {
            return _methods.init.apply(this, arguments);
        } else {
            $.error('Method ' + method + ' does not exist on jQuery.EditableList.');
        }
    };  
})(jQuery)
