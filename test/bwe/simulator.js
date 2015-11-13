var elSimulator = document.querySelector('#simulator');
elSimulator.onload = function() {
	console.log('iframe onload');
	var elSimulator = this,
		js;
	js = document.createElement('script');
	js.type = 'text/javascript';
	js.src = '/test/bwe/mutable.js?_=' + (new Date()).getTime();
	elSimulator.contentWindow.document.body.appendChild(js);
};
elSimulator._mutableEitor = {
	text: function(el) {
		el.innerHTML = '你好';
	},
	options: function(el, options) {
		if (options && options.length) {
			el.removeChild(options[0]);
		}
	}
};
elSimulator.src = 'page.html?_' + (new Date()).getTime();