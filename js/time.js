

Api.onLoadPage(timeMain);
Api.onLoadPost(timeProcess);
Api.onNewPost(timeProcess);
Api.onChangePost(timeProcessForced);


var OPT_LOCALTIME = true;
var OPT_TIMESHORT = true;
var OPT_TIMEFORMATDAY = [' ', ' ', ' ', ' ', ' ', ' ', ' '];
var STORE_AGOS={};
var timeDifference;

timeLoad();

function timeMain() {
	OPT_LOCALTIME = Api.addOptCheckbox('localTimeMode', false, 'l_relative', '', timeOptChange);
	setInterval(timeReload, 1000 * 10);	
}

function timeLoad() {
	
	timeDifference = config.language == 'ru' ? timeDifferenceRU : timeDifferenceEU;

	function D(dayNum, sym=2){

		if (config.language == 'ru') {
			if (sym==2) {
				return ([ "Вс", "Пн", "Вт", "Ср", "Чт", "Пт", "Сб", "Вс"][dayNum]);
			} else {
				return ([ "Вск", "Пнд", "Втр", "Срд", "Чтв", "Птн", "Суб", "Вск"][dayNum]);
			}
		} else {
			return ([ "Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"][dayNum]);
		}
	}

	switch (config.theme) {
		case 'native-lolifox':
			OPT_TIMEFORMATDAY = [' ('+D(0)+') ', ' ('+D(1)+') ', ' ('+D(2)+') ', ' ('+D(3)+') ', ' ('+D(4)+') ', ' ('+D(5)+') ', ' ('+D(6)+') ',' ('+D(7)+') '];
			OPT_TIMESHORT = false
			break;
		case 'native-makaba':
			OPT_TIMEFORMATDAY = [' '+D(0, 3)+' ', ' '+D(1, 3)+' ', ' '+D(2, 3)+' ', ' '+D(3, 3)+' ', ' '+D(4, 3)+' ', ' '+D(5, 3)+' ', ' '+D(6, 3)+' ',' '+D(7, 3)+' '];
			OPT_TIMESHORT = false
			break;
		default:
			OPT_TIMESHORT = true;
			OPT_TIMEFORMATDAY =  [' ', ' ', ' ', ' ', ' ', ' ', ' '];
		break;
	}
} 

function timeOptChange(optLocalTime) {

	OPT_LOCALTIME = optLocalTime;
	timeLoad();
	timeReload(true);
}

function timeReload(reload=false) {

	if(reload) {
		STORE_AGOS={};
	}

	for(let i=0, l=Api.postStore.length; i<l; i++) {
		timeProcess(Api.postStore[i]);
	}
}

function timeProcessForced(obj){
	
	if(STORE_AGOS.hasOwnProperty(obj.id)) {
		delete STORE_AGOS[obj.id]
	}
	
	timeProcess(obj);
}



function timeProcess(obj){

	let currentServerTime = getServerTime();
	let timeAgo = timeDifference(currentServerTime*1000, obj.time*1000);

	if(!STORE_AGOS.hasOwnProperty(obj.id))
	{
		var t = obj.elTime.getAttribute('datetime');
		var abs = dateformatUN(iso8601(t));

		// new element
		if(OPT_LOCALTIME) {
			obj.elTime.innerText = timeAgo;
			obj.elTime.title = abs;
			obj.elTime.setAttribute('data-original-title', abs);
		} else {
			obj.elTime.innerText = abs;
			obj.elTime.title = timeAgo;
			obj.elTime.setAttribute('data-original-title', timeAgo);
		}
	} else if (STORE_AGOS[obj.id] != timeAgo) {
		
		// need change ago info
		if(OPT_LOCALTIME) {
			obj.elTime.innerText = timeAgo;
		} else {
			obj.elTime.title = timeAgo;
			obj.elTime.setAttribute('data-original-title', timeAgo);
		}
	} else {
		return false;
	}


	STORE_AGOS[obj.id] = timeAgo;
}









/** global: config, serverTime,  */


var loadPageTick = new Date().getTime();
var optionLocalTimeKey = 'localTimeMode';
var optionLocalTimeValue;
var OPT_TIMESHORT = true;
var OPT_TIMEFORMATDAY = [' ', ' ', ' ', ' ', ' ', ' ', ' '];


var interval_id;

function getServerTime()
{
	var elapsed_sec = (new Date().getTime() - loadPageTick) / 1000;
	return serverTime + elapsed_sec;
}
 
var dn = new Date();
var cur_year = dn.getFullYear();
var cur_month = dn.getMonth();
var cur_day = dn.getUTCDate();

var iso8601 = function(s) {
	s = s.replace(/\.\d\d\d+/,""); // remove milliseconds
	s = s.replace(/-/,"/").replace(/-/,"/");
	s = s.replace(/T/," ").replace(/Z/," UTC");
	s = s.replace(/([\+\-]\d\d)\:?(\d\d)/," $1$2"); // -04:00 -> -0400
	return new Date(s);
};
var zeropad = function(num, count) {
	return [Math.pow(10, count - num.toString().length), num].join('').substr(1);
};

var dateformatEU = (typeof strftime === 'undefined') ? function(t) 
{

	var year = cur_year == t.getFullYear() ? "" : "/" + t.getFullYear().toString().substring(2);
	var no_date = cur_day == t.getUTCDate() && cur_month == t.getMonth(1 && cur_day == t.getUTCDate())

	var f_date = zeropad(t.getMonth() + 1, 2) + "/" + zeropad(t.getDate(), 2)  + year;
	var f_day = OPT_TIMESHOWDAY ? (" (" + [_("Sun"), _("Mon"), _("Tue"), _("Wed"), _("Thu"), _("Fri"), _("Sat"), _("Sun")][t.getDay()]  + ") ") : ' ';
	var f_time = zeropad(t.getHours(), 2) + ":" + zeropad(t.getMinutes(), 2) + ":" + zeropad(t.getSeconds(), 2);

	return (no_date && !OPT_TIMESHOWDAY) ? f_time : f_date +  f_day + f_time;

} : function(t) {
		// post_date is defined in templates/main.js
	return strftime(window.post_date, t, datelocale);
};

var dateformatRU = (typeof strftime === 'undefined') ? function(t)
{

	var year = cur_year == t.getFullYear() ? "" : "/" + t.getFullYear().toString().substring(2);
	var no_date = cur_day == t.getUTCDate() && cur_month == t.getMonth(1 && cur_day == t.getUTCDate())

	var f_date = zeropad(t.getDate(), 2) + "/" + zeropad(t.getMonth()+1, 2)  + year;
	var f_day = OPT_TIMESHOWDAY ?  (" (" + [_("Вс"), _("Пн"), _("Вт"), _("Ср"), _("Чт"), _("Пт"), _("Сб"), _("Вс")][t.getDay()]  + ") ") : ' ';
	var f_time = zeropad(t.getHours(), 2) + ":" + zeropad(t.getMinutes(), 2) + ":" + zeropad(t.getSeconds(), 2);

	return (no_date && !OPT_TIMESHOWDAY) ? f_time : f_date +  f_day + f_time;

} : function(t) {
		// post_date is defined in templates/main.js
	return strftime(window.post_date, t, datelocale);
};

var dateformatUN = (typeof strftime === 'undefined') ? function(t)
{

	var year = (OPT_TIMESHORT && cur_year == t.getFullYear()) ? "" : "/" + t.getFullYear().toString().substring(2);
	var no_date = cur_day == t.getUTCDate() && cur_month == t.getMonth(1 && cur_day == t.getUTCDate())

	var f_date = zeropad(t.getDate(), 2) + "/" + zeropad(t.getMonth()+1, 2)  + year;
	var f_day = OPT_TIMEFORMATDAY[t.getDay()];
	var f_time = zeropad(t.getHours(), 2) + ":" + zeropad(t.getMinutes(), 2) + ":" + zeropad(t.getSeconds(), 2);

	return (no_date && OPT_TIMESHORT) ? f_time : f_date +  f_day + f_time;

} : function(t) {
		// post_date is defined in templates/main.js
	return strftime(window.post_date, t, datelocale);
};

function timeDifferenceEU(current, previous) 
{

	var msPerMinute = 60 * 1000;
	var msPerHour = msPerMinute * 60;
	var msPerDay = msPerHour * 24;
	var msPerMonth = msPerDay * 30;
	var msPerYear = msPerDay * 365;

	var elapsed = current - previous;

	if (elapsed < msPerMinute) {
		return 'Just now';
	} else if (elapsed < msPerHour) {
		return Math.round(elapsed/msPerMinute) + (Math.round(elapsed/msPerMinute)<=1 ? ' minute ago':' minutes ago');
	} else if (elapsed < msPerDay ) {
		return Math.round(elapsed/msPerHour ) + (Math.round(elapsed/msPerHour)<=1 ? ' hour ago':' hours ago');
	} else if (elapsed < msPerMonth) {
		return Math.round(elapsed/msPerDay) + (Math.round(elapsed/msPerDay)<=1 ? ' day ago':' days ago');
	} else if (elapsed < msPerYear) {
		return Math.round(elapsed/msPerMonth) + (Math.round(elapsed/msPerMonth)<=1 ? ' month ago':' months ago');
	} else {
		return Math.round(elapsed/msPerYear ) + (Math.round(elapsed/msPerYear)<=1 ? ' year ago':' years ago');
	}
}
    
function timeDifferenceRU(current, previous)
{
    
    var ticks = current - previous;
    ticks /= 1000;

    if(ticks < 70)
        return "только что";
    
    //if(ticks < 60)
    //	return  Math.round(ticks) + ' ' + ((((dec = ticks%100) >= 11 && dec <= 19) || (dec = ticks%10) >= 5 || dec == 0) ? 'секунд' :  (dec == 1 ? 'секунду' : 'секунды')) + " назад";
    
    var min =  Math.round(ticks / 60);
    
    if(min == 1)
        return "минуту назад";
    if(min < 60)
        return  min + ' ' + ((((dec = min%100) >= 11 && dec <= 19) || (dec = min%10) >= 5 || dec == 0) ? 'минут' :  (dec == 1 ? 'минуту' : 'минуты')) + " назад";
    
    var hour = Math.round(min / 60);
    
    if(hour == 1)
        return "час назад";
    if(hour < 24)
        return  hour + ' ' + ((((dec = hour%100) >= 11 && dec <= 19) || (dec = hour%10) >= 5 || dec == 0) ? 'часов' :  (dec == 1 ? 'час' : 'часа')) + " назад";
    
        
    var day = Math.round(hour / 24);
    
    if(day == 1)
        return "вчера";
    if(day < 30)
        return  day + ' ' + ((((dec = day%100) >= 11 && dec <= 19) || (dec = day%10) >= 5 || dec == 0) ? 'дней' :  (dec == 1 ? 'день' : 'дня')) + " назад";
    
    var month = Math.round(day/30);
    
    if(month < 12)
        return month + ' ' + ((((dec = month%100) >= 11 && dec <= 19) || (dec = month%10) >= 5 || dec == 0) ? 'месяцев' :  (dec == 1 ? 'месяц' : 'месяца')) + " назад";
	
	var year = Math.round(day/365);
	return  year + ' ' + ((((year = year%100) >= 11 && year <= 19) || (year = year%10) >= 5 || year == 0) ? 'лет' :  (dec == 1 ? 'год' : 'года')) + " назад";
}