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
/**
 * Sequence implemented as an array
 *
 * If the abstraction is proper, every method should have some
 * array specific part for the implementation. ;-)
 */
sys::import('xaraya.structures.sequences.interfaces');

class ArraySequence extends xarObject implements iSequence, iSequenceAdapter
{
    // An array holds our sequence items
    public $items = array();

    // iSequence implementation
    // Get the item at the specified position
    public function &get($position)
    {
        $item = null;
        if($position > $this->tail) return $item;
        switch($position) {
        case $this->head:
            $item = end($this->items);
            break;
        case $this->tail:
            $item = reset($this->items);
            break;
        default:
        	break;
        }
        return $item;
    }
    // Insert an item on the specified position
    public function insert($item, $position)
    {
        if($position > $this->tail) return false;
        switch($position) {
        case $this->head:
            array_unshift($this->items,$item);
            break;
        case $this->tail:
            array_push($this->items, $item);
            break;
        default:
            $first = array_slice($this->items,0,$position-1);
            $last  = array_slice($this->items,$position);
            $first[] = $item;
            $this->items = array_merge($first,$last);
        }
        return true;
    }
    // Delete an item from the specified position
    public function delete($position)
    {
        if($position > $this->tail or $this->empty) return false;
        switch($position) {
        case $this->tail:
            $item = array_pop($this->items);
            break;
        case $this->head:
        case 0:
            $item = array_shift($this->items);
            break;
        default:
            $first = array_slice($this->items,0,$position-1);
            $last = array_slice($this->items,$position+1);
            $this->items = array_merge($first,$last);
        }
        return true;
    }
    // Clear the sequence
    public function clear()
    {
        $this->items = array();
        return true;
    }
    // Load the sequence
    public function load($seq)
    {
        $this->items = $seq;
        return true;
    }

    /**
     * Getter mapper
     *
     * @return mixed
     * @throws Exception
     * @author Marcel van der Boom
     * @todo arguably evil
     **/
    public function __get($name)
    {
        switch($name) {
        case 'size':
            return count($this->items);
        case 'empty':
            return count($this->items) == 0;
        case 'tail':
            return empty($this->items)?-1:0;
        case 'head':
            return count($this->items)-1;
        default:
            throw new Exception("Property $name does not exist");
        }
    }
}
?>
