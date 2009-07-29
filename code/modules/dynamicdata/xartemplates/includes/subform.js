function addItem()
{
    var template = document.getElementById('itemtemplate');
    var itemdiv = document.getElementById('item');
    var numi = document.getElementById('items');
    var num = (document.getElementById('items').value -1)+ 2;
    numi.value = num;
    var divIdName = "item"+num;
    var newdiv = document.createElement('div');
    newdiv.setAttribute("id",divIdName);

    // Replace the placeholder text with the new fieldprefix
    var newhtml = template.innerHTML;
    var regex = new RegExp ('dummyfieldprefix', 'gi') ;
    var prefix = document.getElementById('fieldprefix').value;
    newhtml = newhtml.replace(regex,num+"_"+prefix);

    // Add a remove button and insert the new item
    newdiv.innerHTML =newhtml+"<div class=\"xar-form-input-wrapper-after\"><input type=\"button\" title=\"Remove\"value=\"Remove\" style=\"height: 23px; font-size: small;\" onclick=\"javascript:removeItem(\'"+divIdName+"\')\"></div>";
    itemdiv.appendChild(newdiv);
}

function removeItem(divNum)
{
    var d = document.getElementById('item');
    var olddiv = document.getElementById(divNum);
    d.removeChild(olddiv);
}
