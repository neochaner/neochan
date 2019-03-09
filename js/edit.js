/* 
 DEP :   
            show-own-posts.js : thigger : new_own_post
*/
var edit_sec = 120;

$(document).on('new_own_post', function(e, post, board, post_id) {

    if(active_page == 'index')
        return true;

    
	var $post = $(post);
     
    if(parseInt($post.find('time').attr('unixtime')) + edit_sec > getServerTime())
    {

        $post.find('.post-quote-control').after(
         "<a class='control post-edit post-edit-control' title='"+_T('Редактировать пост')+"' onclick=\"edit_request('get_body', '"+post.dataset.board+"', '"+post.dataset.thread+"', '"+post.dataset.post+"', '')\"><i class='fa fa-pencil'></i></a>");

        setTimeout(function(){ remove_edit_option(post.dataset.board, post.dataset.thread, post.dataset.post) }, edit_sec*1000);

        
        var files = $post.find('.post-file');
        
        for(let i =0; i<files.length; i++){
             
            var $file = $(files[i]);
			var hash = $file.find('.post-file-link').find('img').data('md5');
			
			if(hash){
			
				var href = $file.find('.post-file-link').attr('href');
				
				var delCtrl = "<a class='control-image post-edit-control' title='"+_T('Удалить файл')+"' onclick=\"edit_request('delete_file', '"+post.dataset.board+"', '"+post.dataset.thread+"', '"+post.dataset.post+"', '"+hash+"')\"><i class='fa fa-trash'></i></a>";
				var hideCtrl ="<a class='control-image post-edit-control ml30' title='"+_T('Спойлер')+"' onclick=\"edit_request('spoiler_file', '"+post.dataset.board+"', '"+post.dataset.thread+"', '"+post.dataset.post+"', '"+hash+"')\"><i class='fa fa-eye-slash'></i></a>";
			 
				$file.find('.post-file-info').after(delCtrl+hideCtrl);
				 
			}
		}


     }

})




function remove_edit_option(board, thread_id, post_id)
{
    getPost(board, thread_id, post_id).find('.post-edit-control').remove();
}

function edit_cancel(board, thread_id, post_id)
{

    var post =  getPost(board, thread_id, post_id);

    $(post).find('.control-edit-cancel').remove();
    $(post).find('.control-edit-save').remove();
    
    $(post).find('.edit').remove();
    $(post).find('.post-body').show();
    $(post).find('.post-edit-control').show();

}

function edit_save(board, thread_id, post_id)
{   

    var post = getPost(board, thread_id, post_id);
    var text = $(post).find('.edit').val();
    
    $(post).find('.control-edit-cancel').remove();
    $(post).find('.control-edit-save').remove(); 


    $(post).find('.edit').remove();
    $(post).find('.post-body').show();
    $(post).find('.post-edit-control').show();
 
    edit_request('set_body', board, thread_id, post_id, text);
}

 

function edit_request(action, board, thread_id , post_id, text, skip_do_confirm = false)
{

    if(!skip_do_confirm && action == 'delete_file'){

        alert(_T('Удалить файл?'), true, function(){
            edit_request(action, board, thread_id , post_id, text, skip_do_confirm= true);
        });

        return false;  
    }

    var fdata = new FormData();    
    fdata.append( 'action', action);
    fdata.append( 'board', board);
    fdata.append( 'id',   post_id); 
    fdata.append( 'text', text == '' ? ' ' : text);  
    fdata.append( 'user_edit',   0);
    fdata.append( 'trip', localStorage.name);
    fdata.append( 'json_response',   0);

    $.ajax({
		url: configRoot+'post.php?neo23',
        type: 'POST',
        contentType: 'multipart/form-data', 
        data: fdata,
		success: function(response, textStatus, xhr) {

            if(response.error)
                alert(_T(response.error));

            if(response.get_post_success)
            {
                var post =  get_reply(post_id);
                var post_body = post.find('.post-body');
                var post_message = post.find('.post-message');

                var edit_control =  post.find('.post-edit-control');

                var x = post_body.height()+30;
                var y = post_body.width()-10;

                $(edit_control).after(
                    "<a class='control post-edit control-edit-cancel' onclick=\"edit_cancel('"+board+"', '"+thread_id+"', '"+post_id+"')\"><i class='fa fa-close'></i></div>"+
                    "<a class='control post-edit control-edit-save' onclick=\"edit_save('"+board+"', '"+thread_id+"', '"+post_id+"')\"><i class='fa fa-check'></i></div>")

                $(post_body).hide();
                $(edit_control).hide();
                $(post_body).after("<textarea class='edit rtable reply-body' contenteditable='true' style='resize:both;padding:2px;margin:5px 2px!important;width:"+y+"px;height:"+x+"px'>"+response.source+"</textarea>");


                // меняем пост
            }
            else if(response.delete_file_success)
                autoLoadAll();
            else if(response.spoiler_file_success)
                autoLoadAll();
            else if(response.save_post_success)
                autoLoadAll();
            else if (response.fail) 
                alert(_T(response.fail));
            else if (response.wrong_trip) 
                alert(_T('Неверный трипкод'));
            else 
                alert(_T(response.fail));
		},
		error: function(xhr, status, er) {
			alert(_T('Сервер вернул ошибку: ') + er);
		},
		contentType: false,
		processData: false
	}, 'json');
}


