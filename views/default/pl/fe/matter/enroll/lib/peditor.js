define([], function() {
	'use strict';
	return {
		html: '',
		disableInput: function() {
			var html;
			html = this.html;
			html = $('<div>' + html + '</div>');
			html.find('input[type=text],textarea').attr('readonly', true);
			html.find('input[type=text],textarea').attr('disabled', true);
			html.find('input[type=radio],input[type=checkbox]').attr('readonly', true);
			html.find('input[type=radio],input[type=checkbox]').attr('disabled', true);
			this.html = html.html();

			return this.html;
		},
		purifyInput: function() {
			var html;
			html = this.html;
			html = $('<div>' + html + '</div>');
			html.find('.active').removeClass('active');
			html.find('[readonly]').removeAttr('readonly');
			html.find('[disabled]').removeAttr('disabled');
			this.html = html.html();

			return this.html;
		}
	};
});