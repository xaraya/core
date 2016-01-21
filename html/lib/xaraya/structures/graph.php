<?php
/**
 * @package core
 * @subpackage structures
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 */
/**
 * This code was adapted form the PEAR Structures Graph clases
 * http://pear.php.net/package/Structures_Graph/docs/latest/li_Structures_Graph.html
 */
 
class GraphNode extends Object implements IGraphNode
{
    private $data = null;           // The data of this node
    private $metadata = array();    // The metadata of this node
    private $vertices = array();    // An array of references to the vertices this node is connected to
    private $graph = null;          // A reference to the graph this node belongs to
    private $index = null;

    public function getGraph() { return $this->graph; }
    public function setGraph(Graph $graph) { $this->graph = $graph; }
    public function getData() { return $this->data; }
    public function setData($data) { $this->data =& $data; }
    public function getIndex() { return $this->index; }
    public function setIndex($index) { $this->index =& $index; }

    public function hasIndex() 
    {
        return $this->index != 0;
    }

    public function metadataKeyExists($key) 
    {
        return array_key_exists($key, $this->metadata);
    }

    public function getMetadata($key, $nullIfNonexistent = false) 
    {
        if (array_key_exists($key, $this->metadata)) {
            return $this->metadata[$key];
        } else {
            if ($nullIfNonexistent) {
                $a = null;
                return $a;
            } else {
                throw new Exception('A metadata key does not exist');
            }
        }
    }

    public function unsetMetadata($key) 
    {
        if (array_key_exists($key, $this->metadata)) unset($this->metadata[$key]);
    }

    public function setMetadata($key, $data) 
    {
        $this->metadata[$key] =& $data;
    }

    public function _connectTo(GraphNode $node) 
    {
        $this->vertices[] = $node;
    }

    public function connectTo(GraphNode $node) 
    {
        // Nodes must already be in the graph to be connected
        if ($this->graph == null) 
            throw new Exception('Trying to connect from a node not in a graph');
        if ($node->getGraph() == null) 
            throw new Exception('Trying to connect to a node not in a graph');
        // Make the connection
        $this->_connectTo($node);
        // If graph is undirected, make the reverse connection
        if (!$this->graph->isDirected()) {
            $node->_connectTo($this);
        }
    }

    public function getNeighbours() 
    {
        return $this->vertices;
    }

    public function connectsTo(GraphNode $node) 
    {
        return in_array($node, $this->getNeighbours(), true);
    }

    public function inDegree() 
    {
        if ($this->graph == null) return 0;
        if (!$this->graph->isDirected()) return $this->outDegree();
        $result = 0;
        $graphNodes =& $this->graph->getNodes();
        foreach (array_keys($graphNodes) as $key) {
            if ($graphNodes[$key]->connectsTo($this)) $result++;
        }
        return $result;
    }

    public function outDegree() 
    {
        if ($this->graph == null) return 0;
        return sizeof($this->vertices);
    }
}

class Graph extends Object implements IGraph
{
    private $nodes = array();   // References to the nodes of this graph
    private $directed = false;  // Whether thiis a directed graph or not

    public function __construct($directed=true)
    {
        $this->directed = $directed;
    }

    public function isDirected() 
    {
        return (boolean)$this->directed;
    }

    public function addNode(GraphNode $node)
    {
        // Graphs are node *sets*, so duplicates are forbidden. We allow nodes that are exactly equal, but disallow equal references.
        foreach($this->nodes as $key => $current) {
            /*
             ZE1 equality operators choke on the recursive cycle introduced by the _graph field in the Node object.
             So, we'll check references the hard way (change $this->_nodes[$key] and check if the change reflects in 
             $node)
            */
            $savedData = $this->nodes[$key];
            $referenceIsEqualFlag = false;
            $this->nodes[$key] = true;
            if ($current === true) {
                $this->nodes[$key] = false;
                if ($current === false) $referenceIsEqualFlag = true;
            }
            $this->nodes[$key] = $savedData;
            if ($referenceIsEqualFlag) 
                throw new Exception('Trying to add a duplicate node to a graph');
        }
        $this->nodes[] = $node;
        $node->setGraph($this);
    }

    public function removeNode(GraphNode $node) { }

    public function &getNodes() 
    {
        return $this->nodes;
    }
}

class TopologicalSorter extends Object implements ITopologicalSorter
{
    static function _nonVisitedInDegree(GraphNode $node) 
    {
        $result = 0;
        $graph = $node->getGraph();
        $graphNodes =& $graph->getNodes();
        foreach (array_keys($graphNodes) as $key) {
            if ((!$graphNodes[$key]->getMetadata('topological-sort-visited')) && $graphNodes[$key]->connectsTo($node)) $result++;
        }
        return $result;
    }

    static function _sort(Graph $graph) 
    {
        // Mark every node as not visited
        $nodes =& $graph->getNodes();
        $nodeKeys = array_keys($nodes);
        $refGenerator = array();
        foreach($nodeKeys as $key) {
            $refGenerator[] = false;
            $nodes[$key]->setMetadata('topological-sort-visited', $refGenerator[sizeof($refGenerator) - 1]);
        }

        // Iteratively peel off leaf nodes
        $topologicalLevel = 0;
        do {
            // Find out which nodes are leafs (excluding visited nodes)
            $leafNodes = array();
            foreach($nodeKeys as $key) {
                if ((!$nodes[$key]->getMetadata('topological-sort-visited')) && self::_nonVisitedInDegree($nodes[$key]) == 0) {
                    $leafNodes[] =& $nodes[$key];
                }
            }
            // Mark leafs as visited
            $refGenerator[] = $topologicalLevel;
            for ($i=sizeof($leafNodes) - 1; $i>=0; $i--) {
                $visited = $leafNodes[$i]->getMetadata('topological-sort-visited');
                $visited = true;
                $leafNodes[$i]->setMetadata('topological-sort-visited', $visited);
                $leafNodes[$i]->setMetadata('topological-sort-level', $refGenerator[sizeof($refGenerator) - 1]);
            }
            $topologicalLevel++;
        } while (sizeof($leafNodes) > 0);

        // Cleanup visited marks
        foreach($nodeKeys as $key) $nodes[$key]->unsetMetadata('topological-sort-visited');
    }

    static public function sort(Graph $graph) 
    {
        self::_sort($graph);
        $result = array();
 
        // Fill in result array
        $nodes =& $graph->getNodes();
        $nodeKeys = array_keys($nodes);
        foreach($nodeKeys as $key) {
            if (!array_key_exists($nodes[$key]->getMetadata('topological-sort-level'), $result)) $result[$nodes[$key]->getMetadata('topological-sort-level')] = array();
            $result[$nodes[$key]->getMetadata('topological-sort-level')][] =& $nodes[$key];
            $nodes[$key]->unsetMetadata('topological-sort-level');
        }

        return $result;
    }
}

class AcyclicTest extends Object implements IAcyclicTest
{
    static function _nonVisitedInDegree(GraphNode $node) 
    {
        $result = 0;
        $graph = $node->getGraph();
        $graphNodes =& $graph->getNodes();

        foreach (array_keys($graphNodes) as $key) {
            if ((!$graphNodes[$key]->getMetadata('acyclic-test-visited')) && $graphNodes[$key]->connectsTo($node)) $result++;
        }
        return $result;
    }
    
    static function _isAcyclic(&$graph) 
    {
        // Mark every node as not visited
        $nodes =& $graph->getNodes();
        $nodeKeys = array_keys($nodes);
        $refGenerator = array();
        foreach($nodeKeys as $key) {
            $refGenerator[] = false;
            $nodes[$key]->setMetadata('acyclic-test-visited', $refGenerator[sizeof($refGenerator) - 1]);
        }

        do {
            // Find out which nodes are leafs (excluding visited nodes)
            $leafNodes = array();
            foreach($nodeKeys as $key) {
                if ((!$nodes[$key]->getMetadata('acyclic-test-visited')) && self::_nonVisitedInDegree($nodes[$key]) == 0) {
                    $leafNodes[] =& $nodes[$key];
                }
            }

            // Mark leafs as visited
            for ($i=sizeof($leafNodes) - 1; $i>=0; $i--) {
                $visited =& $leafNodes[$i]->getMetadata('acyclic-test-visited');
                $visited = true;
                $leafNodes[$i]->setMetadata('acyclic-test-visited', $visited);
            }

        } while (sizeof($leafNodes) > 0);
 
        // If graph is a DAG, there should be no non-visited nodes. Let's try to prove otherwise
        $result = true;
        foreach($nodeKeys as $key) if (!$nodes[$key]->getMetadata('acyclic-test-visited')) $result = false;

        // Cleanup visited marks
        foreach($nodeKeys as $key) $nodes[$key]->unsetMetadata('acyclic-test-visited');

        return $result;
    }

    static public function isAcyclic(Graph $graph) 
    {
        if (!$graph->isDirected()) return false; // Only directed graphs may be acyclic
        return self::_isAcyclic($graph);
    }
}

interface IGraphNode
{
    public function getGraph();
    public function setGraph(Graph $graph);
    public function getData();
    public function setData($data);
    public function metadataKeyExists($key);
    public function getMetadata($key, $nullIfNonexistent = false);
    public function unsetMetadata($key);
    public function setMetadata($key, $data);
    function _connectTo(GraphNode $node);
    public function connectTo(GraphNode $node);
    public function getNeighbours();
    public function connectsTo(GraphNode $node);
    public function inDegree();
    public function outDegree();
}
interface IGraph
{
    public function __construct($directed=true);
    public function isDirected();
    public function addNode(GraphNode $node);
    public function removeNode(GraphNode $node);
    public function &getNodes();
}
interface ITopologicalSorter
{
    static function _nonVisitedInDegree(GraphNode $node); 
    static function _sort(Graph $graph);
    static public function sort(Graph $graph);
}
interface IAcyclicTest
{
    static function _nonVisitedInDegree(GraphNode $node);
    static function _isAcyclic(&$graph);
    static public function isAcyclic(Graph $graph);
}
?>