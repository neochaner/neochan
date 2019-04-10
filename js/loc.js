var langData = [
	// lang list
	['en', 'ru', 'de', 'pl', 'jp', 'ko'],

	// titles
	['Attach file (Alt+O)'	, 'Прикрепить файл (Alt+O)'	, 'Datei anhängen (Alt+O)', 'Załącz plik (Alt+O)', 'ファイルを添付する (Alt+O)', '파일을 첨부 (Alt+O)'],
	['Quote (Alt+C)'		, 'Цитировать (Alt+C)'		, 'Zitat (Alt+C)'		  , 'Zacytować (Alt+C)'	 , '見積もり (Alt+C)'		 , '인용문 (Alt+C)'],
	['Bold (Alt+B)'		 	, 'Жирный (Alt+B)'			, 'Fett (Alt+B)'		  , 'śmiały (Alt+B)'	 , '太字 (Alt+B)'			, '굵게 (Alt+B)'],
	['Italic (Alt+I)'	 	, 'Наклонный (Alt+I)'		, 'Kursiv (Alt+I)'		  , 'Pogrubienie (Alt+I)', '斜体 (Alt+I)'			, '기울임꼴 (Alt+I)'],
	['Strikethrough (Alt+T)', 'Зачёркнутый (Alt+T)'		, 'Durchgestrichen (Alt+T)', 'Przekreślenie (Alt+T)', '取り消し線 (Alt+T)'	, '취소선 (Alt+T)'],
	['Spoiler (Alt+P)'		, 'Спойлер (Alt+P)'			, 'Spoiler (Alt+P)'		  , 'Spojler (Alt+P)'	 , 'スポイラー (Alt+P)'		, '망자 (Alt+P)'],
	['Love spoiler (Alt+L)'	, 'Любовь (Alt+L)'			, 'Love spoiler (Alt+L)'  , 'Love spoiler (Alt+L)', 'ラブスポイラー (Alt+L)' , '사랑 스포일러 (Alt+L)'],

	['Server error: '		, 'Сервер вернул ошибку: '	, 'Serverfehler: '		  , 'Błąd serwera: '	  , 'サーバーエラー: '		 , '서버 오류: '],

	// Usermod
	['Show deleted posts', 'Просмотр удалённых постов'		, 'Gelöscht anzeigen', 'Pokaż usunięte', '削除済みを表示', '삭제 된 항목 표시'],

	// other
	['Access Denied'	, 'Доступ запрещён'	, 'Zugriff verweigert', 'Brak dostępu', 'アクセスが拒否されました', '접근 불가'],
	['Spoiler'			, 'Спойлер'			, 'Spoiler'			  , 'Spojler'	  , 'スポイラー'			, '망자'],
	['options'			, 'опции'			, 'optionen'		  , 'opcje'		  , 'オプション'			, '선택권'],
	['name'				, 'имя'				, 'name'			  , 'imię'		  , '名前'				   , 'ko'],
	['subject'			, 'тема'			, 'gegenstand'		  , 'temat'		  , '件名'				   , '제목'],
	['Cancel'			, 'Отмена'			, 'Abbrechen'		  , 'Anuluj'	  , 'キャンセル'			, '취소'],
	['Send'				, 'Отправить'		, 'Senden'			  , 'Wysłać'	  , '書き込む'				, '보내다'],
	['Wait'				, 'Ждём'			, 'Warten'			  , 'Czekać'	  , '待つ'				   , '기다림'],
	['Success'			, 'Готово'			, 'Erfolg'			  , 'Sukces'	  , '成功'				   , '성공'],
	['An error has occurred', 'Произошла ошибка', 'Ein Fehler ist aufgetreten'	  , 'Wystąpił błąd'		   , 'エラーが発生しました'		, '오류가 발생했습니다'],

	 
	[' (You)'			, ' (Вы)'			, ' (Sie)'			  , ' (Ty)'		  , ' (君は)'			   , ' (당신)'],

	['Home'				, 'Главная'			, 'Hauptseite'	, 'Strona główna', 'メインページ', '대문'],
	['Login'			, 'Вход'			, 'Anmelden'	, 'Zaloguj się'	 , 'ログイン'	, '로그인'],
	['Help'				, 'Справка'			, 'Hilfe'		, 'Pomoc'		 , 'ヘルプ'	 	, '도움말'],
	['Settings'			, 'Настройки'		, 'Settings'	, 'Ustawienia'	 , '設定'		, '설정'],
	 
	['OP Moderation'	, 'ОП Модерация'	, 'OP Moderation', 'OP Moderation'	, '節度'			, '절도'],
	['Delete file'		, 'Удалить файл'	, 'Datei löschen', 'Usunąć plik'	, 'ファイルを削除する', '파일 삭제'],
	['Are you sure?'	, 'Вы уверены?'		, 'Bist du sicher?', 'Jesteś pewny?', '本気ですか？'	 , '확실해?'],
	['Edit'				, 'Редактировать'	, 'Bearbeiten'	 , 'Edytować'		, '編集'	 		, '수정'],
	['Reason'			, 'Причина'			, 'Grund'		 , 'Powód'			, '理由'	 		, '이유'], 

	['BUMPLIMIT'		, 'БАМПЛИМИТ'		, 'LIMIT'			, 'LIMIT'		, '限定'	, '한도'],
	['update'			, 'обновить'		, 'aktualisieren'	, 'aktualizacja', '更新'	, '최신 정보'],
	['post count'		, 'количество постов', 'beitragszähler' , 'ilość postów', '投稿数'	, '게시물 수'],
	['Settings'			, 'Настройки'		, 'de', 'pl', 'jp', 'ko'],
	['Settings'			, 'Настройки'		, 'de', 'pl', 'jp', 'ko'],


]

function _T(text)
{

	if(text.length == 0 || text[0] == ':')
		return text;

	var langIndex = langData[0].indexOf(config.language);

	for (let i=1, l=langData.length; i<l; i++) {
		if (langData[i][0] == text) {
			return langData[i][langIndex];
		}
	}

	console.log('unkhown translate : ' + text);
	return text;
}

function addLocaleString(array){

	if (!array.hasOwnProperty('en')) {
		return false;
	}

	let line = [];

	for (let i=0, l=langData[0].length; i<l; i++) {
		let code = langData[0][i];
		line.push(array.hasOwnProperty(code) ? array[code] : array['en']);
	}

	langData.push(line);
}




$(document).ready(function(){

	$("#language").val(config.language);

	jsCssLocalization();
	jsLocalization();

	$('#language').change(function()
	{	 
		localStorage.setItem('language', this.value);
		config.language = this.value;

		document.getElementById("language-css").href = "/stylesheets/lang/" + this.value + ".css";

		jsLocalization();
	
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

 

function jsCssLocalization(){


	var jsLocal = {
		'ru': {'l_latest_threads' : 'Последние треды'},
		'en': {'l_latest_threads' : 'Recent Threads '},
	}

	if(!jsLocal.hasOwnProperty(config.language))
		return;


	var el = document.getElementsByClassName('localize');

	for(let i=0, l=el.length; i<l; i++){
		for(let j=0; j<el[i].classList.length; j++){
			let lclass = el[i].classList[j];
			if(lclass.startsWith('l_')){

				if(el[i].tag = 'option'){
					 jsLocal[config.language].hasOwnProperty(lclass);
					 el[i].text = jsLocal[config.language][lclass];
				}


			}
		}
	}
}
 
function jsLocalization()
{

	$('.reply-attach-control').attr('title', _T('Attach file (Alt+O)'));
	$('.reply-quote-control').attr('title', _T('Quote (Alt+C)'));
	$('.reply-bold-control').attr('title', _T('Bold (Alt+B)'));
	$('.reply-italic-control').attr('title', _T('Italic (Alt+I)'));
	$('.reply-strikethrough-control').attr('title', _T('Strikethrough (Alt+T)'));
	$('.reply-spoiler-control').attr('title', _T('Spoiler (Alt+P)'));
	$('.reply-love-control').attr('title', _T('Love spoiler (Alt+L)'));
	$('.reply-smile-control').attr('title', _T('Smile Box'));


	$('.omit-info').each(function(i, elem){
		
		var posts = $(elem).data('posts');
		var files = $(elem).data('files');

		$(elem).html(omit_text(posts, files));

	});

	var elems = $('.post-link');

	for(var i=0; i<elems.length; i++)
	{
		var link = $(elems[i]);
		link.html(link.html().replace(/ \(.+\)/, _T(' (You)')));
	}

	$('.reply-subject[name=subject]').attr('placeholder', _T('subject')) 
	$('.reply-subject[name=neoname]').attr('placeholder', _T('name'))
	$('.reply-send-button').attr('value', _T('Send'));




}

function omit_text(posts, files)
{
	switch(config.language)
	{
		case 'ru':
			return posts + ' постов и ' + files + ' файлов пропущено';
		default:
			return posts + ' posts and ' + files + ' files missed';
	}
}

 
 
 

var en_lang = {
  
	  

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
