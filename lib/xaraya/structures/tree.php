<?php
sys::import('xaraya.structures.sets.collection');

class TreeNode extends Object implements ITreeNode
{
    public $id;
    public $tree;

    public $parent;
    public $children;
    public $allowschildren;

    function __construct($id=0)
    {
        $this->id = $id;
    }
    function adddata(Array $arr)
    {
        foreach($arr as $key => $value) $this->{$key} = $value;
    }
    function breadthfirstenumeration($depth=null)
    {
        $data = $this->tree->treedata;
        if (empty($data)) return new BasicSet();
        uasort($data, array($this,"comparelevels"));
        var_dump($data);
        echo "<br /><br />";

        $nodeset = new BasicSet();
        foreach ($data as $value) {
            if (isset($depth) && ($value['nodelevel'] > $depth)) break;
            $node = new TreeNode();
            $node->adddata($value);
            $nodeset->add($node);
        }
        $it = $nodeset->getIterator();
        return $nodeset;
    }
    function depthfirstenumeration($depth=null)
    {
        $data = $this->tree->treedata;
        if (empty($data)) return new BasicSet();
        uasort($data, array($this,"rcomparelevels"));

        $data1 = array();
        foreach ($data as $key => $value) {
            if (isset($depth) && ($value['nodelevel'] > $depth)) continue;
            $children = array();
            foreach ($value['children'] as $child) {
                if (isset($data1[$child])) $children[] = array($data1[$child]);
            }
            $data1[$key] = array('id' => $key, 'children' => $children);
            $toplevel = $data1[$key];
        }
        $nodeset = new BasicSet();
        $arrayIterator = new RecursiveArrayIterator($toplevel);
        $iterator = new RecursiveIteratorIterator($arrayIterator);
        foreach($iterator as $value) {
            $node = new TreeNode();
            $node->adddata($data[$value]);
            $nodeset->add($node);
        }
        return $nodeset;
    }
    function getChildCount()
    {
    }
    function getDepth()
    {
    }
    function getLevel()
    {
        return $this->nodelevel;
    }
    function hash()
    {
        return $this->hashCode();
    }

    private function comparelevels($a, $b)
    {
       return ($a['nodelevel'] > $b['nodelevel']);
    }
    private function rcomparelevels($a, $b)
    {
       return ($a['nodelevel'] < $b['nodelevel']);
    }
}

class Tree extends Object implements ITree
{
    public $root;
    public $asksallowschildren;

    public $treedata;

    function __construct(TreeNode $root=null)
    {
        if(!isset($root)) {
            $this->root = $root;
        }
        $root->tree = $this;
        $this->createNodes($root);
    }
    function getRoot()
    {
        return $this->root;
    }
    protected function createNodes(TreeNode $node)
    {
        $inputdata = $this->treedata;
        $tempdata = $this->treedata;
        $this->treedata = array();
        $lastidsdone = array();
        if (!is_object($inputdata)) $inputdata = new ArrayObject($inputdata);

        // Get the toplevel elements..can there be more than 1?
        for($iterator = $inputdata->getIterator();$iterator->valid();$iterator->next()) {           ;
            $thiskey = $iterator->key();
            $thisvalue = $iterator->current();
            if ($thisvalue['id'] == $node->id) {
                $thisvalue['nodelevel'] = 0;
                $thisvalue['children'] = array();
                $this->treedata[$node->id] = $thisvalue;
                $lastidsdone[] = $thisvalue['id'];
                unset($tempdata[$thiskey]);
            }
        }

        // Now do the other elements
        $lastcount = count($tempdata);
        $nodelevel = 0;
        while (true) {
            $thisidsdone = array();
            $nodelevel += 1;
            $inputdata = new ArrayObject($tempdata);
            for($iterator = $inputdata->getIterator();$iterator->valid();$iterator->next()) {           ;
                $thiskey = $iterator->key();
                $thisvalue = $iterator->current();
                if (in_array($thisvalue['parent'],$lastidsdone)) {
                    $thisvalue['nodelevel'] = $nodelevel;
                    $thisvalue['children'] = array();
                    $this->treedata[$thisvalue['id']] = $thisvalue;
                    $this->treedata[$thisvalue['parent']]['children'][] = $thisvalue['id'];
                    $thisidsdone[] = $thisvalue['id'];
                    unset($tempdata[$thiskey]);
                }
            }
            $lastidsdone = $thisidsdone;

            // Bail if we haven't removed any elements, or if there's nothing left to look at
            $thiscount = count($tempdata);
            if (($lastcount == $thiscount) || ($thiscount == 0)) {
                break;
            } else {
                $lastcount = $thiscount;
            }
        }
    }
}

interface ITreeNode
{
    public function adddata(array $arr);
    public function breadthfirstenumeration($arg=null);
    public function depthfirstenumeration($arg=null);
    public function getChildCount();
    public function getDepth();
    public function getLevel();
}
interface ITree
{
    public function getRoot();
}
?>
