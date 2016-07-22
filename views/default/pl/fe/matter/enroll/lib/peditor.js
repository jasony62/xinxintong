define([], function() {
	'use strict';
	return {
		html: '',
		disableInput: function(refresh) {
			var html;
			html = this.html;
			html = $('<div>' + html + '</div>');
			html.find('input[type=text],textarea').attr('readonly', true);
			html.find('input[type=text],textarea').attr('disabled', true);
			html.find('input[type=radio],input[type=checkbox]').attr('readonly', true);
			html.find('input[type=radio],input[type=checkbox]').attr('disabled', true);
			html = html.html();
			refresh === true && (this.html = html);

			return html;
		},
		purifyInput: function(refresh) {
			var html;
			html = this.html;
			html = $('<div>' + html + '</div>');
			html.find('.active').removeClass('active');
			html.find('[readonly]').removeAttr('readonly');
			html.find('[disabled]').removeAttr('disabled');
			html = html.html();
			refresh === true && (this.html = html);

			return html;
		}
	};
});