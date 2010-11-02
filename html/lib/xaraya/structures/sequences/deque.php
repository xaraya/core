<?php
/**
 * @package core
 * @subpackage 
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * 
 * 
 */

sys::import('xaraya.structures.sequences.interfaces');
sys::import('xaraya.structures.sequences.adapters.sequence_adapter');

// A deque can be manipulated at both ends

/**
 * Implementation of the Deque datastructure
 *
 */
class Deque extends SequenceAdapter implements iDeque
{
    // Push an item into the Deque, head or tail
    public function push(&$item, $whichEnd) 
    {
        return parent::insert($item,$whichEnd);
    }

    // Pop an item off the Deque, head or tail
    public function &pop($whichEnd)
    {
        $item = parent::get($whichEnd);
        if($item == null) return $item;
        parent::delete($whichEnd);
        return $item;
    }

    public function clear()
    {
        return parent::clear();
    }
}
?>
