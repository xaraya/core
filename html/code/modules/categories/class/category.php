<?php
/**
 * Categories Module
 *
 * @package modules\categories
 * @subpackage categories
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/147.html
 *
 * @author Marc Lutolf <mfl@netspan.ch>
 */

sys::import('modules.dynamicdata.class.objects.base');

class Category extends DataObject
{
    public $parentindices = array();

    function createItem(Array $args = array())
    {
        if (isset($args['entry'])) {
            // This is a create via an import
            extract($args);

            // there may not be an entry point passed
            $entry = isset($entry) ? $entry : array();

            if (isset($args['parent_id'])) {
                // If this is an import: replace parentid imported with the local ones
                $parentindex = $args['parent_id'];
                if (in_array($parentindex,array_keys($this->parentindices))) {
                    $args['parent_id'] = $this->parentindices[$parentindex];
                } else {
                    // there could be more than 1 entry point, therefore the array
                    if (count($entry > 0)) {
                        $this->parentindices[$parentindex] = array_shift($entry);
                        $args['parent_id'] = $this->parentindices[$parentindex];
                    } else {
                        $args['parent_id'] = 0;
                    }
                }
                $args['left_id'] = null;
                $args['right_id'] = null;
            }

            // we have all the values, do it
            $id = parent::createItem($args);

            // add this category to the list of known parents
            if (isset($args['parent_id'])) $this->parentindices[$args['id']] = $id;

            // do the Celko dance and update all the left/right values
            return xarMod::apiFunc('categories','admin','updatecelkolinks',array('cid' => $id, 'type' => 'create'));
        } else {
            // This is a "normal" programatic create
            // The dataobject may already contain all the information it needs
            // This would be when we are coming from a page submit
            // or we may not have a complete position for the new category but only a parent ID to hang it from
            // We then have to complete the information for the new caegory
            if (isset($args['relative_position'])) {
                switch ((int)$args['relative_position']) {
                    case 1: // before - same level
                    default:
                        $this->properties['position']->rightorleft = 'left';
                        $this->properties['position']->inorout = 'out';
                        break;
                    case 2: // after - same level
                        $this->properties['position']->rightorleft = 'right';
                        $this->properties['position']->inorout = 'out';
                        break;
                    case 3: // last child item
                        $this->properties['position']->rightorleft = 'right';
                        $this->properties['position']->inorout = 'in';
                        break;
                    case 4: // first child item
                        $this->properties['position']->rightorleft = 'left';
                        $this->properties['position']->inorout = 'in';
                        break;
                    default: // any other value
                        $this->properties['position']->rightorleft = 'right';
                        $this->properties['position']->inorout = 'in';
                        break;
                }
            } else {
                $this->properties['position']->rightorleft = 'right';
                $this->properties['position']->inorout = 'in';
            }
            if (isset($args['parent_id'])) {
                // Add the reference to the parent category
                $this->properties['position']->reference_id = $args['parent_id'];
            } else {
                // Make the new category the last child of the root category
                $this->properties['position']->reference_id = 0;
                $this->properties['position']->rightorleft = 'right';
                $this->properties['position']->inorout = 'in';
            }
            // Now that we have all the information, run the create
            $id = parent::createItem($args);
            return $id;
        }
    }
}
?>