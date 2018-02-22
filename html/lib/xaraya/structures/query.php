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
  * Query class for SQL abstraction
  * 
  */

class Query
{
    public $version             = "3.6";
    public $id;                                 // A unique identifier for this query
    public $type                = 'SELECT';     // Normalized array of tables used in the statement
    public $tables              = array();      // Normalized array of tables used in the statement
    public $tablelinks          = array();      // Normalized array of table links used in the statement
    public $fields              = array();      // Normalized array of fields used in the statement
    public $primary;
    public $conditions          = array();      // Normalized array of conditions used in the statement
    public $conjunctions        = array();      // Normalized array of conjunctions used with conditions used in the statement
    public $sorts               = array();      // Aarray of fields used in the sort clause of the statement
    public $groups              = array();      // Normalized array of fields used in the group clause of the statement
    public $having              = array();      // Normalized array of fields used in the having clause of the statement
    public $result              = array();      // Holds the result object of a query
    public $bindvars            = array();      // An array of bindvars in this statement
    public $rows                = 0;
    public $rowfields           = 0;
    public $rowstodo            = 0;            // Holds the number of rows to return in a SELECT query
    public $startat             = 1;            // Holds the first record number to return in a SELECT query
    public $createtablename;
    public $affected            = 0;            
    public $output              = array();      // Holds multiple rows of results
    public $row                 = array();      // Holds a single row of results
    public $dbconn;                             // Holds the conection object to the database
    public $statement;                          // Holds the statement being processed/executed
    public $israwstatement = 0;                 // Flag that indicates whether the statement to be processed was entered as a string
    public $bindstring;
    public $limits              = true;         // Flag that indicates whether the (SELECT) query uses limits
    public $optimize            = true;         // Flag that indicates whether the query will be optimized
    public $distinctselect = false;
    public $distinctarray = array();

    private $starttime;
    private $key = 0;                           // Unique key for this statement (used in nested conditions)
    
// Flags
// Set to true to use binding variables supported by some dbs
    public $usebinding = true;
// Two unrelated conditions will be inserted into the query as AND or OR
    public $implicitconjunction = "AND";
// Use JOIN...ON.. syntax (automatic for left or right joins)
    public $on_syntax = false;
// Before each statement executed, echo the SQL statement
    public $debugflag = false;
// Operator syntax
    public $eqoperator = '=';
    public $neoperator = '!=';
    public $gtoperator = '>';
    public $geoperator = '>=';
    public $ltoperator = '<';
    public $leoperator = '<=';
    public $andoperator = 'AND';
    public $oroperator = 'OR';
//---------------------------------------------------------
// Constructor
//---------------------------------------------------------
    public function __construct($type='SELECT',$tables='',$fields='')
    {
        if (in_array($type,array("SELECT","INSERT","UPDATE","DELETE","DROP"))) $this->type = $type;
        else {
            throw new ForbiddenOperationException($type,'This operation is not supported yet. "#(1)"');
        }
        if ($type != "SELECT" && is_array($tables) && count($tables) > 1) {
            $msg = xarML('The type #(1) can only take  a single table name', $type);
            throw new BadParameterException(null,$msg);
        }

        $this->addtables($tables);
        $this->addfields($fields);
        
        // Create the ID for this query
        $this->id = $this->createID();
    }

    public function run($statement='',$display=1)
    {
        if ($this->debugflag) $querystart = microtime(true);

        if (!isset($this->dbconn)) $this->dbconn = xarDB::getConn();
        if ($this->debugflag && (xarSystemVars::get(sys::CONFIG, 'DB.Middleware') == 'PDO')) {
            $this->dbconn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        if (empty($statement) && ($this->optimize == true)) $this->optimize();

        $this->setstatement($statement);

        if ($this->israwstatement) {
            $result = $this->dbconn->Execute($this->statement);
            // If this is not a SELECT exit here
            if (!is_object($result)) return $result;
        } else {
            // Special case for multitable inserts
            if ($this->type == 'INSERT' && count($this->tables) > 1) {
                if (empty($this->primary))
                    throw new Exception(xarML('Cannot execute a multitable insert without a primary field defined'));
                try {
                    $this->multiinsert(); 
                } catch (Exception $e) {throw $e;}
                return true;
            }

            if ($this->type != 'SELECT') {
                if ($this->usebinding) {
                    $result = $this->dbconn->Execute($this->statement,$this->bindvars);
                    $this->bindvars = array();
                } else {
                    $result = $this->dbconn->Execute($this->statement);
                }
                $this->affected = $this->dbconn->getUpdateCount();
                return $result;
            }
            // This is a select
            if (xarSystemVars::get(sys::CONFIG, 'DB.Middleware') == 'PDO') {
                if($this->rowstodo != 0 && $this->limits == 1) {
                    $begin = $this->startat-1;
                    if ($this->usebinding) {
                        $result = $this->dbconn->SelectLimit($this->statement,$this->rowstodo,$begin,$this->bindvars, PDO::FETCH_ASSOC);
                    }
                    else {
                        $result = $this->dbconn->SelectLimit($this->statement,$this->rowstodo,$begin, PDO::FETCH_ASSOC);
                    }
                } else {
                    if ($this->usebinding) {
//                        $result = $this->dbconn->Execute($this->statement,$this->bindvars);
                        $stmt = $this->dbconn->prepareStatement($this->statement);
                        $result = $stmt->executeQuery($this->bindvars, PDO::FETCH_ASSOC);
                    } else {
                        $result = $this->dbconn->Execute($this->statement);
                    }
                }                
            } else {
                if($this->rowstodo != 0 && $this->limits == 1) {
                    $begin = $this->startat-1;
                    if ($this->usebinding) {
                        $result = $this->dbconn->SelectLimit($this->statement,$this->rowstodo,$begin,$this->bindvars);
                    }
                    else {
                        $result = $this->dbconn->SelectLimit($this->statement,$this->rowstodo,$begin);
                    }
                } else {
                    if ($this->usebinding) {
                        $result = $this->dbconn->Execute($this->statement,$this->bindvars);
                        //$stmt = $this->dbconn->prepareStatement($this->statement);
                        //$result = $stmt->executeQuery($this->bindvars);
                    } else {
                        $result = $this->dbconn->Execute($this->statement);
                    }
                }
            }
        }

        if ($this->debugflag) $loopstart = microtime(true);
        if (!$result) return;
        $this->result =& $result;

        if ($result->fields === false)
            $numfields = 0;
        else
            $numfields = count($result->fields); // Better than the private var, fields should still be protected
        $this->output = array();
        if ($display == 1) {
            if (xarSystemVars::get(sys::CONFIG, 'DB.Middleware') == 'PDO') {
                $this->output = $result->getall();
            } else {
                if (!$this->israwstatement) {
                    if ($this->fields == array() && $numfields > 0) {
                        $result->setFetchMode(ResultSet::FETCHMODE_ASSOC);
                        $result->next(); $result->previous();
                        for ($i=0;$i< $numfields;$i++) {
                            $tmp = array_slice($result->fields,$i,1);
                            $namefield  = key($tmp);
                            $this->fields[$namefield]['name'] = strtolower($namefield);
                        }
                        $result->setFetchMode(ResultSet::FETCHMODE_NUM);
                        $result->next(); $result->previous();
                    }
                    while (!$result->EOF) {
                        $i=0; $line=array();
                        foreach ($this->fields as $key => $value ) {
                            if(!empty($value['alias']))
                                $line[$value['alias']] = $result->fields[$i];
                            elseif(!empty($value['name']))
                                $line[$value['name']] = $result->fields[$i];
                            else
                                $line[] = $result->fields[$i];
                            $i++;
                        }
                        $this->output[] = $line;
                        $result->MoveNext();
                    }
                } else {
                    while (!$result->EOF) {
                        $this->output[] = $result->fields;
                        $result->MoveNext();
                    }
                }
            }
        }
        if ($this->debugflag) {
            $assembletime = $querystart - $this->starttime;
            $querytime = $loopstart - $querystart;
            $looptime = microtime(true) - $loopstart;
            echo $this->qecho($statement);echo "<br />";
            echo "Assemble: " . $assembletime . "    Query: " . $querytime . "   Loops: " . $looptime . "<br />";
        }
        return true;
    }

    public function close()
    {
        return $this->dbconn->close();
    }

    public function open()
    {
        $this->openconnection(xarDB::getConn());
    }

    public function uselimits()
    {
        $this->limits = 1;
    }

    public function nolimits()
    {
        $this->limits = 0;
    }

    public function row($row=0)
    {
        if ($this->output == array()) return array();
        return $this->output[$row];
    }

    public function flatrow($row=0)
    {
        if ($this->output == array()) return false;
        return array_values($this->output[$row]);
    }

    public function output()
    {
        return $this->output;
    }

    public function affected()
    {
        if ($this->type == 'SELECT') {
            return count($this->output());
        } else {
            return $this->affected;
        }
    }

    public function drop($tables=null)
    {
        $this->settype("DROP");
        if (isset($tables)) $this->addtables($tables);
        return true;
    }

    public function createto($newtablename=null)
    {
        if (!isset($newtablename)) $newtablename = "temp" . xarSession::getVar('role_id') . time();
        $this->createtablename = $newtablename;
        $this->settype("CREATE");
        return true;
    }

    public function addtable()
    {
        $numargs = func_num_args();
        if ($numargs == 2) {
            $name = func_get_arg(0);
            $alias = func_get_arg(1);
            $argsarray = array('name' => $name, 'alias' => $alias);
        }
        elseif ($numargs == 1) {
            $table = func_get_arg(0);
            if (!is_array($table)) {
                if (!is_string($table)) {
                    throw new VariableValidationException(array('table',$table,'must be string or array'));
                }
                else {
                    $newtable = explode(' ',$table);
                    if (count($newtable) > 1) {
                        $argsarray = array('name' => trim($newtable[0]), 'alias' => trim($newtable[1]));
                    }
                    else {
                        $argsarray = array('name' => trim($newtable[0]), 'alias' => '');
                    }
                }
            }
            else {
                $argsarray = $table;
            }
        }
        else throw new BadParameterException(null, xarML('This function can only take 1 or 2 parameters'));

        $notdone = true;
        $limit = count($this->tables);
        for ($i=0;$i<$limit;$i++) {
            if ($this->tables[$i]['name'] == $argsarray['name'] &&
                $this->tables[$i]['alias'] == $argsarray['alias']) {
                $this->tables[$i] = $argsarray;
                $notdone = false;
                break;
            }
        }
        if ($notdone) $this->tables[] = $argsarray;

        // Update the ID for this query
        $this->id = $this->createID();
    }

    public function addfield()
    {
        $numargs = func_num_args();
        if ($numargs == 2) {
            $name = func_get_arg(0);
            $argsarray = $this->_deconstructfield($name);
            $argsarray['value'] = func_get_arg(1);
        } elseif ($numargs == 1) {
            $field = func_get_arg(0);
            if (!is_array($field)) {
                if (!is_string($field))
                    throw new BadParameterException($field,'The field #(1) you are trying to add needs to be a string or an array.');
                else {
                    if ($this->type == 'SELECT') {
                        $field = $this->_deconstructfield($field);
                        $argsarray = $field;
                    } else {
                        $newfield = explode('=',$field);
                        if (!isset($newfield[1])) throw new Exception(xarML("The field #(1) needs to have a value", $newfield[0]));
                        $argsarray = $this->_deconstructfield(trim($newfield[0]));
                        $argsarray['value'] = trim($newfield[1]);
                    }
                }
            } else {
                $argsarray = $field;
            }
        }
        else throw new BadParameterException(null, xarML('This function can only take 1 or 2 parameters'));

        $done = false;
        foreach ($this->fields as $key => $field) {
            // if we already have this field , bail
            if ($this->fields[$key] == $argsarray) {$done = true; break;}
            
            // If at least the name and table are identical, we might still be able to add alias info
            if ($this->fields[$key]['name'] == $argsarray['name'] && isset($this->fields[$key]['table']) && $this->fields[$key]['table'] == $argsarray['table']) {
                if (isset($argsarray['alias'])) {
                    $this->fields[$key]['alias'] = $argsarray['alias'];                
                }
                if (isset($argsarray['value'])) $this->fields[$key]['value'] = $argsarray['value'];
                $done = true;
                break;
            }
        }
        if (!$done) $this->fields[] = $argsarray;
    }

    public function addfields($fields)
    {
        if (!is_array($fields)) {
            if (!is_string($fields)) {
            //error msg
            } else {

                if ($fields != '') {
                    $newfields = explode(',',$fields);
                    foreach ($newfields as $field) {
                        $field = $this->_deconstructfield($field);
                        $this->addfield($field);
                    }
                }
            }
        } else {
            if ($this->type == 'SELECT') {
                foreach ($fields as $field) {
                    $field = $this->_deconstructfield($field);
                    $this->addfield($field);
                }
            } else {
                foreach ($fields as $field) $this->addfield($field);
            }
        }
    }

    public function addtables($tables)
    {
        if (!is_array($tables)) {
            if (!is_string($tables)) {
            //error msg
            }
            elseif ($tables=='') {}//error msg
            else {$this->addtable($tables);}
        }
        else {
            foreach ($tables as $table) $this->addtable($table);
        }
    }

    public function addtablelink(Array $args=array())
    {
        $key = $this->key;
        $this->key++;
        $numargs = func_num_args();
        $link = func_get_arg(0);
        if ($numargs == 2) {
            $this->tablelinks[$key]=array('field1' => $link['field1'],
                                      'field2' => $link['field2'],
                                      'op' => $link['op']);
        }
        elseif ($numargs == 4) {
            $this->tablelinks[$key]=array('field1' => func_get_arg(0) . "." . func_get_arg(1),
                                      'field2' => func_get_arg(2) . "." . func_get_arg(3),
                                      'op' => 'JOIN');
        }

        // Update the ID for this query
        $this->id = $this->createID();

        return $key;
    }
    public function addhaving(Array $args=array())
    {
        $key = $this->key;
        $this->key++;
        $numargs = func_num_args();
        $having = func_get_arg(0);
        if ($numargs == 1) {
            $this->having[$key] = $having;
        } else {
        // error msg
        }
        return true;
    }
    public function join($field1,$field2,$active=1)
    {
        $op = $this->on_syntax ? 'INNER JOIN' : 'JOIN';
        return $this->addtablelink(array('field1' => $field1,
                                  'field2' => $field2,
                                  'op' => $op),$active);
    }
    public function leftjoin($field1,$field2,$active=1)
    {
        return $this->addtablelink(array('field1' => $field1,
                                  'field2' => $field2,
                                  'op' => 'LEFT JOIN'),$active);
    }
    public function rightjoin($field1,$field2,$active=1)
    {
        return $this->addtablelink(array('field1' => $field1,
                                  'field2' => $field2,
                                  'op' => 'RIGHT JOIN'),$active);
    }
    public function having($expression, $conjunction='')
    {
        if ($conjunction == '') $conjunction = $this->implicitconjunction;
        return $this->addhaving(array('expression' => $expression,
                                  'conjunction' => $conjunction));
    }
    public function eq($field1,$field2,$active=1)
    {
        return $this->addcondition(array('field1' => $field1,
                                  'field2' => $field2,
                                  'op' => $this->eqoperator),$active);
    }
    public function ne($field1,$field2,$active=1)
    {
        return $this->addcondition(array('field1' => $field1,
                                  'field2' => $field2,
                                  'op' => $this->neoperator),$active);
    }
    public function gt($field1,$field2,$active=1)
    {
        return $this->addcondition(array('field1' => $field1,
                                  'field2' => $field2,
                                  'op' => $this->gtoperator),$active);
    }
    public function ge($field1,$field2,$active=1)
    {
        return $this->addcondition(array('field1' => $field1,
                                  'field2' => $field2,
                                  'op' => $this->geoperator),$active);
    }
    public function le($field1,$field2,$active=1)
    {
        return $this->addcondition(array('field1' => $field1,
                                  'field2' => $field2,
                                  'op' => $this->leoperator),$active);
    }
    public function lt($field1,$field2,$active=1)
    {
        return $this->addcondition(array('field1' => $field1,
                                  'field2' => $field2,
                                  'op' => $this->ltoperator),$active);
    }
    public function like($field1,$field2,$active=1)
    {
        return $this->addcondition(array('field1' => $field1,
                                  'field2' => $field2,
                                  'op' => 'LIKE'),$active);
    }
    public function notlike($field1,$field2,$active=1)
    {
        return $this->addcondition(array('field1' => $field1,
                                  'field2' => $field2,
                                  'op' => 'NOT LIKE'),$active);
    }
    public function in($field1,$field2,$active=1)
    {
        return $this->addcondition(array('field1' => $field1,
                                  'field2' => $field2,
                                  'op' => 'IN'),$active);
    }
    public function notin($field1,$field2,$active=1)
    {
        return $this->addcondition(array('field1' => $field1,
                                  'field2' => $field2,
                                  'op' => 'NOT IN'),$active);
    }
    public function regex($field1,$field2,$active=1)
    {
        return $this->addcondition(array('field1' => $field1,
                                  'field2' => $field2,
                                  'op' => 'REGEXP'),$active);
    }
    public function between($field1,$field2,$active=1)
    {
        return $this->addcondition(array('field1' => $field1,
                                  'field2' => $field2,
                                  'op' => 'BETWEEN'),$active);
    }

    public function peq($field1,$field2)      {return $this->eq($field1,$field2,0);}
    public function pne($field1,$field2)      {return $this->ne($field1,$field2,0);}
    public function pgt($field1,$field2)      {return $this->gt($field1,$field2,0);}
    public function pge($field1,$field2)      {return $this->ge($field1,$field2,0);}
    public function plt($field1,$field2)      {return $this->lt($field1,$field2,0);}
    public function ple($field1,$field2)      {return $this->le($field1,$field2,0);}
    public function plike($field1,$field2)    {return $this->like($field1,$field2,0);}
    public function pnotlike($field1,$field2) {return $this->notlike($field1,$field2,0);}
    public function pin($field1,$field2)      {return $this->in($field1,$field2,0);}
    public function pnotin($field1,$field2)   {return $this->notin($field1,$field2,0);}
    public function pregex($field1,$field2)   {return $this->regex($field1,$field2,0);}

   public function qand()
    {
        $numargs = func_num_args();
        if ($numargs == 2) {
        } elseif ($numargs == 1) {
            $field = func_get_arg(0);
            if ($field == array()) return true;
            $key = $this->_addcondition(1);
            $this->conjunctions[$key] = array('conditions' => $field,
                                             'conj' => $this->andoperator,
                                             'active' => 1);
            if (!is_array($field)) $field = array($field);
            
            // Make the conjunctions below ths one inactive, so as to avoid writng them twice
            foreach ($field as $condition) {
                if (isset($this->conjunctions[$condition])) $this->conjunctions[$condition]['active'] = 0;
            }
        }
        return $key;
    }
    public function qor()
    {
        $numargs = func_num_args();
        if ($numargs == 2) {
        } elseif ($numargs == 1) {
            $field = func_get_arg(0);
            if ($field == array()) return true;
            $key = $this->_addcondition(1);
            $this->conjunctions[$key] = array('conditions' => $field,
                                             'conj' => $this->oroperator,
                                             'active' => 1);
            if (!is_array($field)) $field = array($field);

            // Make the conjunctions below ths one inactive, so as to avoid writng them twice
            foreach ($field as $condition) {
                if (isset($this->conjunctions[$condition])) $this->conjunctions[$condition]['active'] = 0;
            }
        }
        return $key;
    }
    public function pqand()
    {
        $key = $this->_addcondition(0);
        $numargs = func_num_args();
        if ($numargs == 2) {
        } elseif ($numargs == 1) {
            $field = func_get_arg(0);
            $this->conjunctions[$key] = array('conditions' => $field,
                                             'conj' => $this->andoperator,
                                             'active' => 0);
            if (!is_array($field)) $field = array($field);
        }
        return $key;
    }
    public function pqor()
    {
        $key = $this->_addcondition(0);
        $numargs = func_num_args();
        if ($numargs == 2) {
        } elseif ($numargs == 1) {
            $field = func_get_arg(0);
            $this->conjunctions[$key] = array('conditions' => $field,
                                             'conj' => $this->oroperator,
                                             'active' => 0);
            if (!is_array($field)) $field = array($field);
        }
        return $key;
    }
    public function addorders($sorts)
    {
        if (!is_array($sorts)) {
            if (!is_string($sorts)) {
            //error msg
            }
            elseif ($sorts=='') {}//error msg
            else {$this->sorts[]= array('name' => $sorts,
                                        'order' => '');}
        }
        else {
            foreach ($sorts as $sort) {
                if (is_array($sort)) $this->sorts[] = array('name' => $sort['name'],
                                                            'order' => $sort['order']);
            }
        }
    }
    public function getfield($myfield)
    {
        foreach ($this->fields as $field)
            if ($field['name'] == $myfield) return $field['value'];
        return '';
    }
    public function removefield($myfield)
    {
        for($i=0;$i<count($this->fields);$i++)
            if ($this->fields[$i]['name'] == $myfield) {
                unset($this->fields[$i]);
                break;
            }
    }
    public function setalias($name='',$alias='')
    {
        if($name == '' || $alias == '') return false;
        for($i=0;$i<count($this->tables);$i++) {
            if ($this->tables[$i]['name'] == $name) {
                $this->tables[$i]['alias'] = $alias;
                return true;
            }
        }
        return false;
    }
    public function getcondition($mycondition)
    {
        foreach ($this->conditions as $condition)
            if ($condition['field1'] == $mycondition) return $condition['field2'];
        return '';
    }
    public function removecondition($mycondition)
    {
        foreach($this->conditions as $key => $value)
            if ($value['field1'] == $mycondition) {
                unset($this->conditions[$key]);
                unset($this->conjunctions[$key]);
                break;
            }
        // Update the ID for this query
        $this->id = $this->createID();
    }

    public function addsecuritycheck(Array $args=array())
    {
        $numargs = func_num_args();
        if ($numargs == 2) {
            $fields = func_get_arg(0);
            if (is_string($fields)) $fields = array($fields);
            $conditions = func_get_arg(1);
            if (isset($conditions['deny'])) {
                foreach ($conditions['deny'] as $condition) {
                    $limit = count($condition);
                    if (count($fields) != count($condition)) {
                        $msg = xarML('Cannot match #(1) fields with #(2) conditions in addsecuritycheck().', count($fields), $limit);
                        throw new BadParameterException(null,$msg);
                    }
                    for ($i=0;$i<$limit;$i++) $this->ne($fields[$i],$condition[$i]);
                }
            }
        } else {
            $msg = xarML('The addsecuritycheck method can only take 2 parameters');
            throw new BadParameterException(null,$msg);
        }
    }

    public function addcondition($x,$active=1)
    {
        // if we already have this conditio registered, just return its key
        foreach($this->conditions as $key => $value)
            if ($value === $x) return $key;

        // This is a new condition: get a new key value
        $key = $this->_getkey(); 
        
        // Create a conjunction to hold the condition       
        $this->conjunctions[$key]=array('conditions' => $key,
                                        'conj' => 'IMPLICIT',
                                        'active' => $active);
                                        
        // Add the condition to the array of conditions
        $this->conditions[$key] = $x;

        // Update the ID for this query
        $this->id = $this->createID();

        return $key;
    }

/*
// ------ Private methods --------------------------------------------------------
*/
    private function _getbinding($key)
    {
        if (!isset($this->dbconn)) $this->dbconn = xarDB::getConn();
        $binding = $this->binding[$key];
        if (!is_numeric($binding['field2']) && !mb_eregi('JOIN', $binding['op'])) {
            $sqlfield = $this->dbconn->qstr($binding['field2']);
        }
        else {
            $sqlfield = $condition['field2'];
            $binding['op'] = mb_eregi('JOIN', $binding['op']) ? '=' : $binding['op'];
        }
        return $binding['field1'] . " " . $binding['op'] . " " . $sqlfield;
    }

    private function _getcondition($key)
    {
        if (!isset($this->dbconn)) $this->dbconn = xarDB::getConn();
        $condition = $this->conditions[$key];

        if (!isset($condition['field2']) || $condition['field2'] === 'NULL') {
            if ($condition['op'] == '=') return $condition['field1'] . " IS NULL";
            if ($condition['op'] == '!=') return $condition['field1'] . " IS NOT NULL";
        }

        $expression_flag = !is_array($condition['field2']) && strtolower(substr($condition['field2'],0,5)) == 'expr:';
        
        if (!$expression_flag && in_array(strtolower($condition['op']),array('in','not in'))) {
            if (is_array($condition['field2'])) {
                $elements = array();
                if ($this->usebinding) {
                    foreach ($condition['field2'] as $element) {
                        $this->bindvars[] = $element;
                        $elements[] = '?';
                    }
                } else {
                    foreach ($condition['field2'] as $element) {
                        $elements[] = is_numeric($element) ? $element : "'" . $element . "'";
                    }
                }

                $sqlfield = '(' . implode(',',$elements) . ')';
            } else {
                $sqlfield = '(' . $condition['field2'] . ')';
            }
        } elseif (!$expression_flag && in_array(strtolower($condition['op']),array('between'))) {
            if (is_array($condition['field2'])) {
                $elements = array();
                if ($this->usebinding) {
                    foreach ($condition['field2'] as $element) {
                        $this->bindvars[] = $element;
                        $elements[] = '?';
                    }
                } else {
                    foreach ($condition['field2'] as $element) {
                        if (!is_numeric($element)) {
                            $elements[] = $this->dbconn->qstr($element);
                        }
                        else {
                            $elements[] = $element;
                        }
                    }
                }

                $sqlfield = $elements[0] . ' AND ' . $elements[1];
            } else {
                throw new Exception(xarML('Improper syntax for BETWEEN'));
            }
        } else {
            if ($expression_flag) {
                $condition['field2'] = trim(substr($condition['field2'],5));
                $sqlfield = $condition['field2'];
            } elseif (!is_numeric($condition['field2']) && !mb_eregi('JOIN', $condition['op'])) {
                if ($this->usebinding) {
                    $this->bindvars[] = $condition['field2'];
                    $sqlfield = '?';
                } else {
                    $sqlfield = $this->dbconn->qstr($condition['field2']);
                }
            } else {
                if ($this->usebinding && !mb_eregi('JOIN', $condition['op'])) {
                    $this->bindvars[] = $condition['field2'];
                    $sqlfield = '?';
                } else {
                    $sqlfield = $condition['field2'];
                }
                $condition['op'] = mb_eregi('JOIN', $condition['op']) ? '=' : $condition['op'];
            }
        }
        switch ($this->type) {
            case "SELECT" :
                $field = $condition['field1'];
                break;
            case "INSERT" :
            case "UPDATE" :
            case "DELETE" :
                $parts = explode('.',$condition['field1']);
                $field = isset($parts[1]) ? $parts[1] : $parts[0];
                $field = $condition['field1'];
                break;
        }
        return $field . " " . $condition['op'] . " " . $sqlfield;
    }

    private function _getconditions()
    {
       $this->cstring = "";
       $i = 0;
       $limit = count($this->conjunctions);
       foreach ($this->conjunctions as $conjunction) {
            $i++;
            if ($conjunction['active']) {
                $this->_resolve($conjunction,1);
                if ($i != $limit)
                    $this->cstring .= $this->implicitconjunction . " ";
            }
        }
        $this->cstring = trim($this->cstring);
        return $this->cstring;
    }

    private function _resolve($conjunction,$level)
    {
        if (is_array($conjunction['conditions'])) {
            $this->cstring .= "(";
            $count = count($conjunction['conditions']);
            $i=0;
            foreach ($conjunction['conditions'] as $condition) {
                $i++;
                if (isset($this->conjunctions[$condition])) {
                    $this->_resolve($this->conjunctions[$condition],$level+1);
                } else {
                    $this->cstring .= $this->_getcondition($condition);
                }
                if ($i<$count) $this->cstring .= $conjunction['conj'] . " ";
            }
            $this->cstring = trim($this->cstring) . ")";
        } else {
            $this->cstring .= $this->_getcondition($conjunction['conditions']);
        }
        $this->cstring .= " ";
    }

    private function _addcondition($active=1)
    {
        $key = $this->_getkey();
        $this->conjunctions[$key]=array('conditions' => $key,
                                        'conj' => 'IMPLICIT',
                                        'active' => $active);
        // Update the ID for this query
        $this->id = $this->createID();

        return $key;
    }

    function __sleep()
    {
        // Return array of variables to be serialized.
        $vars = array_keys(get_object_vars($this));

        // Strip out the variables we don't want serialized, but don't
        // destroy anything yet, as this object may still be needed.
        foreach(array('dbconn', 'result', 'output') as $var) {
            if (($key = array_search($var, $vars)) !== FALSE) {
                unset($vars[$key]);
            }
        }
        return($vars);
    }

    function __wakeup()
    {
        $this->openconnection();
    }

    private function _getkey()
    {
        $key = $this->key;
        $this->key++;
        return $key;
    }

    private function _statement()
    {
        $this->bindvars = array();
        $st =  $this->type . " ";
        switch ($this->type) {
        case "SELECT" :
            $st .= $this->assembledfields("SELECT");
            $st .= " FROM ";
            $st .= $this->assembledtables();
            $st .= $this->assembledconditions();
            $st .= $this->assembledgroups();
            $st .= $this->assembledhaving();
            $st .= $this->assembledsorts();
            break;
        case "INSERT" :
            $st .= "INTO ";
            $st .= $this->assembledtables();
            $st .= $this->assembledfields("INSERT");
            $st .= $this->assembledconditions();
            break;
        case "UPDATE" :
            $st .= $this->assembledtables();
            $st .= " SET ";
            $st .= $this->assembledfields("UPDATE");
            $st .= $this->assembledconditions();
            break;
        case "DELETE" :
            $st .= $this->assembledaliases();
            $st .= " FROM ";
            $st .= $this->assembledtables();
            $st .= $this->assembledconditions();
            break;
        case "CREATE" :
            $this->setstatement();
            $st = "CREATE TABLE " . $this->createtablename . " AS " . $this->getstatement();;
        case "DROP" :
            $st .= "TABLE " . $this->assembledtables();
        default :
        }
        return $st;
    }

    private function assembledaliases()
    {
        $t = '';
        foreach ($this->tables as $table) {
            if (is_array($table)) {
                $t .= $table['alias'] . ", ";
            }
            else {
                $t .= $table . ", ";
            }
        }
        if ($t != "") $t = trim($t," ,");
        return $t;
    }

    private function assembledtables()
    {
        foreach ($this->tablelinks as $link) {
            if ($link['op'] == 'LEFT JOIN' || $link['op'] == 'RIGHT JOIN') {
                $this->on_syntax = true;
                break;
            }
        }
        $links = array();
        if ($this->on_syntax) {
            foreach ($this->tablelinks as $link) {
                if ($link['op'] == 'JOIN') $link['op'] = 'INNER JOIN';
                $links[] = $link;
            }
        } else {
            foreach ($this->tablelinks as $link) {
                if ($link['op'] == 'INNER JOIN') $link['op'] = 'JOIN';
                $links[] = $link;
            }
        }
        $this->tablelinks = $links;
        if (count($this->tables) == 0) return "*MISSING*";
        $t = '';
        if ($this->on_syntax && count($this->tables) > 1) {
            $t .= $this->assembledtablelinks();
        } else {
            foreach ($this->tables as $table) {
                if (is_array($table)) {
                    switch ($this->type) {
                        case "SELECT" :
                            if (empty($table['alias'])) $t .= $table['name'] . ", ";
                            else $t .= $table['name'] . " AS " . $table['alias'] . ", ";
                            break;
                        case "INSERT" :
                            $t .= $table['name'] . " ";
                            break;
                        case "UPDATE" :
                        case "DELETE" :
                            if (empty($table['alias'])) $t .= $table['name'] . ", ";
                            else $t .= $table['name'] . " AS " . $table['alias'] . ", ";
                            break;
                    }
                } else {
                    $t .= $table . ", ";
                }
            }
        }
        if ($t != "") $t = trim($t," ,");
        return $t;
    }

    private function assembledtablelinks()
    {
//FIXME: bug if two joins are between the same tables
        // We begin with no tables yet processed and an empty query string
        $tablesdone = array();
        $string = '';
        
        // Resort the links. 
        // Each successive link needs to add exactly one new table to the query string

        // Create a stack and load it
        sys::import('xaraya.structures.sequences.stack');
        $stack = new Stack();
        $stack->load($this->tablelinks);
        
        // Create a second stack for rejected links
        $stack1 = new Stack();

        // Process the stack
        $sortedlinks = array();
        while(1) {
            // If the stack is empty then bail
            if ($stack->size == 0) break;
            
            //Get the next link
            $nextlink = $stack->pop();

            // Get the names of the fields on either side of the link
            $fullfield1 = $this->_deconstructfield($nextlink['field1']);
            $fullfield2 = $this->_deconstructfield($nextlink['field2']);

            // Get the short names of the tables
            $name1 = $this->_gettablenamefromalias($fullfield1['table']);
            $name2 = $this->_gettablenamefromalias($fullfield2['table']);
           
            if (!empty($tablesdone) && !isset($tablesdone[$name1]) && !isset($tablesdone[$name2])) {
                // Neither of the tables has been processed; put aside for now
                $stack1->push($nextkey);
            } else {
           
                // At least one of the tables has been processed; accept this link
                $sortedlinks[] = $nextlink;
                
                // Move the previously rejected keys back to the stack
                $size = $stack1->size;
                for ($i=1;$i<=$size;$i++) {
                    $item = $stack1->pop();
                    $stack->push($item);
                }

                // Add the table names to the list of tables processed
                $tablesdone[$name1] = $name1;
                $tablesdone[$name2] = $name2;
            }
        }
        
        // Sanity check
        if (count($sortedlinks) != count($this->tablelinks)) {
            throw new Exception(xarML('Incorrect reordering of query links'));
        }
            
        $this->tablelinks = $sortedlinks;
                
        // Process each of the links in turn
        $tablesdone = array();
        $count = count($this->tablelinks);
        for ($i=1;$i<$count;$i++) $string .= '(';
        $i = 1;
        foreach ($this->tablelinks as $link) {
        
            // Get the names of the fields on either side of the link
            $fullfield1 = $this->_deconstructfield($link['field1']);
            $fullfield2 = $this->_deconstructfield($link['field2']);
            
            // Get the short names of the tables
            $name1 = $this->_gettablenamefromalias($fullfield1['table']);
            $name2 = $this->_gettablenamefromalias($fullfield2['table']);
            
            // If either of the tables has not yet been processed, add them to the query string
            // N.B.: unless we are just starting, maximum one of the two tables will be added
            if (isset($tablesdone[$fullfield1['table']])) {
                $string .= " ";
            } else {
                // Do not add the operation if we are just starting out
                // In that cse te second table will add it
                if ($i > 1) $string .= " " . $link['op'] . " ";
                // Add the table to the query string
                $string .= $name1 . " " . $fullfield1['table'] . " ";
                // Add the table name to the list of tables processed
                $tablesdone[$fullfield1['table']] = $name1;
            }
            if (isset($tablesdone[$fullfield2['table']])) {
                $string .= " ";
            } else {
                // Add the operation
                $string .= $link['op'] . " ";
                // Add the table to the query string
                $string .= $name2 . " " . $fullfield2['table'] . " ";
                // Add the table name to the list of tables processed
                $tablesdone[$fullfield2['table']] = $name2;
            }
            
            // Add the joined fields to the query string
            $string .= "ON " . $link['field1'] . " = " . $link['field2'];
            // Add a closing parenthesis
            if ($i < $count) $string .= ")";
            $i++;
            
        }
        return $string ;
    }

    private function _gettablenamefromalias($alias)
    {
        foreach ($this->tables as $table) {
            if ($table['alias'] == $alias) return $table['name'];
        }
        return false;
    }

    private function assembledfields($type)
    {
        if (!isset($this->dbconn)) $this->dbconn = xarDB::getConn();
        $f = "";
        $this->bindstring = "";
        switch ($this->type) {
        case "SELECT" :
            if (count($this->fields) == 0) {
                if (!empty($this->distinctarray)) {
                    $this->fields = $this->distinctarray;
                } else {
                    return "*";
                }
            } 
            if (!empty($this->distinctarray)) {
                $fields = array();
                $flag = false;
                $distinct = "";
                foreach ($this->fields as $field) {
                    if ((($field['name'] == $this->distinctarray['name']) && ($field['table'] == $this->distinctarray['table'])) || ($field['alias'] == $this->distinctarray['name'])) {
                        $distinct = $field;
                    } else {
                        $fields[] = $field;
                    }
                }
                $this->bindstring .= "DISTINCT ";
                if (!empty($distinct)) {
                    $this->bindstring .= $this->_reconstructfield($distinct) . ", ";
                    $distinct['alias'] = "";
                    $this->distinctname = $this->_reconstructfield($distinct);               
                }
            } else {
                $fields = $this->fields;
            }
            foreach ($fields as $field) {
                if (is_array($field)) {
                    $this->bindstring .= $this->_reconstructfield($field);
                }
                else {
                    $this->bindstring .= $field;
                }
                $this->bindstring .= ", ";
            }
            if ($this->bindstring != "") $this->bindstring = trim($this->bindstring," ,");
            break;
        case "INSERT" :
            $this->bindstring .= " (";
            $names = '';
            $values = '';
            $bindvalues = '';
            foreach ($this->fields as $field) {
                if (is_array($field)) {
                    if(isset($field['name'])) {
                        $names .= $field['name'] . ", ";
                        if(isset($field['value'])) {
                            if ($this->usebinding) {
                                $bindvalues .= "?, ";
                                $this->bindvars[] = $field['value'];
                            } else {
                                if (!is_numeric($field['value'])) {
                                    $sqlfield = $this->dbconn->qstr($field['value']);
                                } else {
                                    $sqlfield = $field['value'];
                                }
                                $values .= $sqlfield . ", ";
                            }
                        } else {
                            if ($this->usebinding) {
                                $bindvalues .= "?, ";
                                $this->bindvars[] = NULL;
                            } else {
                                $values .= "NULL, ";
                            }
                        }
                    } else {
                        throw new BadParameterException(null, xarML('The current field is missing a name'));
                    }
                }
                else {
                    throw new BadParameterException(null, xarML('The field #(1) is not an array:', $field));
                }
            }
            $names = substr($names,0,strlen($names)-2);
            if ($this->usebinding) {
                $bindvalues = substr($bindvalues,0,strlen($bindvalues)-2);
                $this->bindstring .= $names . ") VALUES (" . $bindvalues . ")";
            } else {
                $values = substr($values,0,strlen($values)-2);
                $this->bindstring .= $names . ") VALUES (" . $values . ")";
            }
            break;
        case "UPDATE" :
            if($this->fields == array('*')) {
                throw new BadParameterException(null, xarML('Your query has no fields.'));
            }
            foreach ($this->fields as $field) {
                if (is_array($field)) {
                    if(isset($field['name'])) {
                        if(isset($field['value'])) {
                            if (is_array($field['value'])) throw new BadParameterException(null, xarML('The value of field #(1) is an array.', $field['name']));
                            // Turn off binding if we have an expression for the value (such as another field)
                            if(substr($field['value'],0,1) == '&') $this->usebinding = false;
                            if ($this->usebinding) {
                                $this->bindstring .= $this->_reconstructfield($field) . " = ?, ";
                                $this->bindvars[] = $field['value'];
                            } else {
                                if (!is_numeric($field['value']) && (substr($field['value'],0,1) != '&')) {
                                    //echo substr($field['value'],0,1);exit;
                                    $sqlfield = $this->dbconn->qstr($field['value']);
                                } else {
                                    if(substr($field['value'],0,1) == '&') {
                                        $sqlfield = substr($field['value'],1);
                                    } else {
                                        $sqlfield = $field['value'];
                                    }
                                }
                                $this->bindstring .= $this->_reconstructfield($field) . " = " . $sqlfield . ", ";
                            }
                        } else {
                            if ($this->usebinding) {
                                $this->bindstring .= $this->_reconstructfield($field) . " = ?, ";
                                $this->bindvars[] = NULL;
                            } else {
                                $this->bindstring .= $this->_reconstructfield($field) . " = NULL, ";
                            }
                        }
                    } else {
                        throw new BadParameterException(null, xarML('The current field is missing a name'));
                    }
                } else {
                    throw new BadParameterException(null, xarML('The field #(1) is not an array:', $field));
                }
            }
            if ($this->bindstring != "") $this->bindstring = substr($this->bindstring,0,strlen($this->bindstring)-2);
            break;
        case "DELETE" :
            break;
        }
        return $this->bindstring;
    }

    private function assembledconditions()
    {
        $temp1 = $this->conditions;
        $temp2 = $this->conjunctions;
        $c = "";
        if (!$this->on_syntax) {
            foreach ($this->tablelinks as $link)
            $o = $this->addcondition(array('field1' => $link['field1'],
                                  'field2' => $link['field2'],
                                  'op' => $link['op']),1);
        }

        if (count($this->conditions)>0) {
            $conditions = $this->_getconditions();
            if (!empty($conditions)) $c = " WHERE " . $conditions;
        }
        $this->conditions = $temp1;
        $this->conjunctions = $temp2;
        return $c;
    }

    private function assembledgroups()
    {
        $s = "";
        if (count($this->groups)>0) $s = " GROUP BY ";
        foreach ($this->groups as $groups) {
            if (is_array($groups)) {
                $s .= $groups['name'] . ", ";
            }
            else {
                // error msg
            }
        }
        if ($s != "") $s = substr($s,0,strlen($s)-2);
        return $s;
    }

    private function assembledhaving()
    {
        $s = "";
        if (count($this->having)>0) $s = " HAVING ";
        $first = true;
        foreach ($this->having as $having) {
            if (is_array($having)) {
                if ($first) {
                    $s .= $having['expression'] . " ";
                    $first = false;
                } else {
                    $s .= $having['conjunction'] . " " . $having['expression'] . " ";
                }
            }
            else {
                $result = xarML('Incorrect HAVING clause');
                die($result);
            }
        }
        return $s;
    }

    private function assembledsorts()
    {
        $s = "";
// CHECKME: it should be impossible to not select fields, because an empty array implies *
//        if (count($this->sorts)>0 && count($this->fields) > 0 && !isset($this->fields['COUNT(*)'])) {
        if (count($this->sorts)>0 && !isset($this->fields['COUNT(*)'])) {
            $s = " ORDER BY ";
        foreach ($this->sorts as $sort) {
            if (is_array($sort)) {
                $s .= $sort['name'] . " " . $sort['order']  . ", ";
            }
            else {
                // error msg
            }
        }
        if ($s != "") $s = substr($s,0,strlen($s)-2);
        }
        return $s;
    }

    private function _deconstructfield($field)
    {
        if (preg_match("/(.*) as (.*)/i", $field, $match)) {
            $field = trim($match[1]);
            $alias = trim($match[2]);
        }
        $pos = strpos($field, ' ');
        if ($pos !== false) {
            $fullfield = array('name' => $field, 'table' => '');
        } else {
            $fieldparts = explode('.',$field);
            if (count($fieldparts) > 1) 
                $fullfield = array('name' => $fieldparts[1], 'table' => $fieldparts[0]);
            else 
                $fullfield = array('name' => $field, 'table' => '');
        }
        if (isset($alias)) $fullfield['alias'] = $alias;
        else $fullfield['alias'] = '';
        return $fullfield;
    }

    private function _reconstructfield($field)
    {
        $bindstring = "";
        if(!empty($field['table'])) $bindstring .= $field['table'] . ".";
        $bindstring .= $field['name'];
        if (!empty($field['alias'])) $bindstring .= " AS " . $field['alias'];
        return $bindstring;
    }

    public function deconstructfield($field)
    {
        return $this->_deconstructfield($field);
    }

/*
// ------ Gets and sets and other public methods --------------------------------------------------------
*/
    public function addgroup($x = '')
    {
        if ($x != '') {
            $this->groups[] = array('name' => $x);
        }
    }
    public function addorder($x = '', $y = 'ASC')
    {
        if ($x != '') {
            $this->sorts[] = array('name' => $x, 'order' => $y);
        }
    }
    public function bindstatement()
    {
        if (!isset($this->dbconn)) $this->dbconn = xarDB::getConn();
        $pieces = explode('?',$this->statement);
        $bound = $pieces[0];
        $limit = count($pieces);
        for ($i=1;$i<$limit;$i++){
            if (!is_numeric($this->bindvars[$i-1])) {
                $sqlfield = $this->dbconn->qstr($this->bindvars[$i-1]);
            }
            else {
                $sqlfield = $this->bindvars[$i-1];
            }
            $bound .= $sqlfield . $pieces[$i];
        }
        $this->statement = $bound;
    }
    public function clearconditions()
    {
        $this->conditions = array();
        $this->conjunctions = array();

        // Update the ID for this query
        $this->id = $this->createID();
    }
    public function clearfield($x)
    {
        $count = count($this->fields);
        for ($i=0;$i<$count;$i++) {
            if (($this->fields[$i]['name'] == $x)) {
                unset($this->fields[$i]);
            }
            elseif (isset($this->fields[$i]['alias']) && ($this->fields[$i]['alias'] == $x)) {
                unset($this->fields[$i]);
            }
        }
    }
    public function clearfields()
    {
        $this->fields = array();
    }
    public function clearsorts()
    {
        $this->sorts = array();
    }
    public function cleartables()
    {
        $this->tables = array();
    }
    public function result()
    {
        return $this->result;
    }
    public function clearresult()
    {
        $this->result = NULL;
        $this->output = NULL;
    }
    public function getconnection()
    {
        if (!isset($this->dbconn)) $this->dbconn = xarDB::getConn();
        return $this->dbconn;
    }
    public function getorder($x='')
    {
        if ($this->sorts == array()) return false;
        if ($x == '') return $this->sorts[0]['name'];
        foreach ($this->sorts as $order) if ($order[0] == $x) return $order;
        return false;
    }
    public function getpagerows()
    {
        return $this->pagerows;
    }
    public function getrowfields()
    {
        return $this->rowfields;
    }
    public function getrows()
    {
        if (isset($this->output) && $this->rowstodo == 0) return count($this->output);
        if ($this->optimize == true) $this->optimize();
        if ($this->type == 'SELECT' && $this->rowstodo != 0 && $this->limits == 1) {
            if (!isset($this->dbconn)) $this->dbconn = xarDB::getConn();
            if ($this->israwstatement) {
                $temp1 = $this->rowstodo;
                $temp2 = $this->startat;
                $this->rowstodo = 0;
                $this->startat = 0;
//                $this->setstatement();
                $result = $this->dbconn->Execute($this->statement);
                $this->rows = $result->getRecordCount();
                $this->rowstodo = $temp1;
                $this->startat = $temp2;
// TODO: there must be a better way to do this
            } elseif (count($this->groups) > 0) {
                $temp1 = $this->rowstodo;
                $temp2 = $this->usebinding;
                $this->usebinding = 0;
                $this->setstatement();
                $result = $this->dbconn->Execute($this->statement);
                $this->rows = $result->getRecordCount();
                $this->rowstodo = $temp1;
                $this->usebinding = $temp2;
                $this->setstatement();
            } else {
                $temp1 = $this->fields;
                $this->clearfields();
                $temp2 = $this->sorts;
                $this->clearsorts();
                $temp3 = $this->usebinding;
                $this->usebinding = 0;
                $temp4 = $this->distinctarray;
                $this->distinctarray = array();
                if (!empty($this->distinctname)) $this->addfield('COUNT(DISTINCT ' . $this->distinctname. ')');
                else $this->addfield('COUNT(*)');
                $this->setstatement();
                $result = $this->dbconn->Execute($this->statement);
                list($this->rows) = $result->fields;
                $this->fields = $temp1;
                $this->sorts = $temp2;
                $this->usebinding = $temp3;
                $this->distinctarray = $temp4;;
                $this->setstatement();
            }
        }
        return $this->rows;
    }
    public function getrowstodo()
    {
        return $this->rowstodo;
    }
    public function getsort($x='')
    {
        if ($this->sorts == array()) return false;
        if ($x == '') return $this->sorts[0]['order'];
        foreach ($this->sorts as $order) if ($order[0] == $x) return $order;
        return false;
    }
    public function getstartat()
    {
        return $this->startat;
    }
    public function getstatement()
    {
        if ($this->usebinding) $this->bindstatement();
        return $this->statement;
    }
    public function getto()
    {
        return $this->type;
    }
    public function gettype()
    {
        return $this->type;
    }
    public function getversion()
    {
        return $this->version;
    }
    public function lastid($table="", $id="")
    {
        if (!isset($this->dbconn)) $this->dbconn = xarDB::getConn();
        $parts = explode('.',$id);
        $field = isset($parts[1]) ? $parts[1] : $parts[0];
        $table = isset($parts[1]) ? $parts[0] : $table;
        $result = $this->dbconn->Execute("SELECT MAX($field) FROM $table");
        list($id) = $result->fields;
        return (int)$id;
    }
    public function nextid($table="", $id="")
    {
        if (!isset($this->dbconn)) $this->dbconn = xarDB::getConn();
        return $this->dbconn->PO_Insert_ID($table,$id);
    }
    public function openconnection($x = '')
    {
        if (empty($x)) $this->dbconn = xarDB::getConn();
        else $this->dbconn = $x;
    }
    public function qecho($statement='')
    {
        if (empty($statement)) echo $this->tostring();
        else echo $statement;
    }
    public function sessiongetvar($x)
    {
        $q = xarSession::getVar($x);
        if (empty($q) || !isset($q)) return;
        $this->open();
        return $this;
    }
    public function sessionsetvar($x)
    {
        $q = $this;
        unset($q->dbconn);
        xarSession::setVar($x, serialize($q));
    }
    public function setdistinct($x = 1)
    {
        if ($x == 1) $this->distinctselect = '';
        else {
            $this->distinctselect = $x;
            $this->distinctarray = $this->_deconstructfield($x);
        }
    }
    public function setgroup($x = '')
    {
        if ($x != '') {
            $this->groups = array();
            $this->addgroup($x);
        }
    }
    public function setorder($x = '',$y = 'ASC')
    {
        if ($x != '') {
            $this->sorts = array();
            $this->addorder($x,$y);
        }
    }
    public function setrowstodo($x = 0)
    {
        $this->rowstodo = $x;
    }
    public function setstartat($x = 0)
    {
        $this->startat = $x;
    }
    public function setstatement($statement='')
    {
        if (!empty($statement)) {
            $this->israwstatement = true;
            $this->statement = $statement;
            $st = explode(" ",$statement);
            $this->type = strtoupper($st[0]);
        }
        else {
            $this->israwstatement = false;
            $this->statement = $this->_statement();
        }
    }
    public function settable($x)
    {
        $this->cleartables();
        $this->addtable($x);

        // Update the ID for this query
        $this->id = $this->createID();
    }
    public function settype($x = 'SELECT')
    {
        $this->type = $x;

        // Update the ID for this query
        $this->id = $this->createID();
    }
    public function setusebinding($x = true)
    {
        $this->usebinding = $x;
    }
    public function tostring()
    {
        // Set the current statement aside
        $temp = $this->statement;
        // Regenerate and get the statement
        $this->setstatement();
        $statementstring = $this->getstatement();
        // Restore the original statement
        $this->statement = $temp;
        // Return the generated statement
        return $statementstring;
    }
    public function addconditions($q)
    {
        if ($q->gettype() != $this->gettype()) return false;

        // Shift all the keys of the new conditions by $this->key to avoid conflicts
        $newkey = $this->key;
        
        foreach ($q->conditions as $key => $value) {
            $this->conditions[$key + $this->key] = $value;
            $newkey++;
        }

        foreach ($q->conjunctions as $key => $value) {
            if (is_array($value['conditions'])) {
                // We have nested conditions
                foreach ($value['conditions'] as $k => $v) $value['conditions'][$k] = $v + $this->key;
            } else {
                $value['conditions'] = $value['conditions'] + $this->key;
            }
            $this->conjunctions[$key + $this->key] = $value;
            $newkey++;
        }
        $this->key = $newkey;
    }
    public function addsorts($q)
    {
        if ($q->gettype() != $this->gettype()) return false;
        foreach ($q->sorts as $sort) $this->addorder($sort['name'],$sort['order']);
    }
    public function unite($q1, $q2)
    {
        if ($q1->gettype() != $q2->gettype()) return false;
        $this->fields = $q1->fields;
        $this->fields = array_merge($this->fields, $q2->fields);
        $conditions = $q1->getconditions();
        foreach ($q1->conditions as $key => $value) $this->conditions[$key] = $value;
        foreach ($q2->conditions as $key => $value) $this->conditions[$key] = $value;
        foreach ($q1->conjunctions as $key => $value) $this->conjunctions[$key] = $value;
        foreach ($q2->conjunctions as $key => $value) $this->conjunctions[$key] = $value;
        return $this;
    }
    public function getwhereclause()
    {
        $bind = $this->usebinding;
        $this->setusebinding(false);
        $clause = $this->assembledconditions();
        $this->setusebinding($bind);
        return substr($clause, 6);;
    }
    public function setconditions($q)
    {
        $this->clearconditions();
        $this->addconditions($q);

        // Update the ID for this query
        $this->id = $this->createID();
    }
    public function seteqop($x='=')
    {
        if( in_array($x,array('=','eq'))) $this->eqoperator = $x;
    }
    public function setneop($x='!=')
    {
        if(in_array($x, array('!=','ne'))) $this->neoperator = $x;
    }
    public function setgtop($x='>')
    {
        if(in_array($x, array('>','gt'))) $this->gtoperator = $x;
    }
    public function setgeop($x='>=')
    {
        if(in_array($x, array('>=','ge'))) $this->geoperator = $x;
    }
    public function setltop($x='<')
    {
        if(in_array($x, array('<','lt'))) $this->ltoperator = $x;
    }
    public function setleop($x='<=')
    {
        if(in_array($x, array('<=','le'))) $this->geoperator = $x;
    }
    public function setbinding($x=true)
    {
        $this->usebinding = $x;
    }
    public function setoptimize($x=true)
    {
        $this->optimize = $x;
    }
    public function setorop($x='OR')
    {
        $temp = $this->oroperator;
        if(in_array($x, array('or','OR'))) $this->oroperator = $x;
        if($this->implicitconjunction == $temp) $this->implicitconjunction = $x;
    }
    public function setandop($x='AND')
    {
        $temp = $this->andoperator;
        if(in_array($x, array('and','AND'))) $this->andoperator = $x;
        if($this->implicitconjunction == $temp) $this->implicitconjunction = $x;
    }
    public function setalphaoperators()
    {
        $this->seteqop('eq');
        $this->setneop('ne');
        $this->setgtop('gt');
        $this->setgeop('ge');
        $this->setltop('lt');
        $this->setleop('le');
    }
    public function talktoDD()
    {
        $this->setalphaoperators();
        $this->setandop('and');
        $this->setorop('or');
    }
    
    public function present()
    {
        $string = '';
        $string .= "Tables: <br />";
        foreach ($this->tables as $table) {
            $string .= "name = " . $table['name'] . ", alias = " . $table['alias'] . "<br/>";
        }
        $string .= "Links: <br />";
        foreach ($this->tablelinks as $link) {
            $string .= "field1 = " . $link['field1'] . ", field2 = " . $link['field2'] . "<br/>";
        }
//        $string .= "Bindings: <br />";
//        foreach ($this->bindings as $binding) {
//            $string .= "field1 = " . $binding['field1'] . ", field2 = " . $binding['field2'] . "<br/>";
//        }
        $string .= "Fields: <br />";
        foreach ($this->fields as $field) {
            $string .= "name = " . $field['name'] . ", alias = " . $field['alias'] . ", table = " . $field['table'] . ", value = " . $field['value'] . "<br/>";
        }
        echo $string;
    }

    public function setdebug($debugflag=1)
    {
        if ($debugflag && in_array(xarUser::getVar('id'),xarConfigVars::get(null, 'Site.User.DebugAdmins'))) {
            $this->debugflag = true;
            $this->starttime = microtime(true);
        } else {
            $this->debugflag = false;
        }
    }

/*
 * This method removes tables from the query that we don't need
 * Approach: identify all the relevant tables, then what is left is those we don't need
 * Relevant tables:
 * - those with fields that are being queried
 * - those with fields that are in the conditions
 * - those with more than 1 link
*/
    public function optimize()
    {
        // If we don't have multiple tables, no need to optimize
        if (count($this->tables) < 2) return true;
        
        // If we want ALL fields (i.e. *), no need to optimize
        if (empty($this->fields)) return true;
        
        // Bail if we have a function here
        // CHECKME: do a match function here
        foreach ($this->fields as $field) {
            if (strpos(strtolower($field['name']),'count(') !== false) return true;
        }
        if (empty($this->fields)) return true;
        
        // Put the table names in an array for processing. 
        // We'll remove all the relevant tables from this array
        $tables = array();
        foreach ($this->tables as $table) $tables[$table['alias']] = $table['name'];
        
        // Check which tables the fields reference; remove those that do from the array
        foreach ($this->fields as $field) {
            if (isset($tables[$field['table']])) {
                unset($tables[$field['table']]);
//            } elseif (in_array($field['table'],array_values($tables)))  {
//                $selbat = array_flip($tables);
//                unset($tables[$selbat[$field['table']]]);
            }
        }

        // Check which tables the conditions reference; 
        // We want to keep these, so remove them from the array      
        foreach ($this->conditions as $condition) {
            try {
                $fullfield = $this->_deconstructfield($condition['field1']);
                if (isset($tables[$fullfield['table']])) unset($tables[$fullfield['table']]);
            } catch (Exception $e) {}
            try {
                $fullfield = $this->_deconstructfield($condition['field2']);
                if (isset($tables[$fullfield['table']])) unset($tables[$fullfield['table']]);
            } catch (Exception $e) {}
        }

        // Remove any tables that have more than 1 link
        $tablehits = array();
        foreach ($tables as $key => $table) $tablehits[$key] = 0;
        foreach ($this->tablelinks as $link) {
            $fullfield = $this->_deconstructfield($link['field1']);
            if (isset($tables[$fullfield['table']])) $tablehits[$fullfield['table']] += 1;
            $fullfield = $this->_deconstructfield($link['field2']);
            if (isset($tables[$fullfield['table']])) $tablehits[$fullfield['table']] += 1;
        }
        foreach ($tablehits as $key => $hits) if ($hits > 1) unset($tables[$key]);
                    
        // What is left are the table with no fields; remove them
        $newtables = array();
        foreach ($this->tables as $table) {
            if (!isset($tables[$table['alias']])) $newtables[$table['alias']] = $table;
        }
        $this->tables = $newtables;
        
        // Remove the links that contain them
        $newlinks = array();
        foreach ($this->tablelinks as $link) {
            $fullfield1 = $this->_deconstructfield($link['field1']);
            $fullfield2 = $this->_deconstructfield($link['field2']);
            if (isset($tables[$fullfield1['table']]) || isset($tables[$fullfield2['table']])) continue;
            $newlinks[] = $link;
        }
        $this->tablelinks = $newlinks;
        
        // Remove the sort orders that contain them
        $newsorts = array();
        foreach ($this->sorts as $sort) {
            $fullfield = $this->_deconstructfield($sort['name']);
            if (isset($tables[$fullfield['table']])) continue;
            $newsorts[] = $sort;
        }
        $this->sorts = $newsorts;
        
        // Update the ID for this query
        $this->id = $this->createID();

        return true;
    }

    private function multiinsert()
    {
# --------------------------------------------------------
# Determine which is the primary table and field, get its value
#
        $parts = explode('.',$this->primary);
        if (!isset($parts[1])) 
            throw new Exception(xarML('Incorrect format for primary field: missing table alias'));            
        $primarytable = $parts[0];
        $primaryfield = $parts[1];
        
        $tablesource = '';
        foreach($this->tables as $table) {
            if ($table['alias'] == $parts[0]) {
                $tablesource = $table['name'];
                break;
            }
        }
        $primaryvalue = $this->lastid($tablesource, $parts[1]) + 1;
        
# --------------------------------------------------------
# Get convenient arrays to track the tables, links and fields
# Get the links we will work with; we only consider inner joins
#
        $tablelinks = array();
        foreach ($this->tablelinks as $link) {
            // Only support INNER JOINs
            if (
                ($this->on_syntax && $link['op'] == 'INNER JOIN') ||
                (!$this->on_syntax && $link['op'] == 'JOIN')
            ) $tablelinks[] = $link;
        }
        
# --------------------------------------------------------
# Get the tables joined by the links and re-present them
#
        $tablestodo = $this->findInternalTables($primarytable, $tablelinks);

# --------------------------------------------------------
# Now weed out any of the links above that don't deal with these tables
#
        $linkstodo = array();
        $tablekeys = array_keys($tablestodo);
        foreach ($tablelinks as $link) {
            $field1 = $this->_deconstructfield($link['field1']);
            $field2 = $this->_deconstructfield($link['field2']);
            if (in_array($field1['table'],$tablekeys) || in_array($field2['table'],$tablekeys))
                $linkstodo[] = $link;
        }

# --------------------------------------------------------
# Finally get all the fields we'll be working with
#
        foreach ($this->fields as $field) $fieldstodo[$field['table'] . '.' . $field['name']] = $field;

# --------------------------------------------------------
# Assign values to all the link fields where we can
# At the end of this process we will have linkfields with either values at both ends of the link
# or no values. In the latter case the code will just insert the next possible value, as such cases
# must involve at least one primary key.
#
        $linkstoprocess = $linkstodo;
        $temp = array();
        
        $fieldstodonames = array_keys($fieldstodo);
        while (count($linkstoprocess)) {
            $linkpair = reset($linkstoprocess);
            if (in_array($linkpair['field1'],$fieldstodonames)) {
                $temp[$linkpair['field1']] = $fieldstodo[$linkpair['field1']]['value'];
                $temp[$linkpair['field2']] = $temp[$linkpair['field1']];
            } elseif (in_array($linkpair['field2'],$fieldstodonames)) {
                $temp[$linkpair['field2']] = $fieldstodo[$linkpair['field2']]['value'];
                $temp[$linkpair['field1']] = $temp[$linkpair['field2']];
            } elseif (($linkpair['field1'] == $this->primary)) {
                $temp[$linkpair['field2']] = $primaryvalue;
            } elseif (($linkpair['field2'] == $this->primary)) {
                $temp[$linkpair['field1']] = $primaryvalue;
            }
            array_shift($linkstoprocess);            
        }

        $linkfields = array();
        foreach ($temp as $key => $value) {
            $parts = $this->_deconstructfield($key);
            $linkfields[$parts['table']][$parts['table'] . "." . $parts['name']] = array('name' => $parts['name'], 'table' => $parts['table'], 'value' => $value);
        }

# --------------------------------------------------------
# Set up an array which holds the number of links per table
#
        $tablequeue = array();
        foreach ($tablestodo as $table) $tablequeue[$table['alias']] = 0;

# --------------------------------------------------------
# Go through the tables, running an insert for each and its fields
#
        while (count($tablestodo)) {
            foreach ($linkstodo as $link) {
                // This link is not present in the insert fields
                if (!isset($fieldstodo[$link['field1']])) {
                    $fulllink = $this->_deconstructfield($link['field1']);
                    $tablequeue[$fulllink['table']] += 1;
                }
                if (!isset($fieldstodo[$link['field2']])) {
                    $fulllink = $this->_deconstructfield($link['field2']);
                    $tablequeue[$fulllink['table']] += 1;
                }            
            }

# --------------------------------------------------------
# Now pick up the table to run an insert on
# Look for a table with 1 link, saving the primary table for last
#
            foreach ($tablequeue as $alias => $hits) {
                if (($hits == 1) && ($alias != $primarytable)) {
                    $thistable = $tablestodo[$alias];
                    break;
                }
            }
# --------------------------------------------------------
# Sanity check: do we still have our primary table?
#
            if (!isset($tablestodo[$primarytable])) throw new Exception(xarML('Primary table #(1) no longer available!', $primarytable ));
            
# --------------------------------------------------------
# If we found nothing we must be almost finished: run an insert on the primary table
#
            if (empty($thistable)) $thistable = $tablestodo[$primarytable];

# --------------------------------------------------------
# Run an insert
#
            $theselinks = isset($linkfields[$thistable['alias']]) ? $linkfields[$thistable['alias']] : array();
            try {
                $fieldsdone = $this->partialinsert($thistable,$fieldstodo,$theselinks);
            } catch (Exception $e) {throw $e;}
            
# --------------------------------------------------------
# We've run the insert for this table, remove it from the list of todos
#
            unset($tablestodo[$thistable['alias']]);
            $tablequeue = array();
            foreach ($tablestodo as $table) $tablequeue[$table['alias']] = 0;

# --------------------------------------------------------
# Now check the field links for links to other tables
#
            $newlinks = array();
            foreach ($linkstodo as $link) {
                $fulllink = $this->_deconstructfield($link['field1']);
                if (isset($fieldsdone[$fulllink['name']]) && $fulllink['table'] == $thistable['alias']) {
                    $fulllink1 = $this->_deconstructfield($link['field2']);
                    $fulllink1['value'] = $fieldsdone[$fulllink['name']];
                    $fieldstodo[$link['field2']] = $fulllink1;
                    break;
                }
                $fulllink = $this->_deconstructfield($link['field2']);
                if (isset($fieldsdone[$fulllink['name']]) && $fulllink['table'] == $thistable['alias']) {
                    $fulllink1 = $this->_deconstructfield($link['field1']);
                    $fulllink1['value'] = $fieldsdone[$fulllink['name']];
                    $fieldstodo[$link['field1']] = $fulllink1;
                    break;
                }
                
                // This link was not involved in the last insert; pass it on
                $newlinks[] = $link;
            }
            $linkstodo = $newlinks;
            $thistable = '';
            
        }
        return true;
    }
    
    private function partialinsert($table=array(), $fieldstodo=array(),$linkfields=array())
    {
# --------------------------------------------------------
# Create an insert query based on this table
#
        $q = new Query('INSERT');
        $q->tables[] = $table;
        
# --------------------------------------------------------
# Pick out the fields that are in this table
#
        $fieldsdone = array();
        foreach ($fieldstodo as $key => $field) {
# --------------------------------------------------------
# Ignore the fields of other tables
#
            if ($field['table'] != $table['alias']) continue;
            
# --------------------------------------------------------
# If we used the %next% keyword, get the next itemid
#
            if ($fieldstodo[$key]['value'] === '%next%') {
                $fieldstodo[$key]['value'] = $q->lastid($table['name'], $field['name']) + 1;
            }
# --------------------------------------------------------
# Add it to this query
#
            $q->fields[] =& $fieldstodo[$key];
            $fieldsdone[$key] =& $fieldstodo[$key];
        }

# --------------------------------------------------------
# Now add the link fields from this table, only if it hasn't already been added
#
        foreach ($linkfields as $key => $field) {
            // If we used the %next% keyword, get the next itemid
            if ($linkfields[$key]['value'] === '%next%') {
                $linkfields[$key]['value'] = $q->lastid($table['name'], $field['name']) + 1;
            }
            if (!isset($fieldsdone[$key])) $q->fields[] = $linkfields[$key];
        }

# --------------------------------------------------------
# Run the insert on this table
#
        try {
            $q->run();
        } catch (Exception $e) {
            $msg = 'Failed to execute: ' . $q->tostring();
            throw new Exception($msg);
        }
                
# --------------------------------------------------------
# Try to retrieve the record we just inserted
#
        $dbInfo = $this->dbconn->getDatabaseInfo();
        $tableobject = $dbInfo->getTable($table['name']);
        $primarykey = $tableobject->getPrimaryKey()->getName();
        if (empty($primarykey))
            throw new Exception(xarML('Unable to retrieve primary key'));

        $itemid = $q->lastid($table['name'], $primarykey);
        $q = new Query('SELECT',$table['name']);
        $q->eq($primarykey, $itemid);
        if (!$q->run()) return false;
        
# --------------------------------------------------------
# Return the array of the fields we used for this insert and their values
#
        return $q->row();
    }
    
    private function findInternalTables($primarytable, $linkstodo) 
    {
        foreach ($this->tables as $table) $temp[$table['alias']] = $table;
        $tables[$primarytable] = $temp[$primarytable];
        $links = $linkstodo;

        while (count($links)) {
            $linkpair = reset($links);
            $field1 = $this->_deconstructfield($linkpair['field1']);
            $field2 = $this->_deconstructfield($linkpair['field2']);

            if (in_array($field1['table'],array_keys($tables))) {
                $tables[$field2['table']] = $temp[$field2['table']];
            } elseif (in_array($field2['table'],array_keys($tables))) {
                $tables[$field1['table']] = $temp[$field1['table']];
            }
            array_shift($links);            
        }
        return $tables;
    }
    
    public function suppressTable($thistable) 
    {
        // Support both table names and aliases
        $tablenames = array();
        
        // Remove this table from the list of tables
        foreach ($this->tables as $key => $table) {
            if ($table['name'] == $thistable) {
                $thistable = $table['alias'];
                $tablenames[] = $table['name'];
                $tablenames[] = $table['alias'];
                unset($this->tables[$key]);
                break;
            } elseif ($table['alias'] == $thistable) {
                $tablenames[] = $table['name'];
                $tablenames[] = $table['alias'];
                unset($this->tables[$key]);
                break;
            }
        }
        
        // Remove links with this table
        foreach ($this->tablelinks as $key => $link) {
            $field1 = $this->_deconstructfield($link['field1']);
            $field2 = $this->_deconstructfield($link['field2']);

            if (in_array($field1['table'], $tablenames) || in_array($field2['table'], $tablenames)) {
                unset($this->tablelinks[$key]);
            }
        }
        
        // Remove fields that reference this table
        foreach ($this->fields as $key => $field) {
            if (in_array($field1['table'], $tablenames)) unset($this->fields[$key]);
        }

        // Remove conditions that reference this table
        foreach ($this->conditions as $key => $condition) {
            try {
                $field = $this->_deconstructfield($condition['field1']);
                if (in_array($field1['table'], $tablenames)) unset($this->conditions[$key]);
                break;
            } catch (Exception $e) {}
            try {
                $field = $this->_deconstructfield($condition['field2']);
                if (in_array($field1['table'], $tablenames)) unset($this->conditions[$key]);
                break;
            } catch (Exception $e) {}
        }

        return true;
    }

/*
 * Creates a unique ID for this query. 
 * The ID includes the characteristics assumed "innate" of the query
 * It does not include display-dependant stuff such as fields or sorts
 */
    public function createID()
    {
        $idarray = array($this->tables, $this->tablelinks, $this->conjunctions, $this->conditions);
        $idstring = serialize($idarray);
        $id = md5($idstring);
        return $id;
    }
    public function getQueryID()
    {
        return $this->id;
    }
}
?>