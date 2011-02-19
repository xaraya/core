function toggleDisplay(id)
{
    elem = document.getElementById(id);
    if(elem.style.display == 'none') {
        elem.style.display = '';
    } else {
        elem.style.display = 'none';
    }
return false;
}

function setDisplayOn(id)
{
    if(document.getElementById(id) != undefined)
    {
        document.getElementById(id).style.display = '';
    }
}

function setDisplayOff(id)
{
    if(document.getElementById(id) != undefined)
    {
        document.getElementById(id).style.display = 'none';
    }
}

