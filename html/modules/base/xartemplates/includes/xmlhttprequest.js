/**
 * Xaraya XML HTTP Requests
 *
 * @package modules
 * @copyright (C) 2004-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 * @link http://xaraya.com/index.php/release/68.html
 * @author Marcel van der Boom <marcel@hsdev.com>
 */

// TODO: can this global be eliminated?
var req;
var tagToGo; // Apparently at least safari needs this
var debug = 0;


/**
* Load Content
*
* Perform XML HTTP request
*
* @author  Marcel van der Boom <marcel@hsdev.com>
* @access  public
* @param   string url The URL to request
* @param   string tagid The ID of the form element to put the .innerHTML into
* @param   string method (Optional) 'GET' or 'POST' (default=GET)
* @param   object formobj (Optional) Form object reference (Only needed if method=POST)
* @return  boolean local_debug (Optional) Whether to include debug output (default=false)
* @throws  no exceptions
*/
function loadContent(url, tagid, method, form_obj, local_debug)
{
    // validate inputs and set defaults
    if (local_debug != null) var debug = local_debug
    if (method == null || method == '') method = 'GET'
    method = method.toUpperCase();
    var argstr = null;
    var join = '';
    
    url = url.replace(/&amp;/g,'&');

    // prepare strings according to method
    if (method == 'POST') {
        if (form_obj != null) formobj = document.getElementById(form_obj);
        try {
            argstr = '';
            for ( i = 0; i < formobj.elements.length; i++ ) {
                if (formobj.elements[i].name.length > 0) {
                    argstr = argstr + join + formobj.elements[i].name + "="+formobj.elements[i].value;
                    join = '&';
                }
            }
        } catch(e) {
            alert(e);
            return false;
        }
        if (argstr.search(/\&pageName\=module/) == -1) argstr=argstr+"&pageName=module";
    } else if (method == 'GET') {
        if (url.search(/\&pageName\=module/) == -1) url=url+"&pageName=module";
    }
    if (debug) alert("Method: "+method+"\nURL: "+url+"\nArgs: "+argstr);

    tagToGo = tagid; // required for some implementations
    try {
        if (window.XMLHttpRequest) {
            document.body.style.cursor='wait';
            req = new XMLHttpRequest();
            req.onreadystatechange = processReqChange;
            req.open(method, url, true);
            if (method == 'POST') {
                req.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                req.setRequestHeader("Content-length", argstr.length);
                req.setRequestHeader("Connection", "close");
            }
            req.send(argstr);
            if(method != 'POST')
                return false;
        } else if (window.ActiveXObject) {
            document.body.style.cursor='wait';
            req = new ActiveXObject("Microsoft.XMLHTTP");
            if (req) {
                req.onreadystatechange = processReqChange;
                req.open(method, url, true);
                if (method == 'POST') {
                    req.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                    req.setRequestHeader("Content-length", argstr.length);
                    req.setRequestHeader("Connection", "close");
                }
                req.send(argstr);
            }
            if(method != 'POST')
                return false;
        } else {
            return true; // do the normal action -- should we have an "incompatible browser" error here?
        }
    } catch(e) {
        alert(e);
        return false
    }
}

/**
* Process request
*
* Handler called on state changes of the request object
*
* @author  Marcel van der Boom <marcel@hsdev.com>
* @access  public
* @return  nothing
* @throws  no exceptions
*/
function processReqChange()
{
    try {
        if (req.readyState == 4) {
            if (req.status == 200) {
                // Do whatever we need with the content returned
                tag = document.getElementById(tagToGo);
                if(tag == null) {
                    // not found, fallback
                    document.location = req.url; // TODO: take out 'pageName' var here
                    return true;
                }
                // Make sure we replace the right tag, and dont leave anything behind
                myparent = tag.parentNode;
                tag.id ='dummytogetridoftheoriginal';
                tag.innerHTML = req.responseText;
                if (debug) alert(req.responseText);
                newtag = document.getElementById(tagToGo);
                
                if(newtag == null) {
                    if (debug) alert('cant find the new tag [' + tagToGo + ']');
                    tag.id = tagToGo;
                    document.body.style.cursor='default';
                    return false;
                } else {
                    copyofnew = newtag.cloneNode(true);
                    myparent.replaceChild(copyofnew,tag);
                    document.body.style.cursor='default';
                    return false; // cancel the normal action
                }
            } else {
                // CHECKME: as we are in a looping thingie, displaying an alert here
                // might be premature, but if it really fails how would we know? Just
                // the exception handler?
                //alert("There was a problem retrieving the XML data:\n" +  req.statusText);
            }
        }
    } catch(e) {
        alert("CATCH: " + e);
    }
}
