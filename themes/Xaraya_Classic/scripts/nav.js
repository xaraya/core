/* custom script - do not copy */

function nav_toggle() { 
    var doc = document; 
    var obj = nav_toggle.arguments, elementToToggle; 
    elementToToggle = doc.getElementById(obj[0]).style.display;
    doc.getElementById(obj[0]).style.display = (elementToToggle == 'block') ? 'none' : 'block';
    doc.getElementById(obj[1]).style.display = 'none'; 
    doc.getElementById(obj[2]).style.display = 'none'; 
    doc.getElementById(obj[3]).style.display = 'none';
    doc.getElementById(obj[4]).style.display = 'none';
    doc.getElementById(obj[5]).style.display = (elementToToggle == 'block') ? 'block' : 'none';
}

function nav_reset() { 
    var doc = document; 
    doc.getElementById('aboutlnk').style.display = 'none';
    doc.getElementById('solutionslnk').style.display = 'none'; 
    doc.getElementById('documentationlnk').style.display = 'none'; 
    doc.getElementById('communitylnk').style.display = 'none';
    doc.getElementById('accesslnk').style.display = 'none';
    doc.getElementById('space4rent').style.display = 'block';
}


function show_layer(thisname) {
    if(document.all) { // IE
        document.all[thisname].style.visibility = 'visible';
    } else if(document.layers) { // NS
        document.layers[thisname].visibility = 'visible';
    } else { // DOM
        document.getElementById(thisname).style.visibility = 'visible';
    }
}

function hide_layer(thisname) {
    if(document.all) { // IE
        document.all[thisname].style.visibility = 'hidden';
    } else if(document.layers) { // NS
        document.layers[thisname].visibility = 'hidden';
    } else { // DOM
        document.getElementById(thisname).style.visibility = 'hidden';
    }
}

/* idea and implementation borrowed from http://wadny.com */
setExternalLinks=function() {
    if ( !document.getElementsByTagName ) {
        return null;
    }
 
    var anchors = document.getElementsByTagName( "a" );
    for ( var i = 0; i < anchors.length; i++ ) {
        var anchor = anchors[i];
        if ( anchor.getAttribute( "href" ) && anchor.getAttribute( "rel" ) == "external" ) {
            anchor.setAttribute( "target", "_blank" );
        }
    }
}

nav_hover=function() {
    if (document.all&&document.getElementById) {
        if (document.getElementById("hmenuroot")) {
            navRoot = document.getElementById("hmenuroot");
            for (i=0; i<navRoot.childNodes.length; i++) {
                node = navRoot.childNodes[i];
                if (node.nodeName=="LI") {
                    node.onmouseover=function() {
                        this.className+=" over";
                    }
                    node.onmouseout=function() {
                        this.className=this.className.replace(" over", "");
                    }
                }
            }
        }
        if (document.getElementById("navlinks")) {
            navRoot = document.getElementById("navlinks");
            for (i=0; i<navRoot.childNodes.length; i++) {
                node = navRoot.childNodes[i];
                if (node.nodeName=="DIV") {
                    node.onmouseover=function() {
                        this.className+=" over";
                    }
                    node.onmouseout=function() {
                        this.className=this.className.replace(" over", "");
                    }
                }
            }
        }
    }
}

if (document.all&&window.attachEvent) { // IE-Win
    window.attachEvent("onload", nav_hover);
    window.attachEvent("onload", setExternalLinks);
} else if (window.addEventListener) { // Others
    window.addEventListener("load",setExternalLinks,false);
}

/* } else if (document.all&&document.getElementById) { */
/*     window.onload=nav_hover; // ie5-mac case */
/* } */