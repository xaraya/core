// TODO: some docs
// Global vars for the request object, what we should add to the url and which
// id is in the tag, which subtree we should replace with the result of the
// request.
// Copyright 2004 Marcel van der Boom <marcel@hsdev.com>
// License: GPL <http://www.gnu.org/licenses/gpl.html>

// TODO: can this global be eliminated?
var req;

function loadContent(url,tagid) {
    // TODO: this doesnt belong here
    var postfix = "&pageName=module";
    // Set the tag id as property of the processReqChange function 
    // instead of a global variable
    processReqChange.tagid = tagid;
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
    } catch (e) {
        alert(e);
    }
}

// Handler called on state changes of the request object
function processReqChange()
{   
    try {
        if (req.readyState == 4) {
            if (req.status == 200) {
                // Do whatever we need with the content returned
                tag = document.getElementById(this.tagid);
                if(tag == null) {
                    // not found, fallback
                    document.location = req.url;
                    return true;
                }
                // Make sure we replace the right tag, and dont leave anything behind
                myparent = tag.parentNode;
                tag.id ='dummytogetridoftheoriginal';
                tag.innerHTML = req.responseText;
                newtag = document.getElementById(this.tagid);
                if(newtag == null) {
                    alert('cant find the new tag [' + this.tagid + ']');
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
    } catch(e) {
        alert(e);
    }
}