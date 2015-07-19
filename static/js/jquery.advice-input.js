//
(function($){
    var onClickInput = function(event) {
        $(this).next().toggle();
        return false;
    };
    var onClickAdvice = function(event) {
        var $advices = $(this).parent();
        var $shell = $advices.parent();
        var $input = $advices.prev();
        if ($input.val() != $(this).html()) {
            $input.val($(this).html());
            var oAdvice = $(this).data('device');
            $shell.parent().trigger('choose.advice-input', oAdvice);
        }
        $input.focus();
        $advices.hide();
        return false;
    };
    var _methods = {
        init: function(options) {
            var ID = (new Date()).getTime();
            var oldValue = this.html(), that = this;
            var $divShell = $('<div>');
            var $taInput = $('<textarea>').val(oldValue).data('oldValue', oldValue);
            var w = this.innerWidth();
            var bl = parseInt(this.css('border-left-width'));
            var pl = parseInt(this.css('padding-left'));
            var h = this.innerHeight();
            var bt = parseInt(this.css('border-top-width'));
            var pt = parseInt(this.css('padding-top'));
            var position = this.position();
            $divShell.css({
                position: 'absolute',
                'z-index': 999,
                width: w,
                minHeight: h,
                top: position.top + bt,
                left: position.left + bl,
                display: 'none'
            }).appendTo(this);
            $taInput.css({
                display: 'inline-block',
                border:0,
                outline:'none',
                width: w,
                height: h,
                'padding-top': pt,
                'padding-left': pl,
                margin:0,
                overflow:'hidden',
                resize:'none'
            }).click(onClickInput).appendTo($divShell);

            $('<ul>').addClass('list-group').appendTo($divShell).css({maxHeight:'252px',overflow:'auto'});

            $taInput.keydown(function(event){
                if (event.which == 9) {
                    _methods['close'].apply(that);
                    $('body').off('click.'+ID);
                }
            });
            $('body').on('click.' + ID, function(event){
                if (event.target != that.get(0)) {
                    _methods['close'].apply(that);
                    $('body').off('click.'+ID);
                }
            });
        },
        advices: function(arrAdvices) {
            var $advices = this.find('ul').empty();
            for (var i in arrAdvices) {
                var oAdvice = arrAdvices[i];
                if (oAdvice.v == null || oAdvice.v.length == 0){
                    continue;
                }
                $('<li>').addClass('list-group-item')
                .html(oAdvice.v).data('device', oAdvice).click(onClickAdvice)
                .appendTo($advices);
            }
        },
        show: function(text) {
            var $shell = this.children();
            var $taInput = this.find('textarea'); 
            if (text)
                $taInput.val(text);
            //$taInput.TextAreaExpander(height);
            $shell.show();
            $taInput.focus();
        },
        close: function() {
            var newValue = this.find('textarea').val();
            var oldValue = this.find('textarea').data('oldValue');
            this.trigger('close.advice-input', [newValue, oldValue]);
            this.empty().html(newValue);
        }
    };
    $.fn.AdviceInput = function(method) {
        if (_methods[method]) {
            return _methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
        } else if (typeof method === 'object' || !method){
            return _methods.init.apply(this, arguments);
        } else {
            $.error('Method ' + method + ' does not exist on jQuery.advice-input');
        }
    };
})(jQuery);
