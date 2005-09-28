// TODO: some docs
// Global vars for the request object, what we should add to the url and which
// id is in the tag, which subtree we should replace with the result of the
// request.
// Copyright 2004 Marcel van der Boom <marcel@hsdev.com>
// License: GPL <http://www.gnu.org/licenses/gpl.html>

// TODO: can this global be eliminated?
var req;
var tagToGo; // Apparently at least safari needs this

function loadContent(url,tagid) {
    // TODO: this doesnt belong here
    var postfix = "";
    // Prevent propagating postfixes
    if(url.search(/\&pageName\=module/) == -1) {
        postfix = "&pageName=module";
    }
    //alert('URL: ' + url + postfix +', TagId: ' + tagid);
    // Make the tag id global in this scope, required for some implementations
    tagToGo = tagid;
    try {
        if (window.XMLHttpRequest) {
            document.body.style.cursor='wait';
            req = new XMLHttpRequest();
            req.onreadystatechange = processReqChange;
            req.open("GET", url + postfix, true);
            req.send(null);
            return false;
        } else if (window.ActiveXObject) {
            document.body.style.cursor='wait';
            req = new ActiveXObject("Microsoft.XMLHTTP");
            if (req) {
                req.onreadystatechange = processReqChange;
                req.open("GET", url + postfix, true);
                req.send();
            }
            return false;
        } else {
            return true; // do the normal action
        }  
    } catch(e) {
        alert("CATCH: " + e);
    }
}

// Handler called on state changes of the request object
function processReqChange()
{   
    try {
        if (req.readyState == 4) {
            if (req.status == 200) {
                // Do whatever we need with the content returned
                tag = document.getElementById(tagToGo);
                if(tag == null) {
                    // not found, fallback
                    document.location = req.url;
                    return true;
                }
                // Make sure we replace the right tag, and dont leave anything behind
                myparent = tag.parentNode;
                tag.id ='dummytogetridoftheoriginal';
                tag.innerHTML = req.responseText;
                newtag = document.getElementById(tagToGo);
                if(newtag == null) {
                    alert('cant find the new tag [' + tagToGo + ']');
                    tag.innerHTML = req.responseText;
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