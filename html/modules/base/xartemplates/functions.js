/*
 * *** Deprecated ***
 * Please instead use xar_base_confirmlink() and xar_base_formcheck()
 * available to a template through the following theme tags:
 * <xar:base-include-javascript module="base" filename="comfirmlink.js" position="head" /> and
 * <xar:base-include-javascript module="base" filename="formcheck.js" position="head" />
 */

function confirmLink(theLink, xarConfirmMsg)
{
    var confirmMsg  = 'Do you really want to';
    // Confirmation is not required in the configuration file
    // or browser is Opera (crappy js implementation)
    if (confirmMsg == '' || typeof(window.opera) != 'undefined') {
        return true;
    }

    var is_confirmed = confirm(confirmMsg + ' :\n' + xarConfirmMsg);
    if (is_confirmed) {
        theLink.href += '&confirm=1';
    }

    return is_confirmed;
}

/***********************************************
* Required field(s) validation v1.10- By NavSurf
* Visit Nav Surf at http://navsurf.com
* Visit http://www.dynamicdrive.com/ for full source code
***********************************************/

function formCheck(formobj, fieldRequired, fieldDescription){
    // dialog message
    var alertMsg = "#xarML('Please complete the following fields:')#\n";
    
    var l_Msg = alertMsg.length;
    
    for (var i = 0; i < fieldRequired.length; i++){
        var obj = formobj.elements[fieldRequired[i]];
        if (obj){
            switch(obj.type){
            case "select-one":
                if (obj.selectedIndex == -1 || obj.options[obj.selectedIndex].text == ""){
                    alertMsg += " - " + fieldDescription[i] + "\n";
                }
                break;
            case "select-multiple":
                if (obj.selectedIndex == -1){
                    alertMsg += " - " + fieldDescription[i] + "\n";
                }
                break;
            case "text":
            case "textarea":
                if (obj.value == "" || obj.value == null){
                    alertMsg += " - " + fieldDescription[i] + "\n";
                }
                break;
            default:
            }
            if (obj.type == undefined){
                var blnchecked = false;
                for (var j = 0; j < obj.length; j++){
                    if (obj[j].checked){
                        blnchecked = true;
                    }
                }
                if (!blnchecked){
                    alertMsg += " - " + fieldDescription[i] + "\n";
                }
            }
        }
    }

    if (alertMsg.length == l_Msg){
        return true;
    }else{
        alert(alertMsg);
        return false;
    }
}