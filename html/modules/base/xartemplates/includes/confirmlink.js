function xar_base_confirmLink(theLink, xarConfirmMsg)
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
