/*
 * file-selector.js - Add support for drag and drop file selection, and paste from clipbboard on supported browsers.
 *
 * Usage:
 *   $config['additional_javascript'][] = 'js/jquery.min.js';
 *   $config['additional_javascript'][] = 'js/file-selector.js';
 */


$(document).ready(function() {
	if(typeof config != 'undefined')
		init_file_selector(config.max_images);
});


function init_file_selector(max_images) {


// incompatible browser.
if ( !(window.URL && window.URL.createObjectURL && window.File))
	return;

// multipost not enabled
if (typeof max_images == 'undefined') {
	var max_images = 1;
}

var files = [];
$('#upload_file').hide();  // remove the original file selector
$('.dropzone-wrap').css('user-select', 'none').show();  // let jquery add browser specific prefix

function addFile(file) 
{
	
	if (files.length == max_images)
	{
		return;
	}

	files.push(file);
	addThumb(file);
}

function removeFile(file) {
	files.splice(files.indexOf(file), 1);
}

function getThumbElement(file) {
	return $('.tmb-container').filter(function(){return($(this).data('file-ref')==file);});
}

function addThumb(file) {

	var fileName = (file.name.length < 24) ? file.name : file.name.substr(0, 22) + '…';
	var spoiler = "<label class='checktainer_xs' style='margin-left:5px;bottom:2px'>Спойлер<input id=\"spoiler_"+file.size+"\" type='checkbox'><span class='checkmark_xs'></span></label>";
	var fileType = file.type.split('/')[0];
	var fileExt = file.type.split('/')[1];
	var $container = $('<div>')
		.addClass('tmb-container')
		.data('file-ref', file)
		.append(
			$('<div>').addClass('remove-btn').html('✖'),
			$('<div>').addClass('file-tmb'),
			$('<div>').addClass('tmb-filename').html(spoiler)
			//$('<div>').addClass('tmb-filename').html(fileName)
			
		)
		.appendTo('.file-thumbs');

	var $fileThumb = $container.find('.file-tmb');
	if (fileType == 'image') {
		// if image file, generate thumbnail
		var objURL = window.URL.createObjectURL(file);
		$fileThumb.css('background-image', 'url('+ objURL +')');
	} else {
		$fileThumb.html('<span>' + fileExt.toUpperCase() + '</span>');
	}
}

$(document).on('ajax_before_post', function (e, formData) {

	var old_vi = false;

	for (var i=0; i<max_images; i++)
	{
		var key = 'file';
		if (i > 0) key += i + 1;
		if (typeof files[i] === 'undefined') break;
 
		var spkey = "spoiler_" + files[i].size;
		var state = false;

		if(document.getElementById(spkey) && document.getElementById(spkey).checked)
		{
			state = true;
		} 
		formData.append(spkey, state);
		formData.append(key, files[i]);

		// old vichan support
		if(!old_vi && state)
		{
			formData.append('spoiler', 'on');
			old_vi = true;
		}
	}



});

// clear file queue and UI on success
$(document).on('clear_post_files', function () {
	files = [];
	$('.file-thumbs').empty();
});

var is_firefox = (/Firefox/i.test(navigator.userAgent));

var dragCounter = 0;
var dropHandlers = {
	dragenter: function (e) 
	{
		if(e.originalEvent.dataTransfer == null || e.originalEvent.dataTransfer.types[0] == 'Files') 
		{

			e.stopPropagation();
			e.preventDefault();

			if (dragCounter === 0) $('.dropzone').addClass('dragover');
			dragCounter++;
		}

	},
	dragover: function (e) {
		// needed for webkit to work
 

		if(is_firefox)// && (e.originalEvent.dataTransfer == null || e.originalEvent.dataTransfer.types[0] == 'Files'))
		{
			e.stopPropagation();
			e.preventDefault();
		}
	},
	dragleave: function (e) {

		if(e.originalEvent.dataTransfer.types[0] == 'Files')
		{
			e.stopPropagation();
			e.preventDefault();

			dragCounter--;
			if (dragCounter === 0) $('.dropzone').removeClass('dragover');
		}
	},
	drop: function (e) {

		if(e.originalEvent.dataTransfer.files.length != 0)
		{
	
			e.stopPropagation();
			e.preventDefault();

			$('.dropzone').removeClass('dragover');
			dragCounter = 0;

			var fileList = e.originalEvent.dataTransfer.files;
			for (var i=0; i<fileList.length; i++) {
				addFile(fileList[i]);
			}
		}

	}
};




if(!is_mobile)
{
	$(document).on(dropHandlers);
}

$(document).on('click', '.dropzone .remove-btn', function (e) {
	e.stopPropagation();

	var file = $(e.target).parent().data('file-ref');

	getThumbElement(file).remove();
	removeFile(file);
});


$(document).on('keypress click', '.reply-attach-control', function (e) {
	e.stopPropagation();


	$('<input type="file" id="fsel" style="display:none" multiple>').insertAfter('.reply-files')

	$('#fsel').on('change', function (e) {
		if (this.files.length > 0) {
			for (var i=0; i<this.files.length; i++) {
				addFile(this.files[i]);
			}
		}
		$('#fsel').remove();
	});

	$('#fsel').click();
});


$(document).on('paste', function (e) {
	var clipboard = e.originalEvent.clipboardData;
	if (typeof clipboard.items != 'undefined' && clipboard.items.length != 0) {
		
		//Webkit
		for (var i=0; i<clipboard.items.length; i++) {
			if (clipboard.items[i].kind != 'file')
				continue;

			//convert blob to file
			var file = new File([clipboard.items[i].getAsFile()], 'ClipboardImage.png', {type: 'image/png'});
			addFile(file);
		}
	}
});

}
