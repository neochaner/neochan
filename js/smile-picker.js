/*
	Добавляет кнопку выбора смайлов в форму ответа
*/

 
addLocaleString({'en':'Smile Box', 'ru':'Вставить смайл', 'de':'Smile Box', 'pl':'Smile Box', 'jp':'スマイルボックス', 'ko': '스마일 박스'});
addLocaleString({'en':'show more', 'ru':'показать больше', 'de':'zeig mehr', 'pl':'pokaż więcej', 'jp':'もっと見せる', 'ko': '자세히보기'});



var MAX_FAV_SMILES = 48;
var smilePickerState = -1;
var csmiles = {
	
	'peka_lol' :  {'pack' : [ 
		'peka',  'peka_lol', 'pepe_sad', 'pepe_cool', 'v', 'smokeman', 'oldfag', 'oldfag_lol',
		'troll', 'crying', 'hide_cry' , 'cry2', 'foreveralone', 'nobrain', 'di',  'cage_lol', 'shakal', 
		'harold', 'tat' , 'tellme', 'cage_waw', 'gop', 'nap' ,'cruz_lol','facepalm', 'hackerman' , 
		'bstory', 'shit', 'old_man', 'trick', 'cat_tired', 'luffy_sugoi', 'ohmygod', 'klass'
	]},
	
	
	'kanna':  {'pack': [
		'madoka_dumb', 'mayoi', 'sleep', 'aihara', 'ibiza', 'kanna', 'ruiko', 'tamako', 'araragi', 
		'souseiseki', 'suiseiseki', 'DESU', 'hitagi', 'karen', 'kobayashi', 'kyoko', 'kotori', 'nonon', 
		'oshino', 'reiji', 'rena', 'riko_blood', 'ruyko', 'sakura_cc', 'smugface', 'suruga', 'tsumugi', 
		'umaru', 'umaru_smug', 'yui', 'asuka', 'chito', 'kakyoin', 'mari', 'nanachi', 'rei', 'shinji', 'shion',
		'nozomi_yes'
	]}, 


	'jp_half_anon' : {'pack': [
		'mouse_yessir', 'zontjp', 'eye', 'ems_pamu', 'jp_anon', 'jp_half_anon', 'egg_ufo', 'ubu_tea', 
		'thinking', 'tagaren_fist', 'smug', 'panluna', 'panluna2', 'rly', 'mug', 'interesting', 'gyaru', 
		'jp_hug', 'emp_bed', 'suss', 'jp_dude','jp_dude2', 'atsuko_scorn', 'chicchi_smug', 'bis_pand', 
		'jp_coolstory', 'best_work', 'aina_drink', 'aina_smile', 'aina_smile2', 'hospade_kakya_nyasha', 
		'run', 'komi_blood', 'bloodyeye', 'juon', 'mitoll', 'drochesh', 'hage_tyan','daoko_gun', 'bite', 
		'pikarin_devil', 'tongue', 'jpgun'
	]},



	'suy_happy' : {'pack': [

		'heart2','heart3','heart1','katana','soju', 'cocacola', 'box_shy', 'shy','han','mina_fail','suy_happy','autism','sosmile','yuji_np','dahyun_smile','bin_pl','kewow','eunha_fat',
		'hana_smile','hana_sleep','euth','eunha_like','ye_lol','yuji_hope','yuji_bad','hana_lol',
		'hyeri_pls','bup','cgluda','hyperlol','hana_pretty','cs_why','hyejeong_blame','bin_wow','hana_ohyou','jim_smile1',
		'hana_hard','umji_glum','hana_you','nay_gl','hyejeong_skirt','sana_sorrow','shui','chae_smile','yuji_trouble','day_wow',
		'kgirl2','nunu_smart','elk_lol','hana_lips','yuji_1','hyejeong_sad','somi_angry','yuji_wow','h_s_hug','mina_cry',
		'chae_laught','rose_np','sumni_sulk','day_you','somi_rage','chae_sulk','yuji_2','yoojin_sulk','arin_dream','yuji_sml',
		'seol_smart','chae_tiger','jim_depression','kpalm','yuji_like','hyejeong_cute','chae_lol','cha_wine','yuji_science',
		'eunha_beast','rose_fun','jyp2','hana_wtf','rose_angry','spoon','somi_sight','somi_asight','dayo_what','chae_like',
		'hyejeong_laugh','kgirl1','sana_smile','chae_wut','hyejong_stare','jyp1','yuji_listen','yeeun_sad','mina_sorrow',
		'hyejeong_amazed','hyejeong_cream','somi_angry2','hyejeong_wink','gun','hyejeong_feed','eunji_hi','seulgi_hubris','nana_smile',
		'nana_wow','mina_boring','rose_heart','yuji_sunshine','hyejeong_funny','sana_sorry','joy_side','hyejeong_rejection','sally_wow'
 

	], 'other' : [
		 
		'chae_eat','chae_bombom','hyejeong_heart','unn_notbad','chae_notbad','irene_see','somi_flashback',
		'rose_shy','yuji_prt','yves_shot','yuji_wonder','hyunjoo_sexy','hyejeong_trick','hyejeong_finderkiss','hyejeong_omg',
		'hyejeong_bestgirl','hyejeong_ohyou','hyejeong_ownage','hyejeong_frowns','hyejeong_tired','hyejeong_wow','hyejeong_kiss','hyejeong_fun',
		'hyejeong_contain','hyejeong_cheeks','hyejeong_hz','hyejeong_sulk','hyejeong_truestory','hyejeong_resist','hyejeong_pokerface',
		'hyejeong_really','hyejeong_giggle','hyejeong_ohmy','hyejeong_bed','hyejeong_tears','hyejeong_weed','hugs','chae_ser','rose_laugh',
		'rose_maniac','rose_aw','rose_pleased','rose_rly','rose_boring','rose_ooo','rose_giggle','rose_chortle','rose_wa','rose_ha',
		'nayong_lol','yeri_ok','tzuyu_revnost','soyee_ok','rose_see','rose_ok','rose_zero','blackpink_kiss','jenni_sing','reina_see',
		'nayeon_disgust','joy_say','joy_contempt','jim_wut','jim_smile2','jim_smile3','bomi_hey','sana_cheese','sana_wow','jooe_herpderp',
		'yves_aim','jennie_vaseline','momo_song','seol_coffee','yuna_listen','luda_sexy','naeun_tease','naeun_wrinkle','eunji_smile',
		'eunji_secret','im_crazy','sexy_dance','salt','cheeck','nayoung_smile','rose_chee','rose_chuu','wink1','vulgar_wink',
		'soyeon_lol','cb_yuju_susp', 'shy2', 'cb_yuju_hide', 'sejeong_ew', 'mimi_smile', 'sumin_cute', 'sumin_finger',
		'jeong_funny', 'jeong_lol', 'jeong_peek', 'jeong_smile', 'jeong_teased', 'jeong_wink','jeong_wut','mina_shrug','dahyun_lol',

		'yaschitayu', 'bora_sulk', 'eunseo_chuu', 'mirae_smile', 'cb_yuju_smile', 'cb_yuju_ohyou','yujeong_smile','eunseo_ohyou','bora_smile', 'bora_shy',
		'kyulkyung_smile', 'kyulkyung_smile2', 'kyulkyung_sulk',
		'jeong_boring', 'kokoro_sulk','mirae_sad',  'jeong_morning', 'jeong_heart', 'jeong_ok', 'jihyo_listening', 'tzuyu_angry',
		'chae_tt', 'chae_fighting', 'jeong_tt', 'chae_you', 'sana_come_here', 'mina_hi',
		'remi_hope', 'jeong_crying', 'sana_thinking',  

		'dahyun_calling', 'sana_neomuhae', 'haeyoon_lips', 'jiwon_hi', 'may_lol', 'chaerin_hwaiting', 'linlin_glad', 
		'sejeong_cool', 'joy_blame', 'jiwon_strast'
		
	]},

	// favorite smiles
	'star_gold' : {'favorite':true, 'pack': [ ]}
	};


 
$(document).ready(function(){
	$('<div class="control reply-footer-control reply-smile-picker" data-tippy-content="'+_T('Smile Box')+'" onclick="toggleSmilePicker()"><i class="fa fa-smile-o" style="font-size:19px"></i></div>').insertBefore('.reply-footer-controls .reply-dragger');
	tippy('.reply-smile-picker');
});

$(document).on('new_post', function(e, post) {

	if(smilePickerState == 1)
    	repositionSmilePicker();
});



function AddSmile(smile)
{
	AddTag(':'+smile+':', '', 'replybox_text');
	hideSmilePicker();
	setFavSmile(smile);

}


function toggleSmilePicker(){
	
	switch(smilePickerState){
		
		case -1:
			loadSmilePicker();
			showSmilePicker();
			break;
		
		case 0:
			showSmilePicker();
			break;
		
		case 1:
			hideSmilePicker();
			break;
	}
	
}

function loadSmilePicker(){

	smilePickerState = 0;

	// get fovorite smiles
	let favs = getKey("fav_smiles", []); 

	for (var i = 0; i < favs.length; i = i+2) {
    csmiles['star_gold'].pack.push(favs[i]);
	};



	
	var box = document.createElement('div');
	box.className = "smile-picker";
	box.id = 'smile-box';
	box.style.position = 'absolute';
	
	
	
	
	// window
	var tabs = '<div class="smile-picker-box" tabindex="0">';

	// packs
	let index = 0;
	let selected_index = getKey('ssm', 0);

	for(var key in csmiles)
	{
		var pack = csmiles[key].pack;

		if(index++ == selected_index){
			tabs += '<ul class="smile-tab smile-tab-'+key+' smile-tab-active" style="list-style: none; text-align: left;">';
			set_active = false;
		} else {
			tabs += '<ul class="smile-tab smile-tab-'+key+'" style="list-style: none; text-align: left; display: none;">';
		}
		
		for (let i=0; i<pack.length; i++) {
			tabs += '<div class="smile" style="display: inline"><i class="s42 s42-'+pack[i]+' smiles-icon" onclick="AddSmile(\''+pack[i]+'\', event)"></i></div>';
		}

		
		if(csmiles[key].hasOwnProperty('other')){
			tabs += '<div id="btn-more-'+(index-1)+'" class="button" style="display: block!important;margin:2px 8px" onclick="showMoreSmiles(\''+key+'\', '+(index-1)+')">'+_T('show more')+'</div>';
		}
		
		tabs += "</ul>";
		 
	}
 
		

	// header category
	tabs += '<ul class="smile-category">';
		
	index = 0;
	for(var key in csmiles)
	{
		let claass = (index++ == selected_index) ? 'sc-item sc-item-active':'sc-item';
		tabs += "<div class='"+claass +"' onclick='changeSmileTab(\""+key+"\")'>";
			tabs += '<i class="smile-category-image s42-'+key+'"></i>';
		tabs += '</div>';
	}
		
	tabs += "</ul>";
		
	
			
		
	tabs += "</div>";

	tabs+='<div class="smile-picker-mark"><svg style="position: absolute;left: 0;-webkit-transform: rotate(180deg);transform: rotate(180deg);" viewBox="0 0 24 8" xmlns="http://www.w3.org/2000/svg"><path d="M3 8s2.021-.015 5.253-4.218C9.584 2.051 10.797 1.007 12 1c1.203-.007 2.416 1.035 3.761 2.782C19.012 8.005 21 8 21 8H3z"></path></svg></div>';
	
	
	box.innerHTML = tabs;
	
	document.body.appendChild(box);
	

 


	
}

function showSmilePicker(){


	let box = document.getElementById('smile-box'); 

	if(config.active_page == 'thread'){

		let replybox = document.getElementById('replybox');

		if(replybox.style.position == 'fixed'){
			box.style.position = 'fixed';
			box.style.zIndex= parseInt(replybox.style.zIndex) + 1;
		} else {
			box.style.position = 'absolute';
		}


	}


	smilePickerState = 1;
	
	var button = $('.reply-smile-control');
	var offset = button.offset();
	var min = 10;
	
	
	$('.smile-picker').fadeIn(200)
		
	repositionSmilePicker();
	watcherSmilePicker();
	
}

function showMoreSmiles(packName, tabIndex){

	let div = '';
	let tab = document.getElementsByClassName('smile-tab')[tabIndex];
	
	csmiles[packName].other.forEach(element => {
		div += '<div class="smile" style="display: inline"><i class="s42 s42-'+element+' smiles-icon" onclick="AddSmile(\''+element+'\', event)"></i></div>';
	});
 
	tab.innerHTML += div;

	document.getElementById('btn-more-' + tabIndex).remove();

}

function hideSmilePicker(){
	smilePickerState = 0;
	
	$('.smile-picker').hide();
	
}

function changeSmileTab(name){


	var els = document.getElementsByClassName("smile-tab");

	for(let i=0; i<els.length; i++){

		if(els[i].classList.contains('smile-tab-active')){
				els[i].classList.remove('smile-tab-active')
				els[i].style.display = 'none';

				
			document.querySelectorAll(".smile-category>div")[i].classList.remove('sc-item-active');
		}

		if(els[i].classList.contains('smile-tab-' + name)){

			els[i].classList.add('smile-tab-active');
			els[i].style.display = 'block';

			document.querySelectorAll(".smile-category>div")[i].classList.add('sc-item-active');
			
			setKey('ssm', i);
		}
	}
 

}

function repositionSmilePicker(){


	var picker_btn = document.getElementsByClassName('reply-smile-picker')
	var picker_box = document.getElementsByClassName('smile-picker')

	if(picker_btn.length == 0 || picker_box.length == 0)
		return;

	var rect  = getAbsolute(picker_btn[0]);
 
	picker_box[0].style.left = (rect.left - 6) + 'px';
	picker_box[0].style.top = (rect.top - picker_box[0].clientHeight - 8) + 'px';
	
	let all_width = picker_box[0].clientWidth + rect.left - 6;
	let need_offset = all_width - $(window).width();
 

	if(need_offset > 0){
		picker_box[0].style.left = ( rect.left - 6 - need_offset - 8)+ 'px';
	}




}

function getAbsolute( el ) {
	var _x = 0;
	var _y = 0;
	while( el && !isNaN( el.offsetLeft ) && !isNaN( el.offsetTop ) ) {
			_x += el.offsetLeft - el.scrollLeft;
			_y += el.offsetTop - el.scrollTop;
			el = el.offsetParent;
	}
	return { top: _y, left: _x };
}


function setFavSmile(name){

	let new_item = true;
	let fav = getKey("fav_smiles", []); 

	for(let i=0; i<fav.length; i+=2){

		if(fav[i] == name){
			fav[i+1]++;
			new_item = false;
		}

		if(i!=0&& fav[i+1] > fav[i-1]){
			let c_name = fav[i];
			let c_value = fav[i+1];

			fav[i] = fav[i-2];
			fav[i+1] = fav[i-1];
			
			fav[i-2] = c_name;
			fav[i-1] = c_value;

		}
	}

	if(fav.length > MAX_FAV_SMILES * 2)
		fav.splice(fav.length-2, 2);

	if(new_item)
		fav.push(name, 1);


	setKey("fav_smiles", 	fav);

}

function watcherSmilePicker(){

	document.addEventListener('click', function(event){

			if(event.target.closest(".reply-smile-picker")){

			}
			else if(event.target.closest(".smile-picker"))
				watcherSmilePicker();
			else 
				hideSmilePicker();
		
	}, {
		once: true,
		passive: true,
		capture: true
	});


}















