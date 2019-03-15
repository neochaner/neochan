var optionNSFMKey = 'optionNSFM';
var optionNSFMValue;
var optionNSFMOpacity = 0.01;
var optionNSFMSmileOpacity = 0.1;

	
$(document).ready(function(){

	optionNSFMValue = Menu.addCheckBox(optionNSFMKey, false, 'l_nsfm', '', toggleNSFM);

	if(optionNSFMValue)
        enableNSFM();
});


function toggleNSFM(value)
{  
    optionNSFMValue = value;

	if(value)
		enableNSFM();
	else
		disableNSFM();
}

function enableNSFM()
{

    $("img").css("opacity", optionNSFMOpacity)
    $(".s42").css("opacity", optionNSFMSmileOpacity)

    $("img").hover(function(){
        $(this).css("opacity", 1);
        }, function(){
        $(this).css("opacity", optionNSFMOpacity);
    });

    $(".s42").hover(function(){
        $(this).css("opacity", 1);
        }, function(){
        $(this).css("opacity", optionNSFMSmileOpacity);
    });
}

function disableNSFM()
{
    $("img").css("opacity", 1)
    $(".s42").css("opacity", 1)

    $("img").unbind('mouseenter mouseleave');
    $(".s42").unbind('mouseenter mouseleave');
}


$(document).on('new_post', function(e, post) {

    if(optionNSFMValue)	
    {
        $(post).find("img").css("opacity", optionNSFMOpacity)
        $(post).find(".s42").css("opacity", optionNSFMSmileOpacity)

        $(post).find("img").hover(function(){
            $(this).css("opacity", 1);
            }, function(){
            $(this).css("opacity", optionNSFMOpacity);
        });

        $(post).find(".s42").hover(function(){
            $(this).css("opacity", 1);
            }, function(){
            $(this).css("opacity", optionNSFMSmileOpacity);
        });


    }
});
