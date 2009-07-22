function toggleDisplay(id)
{
    elem = document.getElementById(id);
    if(elem.style.display == 'none') {
        elem.style.display = 'block';
    } else {
        elem.style.display = 'none';
    }
return false;
}

function setDisplayOn(id)
{
    if(document.getElementById(id) != undefined)
    {
        document.getElementById(id).style.display = 'block';
    }
}

function setDisplayOff(id)
{
    if(document.getElementById(id) != undefined)
    {
        document.getElementById(id).style.display = 'none';
    }
}

