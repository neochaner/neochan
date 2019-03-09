var tripcodeMaskConfig = {
    key: 'optionTripcodeMask',
    value: null
};

$(document).ready(function() {
    tripcodeMaskConfig.value = menu_add_checkbox(tripcodeMaskConfig.key, getKey(tripcodeMaskConfig.key, false) , 'l_tripmask');
    tripcodeMaskConfig.value ? enableTripcodeMask() : disableTripcodeMask();

});

$(document).on(tripcodeMaskConfig.key, function(e, value) {
    tripcodeMaskConfig.value = value;
    tripcodeMaskConfig.value ? enableTripcodeMask() : disableTripcodeMask();
});

$(document).ready(function () {
    $('body').on('mouseenter', '.reply-subject', function() {

        if(tripcodeMaskConfig.value) {
            $(this).css({'filter': 'blur(0px)'});
        }

    }).on('mouseleave', '.reply-subject', function() {

        if(tripcodeMaskConfig.value) {
            if($('.reply-subject').is(':focus') === false) {
                $(this).css({'filter': 'blur(3px)'});
            }
        }
    });
});

$(document).on('click', function(e) {
    if(e.target !== document.querySelector('.reply-subject')) {
        if(tripcodeMaskConfig.value) {
                $('.reply-subject').css({'filter': 'blur(3px)'});
        }
    }
});

function enableTripcodeMask() {
    $(document).find('.reply-subject').css({
        'filter': 'blur(3px)'
    });
}

function disableTripcodeMask() {
    $(document).find('.reply-subject').css({
        'filter': 'blur(0px)'
    });
}