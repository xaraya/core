/* modified version of a script once published in ALA http://www.alistapart.com/articles/alternate/ */

function setActiveStyleSheetTxt(title) {
  var i, a, main;
  for(i=0; (a = document.getElementsByTagName("link")[i]); i++) {
    if(a.getAttribute("rel").indexOf("style") != -1 && a.getAttribute("title") && a.getAttribute("title").indexOf("classictext") != -1) {
      	a.disabled = true;
      	if(a.getAttribute("title") == title) a.disabled = false;
    }
  }
}

function setActiveStyleSheetCol(title) {
  var i, a, main;
  for(i=0; (a = document.getElementsByTagName("link")[i]); i++) {
    if(a.getAttribute("rel").indexOf("style") != -1 && a.getAttribute("title") && a.getAttribute("title").indexOf("classiccolors") != -1) {
      a.disabled = true;
      if(a.getAttribute("title") == title) a.disabled = false;
    }
  }
}

function getActiveStyleSheetTxt() {
  var i, a;
  for(i=0; (a = document.getElementsByTagName("link")[i]); i++) {
    if(a.getAttribute("rel").indexOf("style") != -1 && !a.disabled && a.getAttribute("title").indexOf("classictext") != -1) return a.getAttribute("title");
  }
  return null;
}

function getActiveStyleSheetCol() {
  var i, a;
  for(i=0; (a = document.getElementsByTagName("link")[i]); i++) {
    if(a.getAttribute("rel").indexOf("style") != -1 && a.getAttribute("title").indexOf("classiccolors") != -1 && !a.disabled) return a.getAttribute("title");
  }
  return null;
}

function getPreferredStyleSheetTxt() {
  var i, a;
  for(i=0; (a = document.getElementsByTagName("link")[i]); i++) {
    if(a.getAttribute("rel").indexOf("style") != -1 && a.getAttribute("rel").indexOf("alt") == -1 && a.getAttribute("title") && a.getAttribute("title").indexOf("classictext")  != -1) return a.getAttribute("title");
  }
  return null;
}

function getPreferredStyleSheetCol() {
  var i, a;
  for(i=0; (a = document.getElementsByTagName("link")[i]); i++) {
    if(a.getAttribute("rel").indexOf("style") != -1 && a.getAttribute("rel").indexOf("alt") == -1 && a.getAttribute("title") && a.getAttribute("title").indexOf("classiccolors")  != -1) return a.getAttribute("title");
  }
  return null;
}


function createCookie(name,value,days) {
  if (days) {
    var date = new Date();
    date.setTime(date.getTime()+(days*24*60*60*1000));
    var expires = "; expires="+date.toGMTString();
  }
  else expires = "";
  document.cookie = name+"="+value+expires+"; path=/";
}

function readCookie(name) {
  var nameEQ = name + "=";
  var ca = document.cookie.split(';');
  for(var i=0;i < ca.length;i++) {
    var c = ca[i];
    while (c.charAt(0)==' ') c = c.substring(1,c.length);
    if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
  }
  return null;
}

window.onload = function(e) {
  var cookie = readCookie("xarayaclassic_textsize");
  var title = cookie ? cookie : getPreferredStyleSheetTxt();
  setActiveStyleSheetTxt(title);
  var cookie2 = readCookie("xarayaclassic_colscheme");
  var title2 = cookie2 ? cookie2 : getPreferredStyleSheetCol();
  setActiveStyleSheetCol(title2);
}

/* window.onunload = function(e) { */
/*   var title = getActiveStyleSheetTxt(); */
/*   createCookie("xarayaclassic_textsize", title, 365); */
/*   var title2 = getActiveStyleSheetCol(); */
/*   createCookie("xarayaclassic_colscheme", title2, 365); */
/* } */

var cookie = readCookie("xarayaclassic_textsize");
var title = cookie ? cookie : getPreferredStyleSheetTxt();
setActiveStyleSheetTxt(title);
var cookie2 = readCookie("xarayaclassic_colscheme");
var title2 = cookie2 ? cookie2 : getPreferredStyleSheetCol();
setActiveStyleSheetCol(title2);