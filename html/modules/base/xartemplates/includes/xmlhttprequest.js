// TODO: some docs
// Global vars for the request object, what we should add to the url and which
// id is in the tag, which subtree we should replace with the result of the
// request.
// Copyright 2004 Marcel van der Boom <marcel@hsdev.com>
// License: GPL <http://www.gnu.org/licenses/gpl.html>

var req;
var postfix = "&pageName=module";

function loadContent(url,tagid) {
    if (window.XMLHttpRequest) {
        document.body.style.cursor='wait';
        req = new XMLHttpRequest();
        req.onreadystatechange = processReqChange(tagid);
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
}

// Handler called on state changes of the request object
function processReqChange(tagid)
{
    if (req.readyState == 4) {
        if (req.status == 200) {
            // Do whatever we need with the content returned
            tag = document.getElementById(tagid);
            if(tag == null) {
                // not found, fallback
                document.location = req.url;
                return true;
            }
            // Make sure we replace the right tag, and dont leave anything behind
            myparent = tag.parentNode;
            tag.id ='dummytogetridoftheoriginal';
            tag.innerHTML = req.responseText;
            newtag = document.getElementById(tagid);
            if(newtag == null) {
                alert('cant find the new tag [' + tagid + ']');
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
            alert("There was a problem retrieving the XML data:\n" +  req.statusText);
        }
    }
}