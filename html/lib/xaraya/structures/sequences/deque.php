<?php
/**
 * @package core\structures
 * @subpackage structures
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 */

sys::import('xaraya.structures.sequences.interfaces');
sys::import('xaraya.structures.sequences.adapters.sequence_adapter');

// A deque can be manipulated at both ends
// pop() and push() methods need a parameter $whichEnd, which can be:
// head: items get added or removed from the end of the array (last in, first out)
// tail: items get added or removed from the beginning of the array (first in, first out)

/**
 * Implementation of the Deque datastructure
 *
 */
class Deque extends SequenceAdapter implements iDeque
{
    // Push an item into the Deque, head or tail
    public function push($item, $whichEnd) 
    {
        // Unfortunate kludge: when there is exactly 1 item in the deque the code cannot tell the difference between head and tail
        // So we temporarily increase the size of the array to make the difference noticeable
        // Since this is an issue specific to the deque, we cannot fix it at the level of the parent. We have do do something here,
        // because the parent only understands position and knows nothing of head and tail
        $added_dummy = false;
        if ($this->__get('size') == 1) {
        	parent::insert('void',1);
        	$added_dummy = true;
        	$dummy_position = 2;
        }
        
        // Add the "real" item
        $position = $this->__get($whichEnd);
       	$pushed = parent::insert($item,$position);
        
        // Remove the item we added
		if ($added_dummy) {
			$position = ($whichEnd == 'tail') ? 2 : 1;echo $position;
			parent::delete($position);
		}
       return $pushed;
    }

    // Peek at an item at the head or tail of the Deque
    public function &peek($whichEnd)
    {
        $position = $this->__get($whichEnd);
        if ($position < 0) {
            $item = null;
        } else {
            $item = parent::get($position);
        }
        return $item;
    }

    // Pop an item off the Deque, head or tail
    public function &pop($whichEnd)
    {
        $item = $this->peek($whichEnd);
        if ($item == null) return $item;
        $position = $this->__get($whichEnd);
        parent::delete($position);
        return $item;
    }

    public function clear()
    {
        return parent::clear();
    }
    
    public function load($seq)
    {
        return parent::load($seq);
    }
}
