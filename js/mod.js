var enable_opmode_state = false;
var menuOpMod = 'modControls';
var modSecQuestion=  true;
var modAccess;


$(document).ready(function(){

    if($('.thread_index').length != 1)
        return;
 
    //var enable_control = "<a class='control post-control post-opmod-control' title='"+_T('Модерировать тред')+"' onclick=\"enable_opmod()\"><i class='fa fa-shield'></i></a>";
    var enable_watch_deleted = "<a class='control post-control' id='watch_hell' title='"+_T('Просмотр удалённых постов')+"' onclick=\"show_deleted_posts()\"><i class='fa fa-minus-circle'></i></a>";

    $('.post_op').find('.post-controls').append(enable_watch_deleted);

});

/*
$(document).on('new_post', function(e, post) {
 
    if(enable_opmode_state)
        enablePostModControls(post);
});


$(document).on('change_post', function(e, post) {
 
    if(enable_opmode_state)
        enablePostModControls(post);
});

*/

function enablePostModControls(post)
{

    if(!modAccess)
        return false;

    var accessBoard = false;
    var accessThread = false;

    // GLOBAL MODERATOR
    for(let i=0; i<modAccess.boards.length; i++)
    {
        if(modAccess.boards[i] == '*' || modAccess.boards[i] == post.dataset.board)
        {
            accessBoard = true;
            accessThread = true;
        }
    }

    // THREAD OP MOD
    for(let i=0; i<modAccess.threads.length; i++)
    { 
        if(modAccess.threads[i] == post.dataset.board + '_' + post.dataset.thread)
            accessThread = true;
    }

    if(!accessBoard && !accessThread)
        return false;

    $(post).find('.moder').remove();

    var is_oppost = post.dataset.thread == post.dataset.post;
    var boardThreadPostID = "'"+post.dataset.board+"', '"+post.dataset.thread+"', '"+post.dataset.post+"'";
    var hide_control = "<a class='control post-mod-control moder' title='"+_T('Удалить пост')+"' onclick=\"opmod_request('hide_post', "+boardThreadPostID+", '"+_T('Удалить пост?')+"')\"><i class='fa fa-trash'></i></a>"
    var delete_control = "<a class='control post-mod-control moder' title='"+_T('Удалить пост для всех')+"' onclick=\"opmod_request('delete_post', "+boardThreadPostID+", '"+_T('Удалить пост?')+"')\"><i class='fa fa-trash' style='color:#b73636'></i></a>"
    var ban_control = "<a class='control post-mod-control moder' title='"+_T('Забанить автора')+"' onclick=\"opmod_toggle_ban("+boardThreadPostID+")\"><i class='fa fa-gavel'></i></a>"
    var edit_control = "<a class='control post-edit post-edit-control moder' title='"+_T('Редактировать пост')+"' onclick=\"edit_request('get_body', "+boardThreadPostID+", '')\"><i class='fa fa-pencil'></i></a>";
    
 
    var quote = $(post).find('.post-quote-control');

    if(is_oppost && (accessBoard || accessThread))
    {

		if(accessBoard)
			quote.after(delete_control);
        quote.after(hide_control); 
        quote.after(ban_control);
		quote.after(edit_control);
    }
    else
    {
        if(accessBoard)
            quote.after(delete_control);
            
        quote.after(hide_control);
        quote.after(ban_control);

    }

 

    $(post).find('.post-file').each(function(i, file){

        var md5 = $(file).find('.preview').data('md5');
        var delete_file_control = "<a class='moder' style='cursor:pointer; margin-left:5px' title='"+_T('Удалить файл')+"' onclick=\"opmod_request('delete_file', "+boardThreadPostID+", '"+_T('Удалить файл?')+"', '"+md5+"')\">Удалить</a>"
        var spoiler_file_control = "<a class='moder' style='cursor:pointer; margin-left:5px' title='"+_T('Установить спойлер')+"' onclick=\"opmod_request('spoiler_file', "+boardThreadPostID+", '"+_T('Установить спойлер?')+"', '"+md5+"')\">Спойлер</a>"
        $(file).find('.post-file-info-item').hide();
        $(file).find('.post-file-info').append(delete_file_control + spoiler_file_control);

    });

}


function disable_opmod()
{
    $('.post-mod-control').remove();
    $('.moder').remove();
}

function enable_opmod()
{
    disable_opmod();
    opmod_request('login', $('.post_op')[0].dataset.board);
}
 

function opmod_toggle_unban()
{

    var menu_id = 'mod_unban';

    if(gm_show(menuOpMod, menu_id))
    {
        var div = "<div id='"+menu_id+"' class='modal' style='margin:10px 0 6px 0;'>" +
        "<label class='option-label'>"+_T('Номер бана')+"</label>" +
        "<input class='ml15' id='unban_id' type='text'>"+
        "<a class='button ml15' onclick=\"opmod_unban()\">"+_T('Разбанить')+"<a/></div>";
     
        var reply = $('.post_op');
    
        $(div).insertAfter(reply)
    }

}


function opmod_toggle_ban(board, thread, id)
{

    var menu_id = 'mod_ban';

    if(gm_show(menuOpMod, menu_id))
    {
       
        var div = "<div id='"+menu_id+"' class='modal' style='margin:5px 0 16px 0;'>" +
        "<label class='option-label'>"+_T('Причина')+"</label>" +
        "<input class='ml10' id='ban_reason' type='text'>"+
        "<select id='ban_time' class='option-select ml15'>"+
        "<option value=300>5 "+_T('минут')+"</option>"+
        "<option value=600>10 "+_T('минут')+"</option>"+
        "<option value=3600>1 час</option>"+
        "<option value=10800>3 часа</option>"+
        "<option value=21600>6 часов</option>"+
        "<option value=86400>1 день</option>"+
        "<option value=172800>2 дня</option>"+
        "<option value=259200>3 дня</option>"+
        "<option value=345600>4 дня</option>"+
        "<option value=432000>5 дней</option>"+
        "<option value=604800>7 дней</option>"+
        "<option value=864000>10 дней</option>"+
        "<option value=1209600>14 дней</option>"+
        "<option value=1814400>21 день</option>"+
        "<option value=2592000>30 дней</option>"+
        "<option value=''>Навсегда</option>"+
        "</select>"+
        "<label class='checktainer_xs ml10'>"+_T('Бан подсети')+"<input type='checkbox' id='ban_range'><span class='checkmark_xs'></span></label>"+
        "<label class='checktainer_xs ml10'>"+_T('Удалить все посты')+"<input type='checkbox' id='ban_delete_all'><span class='checkmark_xs'></span></label>"+
        
        "<a class='button ml15' onclick=\"opmod_ban('"+board+"','"+thread+"','"+id+"')\">"+_T('Забанить')+"</a></div>";
   
        var reply = getPost(board, thread, id);
        
        $(div).insertAfter(reply)
    }

}

function opmod_banlist()
{
    opmod_request('banlist');
}

function opmod_ban(board_id, thread_id, post_id, confirm)
{

    var time =  $('#ban_time :selected').val()
    var reason =  $('#ban_reason').val()
    var delete_all =$('#ban_delete_all').prop('checked');
    var range_ban =$('#ban_range').prop('checked');

    opmod_request('ban_post', board_id, thread_id, post_id, confirm, time, reason, delete_all, range_ban);
}

function opmod_unban(board_id, thread_id, post_id, confirm)
{ 
    opmod_request('unban_post', board_id, thread_id, post_id, confirm, $('#unban_id').val());
    $('#unban_option').remove();
}


function opmod_request(action, board, thread, post_id, confirm_text, param1, param2, param3, param4)
{

    if(modSecQuestion)
        if(confirm_text && !confirm(confirm_text))
            return;
    
    var fdata = new FormData();    
    fdata.append( 'action', action);//'login');
    fdata.append( 'trip',  (action == 'get_deleted')  ? 'none' : localStorage.name);

    if(board)
        fdata.append( 'board', board);
    if(thread)   
        fdata.append( 'thread_id', thread);
    if(post_id)   
        fdata.append( 'post_id', post_id);
  
    fdata.append( 'param1', param1);
    fdata.append( 'param2', param2);
    fdata.append( 'param3', param3);
    fdata.append( 'param4', param4);

    
    gm_remove(menuOpMod);

    $.ajax({
		url: configRoot+'opmod.php',
        type: 'POST',
        contentType: 'multipart/form-data', 
        data: fdata,
		success: function(response, textStatus, xhr) {

            if(response.alert)
                alert(_T(response.alert));
            if (response.error) 
                alert(_T(response.error));
            if (response.fail) 
                alert(_T(response.fail));
            if (response.wrong_trip) 
                alert(_T('Неверный трипкод'));
            else if(response.wrong_params)
                alert(_T('Ошибка в параметрах запроса'));
            else if(response.login_fail)
                alert(_T('Неверный трипкод'));
            else if(response.login_success)
            {
                enable_opmode_state = true;
                modAccess = response;
              
                $('.post').each(function(i, post){
                    enablePostModControls(post);
                });
            }
            else if(response.file_delete_success || response.spoiler_file_success)
            {
                var post = getPost(board, thread, post_id);

                $(post).find('.post-file').each(function(i, file){
                    var md5 = $(file).find('.preview').data('md5');

                    if(md5 == param1)
                    {
                        if(response.file_delete_success)
                            $(file).remove();
                        if(response.spoiler_file_success)
                            $(file).find('.preview').attr('src', '/static/spoiler.png');
                    }
                })
            }
            else if(response.ban_success)
            {
                alert(_T('Пользователь успешно забанен'));
                autoLoadAll();
            }
            else if(response.unban_success)
            {
                alert(_T('Пользователь успешно разбанен'));
                autoLoadAll();
                $('#mod_unban').remove();
            }
            else if(response.banlist)
                alert(response.banlist);
            else if(response.post_delete_success)
                getPost(board, thread, post_id).remove();
            else if(response.post_delete_view)
            {
                enable_devilstyle();

                $(response.data).find('article.post').each(function(){
                   	var id = parseInt($(this).attr('id').replace('reply_', ''));

                    if($('#' + id).length != 0)
                        return true;

                    $(this).addClass('new_post');
                    $(this).addClass('deleted_post');
                    $(this).find('.post-quote-control').remove();
					
					
					
					addNewPost($(this)[0], parseInt( this.getElementsByTagName('time')[0].getAttribute('unixtime')));
					
					return true;
					/*
					
					
					
					
					
					$(document).trigger('new_post', this);

                    for (var key in postStore) {

                        var kee = parseInt(key.replace('reply_', ''));
                        
                        if(kee > id)
                        {
                            $(this).insertBefore('#reply_'+kee);
                            return true;
                        }
                    }

                    $(this).insertAfter($('main .post:last'));*/
                    
                })

            }
                
            if(response.deleted_count)
                alert(_T('Удалено постов: ')+ response.deleted_count);

            if(response.need_reload)
               setTimeout(function(){ window.location.reload() }, 5000);
 
		},
		error: function(xhr, status, er) {
			alert(_T('Сервер вернул ошибку: ') + er);
		},
		contentType: false,
		processData: false
	}, 'json');
}



function enable_devilstyle()
{
    document.getElementById("theme-css").href = "/stylesheets/devil.css";
}

function show_deleted_posts()
{
    var post = $('.post_op')[0];

    $('#watch_hell').remove();
    opmod_request('get_deleted', post.dataset.board, post.dataset.thread);

}

















