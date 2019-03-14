

$(document).ready(function(){


	// язык
	$("#language").val(selected_language);
  

	var t0 = performance.now();


	jsLocalization();

	var t1 = performance.now();

	reload();

	$('#language').change(function()
	{	 
		localStorage.setItem('language', this.value);
		selected_language = this.value;
		document.getElementById("language-css").href = "/stylesheets/lang/" + this.value + ".css";
		reload();
	
		var optionLocalTimeKey = 'localTimeMode';
		if(getKey(optionLocalTimeKey, true))
		{		
			do_localtime(document);
		}
		else
		{
			do_absolutetime(document);
		}
	
	});
	


})

function jsLocalization(){


	var jsLocal = {
		'ru': {'l_latest_threads' : 'Последние треды'},
		'en': {'l_latest_threads' : 'Recent Threads '},
	}

	if(!jsLocal.hasOwnProperty(selected_language))
		return;


	var el = document.getElementsByClassName('localize');

	for(let i=0, l=el.length; i<l; i++){
		for(let j=0; j<el[i].classList.length; j++){
			let lclass = el[i].classList[j];
			if(lclass.startsWith('l_')){

				if(el[i].tag = 'option'){
					 jsLocal[selected_language].hasOwnProperty(lclass);
					 el[i].text = jsLocal[selected_language][lclass];
				}


			}
		}
	}
}


/*
function getLocalizeFromClass(className){
	



	var t0 = performance.now();



	let le = document.createElement('p');
	le.classList.add(className);	
	le.style.display='none';

	document.body.appendChild(le)

	let elStyle = window.getComputedStyle(le, '::before')
	let text = elStyle.getPropertyValue("content")


	le.parentElement.removeChild(le);

	if(text && text.length > 3 && (text[0] == '"' || text[0] == '\'')){
		text = text.substr(1, text.length-2);
	}





	
	var t1 = performance.now();
	console.log("Call getLocalizeFromClass took... " + (t1 - t0) + " ms")




	return text;


}*/

function reload()
{
	$('.reply-attach-control').attr('title', _T('Прикрепить файл (Alt+O)'));
	$('.reply-quote-control').attr('title', _T('Цитировать (Alt+C)'));
	$('.reply-bold-control').attr('title', _T('Жирный (Alt+B)'));
	$('.reply-italic-control').attr('title', _T('Наклонный (Alt+I)'));
	$('.reply-strikethrough-control').attr('title', _T('Зачёркнутый (Alt+T)'));
	$('.reply-spoiler-control').attr('title', _T('Спойлер (Alt+P)'));
	$('.reply-love-control').attr('title', _T('Любовь (Alt+L)'));
	$('.reply-smile-control').attr('title', _T('Вставить смайл'));

	$('.omit-info').each(function(i, elem){
		
		var posts = $(elem).data('posts');
		var files = $(elem).data('files');

		
		$(elem).html(omit_text(posts, files));

	});

	var elems = $('.post-link');

	for(var i=0; i<elems.length; i++)
	{
		var link = $(elems[i]);
		link.html(link.html().replace(/ \(.+\)/, _T(' (Вы)')));
	}

	$('.reply-subject[name=subject]').attr('placeholder', _T('тема')) 
	$('.reply-subject[name=neoname]').attr('placeholder', _T('имя'))
	$('.reply-send-button').attr('value', _T('Отправить'));




}

function omit_text(posts, files)
{
	switch(selected_language)
	{
		case 'ru':
			return posts + ' постов и ' + files + ' файлов пропущено';
		default:
			return posts + ' posts and ' + files + ' files missed';
	}
}


function _T(ru_text)
{
	/*
	if(selected_language == 'en')
		return _E(ru_text);
	return ru_text;*/

	if(omaeva.hasOwnProperty(selected_language))
	{
		var la = omaeva[selected_language];
		return la[ru_text];
	}

	return ru_text;
}
 

var en_lang = {



	
	'Прикрепить файл (Alt+O)':'Attach file (Alt+O)',
	'Цитировать (Alt+C)':'Quote (Alt+C)',
	'Жирный (Alt+B)':'Bold (Alt+B)',
	'Наклонный (Alt+I)':'Italic (Alt+I)',	
	'Зачёркнутый (Alt+T)':'Strikethrough (Alt+T)',
	'Спойлер (Alt+P)':'Spoiler (Alt+P)',
	'Спойлер':'Spoiler',
	'Любовь (Alt+L)':'Love spoiler (Alt+L)',
	'Вставить смайл':'Smile Box',

	'опции':'options',
	'имя':'name',
	'тема':'subject',
	'Доступ запрещён':'Access Denied',


	
	'Отмена' : 'Cansel',
	'Отправить':'Send',
	'Ждём':'Wait',

	' (Вы)' : ' (You)',
	'Громкость плеера' : 'Player volume', 

	'Не хватает прав.':'You don\'t have permission to do that.',

	'Удалить файл' : 'Delete file',
	'Удалить файл?' : 'Delete file?',
	'Установить спойлер': 'Set spoiler',
	'Установить спойлер?': 'Set spoiler?',

	'Показать пост':'Show post',
	'Скрыть пост':'Hide',
	'Показывать трип':'Showe tripcode',
	'Скрывать трип':'Hide tripcode',
	'Жалоба':'Report',
	 
	
	'Просмотр удалённых постов':'Watch deleted posts',
	'Редактировать пост' : 'Edit post',
	'Истекло время редактирования' : 'Time for edit is over',
	'Нельзя редактированить сообщение со спец. тегами' : 'You can\'t edit post with special tags',
	'Удалено постов: ' : 'Posts deleted: ',


	'Произошла ошибка' : 'An error has occurred',
	'Неверный трипкод' : 'Tripcode is incorrect',
	'Сервер вернул ошибку: ' : 'Server error: ',
	'Ошибка в параметрах запроса' : 'Error in query parameters',
	'Неправильное имя или пароль' : 'Invalid username or password.',



	'Пользователь успешно забанен' : 'User ban successfully',
	'Пользователь успешно разбанен' : 'User unbanned',


	'Удалить тред' : 'Delete thread',
	'Удалить тред?' : 'Delete thread?',
	'Разрабанить' : 'Unban',
	'Список банов' : 'View bans',
	'Модерировать тред' : 'Enable moderator mode', 
	'Забанить автора' : 'Ban user',
	'Забанить' : 'Ban',
	'Удалить пост' : 'Delete post',
	'Удалить пост?' : 'Delete post?',
	'Удалить все посты (с этого IP)' : 'Delete all posts (by ip)',
	'Удалить все посты (с этого IP)?' : 'Delete all posts (by ip)?', 
	'Разбанить' : 'Unban',
	'Номер бана' : 'Ban ID',
	'Причина':'Reason',
	'минут':'min',
	'час':'hour',
	
	'БАМПЛИМИТ':'BUMPLIMIT',
	'Это пример':"It's a sample",
	'Причина': 'Reason',
	'копия админу':'copy for admin',
	'Репорт отправлен': 'Report sent',

	'Бан':'Ban',
	'Причина':'Reason',
	'Дата окончания': 'Expire',
	'Бессрочно' : 'Permanent',
	'Отправка':'Sending',
	'Произошла ошибка во время отправки поста':'An error occurred while posting a message',
	'Это похоже на флуд, пост отклонён. Извините :(' : 'Flood detected, post discarded. Sorry :(',
	'Капча введена неверно.' : 'You seem to have mistyped the verification',
	
	






	// T O O L T I P S
	'Вход':'Login', 
	'Справка':'F.A.Q.', 
	'Настройки':'Settings', 
	'Зеркало сайта в даркнете':'Darknet Mirror', 
	'Кинотеатр':'Neotube',
	'обновить тред' : 'update thread',


	// TUBE
	'Введите ссылку на ролик Youtube' : 'Enter Youtube Link',
	'Воспроизвести': 'Play',
	'Пауза':'Pause',
	'Следующее видео' : 'Next video',
	'Добавить Youtube видео':'Add Youtube video',
	'Загрузить видео':'Upload video',
	'Полноэкранный режим':'Fullscreen mode',

	// OTHER
	'показать больше' : 'show more',
};



var omaeva = {
	'en' : en_lang,
}
