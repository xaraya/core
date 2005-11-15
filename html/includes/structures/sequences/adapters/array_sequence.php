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
    private $items = array();

    // iSequenceAdapter implementation
    public function head() 
    {
        return $this->empty ? -1: 0;
    }
    public function tail()
    {
        return $this->size - 1;
    }
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
        switch($position) {
        case $this->head():
            array_unshift($this->items,$item);
            break;
        case $this->tail():
            array_push($this->items, $item);
            break;
        default:
            // TODO: insert at position $position
        }
        return true;
    }
    // Delete an item from the specified position
    public function delete($position)
    {
        switch($position) {
        case $this->tail():
            $item = array_shift($this->items);
            break;
        case $this->head():
            $item = array_pop($this->items);
            break;
        default:
            //TODO: delete at position $position
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
        }
    }
}
?>