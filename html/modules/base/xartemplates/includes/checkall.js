/**
 * check or uncheck all checkboxes in a form
 * Example :
 * <a href="javascript:xar_base_checkall(document.forms['thisform'],true)">Check All</a>
 * <a href="javascript:xar_base_checkall(document.forms['thisform'],false)">Uncheck All</a>
 */
function xar_base_checkall(formobject, value) {
    for (i = 0; i < formobject.length; i++) {
        if (formobject.elements[i].type == 'checkbox') {
            formobject.elements[i].checked = value;
        }
    }
}

