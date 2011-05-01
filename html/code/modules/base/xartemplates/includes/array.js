/**
 * @package modules
 * @subpackage base module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/68.html
 */
/**
 * DynamicData Array Property JavaScript Functions
 *
 * @author Chris Powis <crisp@xaraya.com>
**/
function arrayTable(args)
{
    // get args, set defaults 
    this.id        = (typeof args.id == 'undefined')        ? null      : args.id;
    this.name      = (typeof args.name == 'undefined')      ? null      : args.name;
    this.min       = (typeof args.min == 'undefined')       ? 2         : parseInt(args.min);
    this.max       = (typeof args.max == 'undefined')       ? 10        : parseInt(args.max);
    this.addremove = (typeof args.addremove == 'undefined') ? 0         : parseInt(args.addremove);
    this.row_tpl   = (typeof args.row_tpl == 'undefined')   ? 'row_tpl' : args.row_tpl;
    this.add_btn   = (typeof args.add_btn == 'undefined')   ? 'add_btn' : args.add_btn;
    this.debug     = (typeof args.debug == 'undefined')     ? false     : args.debug;

    if (this.debug && (!this.id || !this.name))
        this.debugMsg('Missing id and/or name parameters');
     
    this.tbody = document.getElementById(this.id); // the table rows object (tbody) 
    if (!this.tbody && this.debug) 
        this.debugMsg('Unable to locate table rows with id="' + this.id + '"');
    
    this.addRow = function() {
        numrows = this.tbody.rows.length;
        // make sure we haven't reached the row limit (-1 for the template)
        if (numrows-1 >= this.max) {
            if (this.debug) 
                this.debugMsg('Unable to add row, limit reached (' + this.max + ' rows)');
            return;
        }
        // get the row template (this should be a hidden field with a unique id) 
        tpl = document.getElementById(this.row_tpl);
        if (!tpl) {
            if (this.debug) 
                this.debugMsg('Unable to locate row template with id="' + this.row_tpl + '"');
            return;
        }
        // clone the template 
        row = tpl.cloneNode(true);
        // reset the row id so this row isn't recognised as the template on reIndex 
        row.id = row.id.replace(/rowtemplate$/, numrows-1);
        // make the row visible 
        row.style.display = '';
        // add the row 
        this.tbody.appendChild(row);
        // move the template to the end (NOTE: no need to use removeRow method here) 
        this.tbody.removeChild(tpl); 
        this.tbody.appendChild(tpl);
        // reindex the rows 
        this.reIndex();
    }
    
    this.removeRow = function(row_id) {
        numrows = this.tbody.rows.length;
        // make sure we haven't reached the row limit
        if (numrows-1 <= this.min) {
            if (this.debug) 
                this.debugMsg('Unable to remove row, limit reached (' + this.min + ' rows)');
            return;
        }
        row = document.getElementById(row_id);     
        this.tbody.removeChild(row);
        this.reIndex();
    }
    
    this.reIndex = function() {
        rowid_re = /^(\w+)+(\d+)$/; // match the current row id eg (dd_99_0) 
        // can't use i iterator for the index, since we may skip some rows 
        idx = 0;
        numrows = this.tbody.rows.length;
        for (i=0; i<numrows; i++) {
            row = this.tbody.rows[i];
            // reindex the row id if this isn't the row template 
            if (row.id.match(rowid_re)) {
                row.id = row.id.replace(rowid_re, "$1"+idx);
            }
            // reindex id and name of all inputs, selects and textareas within the row 
            inputs = row.getElementsByTagName('input');
            if (inputs.length) {
                this.setIndex(inputs, idx);
                // additional processing on all but the template row 
                if (row.id.match(rowid_re)) {
                    for (k=0; k<inputs.length; k++) {
                        // disable the hidden delete checkbox 
                        if (inputs[k].type == 'checkbox' && inputs[k].id.match(/_delete$/)) {
                            inputs[k].disabled = true;
                        // update the hidden index value 
                        } else if (inputs[k].type == 'hidden' && inputs[k].id.match(/_0$/)) {
                            inputs[k].value = idx;
                        }
                    }
                }
            }
            selects = row.getElementsByTagName('select');
            if (selects.length)
                this.setIndex(selects, idx);
            areas = row.getElementsByTagName('textarea');
            if (areas.length)
                this.setIndex(areas, idx);
                
            // set the class and onclick of the delbutton
            images = row.getElementsByTagName('img');
            if (images.length) {
                this.setIndex(images, idx);
                for (j=0; j<images.length; j++) {
                    if (images[j].id.length && images[j].id.match(/delbutton$/)) {
                        images[j].style.display = '';
                        // allowed to delete rows and not at the minimum 
                        if (this.addremove == 2 && numrows-1 > this.min) {
                            images[j].setAttribute('class', 'xar-icon');
                            images[j].setAttribute('onclick', 
                                'javascript:'+this.name+'.removeRow(\''+row.id+'\');');
                        } else  {
                            images[j].setAttribute('class', 'xar-icon-disabled');
                            images[j].setAttribute('onclick', 'return false;');
                        }   
                    }
                }
            }

            idx++;
        }
        add = document.getElementById(this.add_btn);
        // not allowed to add rows or at maximum 
        if (this.addremove == 0 || numrows-1 >= this.max) {
            // disable add button
            add.setAttribute('class', 'xar-icon-disabled');
            add.setAttribute('onclick', 'return false;');
        } else {
            // enable add button
            add.setAttribute('class', 'xar-icon');
            add.setAttribute('onclick', 'javascript:'+this.name+'.addRow();');
        }
    }

    
    this.setIndex = function(els, idx)
    {
        value_re = /^(\w+\[\w+\]\[)(\d+)(\]\[\d+\])$/;  // matches name = propname[value][rowid][column]
        valueid_re = /^(\w+)(\d+)+(_\d+)$/;             // matches id = propname_rowid_column 
        delete_re = /^(\w+\[\w+\]\[)(\d+)(\]\[\w+\])$/; // matches name = propname[rowid][delete]
        deleteid_re = /^(\w+)(\d+)+(\w+)$/;             // matches id = propname_rowid_delete
        for (j=0; j<els.length; j++) {
            // replace the idx in name attributes
            if (els[j].name) {
                if (els[j].name.match(value_re)) {
                    els[j].name = els[j].name.replace(value_re, "$1"+idx+"$3")
                } else if (els[j].name.match(delete_re)) {
                    els[j].name = els[j].name.replace(delete_re, "$1"+idx+"$3");
                }
            }
            // replace the idx in id attributes 
            if (els[j].id) {
                if (els[j].id.match(valueid_re)) {
                    els[j].id = els[j].id.replace(valueid_re, "$1"+idx+"$3")
                } else if (els[j].id.match(deleteid_re)) {
                    els[j].id = els[j].id.replace(deleteid_re, "$1"+idx+"$3");
                }
            }  
        }    
    }

    
    this.debugMsg = function(msg) {
        msg = "Debug Message: " +
              msg + "\n" +
              'Rows Element (id): ' + this.id + "\n" +
              'Object Name (name): ' + this.name + "\n" +
              'Minimum Rows (min): ' + this.min + "\n" +
              'Maximum Rows (max): ' + this.max + "\n" +
              'AddRemove (addremove): ' + this.addremove + "\n" +
              'Row Template (row_tpl): ' + this.row_tpl + "\n";
        
        alert(msg);
    }
    

}