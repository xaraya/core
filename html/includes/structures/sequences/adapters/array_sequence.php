<?php
/**
 * Sequence implemented as an array
 *
 * If the abstraction is proper, every method should have some
 * array specific part for the implementation. ;-)
 */ 
class ArraySequence implements iSequence, iSequenceAdapter
{
    // An array holds our sequence items
    protected $items = array();

    // iSequence implementation
    // Get the item at the specified position
    public function &get($position)
    {
        $item = null;
        $item = $this->items[$position];
        return $item;
    }
    // Insert an item on the specified position
    public function insert(&$item, $position)
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
            $last  = aray_slice($this->items,$position);
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
    // Map the getters 
    public function __get($name)
    {
        switch($name) {
        case 'size':
            return count($this->items);
        case 'empty':
            return count($this->items) == 0;
        case 'tail':
            return count($this->items)-1;
        case 'head':
            return empty($this->items)?-1:0;
        default:
            die("wrong property: $name"); // TODO: raise exception
        }
    }
}
?>