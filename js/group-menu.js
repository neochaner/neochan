var menu_groups = [ ];


function gm_show(key, id)
{
  
    var menu = menu_groups[key];

    if(typeof menu == 'undefined' || menu == '')
    {
        menu_groups[key] = id;
        return true;
    }
    else if(menu != id)
    {
        $('#'+menu).remove();
        menu_groups[key] = id;
        return true;
    }
    else 
    {    
        menu_groups[key] = '';
        $('#'+menu).remove();
        return false;
    }

}

function gm_remove(key)
{

    var menu = menu_groups[key];

    if(typeof menu != 'undefined' && menu != '')
    {
        $('#'+menu).fadeOut(300, function() { $(this).remove(); });
        menu_groups[key] = '';
    }

}