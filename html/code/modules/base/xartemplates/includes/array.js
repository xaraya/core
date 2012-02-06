/**
 * @package modules
 * @subpackage base module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
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
    // config values and their defaults
    this.id = (typeof args.id == 'undefined') ? null : args.id;
    this.name = (typeof args.name == 'undefined') ? this.id : args.name;
    this.min       = (typeof args.min == 'undefined')       ? 2         : parseInt(args.min);
    this.max       = (typeof args.max == 'undefined')       ? 10        : parseInt(args.max);
    this.addremove = (typeof args.addremove == 'undefined') ? 0         : parseInt(args.addremove);

    // suffixes to look for
    this.rows_id = (typeof args.rows_id == 'undefined') ? 'rows' : args.rows_id;
    this.tpl_id   = (typeof args.tpl_id == 'undefined')   ? 'template' : args.tpl_id;
    this.add_id   = (typeof args.add_id == 'undefined')   ? 'addrow' : args.add_id;
    //this.del_id   = (typeof args.del_id == 'undefined') ? 'delete' : args.del_id;
    this.del_id = 'delete'; // this is a fixed value since it's used in the property code 
    this.count_id = (typeof args.count_id == 'undefined') ? null : args.count_id;
    // Delete buttons 
    this.del_icon   = (typeof args.del_icon == 'undefined') ? '' : args.del_icon;
    this.del_alt   = (typeof args.del_alt == 'undefined') ? 'Remove' : args.del_alt;
    this.del_title   = (typeof args.del_title == 'undefined') ? 'Remove row' : args.del_title;
    // Add button 
    this.add_icon   = (typeof args.add_icon == 'undefined') ? '' : args.add_icon;
    this.add_alt   = (typeof args.add_alt == 'undefined') ? 'Add' : args.add_alt;
    this.add_title   = (typeof args.add_title == 'undefined') ? 'Add row' : args.add_title;
    // Up button
    this.up_icon = (typeof args.up_icon == 'undefined') ? '' : args.up_icon;
    this.up_alt   = (typeof args.up_alt == 'undefined') ? 'Up' : args.up_alt;
    this.up_title   = (typeof args.up_title == 'undefined') ? 'Move row up' : args.up_title;
    // Down button
    this.down_icon = (typeof args.down_icon == 'undefined') ? '' : args.down_icon;
    this.down_alt   = (typeof args.down_alt == 'undefined') ? 'Down' : args.down_alt;
    this.down_title   = (typeof args.down_title == 'undefined') ? 'Move row down' : args.down_title;
    // Icon classes 
    this.icon_enabled   = (typeof args.icon_enabled == 'undefined') ? 'xar-icon' : args.icon_enabled;
    this.icon_disabled  = (typeof args.icon_disabled == 'undefined') ? 'xar-icon-disabled' : args.icon_disabled;
    // Pointer styles 
    this.pointer_enabled = (typeof args.pointer_enabled == 'undefined') ? 'pointer' : args.pointer_enabled;
    this.pointer_disabled = (typeof args.pointer_disabled == 'undefined') ? 'not-allowed' : args.pointer_disabled;
    // Optionally debug 
    this.debug     = (typeof args.debug == 'undefined')     ? false     : args.debug;
    
    // the element containing rows (table or tbody)
    this.table;

/**
 * Initialize the object
 * This method takes care of setting up the template
 * NOTE: this method must be called *after* the table is rendered in the DOM
**/
    this.init = function() {
        if (!this.id || !this.name) {
            if (this.debug)
                this.debugMsg(this.id + '.init() id and name are required');
            return;
        }
        this.table = document.getElementById(this.id + '_' + this.rows_id);
        if (!this.table) {
            if (this.debug)
                this.debugMsg(this.id + '.init() missing id="' + this.id + '_' + this.rows_id + '"');
            return;        
        } else if (!this.table.rows.length) {
            if (this.debug)
                this.debugMsg(this.id + '.init() missing rows in id="' + this.id + '_' + this.rows_id + '"');
            return;
        }
        this.reIndex();        
        if (this.debug)
            this.debugMsg(this.id + '.init() success');
        return true;
    }

/**
 * add row to table
 * @checkme: do we want to be able to insert rows at specific poisitions some day?
**/
    this.addRow = function() {
        numrows = this.table.rows.length;
        // current index is numrows minus the template itself
        idx = numrows-1;
        // make sure we haven't reached the row limit
        if (idx >= this.max) {
            if (this.debug) 
                this.debugMsg('Unable to add row, limit reached (' + this.max + ' rows)');
            return;
        }
        // get the row template dd_id + tpl_id 
        tpl = document.getElementById(this.id + '_' + this.tpl_id);
        if (!tpl) {
            if (this.debug) 
                this.debugMsg('Unable to locate row template with id="' + this.tpl_id + '"');
            return;
        }
        // clone the template 
        var row = tpl.cloneNode(true);
        // match the tpl_id suffix
        row_re = new RegExp(this.tpl_id + '$');
        // replace the tpl_id suffix with the current index  
        row.id = row.id.replace(row_re, idx);
        // make the row visible 
        row.style.display = '';
        // append the row 
        this.table.appendChild(row);
        // move the template to the end (NOTE: no need to use removeRow method here) 
        this.table.removeChild(tpl); 
        this.table.appendChild(tpl);
        // reindex the rows 
        this.reIndex();

        if (this.debug)
            this.debugMsg(this.id + '.addRow() success adding ' + row.id);
            
        return true;        
    }

/**
 * Remove row from table
**/
    this.removeRow = function(row_id) {
        numrows = this.table.rows.length;
        // make sure we haven't reached the row limit
        if (numrows-1 <= this.min) {
            if (this.debug) 
                this.debugMsg('Unable to remove row, limit reached (' + this.min + ' rows)');
            return;
        }
        var row = document.getElementById(row_id);     
        this.table.removeChild(row);
        this.reIndex();
        if (this.debug)
            this.debugMsg(this.id + '.removeRow() success removing ' + row_id);
        return true;
    }

/**
 * Util function to reindex rows, called by init and add/removeRow methods
**/
    this.reIndex = function() {
        numrows = this.table.rows.length;
        // reindex rows
        for (r=0; r<numrows; r++) {
            var row = this.table.rows[r];
            // index this rows element attributes NOTE: we *must* do this first 
            this.indexRow(row, r);
                       
            // look for the row template dd_id + _ + tpl_id
            tpl_re = new RegExp(this.id + '_' + this.tpl_id);
            if (row.id.match(tpl_re)) {
                // hide the template
                row.style.display = 'none';
            } else {
                // all other rows are displayed, reset row id to current index (r) 
                row.setAttribute('id', this.id + '_' + r);
                // see if we're allowed to delete rows and if so add a delete icon (button) 
                if (this.addremove == 2) {
                    // see if the delete buttons already set
                    delbtn_id = this.id + '_' + r + '_' + this.del_id + '_btn';
                    delbtn = document.getElementById(delbtn_id);
                    if (!delbtn) {
                        // not already set, 
                        // look for the delete checkbox dd_id + _ + row + del_id                
                        del = document.getElementById(this.id + '_' + r + '_' + this.del_id);  
                        // if it exists and is a checkbox, disable it, and hide it
                        if (del && del.type == 'checkbox') {
                            del.style.display = 'none';
                            del.disabled = true;
                            // create the delete button and add attributes from config 
                            delbtn = this.createButton(
                                delbtn_id, 
                                this.del_alt, 
                                this.del_title, 
                                this.del_icon
                            );
                            // append the button to the checkbox parent 
                            del.parentNode.appendChild(delbtn);
                        }
                    }
                    // if we have a delete button, set appropriate attributes 
                    if (delbtn) {
                         // see if we're at the minimum rows
                        if (numrows-1 <= this.min) {
                            // disable the button 
                            delbtn.setAttribute('onclick', 'return false;');
                            delbtn.setAttribute('class', this.icon_disabled);
                            delbtn.style.cursor = this.pointer_disabled;
                        } else {
                            // enable the button 
                            delbtn.setAttribute('onclick', 
                                this.id + '.removeRow(\'' + row.id + '\');return false;');
                            delbtn.setAttribute('class', this.icon_enabled);
                            delbtn.style.cursor = this.pointer_enabled;
                        }
                    }
                }
            } 
        } // end rows

        // show the add button if add rows only AND not at maximum rows, OR if we can add and remove
        if ( (this.addremove == 1 && numrows-1 < this.max) || (this.addremove == 2) ) {
            // see if the buttons already set 
            addbtn_id = this.id + '_' + this.add_id + '_btn';
            addbtn = document.getElementById(addbtn_id);
            if (!addbtn) {    
                // not already set, get the button container dd_id + add_id 
                add = document.getElementById(this.id + '_' + this.add_id);
                if (add) {
                    // create the button and add attributes from config
                    addbtn = this.createButton(
                        addbtn_id, 
                        this.add_alt, 
                        this.add_title, 
                        this.add_icon
                    );
                    add.appendChild(addbtn);
                }
            }                
            if (addbtn) {
                // see if we're at the maximum rows
                if (numrows-1 >= this.max) {
                    // disable the button 
                    addbtn.setAttribute('onclick', 'return false;');
                    addbtn.setAttribute('class', this.icon_disabled);
                    addbtn.style.cursor = this.pointer_disabled;
                } else {
                    // enable the button 
                    addbtn.setAttribute('onclick', this.id + '.addRow();return false;');
                    addbtn.setAttribute('class', this.icon_enabled);
                    addbtn.style.cursor = this.pointer_enabled;
                }
            }
        } // end add button

        // if a count_id is specified
        if (this.count_id) {
            // get the container with the specified id
            count = document.getElementById(this.id + '_' + this.count_id);
            if (count)
                // update the containers inner html with the current row count  
                count.innerHTML = numrows-1;
        } // end count
        // update value of hidden last row field 
        lastrow = document.getElementById(this.id + '_lastrow');
        if (lastrow)
            lastrow.value = numrows-1;
        
        return true;
    }

/**
 * Update name and id attributes of all elements in a row with specified index
**/
    this.indexRow = function(row, idx) {
        if (!row) return;
        inputs = row.getElementsByTagName('input');
        if (inputs)
            this.setIndex(inputs, idx);
        selects = row.getElementsByTagName('select');
        if (selects)
            this.setIndex(selects, idx);
        textareas = row.getElementsByTagName('textarea');
        if (textareas)
            this.setIndex(textareas, idx);
        images = row.getElementsByTagName('img');
        if (images)
            this.setIndex(images, idx);
        return true;
    }

/**
 * Update name and id attributes of a collection of elements with specified index
**/

    this.setIndex = function(els, idx)
    {
        value_re = new RegExp('^(' + this.name + '\\[value\\]\\[)(\\d+)(\\]\\[\\d+\\])$');
        valueid_re = new RegExp('^(' + this.id + '_)(\\d+)(_\\d+)$');
        delete_re = new RegExp('^(' + this.name + '\\[value\\]\\[)(\\d+)(\\]\\[' + this.del_id + '\\])$');
        deleteid_re = new RegExp('^(' + this.id + '_)(\\d+)(_' + this.del_id + ')$');
        delbtnid_re = new RegExp('^(' + this.id + '_)(\\d+)(_' + this.del_id + '_btn)$');
        indexid_re = new RegExp('^(' + this.id + '_)(\\d+)(_0)$');
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
                    els[j].id = els[j].id.replace(valueid_re, "$1"+idx+"$3");
                    if (els[j].id.match(indexid_re))
                        els[j].value = idx;
                } else if (els[j].id.match(deleteid_re)) {
                    els[j].id = els[j].id.replace(deleteid_re, "$1"+idx+"$3");
                } else if (els[j].id.match(delbtnid_re)) {
                    els[j].id = els[j].id.replace(delbtnid_re, "$1"+idx+"$3");
                }                   
            }  
        }
        return true; 
    }

/**
 * Util function to create a button (image) 
**/
    this.createButton = function(id, alt, title, src) {
        btn = new Image(); 
        btn.setAttribute('id', id);
        btn.setAttribute('alt', alt);
        btn.setAttribute('title', title);
        btn.setAttribute('src', src);
        return btn;
    }        
/**
 * Debug info 
**/
    this.debugMsg = function(msg) {
        msg = "Debug : " +
              msg + "\n" +
              'id: ' + this.id + "\n" +
              'name: ' + this.name + "\n" +
              'min: ' + this.min + "\n" +
              'max: ' + this.max + "\n" +
              'addremove: ' + this.addremove + "\n" +
              'rows_id: ' + this.rows_id + "\n" +
              'tpl_id: ' + this.tpl_id + "\n" +
              'add_id: ' + this.add_id + "\n";      
        alert(msg);
    }
    
}