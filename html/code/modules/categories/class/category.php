<?php
/**
 * Categories Module
 *
 * @package modules\categories
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
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
                extract($args);

                // there may not be an entry point passed
                $entry = isset($entry) ? $entry : array();

                if (isset($args['parent_id'])) {
                    // If this is an import replace parentid imported with the local ones
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
                $id = parent::createItem($args);
            }
        }
    }
?>
