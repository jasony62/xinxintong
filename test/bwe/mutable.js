(function() {
	console.log('load mutable');
	var findMutable = function(el) {
		if (el.classList.contains('mutable')) {
			return el;
		} else if (el.tagName === 'BODY') {
			return null;
		} else {
			return findMutable(el.parentNode);
		}
	};
	var clickMutable = function(event) {
		console.log('click mutable', event);
		var elMutable, elClasses, elSimulator, mutableEditor;
		elMutable = findMutable(event.target);
		elClasses = elMutable.classList;
		elSimulator = window.parent.document.querySelector('#simulator');
		mutableEditor = elSimulator._mutableEitor;
		if (elClasses.contains('mutable-text')) {
			// change text
			if (mutableEditor.text) {
				mutableEditor.text(elMutable, elMutable.innerHTML);
			}
		} else if (elClasses.contains('mutable-options')) {
			if (mutableEditor.options) {
				var options = elMutable.querySelectorAll('.mutable-option');
				mutableEditor.options(elMutable, options);
			}
		} else if (elClasses.contains('mutable-arbitrary')) {
			elMutable.innerHTML = "welcome";
			var elStyle = document.createElement('style');
			elStyle.type = 'text/css';
			elStyle.innerHTML = '#m001{color:red}';
			document.querySelector('head').appendChild(elStyle);
		}
	};
	var elMutables, i, j, elMutable;
	elMutables = document.querySelectorAll('.mutable');
	for (i = 0, j = elMutables.length; i < j; i++) {
		elMutable = elMutables[i];
		elMutable.onclick = clickMutable;
	}
	window.mutableHelper = {
		changeMutableConfigs: function(data) {
			var elHtml, elNgInit;
			elHtml = document.querySelector('html');
			if (elNgInit = elHtml.getAttribute('ng-init')) {
				elHtml.setAttribute('ng-init', 'mutableConfigs=' + JSON.stringify(data));
				alert('need save and fresh');
			}
		}
	};
})();