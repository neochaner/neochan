var loadPageTick = new Date().getTime();
var optionLocalTimeKey = 'localTimeMode';
var optionLocalTimeValue;
var interval_id;

function getServerTime()
{
	var elapsed_sec = (new Date().getTime() - loadPageTick) / 1000;
	return serverTime + elapsed_sec;
}

$(document).ready(function(){

	optionLocalTimeValue = menu_add_checkbox(optionLocalTimeKey, false, 'l_relative');

	do_time(document);
	interval_id = setInterval(do_time, 1000 * 60, document);	
	 
	$(document).on(optionLocalTimeKey, function(e, value) {	
		
		optionLocalTimeValue = value;
		do_time(document);
	});


});


 
 

$(document).on('new_post', function(e, post) {
	do_time(post);
});

$(document).on('change_post', function(e, post) {
	do_time(post);
});


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
		var f_day = " ";// " (" + [_("Sun"), _("Mon"), _("Tue"), _("Wed"), _("Thu"), _("Fri"), _("Sat"), _("Sun")][t.getDay()]  + ") ";
		var f_time = zeropad(t.getHours(), 2) + ":" + zeropad(t.getMinutes(), 2) + ":" + zeropad(t.getSeconds(), 2);

		return no_date ? f_time : f_date +  f_day + f_time;

	} : function(t) {
		// post_date is defined in templates/main.js
		return strftime(window.post_date, t, datelocale);
	};

	var dateformatRU = (typeof strftime === 'undefined') ? function(t)
	{

		var year = cur_year == t.getFullYear() ? "" : "/" + t.getFullYear().toString().substring(2);
		var no_date = cur_day == t.getUTCDate() && cur_month == t.getMonth(1 && cur_day == t.getUTCDate())

		var f_date = zeropad(t.getDate(), 2) + "/" + zeropad(t.getMonth()+1, 2)  + year;
		var f_day = " ";// (" + [_("Вс"), _("Пн"), _("Вт"), _("Ср"), _("Чт"), _("Пт"), _("Сб"), _("Вс")][t.getDay()]  + ") ";
		var f_time = zeropad(t.getHours(), 2) + ":" + zeropad(t.getMinutes(), 2) + ":" + zeropad(t.getSeconds(), 2);

		return no_date ? f_time : f_date +  f_day + f_time;

 

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
    
    return null;
}


function do_time(elem)
{
	if(optionLocalTimeValue)	
		do_localtime(elem);
	else
		do_absolutetime(elem);
}

function do_localtime(elem)
{
	if(selected_language == 'ru')
		do_localtime_ru(elem);
	else
		do_localtime_eu(elem);
}

function do_absolutetime(elem)
{
	if(selected_language == 'ru')
		do_absolutetime_ru(elem);
	else
		do_absolutetime_eu(elem);
}


var do_localtime_ru = function(elem) 
{	

	var times = elem.getElementsByTagName('time');
	var currentServerTime = getServerTime();

    for(var i = 0; i < times.length; i++) 
    { 
		var unixtime = times[i].getAttribute('unixtime');
		var timeAgo = timeDifferenceRU(currentServerTime*1000, unixtime*1000);

		if(times[i].innerHTML != timeAgo)
		{

			var t = times[i].getAttribute('datetime');
			var isoDateTime =  dateformatRU(iso8601(t));

			times[i].innerText = timeAgo;
			times[i].title = isoDateTime;
			times[i].setAttribute('data-original-title', isoDateTime);
		}
		
	}
};

var do_localtime_eu = function(elem) 
{	

	var times = elem.getElementsByTagName('time');
	var currentServerTime = getServerTime();

    for(var i = 0; i < times.length; i++) 
    { 
		var unixtime = times[i].getAttribute('unixtime');
		var timeAgo = timeDifferenceEU(currentServerTime*1000, unixtime*1000);

		if(times[i].innerHTML != timeAgo)
		{

			var t = times[i].getAttribute('datetime');
			var isoDateTime =  dateformatEU(iso8601(t));

			times[i].innerText = timeAgo;
			times[i].title = isoDateTime;
			times[i].setAttribute('data-original-title', isoDateTime);
		}
		
	}
};
	


 
var do_absolutetime_ru = function(elem) 
{	

	var times = elem.getElementsByTagName('time');
	var currentTime = Date.now();

    for(var i = 0; i < times.length; i++) 
    {
		var t = times[i].getAttribute('datetime');
		var abs = dateformatRU(iso8601(t));

		if(times[i].innerHTML != abs)
		{
			times[i].innerText = abs;
		}

		var postTime = new Date(t);
		var timeDiff = timeDifferenceRU(currentTime, postTime.getTime());
		
		if(times[i].getAttribute('data-original-title') != timeDiff)
		{
			times[i].title = timeDiff;
			times[i].setAttribute('data-original-title', timeDiff);
		}


	}
};
	
var do_absolutetime_eu = function(elem) 
{	

	var times = elem.getElementsByTagName('time');
	var currentTime = Date.now();

    for(var i = 0; i < times.length; i++) 
    {

		var t = times[i].getAttribute('datetime');
		var abs =  dateformatEU(iso8601(t));

		if(times[i].innerHTML != abs)
		{
			times[i].innerText = abs;
		}

		var postTime = new Date(t);
		var timeDiff = timeDifferenceEU(currentTime, postTime.getTime());
		
		if(times[i].getAttribute('data-original-title') != timeDiff)
		{
			times[i].title = timeDiff;
			times[i].setAttribute('data-original-title', timeDiff);
		}
	}
};
	