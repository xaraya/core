//
// Adapted from original code found at http://www.peterbailey.net/
//

var xarTree_config = {
    persistance   : true,                                    // Toogle cookie-based persistence
    img_collapsed : "modules/roles/xarimages/k3.gif", // URI for collapsed menu subhead
    img_expanded  : "modules/roles/xarimages/k2.gif", // URI for expanded menu subhead
    branchname    : "xarbranch",                             // How we tag branches
    leafname      : "xarleaf",                               // How we tag leaves
    clickname     : "box",                                   // How we tag the tag that receives clicks
    attr          : "showhide",                              // Attribute used for display tracking
    ignore        : "ignore",                                // Attribute used to ignore tree nodes when saving/loading
    is            : new xarTree_browsersniffer(),            // Browser sniffer object
    count         : 0,                                       // Placeholder for counting
    parseType     : null,                                    // Placeholder for open/save parsing type
    operation     : null                                        // Placeholder for operation type
}

function xarTree_init(treeID) {
    if (!xarTree_config.is.dom || xarTree_config.is.mac) return;
    var tree = document.getElementById(treeID);
    if (document.getElementById(treeID) == null) return;
    if (xarTree_config.is.norm) tree.normalize();
    xarTree_buildTree(tree);
//    if (xarTree_config.persistance) xarTree_open(treeID);
}

function xarTree_open(treeID) {
    xarTree_config.parseType = 'open';
    xarTree_config.count = 0;
    xarTree_saveLoad(document.getElementById(treeID));
}

function xarTree_save(treeID) {
    xarTree_config.parseType = 'save';
    xarTree_config.count = 0;
    var tree = document.getElementById(treeID)
    if (tree == null) return;
    xarTree_saveLoad(tree);
}

function xarTree_browsersniffer() {
    this.dom    = Boolean( document.getElementById );
    this.ie     = Boolean( document.all );
    this.gecko  = Boolean( ( navigator.product ) && ( navigator.product.toLowerCase()=="gecko" ) );
    this.norm   = Boolean( document.normalize );
    this.mac    = Boolean( navigator.userAgent.indexOf("Mac") > -1 );
}

function xarTree_buildTree(tree) {
    var i = 0;
    while( i < tree.childNodes.length ) {
        var currNode = tree.childNodes[i];
        if (currNode.childNodes.length > 0)
            xarTree_buildTree(currNode);
        if (currNode.className == xarTree_config.leafname){
            var titlenode = currNode.parentNode;
            var j = 0;
            while(j < titlenode.childNodes.length) {
                var branch = titlenode.childNodes[j];
                if (branch.className == xarTree_config.clickname) {
                    xarTree_getParent(branch).setAttribute(xarTree_config.attr, "visible");
                    if (xarTree_config.is.ie) {
                        branch.attachEvent("onclick", xarTree_doEvent);}
                    else if (xarTree_config.is.gecko) {
                        branch.addEventListener("click", xarTree_doEvent, false);}
                    else return;
                    break;
                }
                j++;    
            }
        }
        i++;
    }
}

function xarTree_toggleDisplay(branch, leaf, clicked) {
        if (branch.getAttribute(xarTree_config.attr) == 'hidden') {
            leaf.style.display = 'block';
            branch.setAttribute(xarTree_config.attr, "visible");
            clicked.src = xarTree_config.img_expanded;
        }
        else {
            leaf.style.display = 'none';
            branch.setAttribute(xarTree_config.attr, "hidden");
            clicked.src = xarTree_config.img_collapsed;
        }
}

function xarTree_doEvent(e) {
    if (xarTree_config.is.ie) e = window.event;
    e.cancelBubble = true;
    var clicked = (xarTree_config.is.ie) ? e.srcElement : e.target;
    var branch = xarTree_getParent(clicked);
//    if (branch.className != xarTree_config.branchtitle) return;
    var leaf = xarTree_getChild(branch.childNodes);
    if (typeof leaf == 'undefined' || leaf.nodeType == 3) return;
    xarTree_toggleDisplay(branch, leaf, clicked);
}

function xarTree_getParent(node) {
    while (node.parentNode.className != xarTree_config.branchname)
        node = node.parentNode;
    return node.parentNode;
}

function xarTree_getChild(branch) {
    var node = branch[0];
    while (node.className != xarTree_config.leafname) node = node.nextSibling;
    return(node);
}    

function xarTree_exec(id, op) { 
    xarTree_config.operation = op; 
    var node = document.getElementById(id) 
    if (node == null) return; 
    xarTree_commands(node); 
} 
     
function xarTree_commands(thisnode) {
    var i = 0;
    while( i < thisnode.childNodes.length ) {
        var currNode = thisnode.childNodes[i];
        if (currNode.childNodes.length > 0)
            xarTree_commands(currNode);
        if (currNode.className == xarTree_config.clickname){
        var branch = xarTree_getParent(currNode);
            switch (parseInt(xarTree_config.operation)) {
                case 0 : // Hide all
                    if (branch.getAttribute(xarTree_config.attr) == 'visible') currNode.click(); break;
                case 1 : // Show all
                    if (branch.getAttribute(xarTree_config.attr) == 'hidden') currNode.click(); break;
                case 2 : // Toggle all
                    currNode.click(); break;
                default: return;
            }
        xarTree_config.count++;
        }
    i++;
    }
}

/*function xarTree_saveLoad(thisnode) {
    var i = 0;
    while( i < thisnode.childNodes.length ) {
        var currNode = thisnode.childNodes[i];
        if (currNode.childNodes.length > 0)
            xarTree_saveLoad(currNode);
        if (currNode.className == xarTree_config.branchname) {
            if (xarTree_config.parseType == 'save')
                    setCookie("subtree_" + xarTree_config.count, currNode.getAttribute(xarTree_config.attr));
            if (xarTree_config.parseType == 'open')
                if (getCookie("subtree_" + xarTree_config.count) == 'visible')
                    currNode.click();
            xarTree_config.count++;
        }
    i++;
    }
}*/

function toggleBranch(img, node){
    if (!xarTree_config.is.dom || xarTree_config.is.mac) return;
    if(node.style.display != 'none'){
        node.style.display = 'none';
        img.src = 'modules/privileges/xarimages/k3.gif';
    }
    else {
        node.style.display = 'block';
        img.src = 'modules/privileges/xarimages/k2.gif';
    }
}
