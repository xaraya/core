function xar_base_confirmLink(theLink, xarConfirmMsg)
{
    // Confirmation is not required in the configuration file
    // or browser is Opera (crappy js implementation)
// CHECKME: is this still valid for Opera ?
    if (typeof(window.opera) != 'undefined') {
        return true;
    }

    var is_confirmed = confirm(xarConfirmMsg);
    if (is_confirmed) {
        theLink.href += '&confirm=1';
    }

    return is_confirmed;
}
