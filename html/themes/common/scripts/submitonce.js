/*
  Submit Once form validation- 
   Dynamic Drive (www.dynamicdrive.com)
  For full source code, usage terms, and 100's more DHTML scripts, visit http://dynamicdrive.com
*/

function submitonce(theform) {
    //if IE 4+ or NS 6+
    if (document.all||document.getElementById){
        //screen thru every element in the form, and hunt down "submit" and "reset"
        for (i=0;i<theform.length;i++) {
            var tempobj=theform.elements[i];
            if(tempobj.type.toLowerCase()=="submit"||tempobj.type.toLowerCase()=="reset") {
                //disable em
                tempobj.disabled=true;
            }
        }
    }
}
