
$(document).ready(function() {
	menu_add_checkbox("testKey", false, 'l_test_mode', _T('Тестовый режим'));
	
	$(document).on('testKey', function(e, value) {	
		console.log('testMenu : ' + value);
	});
	
});


function runTest()
{
	var iframe = '<iframe width="560" height="315" src="https://www.youtube.com/embed/XzXjuDM_Z54" frameborder="0" allowfullscreen></iframe>';

	$('#fullscreen-container').html(iframe);
} 
