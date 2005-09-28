/*
 * $Id$
 */

/*
 * xar_base_getElement() will return the object identified by the given id
 * This should work on most browsers.
 * TODO: make this available as a standalone function available to
 * other modules.
 * TODO: introduce JS dependancies so this function is pulled in to
 * the page automatically when it is needed. Probably a more formal
 * JS libary will be needed to do that.
 */

function xar_base_getElement(id, d) {
    if (!d) {d=document;}
    if (d.all) {
        return d.all[id];
    } else if (d.getElementById) {
        return d.getElementById(id);
    } else {
        for (iLayer = 1; iLayer < d.layers.length; iLayer++) {
            if (d.layers[iLayer].id == id) {
                return d.layers[iLayer];
            }
        }
    }
    return Null;
} 

/*
 * Take the values from a select list id 'src', and store them
 * in form item id 'dest' as a colon-separated list.
 */

function xar_base_collapse_select(src, dest) {
    var ord = ""; 
    var srcobj = xar_base_getElement(src);
    if (!srcobj) {
        alert ("Missing form select item '" + src + "'");
        return false;
    }
    if (!dest) {dest = src + "result";}
    var destobj = xar_base_getElement(dest);
    if (!destobj) {
        alert ("Missing form text item '" + dest + "'");
        return false;
    }
    for (var i = 0; i < srcobj.options.length; i++) {
        ord += ";" + srcobj.options[i].value;
    }
    destobj.value = ord.substring(1, ord.length);
}

/*
 * Allow items in a select-list to be reordered. This function allows a user to
 * select an item in the list, then shift it up or down. The re-ordered values
 * from the list are then placed into a text element as a list of values.
 *
 * id: the select list element id
 * dir: direction; true=up, false=down
 * dest: id of the destination form text item element (optional)
 * Note: dest defaults to <id>result, as per xar_base_collapse_select()
 *
 * Example:
 * 1. Include this JS file in the page header, usually like this:
 *    <xar:base-include-javascript module="base" filename="orderitem.js" position="head" />
 * 2. Include inline JS in the header if you want to over-ride the default error message,
 *    e.g. xar_base_reorder_warn = 'You must select the widget to move.';
 * 3. Create a select element for the items you want to allow the user to order, with item ids as
 *    the values. Give the element an id, for example 'items'.
 * 4. Create an 'up' anchor with a trigger onclick="return xar_base_reorder('items', true);"
 * 5. Create an 'down' anchor with a trigger onclick="return xar_base_reorder('items', false);"
 * 6. Create a hidden blank text item called 'itemsresult' (make this displayed to see how it works)
 * 7. Pick up the string item1;item2;item3 etc. from the 'itemsresult' value submitted by the form.
 * You can over-ride the result item id with a third parameter to xar_base_reorder().
 */

function xar_base_reorder(id, dir, dest) {
    var el = xar_base_getElement(id);
    if (!el) {
        alert ("Missing form select item '" + id + "'");
        return false;
    }
    var idx = el.selectedIndex;
    if (idx==-1) {
        if (!xar_base_reorder_warn) {
            xar_base_reorder_warn = "You must select the item to move.";
        }
        alert(xar_base_reorder_warn);
    } else {
        var nxidx = idx+( dir? -1 : 1)
        if (nxidx<0) {nxidx=el.length-1;}
        else if (nxidx>=el.length) {nxidx=0;}
        var oldVal = el[idx].value;
        var oldText = el[idx].text;
        el[idx].value = el[nxidx].value;
        el[idx].text = el[nxidx].text;
        el[nxidx].value = oldVal;
        el[nxidx].text = oldText;
        el.selectedIndex = nxidx;
        xar_base_collapse_select(id, dest);
    }
    return false;
}
