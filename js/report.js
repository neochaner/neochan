 
/** global: Api, config */


Api.addPostMenu(addReportMenu);


function addReportMenu(obj){
	 
	 return [{
		 onclick:'report_toggle("'+obj.board+'", '+obj.thread+', '+obj.post+')',
		 name:'Репорт'	
	}];
}
 
 
 
 
function report_toggle(board, thread, post)
{

    if ($('#report_option').length != 0) {
        $('#report_option').remove();
        return;
	}

	var div = "<div id='report_option' class='report' style='margin:0 0 20px 0;'>" +
    "<label class='option-label'>"+_T('Reason')+"</label>" +
	"<input id='report_reason' type='text'>"+
	"<label class='checktainer_xs ml15' style='display:none'>"+_T('global')+
	"<input type='checkbox' id='report_global'><span class='checkmark_xs'></span>"+
	"</label>"+

    "<input class='button ml10' value='"+_T('Send')+"' onclick=\"send_report('"+board+"','"+thread+"', '"+post+"', )\" readonly></div>";
  
	var reply =  getPost(board, thread, post)
    $(div).insertAfter(reply)

}

function send_report(board, thread, post)
{

	var reason = $('#report_reason').prop('value');

	$('#report_option').fadeOut(300, function() { $(this).remove(); });
    
	var fdata = new FormData();  
	fdata.append( 'report', 1);
	fdata.append( 'board', board);
	fdata.append( 'thread', thread);
	fdata.append( 'post', post);
    fdata.append( 'delete_'+post, 1);
    fdata.append( 'reason', reason);
	fdata.append( 'json_response', 1);

	if ($('#report_global').prop('checked')) {
		fdata.append( 'global', 1);
	}
 

    $.ajax({
		url: config.root+'post.php?neo23',
        type: 'POST',
        contentType: 'multipart/form-data', 
        data: fdata,
		success: function(response, textStatus, xhr) {

            if(response.alert)
                alert(_T(response.alert));
            else if (response.error) 
                alert(_T(response.error));
			else if (response.fail) 
                alert(_T('An error has occurred'));
            else if(response.success)
				alert(_T('Success'));
			else
				alert(_T(response));
		},
		error: function(xhr, status, er) {
			alert(_T('Server error: ') + er);
		},
		contentType: false,
		processData: false
	}, 'json');


} 


 
 
 
 
 
 
 
 
 
 
 
 
 
 