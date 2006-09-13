<?php

	sys::import('structures.sets.collection');

	class TreeNode extends xarObject implements ITreeNode
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
		function breadthfirstenumeration()
		{
			$data = $this->tree->treedata;
			uasort($data, array($this,"comparelevels"));
			$nodeset = new BasicSet();
			foreach ($data as $datum) {
				$node = new TreeNode();
				$node->adddata($datum);
				$nodeset->add($node);
			}
			$it = $nodeset->getIterator();
			foreach($it as $val){
				echo $val->name ." ";
			}
			return $nodeset;
		}
		function depthfirstenumeration()
		{
			$data = $this->tree->treedata;
			uasort($data, array($this,"comparelevels"));
			krsort($data);
			$data1 = array();
			foreach ($data as $key => $value) {
				$children = array();
				foreach ($value['children'] as $child) {
					if (isset($data1[$child])) $children[] = array($data1[$child]);
				}
				$data1[$key] = array('id' => $key, 'children' => $children);
			}
			$nodeset = new BasicSet();
			$arrayIterator = new RecursiveArrayIterator(array_pop($data1));
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

		private function comparelevels($a, $b)
		{
		   return ($a['nodelevel'] > $b['nodelevel']);
		}
	}

	class Tree implements ITree
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
		public function adddata(Array $arr);
		public function breadthfirstenumeration();
		public function depthfirstenumeration();
		public function getChildCount();
		public function getDepth();
		public function getLevel();
	}
	interface ITree
	{
		public function getRoot();
	}
?>
