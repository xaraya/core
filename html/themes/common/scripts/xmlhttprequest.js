/**
 * Xaraya XML HTTP Requests
 *
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @subpackage base module
 * @link http://xaraya.info/index.php/release/68.html
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
*/
function loadContent(url, tagid, method, form_obj, local_debug)
{
    // validate inputs and set defaults
    if (local_debug != null) var debug = local_debug
    if (method == null || method == '') method = 'GET'
    method = method.toUpperCase();
    var argstr = '';
    var join = '';
    var pagepat = /\&pageName\=module/;
    var postfix ='&pageName=module';
    
    /**
     * @todo the url handling needs to be documented, a bit strange to do this here
     */
    url = url.replace(/&amp;/g,'&');

    // prepare strings according to method
    if (method == 'POST') {
        if (form_obj != null) formobj = document.getElementById(form_obj);
        try {
            for ( i = 0; i < formobj.elements.length; i++ ) {
                if (formobj.elements[i].name.length > 0) {
                    argstr = argstr + join + formobj.elements[i].name + "=" + formobj.elements[i].value;
                    join = '&';
                }
            }
        } catch(e) {
            if (debug) alert(e);
            return false;
        }
        if (argstr.search(pagepat) == -1) argstr = argstr + postfix;
    } else if (method == 'GET') {
        if (url.search(pagepat) == -1) url = url + postfix;
    }
    if (debug) alert("Method: " + method + "\nURL: " + url + "\nArgs: " + argstr);

    tagToGo = tagid; // required for some implementations
    try {
        if (window.XMLHttpRequest) {
            req = new XMLHttpRequest();
        } else if (window.ActiveXObject) {
            req = new ActiveXObject("Microsoft.XMLHTTP");
        } else {
            return true; // do the normal action -- should we have an "incompatible browser" error here?
        }
        if (req) {
            document.body.style.cursor = 'wait';
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
        }
    } catch(e) {
        if(debug) alert(e);
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
* @return  void
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
                    document.body.style.cursor='default'; // set back to be sure
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
                } else {
                    copyofnew = newtag.cloneNode(true);
                    myparent.replaceChild(copyofnew,tag);
                }
                document.body.style.cursor='default';
                return false; // cancel the normal action
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
