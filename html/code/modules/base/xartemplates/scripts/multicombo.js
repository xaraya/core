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
 * DynamicData Multiselect Property JavaScript Functions
 *
 * @author Chris Powis <crisp@xaraya.com>
**/
function multiCombo(args)
{
    // config values and their defaults
    // the id of this javascript object instance (eg myMulti = new multiCombo({ id: 'myMulti' })
    this.id = (typeof args.id == 'undefined') ? null : args.id;
    // left select element id (required)
    this.left = (typeof args.left == 'undefined') ? 'left_select' : args.left;
    // right select element id (required)
    this.right = (typeof args.right == 'undefined') ? 'right_select' : args.right;

    // button elements to attach onclick events to
    this.right_move = (typeof args.right_move == 'undefined') ? 'right_move' : args.right_move;
    this.right_move_all = (typeof args.right_move_all == 'undefined') ? 'right_move_all' : args.right_move_all;
    this.left_move = (typeof args.left_move == 'undefined') ? 'left_move' : args.left_move;
    this.left_move_all = (typeof args.left_move_all == 'undefined') ? 'left_move_all' : args.left_move_all;

    // array of ids to make visible on init
    this.make_visible = (typeof args.make_visible == 'undefined') ? null : args.make_visible;

    // en/disable automatic sorting (default enabled)
    this.auto_sort = (typeof args.auto_sort == 'undefined') ? true : args.auto_sort;

    this.leftel; // the left select element
    this.rightel; // the right select element

    // init takes care of everything, this is the only method we need to call in the template 
    this.init = function()
    {
        // pointless if we don't have these element ids
        if (!this.left || !this.right || !this.id) return;

        // get the right select element with values to be submitted
        this.rightel = document.getElementById(this.right);
        if (!this.rightel) return;

        // make sure we have a form element ancestor to attach our onsubmit event to
        formel = this.getAncestor(this.rightel, 'form');
        if (!formel) return;

        // the right select has everything we need, so we just clone that to the left select
        leftel = document.getElementById(this.left);
        if (!leftel) return;
        newel = this.rightel.cloneNode(true);
        // remembering to put back the id and name
        newel.setAttribute('id', leftel.id);
        newel.setAttribute('name', leftel.name);
        leftel.parentNode.replaceChild(newel, leftel);
        // get the left select element
        this.leftel = document.getElementById(this.left);

        // now we remove all selected elements from the left select
        this.removeSelected(this.leftel);
        // and remove all unselected elements on the right
        this.removeUnselected(this.rightel);

        // ok now we can set up our button actions
        rightbtn = document.getElementById(this.right_move);
        if (rightbtn) {
            rightbtn.setAttribute('onclick',
                this.id + '.move(\'left\',\'right\');return false;');
            rightbtn.disabled = false;
        }
        rightbtnall = document.getElementById(this.right_move_all);
        if (rightbtnall) {
            rightbtnall.setAttribute('onclick',
                this.id + '.move(\'left\',\'right\', true);return false;');
            rightbtnall.disabled = false;
        }
        leftbtn = document.getElementById(this.left_move);
        if (leftbtn) {
            leftbtn.setAttribute('onclick',
                this.id + '.move(\'right\',\'left\');return false;');
            leftbtn.disabled = false;
        }
        leftbtnall = document.getElementById(this.left_move_all);
        if (leftbtnall) {
            leftbtnall.setAttribute('onclick',
                this.id + '.move(\'right\',\'left\', true);return false;');
            leftbtnall.disabled = false;
        }

        // prepend our submitForm() method to the parent forms existing onsubmit event
        onsubmit = formel.getAttribute('onsubmit');
        formel.setAttribute('onsubmit',
            this.id + '.submitForm();' + onsubmit);
        
        // deselect all options, sort them, and set handlers 
        this.refreshElements();      
        
        // and finally, display the hidden elements
        if (this.make_visible.length) {
            for (i=0;i<this.make_visible.length;i++) {
                el = document.getElementById(this.make_visible[i]);
                if (el) {
                    el.style.display = '';
                }
            }
        }

    }

    this.refreshElements = function()
    {
        // deselect all options
        this.deselectAll(this.rightel);
        this.deselectAll(this.leftel);
        // sort options
        this.sortOptions(this.rightel);           
        this.sortOptions(this.leftel);
        // set dblclick events on options
        this.setOptionHandlers(this.rightel, 'left'); 
        this.setOptionHandlers(this.leftel, 'right');
    }

    // called onsubmit to disable left and select all options on right
    this.submitForm = function()
    {
        this.leftel.disabled = true;
        this.deselectAll(this.leftel);
        this.selectAll(this.rightel);
    }


    // set dblclick handlers on options from select element 
    this.setOptionHandlers = function(from, dir)
    {
        numopts = from.options.length;
        for (i=0;i<numopts;i++) {
            from.options[i].setAttribute('ondblclick',
                this.id + '.moveOption(this, \'' + dir + '\');');
        }        
    }

    // called by button onclick event to move options
    this.move = function(from, to, all)
    {
        if (from == 'left') {
            this.moveSelected(this.leftel, this.rightel, all);
        } else {
            this.moveSelected(this.rightel, this.leftel, all);
        }
    }

    // move selected options from one select element to another
    this.moveSelected = function(from, to, all)
    {
        var i,
            all = (typeof all == 'undefined') ? false : all;
        if (all) this.selectAll(from);
        // have to work backwards here, because remove() automatically reindexes the array
        for (i = from.length - 1; i>=0; i--) {
            if (from.options[i].selected) {
                to.add(from.options[i]);
            }
        }
        // remove options from select
        this.removeSelected(from);        
        this.refreshElements();
    }

    // move a single option element (fired by ondblclick) 
    this.moveOption = function(el, dir)
    {
        el.parentNode.remove(el.index);
        if (dir == 'left') {            
            this.leftel.add(el);
        } else {
            this.rightel.add(el);
        }
        this.refreshElements();
    }

    // select all options from a select element
    this.selectAll = function(from)
    {
        this.setSelected(from, true);
    }

    // deselect all options from a select element
    this.deselectAll = function(from)
    {
        this.setSelected(from, false);
    }

    // set selected state of all options from a select element
    this.setSelected = function(from,s)
    {
        var s = (typeof s == 'undefined') ? false : s;
        numopts = from.options.length;
        for (i=0;i<numopts;i++) {
            from.options[i].selected = s;
        }        
    }

    // remove all options with selected attribute from a select element
    this.removeSelected = function(from)
    {
        this.removeOptions(from, true);
    }
    // remove all options with selected attribute from a select element
    this.removeUnselected = function(from)
    {
        this.removeOptions(from, false);
    }

    this.removeOptions = function(from, s)
    {
        var s = (typeof s == 'undefined') ? false : s,
            i;
        // have to work backwards here, because remove() automatically reindexes the array
        for (i = from.length - 1; i>=0; i--) {
            if (from.options[i].selected == s) {
                from.remove(i);
            }
        }
    }

    // helper method to get parent form element
    this.getAncestor = function(o, tag)
    {
        for(tag = tag.toLowerCase(); o = o.parentNode;)
            if(o.tagName && o.tagName.toLowerCase() == tag)
                return o;
        return null;
    }

    // helper method to sort options alphabetically
    this.sortOptions = function(obj){
        if (!this.auto_sort) return;
        var o = new Array();
        if(!obj.options.length) return;
        for(var i=0;i<obj.options.length;i++) {
            o[o.length] = new Option(
                obj.options[i].text,
                obj.options[i].value,
                obj.options[i].defaultSelected,
                obj.options[i].selected
            );
        }
        if(o.length==0) return;
        o = o.sort(
            function(a,b) {
                if((a.text.toLowerCase( )+"") <(b.text.toLowerCase( )+"")) return -1;
                if((a.text.toLowerCase( )+"") >(b.text.toLowerCase( )+"")) return 1;
                return 0;
            }
        );
        for(var i=0;i<o.length;i++){
            obj.options[i] = new Option(o[i].text, o[i].value, o[i].defaultSelected, o[i].selected);
        }
    }

}