require.config({　　　　
	paths: {　　　　　　
		"jQuery": "https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min"　　　　
	}　　
});
require(['jQuery'], function(jQuery) {
	document.querySelector('.loading').style.display = 'none';
}, function(err) {
	document.querySelector('.loading').style.display = 'none';
});