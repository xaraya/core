<?php
/**
 * @package modules
 * @subpackage roles
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */

/**
 * Query class for SQL abstraction
 * This version taken from com.xaraya.modules.query.unstable branch
 * renamed to avoid conflict for now
 * @todo what are we going to do with this long term?
 */
class xarQuery
{

    public $version = "3.1";
    public $key;
    public $type;
    public $tables;
    public $fields;
    public $conditions;
    public $conjunctions;
    public $bindings;
    public $tablelinks;
    public $sorts;
    public $result;
    public $rows = 0;
    public $rowfields = 0;
    public $rowstodo = 0;
    public $startat = 1;
    public $createtablename;
    public $output;
    public $row;
    public $dbconn;
    public $statement;
    public $israwstatement = 0;
    public $bindpublics;
    public $bindstring;
    public $limits = 1;
    public $uniqueselect = false;

// Flags
// Set to true to use binding variables supported by some dbs
    public $usebinding = true;
// Two unrelated conditions will be inserted into the query as AND or OR
    public $implicitconjunction = "AND";
// Use JOIN...ON.. syntax (automatic for left or right joins)
    public $on_syntax = false;
// Before each statement executed, echo the SQL statement
    public $debug = false;
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
    function __construct($type='SELECT',$tables='',$fields='')
    {
        if (in_array($type,array("SELECT","INSERT","UPDATE","DELETE","DROP"))) $this->type = $type;
        else {
            throw new ForbiddenOperationException($type,'This operation is not supported yet. "#(1)"');
        }
        if ($type != "SELECT" && is_array($tables) && count($tables) > 1) {
            $msg = xarML('The type #(1) can only take  a single table name', $type);
            throw new BadParameterException(null,$msg);
        }

        $this->key = time();
        $this->tables = array();
        $this->addtables($tables);
        $this->fields = array();
        $this->addfields($fields);
        $this->conditions = array();
        $this->conjunctions = array();
        $this->bindings = array();
        $this->tablelinks = array();
        $this->sorts = array();
        $this->groups = array();
        $this->having = array();
        $this->result = array();
        $this->output = null;
        $this->row = array();
        $this->bindvars = array();
    }

    function run($statement='',$display=1)
    {
        if (!isset($this->dbconn)) $this->dbconn = xarDB::getConn();
        $this->setstatement($statement);
        if ($this->debug) $this->qecho();
        if ($this->type != 'SELECT') {
            if ($this->usebinding  && !$this->israwstatement) {
                $result = $this->dbconn->Execute($this->statement,$this->bindvars);
                $this->bindvars = array();
            } else {
                $result = $this->dbconn->Execute($this->statement);
            }
            if(!$result) return;
//            $this->rows = $result;
            return true;
        }
        if($this->rowstodo != 0 && $this->limits == 1) {
            $begin = $this->startat-1;
            if ($this->usebinding && !$this->israwstatement) {
                $result = $this->dbconn->SelectLimit($this->statement,$this->rowstodo,$begin,$this->bindvars);
                $this->statement .= " LIMIT " . $begin . "," . $this->rowstodo;
            }
            else {
                $result = $this->dbconn->SelectLimit($this->statement,$this->rowstodo,$begin);
            }
        }
        else {
            if ($this->usebinding && !$this->israwstatement) {
                $result = $this->dbconn->Execute($this->statement,$this->bindvars);
            } else {
                $result = $this->dbconn->Execute($this->statement);
            }
        }
//            $this->rows = $result->getRecordCount();
        if (!$result) return;
        $this->result =& $result;

        if (($result->fields) === false)
            $numfields = 0;
        else
            $numfields = count($result->fields); // Better than the private var, fields should still be proteced
        $this->output = array();
        if ($display == 1) {
            if ($statement == '') {
                if ($this->fields == array() && $numfields > 0) {
                    $result->setFetchMode(ResultSet::FETCHMODE_ASSOC);
                    $result->next(); $result->previous();
                    for ($i=0;$i< $numfields;$i++) {
                        // Fetchfield was the only one used throughout the whole codebase, simulate it here instead of in creole
                        //$o = $result->FetchField($i);
                        // FIXME: get rid of it more globally since this never was portable anyway and it kills performance
                        $tmp = array_slice($result->fields,$i,1);
                        $finally_we_got_the_name_of_the_field  = key($tmp);
                        $this->fields[$finally_we_got_the_name_of_the_field]['name'] = strtolower($finally_we_got_the_name_of_the_field);
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
                    $line = array();
                    for ($i=0;$i<$this->rowfields;$i++) {
                        $line[] = $result->fields[$i];
                    }
                    $this->output[] = $line;
                    $result->MoveNext();
                }
            }
        }
        return true;
    }

    function close()
    {
        return $this->dbconn->close();
    }

    function open()
    {
        $this->openconnection(xarDB::getConn());
    }

    function uselimits()
    {
        $this->limits = 1;
    }

    function nolimits()
    {
        $this->limits = 0;
    }

    function row($row=0)
    {
        if ($this->output == array()) return array();
        return $this->output[$row];
    }

    function flatrow($row=0)
    {
        if ($this->output == array()) return false;
        return array_values($this->output[$row]);
    }

    function output()
    {
        return $this->output;
    }

    function drop($tables=null)
    {
        $this->settype("DROP");
        if (isset($tables)) $this->addtables($tables);
        return true;
    }

    function createto($newtablename=null)
    {
        if (!isset($newtablename)) $newtablename = "temp" . xarSession::getVar('role_id') . time();
        $this->createtablename = $newtablename;
        $this->settype("CREATE");
        return true;
    }

    function addtable()
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
        else throw new BadParameterException(null,'This function can only take 1 or 2 parameters');

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
    //    $this->tables[] = $argsarray;
    }

    function addfield()
    {
        $numargs = func_num_args();
        if ($numargs == 2) {
            $name = func_get_arg(0);
            $value = func_get_arg(1);
            $argsarray = array('name' => $name, 'value' => $value);
        }
        elseif ($numargs == 1) {
            $field = func_get_arg(0);
            if (!is_array($field)) {
                if (!is_string($field))
                    throw new BadParameterException($field,'The field #(1) you are trying to add needs to be a string or an array.');
                else {
                    if ($this->type == 'SELECT') {
                        $field = $this->_deconstructfield($field);
                        $argsarray = $field;
                    }
                    else {
                        $newfield = explode('=',$field);
                        $argsarray = array('name' => trim($newfield[0]), 'value' => trim($newfield[1]));
                    }
                }
            }
            else {
                $argsarray = $field;
            }
        }
        else throw new BadParameterException(null,'This function can only take 1 or 2 parameters');

        $done = false;
        for ($i=0;$i<count($this->fields);$i++) {
            if ($this->fields[$i]['name'] == $argsarray['name']) {
                if (isset($this->fields[$i]['alias']) && isset($argsarray['alias']) &&
                   ($this->fields[$i]['alias'] != $argsarray['alias'])) break;
                $this->fields[$i] = $argsarray;
                $done = true;
                break;
            }
        }
        if (!$done) $this->fields[] = $argsarray;
    }

    function addfields($fields)
    {
        if (!is_array($fields)) {
            if (!is_string($fields)) {
            //error msg
            }
            else {

                if ($fields != '') {
                    $newfields = explode(',',$fields);
                    foreach ($newfields as $field) {
                        $field = $this->_deconstructfield($field);
                        $this->addfield($field);
                    }
                }
            }
        }
        else {
            if ($this->type == 'SELECT') {
                foreach ($fields as $field) {
                    $field = $this->_deconstructfield($field);
                    $this->addfield($field);
                }
            }
            else {
                foreach ($fields as $field) $this->addfield($field);
//            $this->fields = array_merge($this->fields,$fields);
            }
        }
    }

    function addtables($tables)
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
//            $this->tables = array_merge($this->tables,$tables);
        }
    }

    function addtablelink($args)
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
        return $key;
    }
    function addhaving($args)
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
    function join($field1,$field2,$active=1)
    {
        $op = $this->on_syntax ? 'INNER JOIN' : 'JOIN';
        return $this->addtablelink(array('field1' => $field1,
                                  'field2' => $field2,
                                  'op' => $op),$active);
    }
    function leftjoin($field1,$field2,$active=1)
    {
        return $this->addtablelink(array('field1' => $field1,
                                  'field2' => $field2,
                                  'op' => 'LEFT JOIN'),$active);
    }
    function rightjoin($field1,$field2,$active=1)
    {
        return $this->addtablelink(array('field1' => $field1,
                                  'field2' => $field2,
                                  'op' => 'RIGHT JOIN'),$active);
    }
    function having($expression, $conjunction='')
    {
        if ($conjunction == '') $conjunction = $this->implicitconjunction;
        return $this->addhaving(array('expression' => $expression,
                                  'conjunction' => $conjunction));
    }
    function eq($field1,$field2,$active=1)
    {
        $key = $this->_addcondition($active);
        $limit = count($this->conditions);
        $this->conditions[$key]=array('field1' => $field1,
                                  'field2' => $field2,
                                  'op' => $this->eqoperator);
        return $key;
    }
    function ne($field1,$field2,$active=1)
    {
        return $this->addcondition(array('field1' => $field1,
                                  'field2' => $field2,
                                  'op' => $this->neoperator),$active);
    }
    function gt($field1,$field2,$active=1)
    {
        return $this->addcondition(array('field1' => $field1,
                                  'field2' => $field2,
                                  'op' => $this->gtoperator),$active);
    }
    function ge($field1,$field2,$active=1)
    {
        return $this->addcondition(array('field1' => $field1,
                                  'field2' => $field2,
                                  'op' => $this->geoperator),$active);
    }
    function le($field1,$field2,$active=1)
    {
        return $this->addcondition(array('field1' => $field1,
                                  'field2' => $field2,
                                  'op' => $this->leoperator),$active);
    }
    function lt($field1,$field2,$active=1)
    {
        return $this->addcondition(array('field1' => $field1,
                                  'field2' => $field2,
                                  'op' => $this->ltoperator),$active);
    }
    function like($field1,$field2,$active=1)
    {
        return $this->addcondition(array('field1' => $field1,
                                  'field2' => $field2,
                                  'op' => 'LIKE'),$active);
    }
    function notlike($field1,$field2,$active=1)
    {
        return $this->addcondition(array('field1' => $field1,
                                  'field2' => $field2,
                                  'op' => 'NOT LIKE'),$active);
    }
    function in($field1,$field2,$active=1)
    {
        return $this->addcondition(array('field1' => $field1,
                                  'field2' => $field2,
                                  'op' => 'IN'),$active);
    }
    function notin($field1,$field2,$active=1)
    {
        return $this->addcondition(array('field1' => $field1,
                                  'field2' => $field2,
                                  'op' => 'NOT IN'),$active);
    }
    function regex($field1,$field2,$active=1)
    {
        return $this->addcondition(array('field1' => $field1,
                                  'field2' => $field2,
                                  'op' => 'REGEXP'),$active);
    }

    function peq($field1,$field2)
    {
        return $this->eq($field1,$field2,0);
    }
    function pne($field1,$field2)
    {
        return $this->ne($field1,$field2,0);
    }
    function pgt($field1,$field2)
    {
        return $this->gt($field1,$field2,0);
    }
    function pge($field1,$field2)
    {
        return $this->ge($field1,$field2,0);
    }
    function ple($field1,$field2)
    {
        return $this->le($field1,$field2,0);
    }
    function plt($field1,$field2)
    {
        return $this->lt($field1,$field2,0);
    }
    function plike($field1,$field2)
    {
        return $this->like($field1,$field2,0);
    }
    function pnotlike($field1,$field2)
    {
        return $this->notlike($field1,$field2,0);
    }
    function pregex($field1,$field2)
    {
        return $this->regex($field1,$field2,0);
    }

   function qand()
    {
        $numargs = func_num_args();
        if ($numargs == 2) {
        }
        elseif ($numargs == 1) {
            $field = func_get_arg(0);
            if ($field == array()) return true;
            $key = $this->_addcondition(1);
            $this->conjunctions[$key] = array('conditions' => $field,
                                             'conj' => $this->andoperator,
                                             'active' => 1);
            if (!is_array($field)) $field = array($field);
        }
        return $key;
    }
    function qor()
    {
        $numargs = func_num_args();
        if ($numargs == 2) {
        }
        elseif ($numargs == 1) {
            $field = func_get_arg(0);
            if ($field == array()) return true;
            $key = $this->_addcondition(1);
            $this->conjunctions[$key] = array('conditions' => $field,
                                             'conj' => $this->oroperator,
                                             'active' => 1);
            if (!is_array($field)) $field = array($field);
        }
        return $key;
    }
   function pqand()
    {
        $key = $this->_addcondition(0);
        $numargs = func_num_args();
        if ($numargs == 2) {
        }
        elseif ($numargs == 1) {
            $field = func_get_arg(0);
            $this->conjunctions[$key] = array('conditions' => $field,
                                             'conj' => $this->andoperator,
                                             'active' => 0);
            if (!is_array($field)) $field = array($field);
        }
        return $key;
    }
    function pqor()
    {
        $key = $this->_addcondition(0);
        $numargs = func_num_args();
        if ($numargs == 2) {
        }
        elseif ($numargs == 1) {
            $field = func_get_arg(0);
            $this->conjunctions[$key] = array('conditions' => $field,
                                             'conj' => $this->oroperator,
                                             'active' => 0);
            if (!is_array($field)) $field = array($field);
        }
        return $key;
    }
    function addorders($sorts)
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
    function getfield($myfield)
    {
        foreach ($this->fields as $field)
            if ($field['name'] == $myfield) return $field['value'];
        return '';
    }
    function removefield($myfield)
    {
        for($i=0;$i<count($this->fields);$i++)
            if ($this->fields[$i]['name'] == $myfield) {
                unset($this->fields[$i]);
                break;
            }
    }
    function setalias($name='',$alias='')
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
    function getcondition($mycondition)
    {
        foreach ($this->conditions as $condition)
            if ($condition['field1'] == $mycondition) return $condition['field2'];
        return '';
    }
    function removecondition($mycondition)
    {
        foreach($this->conditions as $key => $value)
            if ($value['field1'] == $mycondition) {
                unset($this->conditions[$key]);
                unset($this->conjunctions[$key]);
                break;
            }
    }

    function addsecuritycheck($args)
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

    function addcondition($x,$active=1)
    {
        $key = $this->_getkey();
        $this->conjunctions[$key]=array('conditions' => $key,
                                        'conj' => 'IMPLICIT',
                                        'active' => $active);
        $this->conditions[$key] = $x;
        return $key;
    }

    function _addconditions($x)
    {
        foreach ($x as $condition); $this->addcondition($condition);
    }

/*
// ------ Private methods --------------------------------------------------------
*/
    function _getbinding($key)
    {
        if (!isset($this->dbconn)) $this->dbconn = xarDB::getConn();
        $binding = $this->binding[$key];
        if (gettype($binding['field2']) == 'string' && !eregi('JOIN', $binding['op'])) {
            $sqlfield = $this->dbconn->qstr($binding['field2']);
        }
        else {
            $sqlfield = $condition['field2'];
            $binding['op'] = eregi('JOIN', $binding['op']) ? '=' : $binding['op'];
        }
        return $binding['field1'] . " " . $binding['op'] . " " . $sqlfield;
    }

    function _getbindings()
    {
        $this->bstring = "";
        foreach ($this->bindings as $binding) {
           $binding['op'] = eregi('JOIN', $binding['op']) ? '=' : $binding['op'];
           $this->bstring .= $binding['field1'] . " " . $binding['op'] . " " . $binding['field2'] . " " . $this->andoperator . " ";
        }
        if ($this->bstring != "") $this->bstring = substr($this->bstring,0,strlen($this->bstring)-5);
        return $this->bstring;
    }

    function _getcondition($key)
    {
        if (!isset($this->dbconn)) $this->dbconn = xarDB::getConn();
        $condition = $this->conditions[$key];

        if (!isset($condition['field2']) || $condition['field2'] === 'NULL') {
                return $condition['field1'] . " IS NULL";
        }

        if ($condition['op'] == 'in' || $condition['op'] == 'IN') {
            if (is_array($condition['field2'])) {
                $elements = array();
                if ($this->usebinding) {
                    foreach ($condition['field2'] as $element) {
                        $this->bindvars[] = $element;
                        $elements[] = '?';
                    }
                } else {
                    foreach ($condition['field2'] as $element) $elements[] = $this->dbconn->qstr($element);
                }

                $sqlfield = '(' . implode(',',$elements) . ')';
            }
            else {
                $sqlfield = '(' . $condition['field2'] . ')';
            }
        } else {
            if (gettype($condition['field2']) == 'string' && !eregi('JOIN', $condition['op'])) {
                if ($this->usebinding) {
                    $this->bindvars[] = $condition['field2'];
                    $sqlfield = '?';
                } else {
                    $sqlfield = $this->dbconn->qstr($condition['field2']);
                }
            }
            else {
                if ($this->usebinding && !eregi('JOIN', $condition['op'])) {
                    $this->bindvars[] = $condition['field2'];
                    $sqlfield = '?';
                } else {
                    $sqlfield = $condition['field2'];
                }
                $condition['op'] = eregi('JOIN', $condition['op']) ? '=' : $condition['op'];
            }
        }
        return $condition['field1'] . " " . $condition['op'] . " " . $sqlfield;
    }

    function _getconditions()
    {
        $this->cstring = "";
       foreach ($this->conjunctions as $conjunction) {
            if ($conjunction['active']) $this->_resolve($conjunction);
        }
        return $this->cstring;
    }

    function _resolve($conjunction)
    {
        if (is_array($conjunction['conditions'])) {
//                echo $this->cstring . "<br />";
            if ($this->cstring == "") {
                $this->cstring .= "(";
            }
            else {
                $tokens = explode(" ",trim($this->cstring));
                $last = array_pop($tokens);
                if (($last == $this->andoperator) || ($last == $this->oroperator) || ($last == '('))
                    $this->cstring .= "(";
                else $this->cstring .= $this->implicitconjunction . " (";
            }
            $count = count($conjunction['conditions']);
            $i=0;
            foreach ($conjunction['conditions'] as $condition) {
                $i++;
                if (isset($this->conjunctions[$condition])) {
                    $this->_resolve($this->conjunctions[$condition]);
                }
                else {
                    $this->cstring .= $this->_getcondition($condition) . " ";
                }
                if ($i<$count) $this->cstring .= $conjunction['conj'] . " ";
                else $this->cstring = trim($this->cstring) . ") ";
            }
        }
        elseif (!is_array($conjunction['conditions'])) {
            if (($this->cstring == "") || (substr($this->cstring,strlen($this->cstring)-1) == '(')) $conj = "";
            else {
                if ($conjunction['conj'] == "IMPLICIT") $conj = $this->implicitconjunction;
                else $conj = $conjunction['conj'];
            }
            $tokens = explode(" ",trim($this->cstring));
            $last = array_pop($tokens);
            if (($last == $this->andoperator) || ($last == $this->oroperator) || ($last == '('))
                $this->cstring .= $this->_getcondition($conjunction['conditions']) . " ";
            else $this->cstring .= $conj . " " . $this->_getcondition($conjunction['conditions']) . " ";
        }
    }

    function _addcondition($active=1)
    {
        $key = $this->_getkey();
        $this->conjunctions[$key]=array('conditions' => $key,
                                        'conj' => 'IMPLICIT',
                                        'active' => $active);
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

    function _getkey()
    {
        $key = $this->key;
        $this->key++;
        return $key;
    }

    function _statement()
    {
        $this->bindvars = array();
        $st =  $this->type . " ";
        switch ($this->type) {
        case "SELECT" :
            if ($this->uniqueselect) $st .= " DISTINCT ";
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

    function assembledtables()
    {
        foreach ($this->tablelinks as $link) {
            if ($link['op'] != 'JOIN') {
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
        if ($this->on_syntax) {
            $t .= $this->assembledtablelinks();
        } else {
            foreach ($this->tables as $table) {
                if (is_array($table)) {
                    $t .= $table['name'] . " " . $table['alias'] . ", ";
                }
                else {
                    $t .= $table . ", ";
                }
            }
        }
        if ($t != "") $t = trim($t," ,");
        return $t;
    }

    function assembledtablelinks()
    {
//FIXME: bug if two joins are between the same tables
        $tablesdone = array();
        $t = '';
        $count = count($this->tablelinks );
        for ($i=0;$i<$count;$i++) $t .= '(';
        foreach ($this->tablelinks as $link) {
            $fullfield1 = $this->_deconstructfield($link['field1']);
            $fullfield2 = $this->_deconstructfield($link['field2']);
            if (isset($tablesdone[$fullfield2['table']])) {
                $temp = $fullfield2;
                $fullfield2 = $fullfield1;
                $fullfield1 = $temp;
                $temp = $link['field2'];
                $link['field2'] = $link['field1'];
                $link['field1'] = $temp;
            }
            $name = $this->_gettablenamefromalias($fullfield1['table']);
            if (isset($tablesdone[$fullfield1['table']])) {
                $t .= " ";
            } else {
                $t .= $name . " " . $fullfield1['table'] . " ";
            }
            $tablesdone[$fullfield1['table']] = $name;
            $name = $this->_gettablenamefromalias($fullfield2['table']);
            $tablesdone[$fullfield2['table']] = $name;
            $t .= $link['op'] . " ";
            $t .= $this->_gettablenamefromalias($fullfield2['table']);
            $t .= " " . $fullfield2['table'] . " ";
            $t .= "ON " . $link['field1'] . " = " . $link['field2'];
            $t .= ")";
        }

        return $t ;
    }

    function _gettablenamefromalias($alias)
    {
        foreach ($this->tables as $table) {
            if ($table['alias'] == $alias) return $table['name'];
        }
        return false;
    }

    function assembledfields($type)
    {
        if (!isset($this->dbconn)) $this->dbconn = xarDB::getConn();
        $f = "";
        $this->bindstring = "";
        switch ($this->type) {
        case "SELECT" :
            if (count($this->fields) == 0) return "*";
            foreach ($this->fields as $field) {
                if (is_array($field)) {
                    if(isset($field['table']) && $field['table'] != '')
                        $this->bindstring .= $field['table'] . ".";
                    $this->bindstring .= $field['name'];
                    $this->bindstring .= (isset($field['alias']) && $field['alias'] != '') ? " AS " . $field['alias'] . ", " : ", ";
                }
                else {
                    $this->bindstring .= $field . ", ";
                }
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
                    if(isset($field['name']) && isset($field['value'])) {
                        $names .= $field['name'] . ", ";
                        if ($this->usebinding) {
                            $bindvalues .= "?, ";
                            $this->bindvars[] = $field['value'];
                        }
                        else {
                        if (gettype($field['value']) == 'string') {
                            $sqlfield = $this->dbconn->qstr($field['value']);
                        }
                        else {
                            $sqlfield = $field['value'];
                        }
                        $values .= $sqlfield . ", ";
                    }
                }
                }
                else {
                }
            }
            $names = substr($names,0,strlen($names)-2);
            if ($this->usebinding) {
                $bindvalues = substr($bindvalues,0,strlen($bindvalues)-2);
                $this->bindstring .= $names . ") VALUES (" . $bindvalues . ")";
            }
            else {
            $values = substr($values,0,strlen($values)-2);
                $this->bindstring .= $names . ") VALUES (" . $values . ")";
            }
            break;
        case "UPDATE" :
            if($this->fields == array('*')) {
                throw new BadParameterException(null,'Your query has no fields.');
            }
            foreach ($this->fields as $field) {
                if (is_array($field)) {
                    if(isset($field['name']) && isset($field['value'])) {
                        if ($this->usebinding) {
                            $this->bindstring .= $field['name'] . " = ?, ";
                            $this->bindvars[] = $field['value'];
                        }
                        else {
                            if ((gettype($field['value']) == 'string') && (substr($field['value'],0,1) != '&')) {
                                echo substr($field['value'],0,1);exit;
                                $sqlfield = $this->dbconn->qstr($field['value']);
                            }
                            else {
                                if(substr($field['value'],0,1) == '&') {
                                    $sqlfield = substr($field['value'],1);
                                } else {
                                    $sqlfield = $field['value'];
                                }
                            }
                            $this->bindstring .= $field['name'] . " = " . $sqlfield . ", ";
                        }
                    }
                }
                else {
                }
            }
            if ($this->bindstring != "") $this->bindstring = substr($this->bindstring,0,strlen($this->bindstring)-2);
            break;
        case "DELETE" :
            break;
        }
        return $this->bindstring;
    }

    function assembledconditions()
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
        if (count($this->bindings)>0) {
            $c = " WHERE ";
            $c .= $this->_getbindings();
        }
        if (count($this->conditions)>0) {
            $conditions = $this->_getconditions();
            if ($conditions == '') return $c;
            if ($c == '') $c = " WHERE " . $conditions;
            else $c .= " " . $this->implicitconjunction . " "  . $conditions;
        }
        $this->conditions = $temp1;
        $this->conjunctions = $temp2;
        return $c;
    }

    function assembledgroups()
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

    function assembledhaving()
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
                // error msg
            }
        }
        return $s;
    }

    function assembledsorts()
    {
        $s = "";
        if (count($this->sorts)>0 && count($this->fields) > 0 && !isset($this->fields['COUNT(*)'])) {
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

    function _deconstructfield($field)
    {
        if (preg_match("/(.*) as (.*)/i", $field, $match)) {
            $field = trim($match[1]);
            $alias = trim($match[2]);
        }
        $fieldparts = explode('.',$field);
        if (count($fieldparts) > 1) {
            $table = $fieldparts[0];
            $name = substr($field,strlen($fieldparts[0])+1);
            $fullfield = array('name' => $name, 'table' => $table);
        }
        else {
            $fullfield = array('name' => $field, 'table' => '');
        }
        if (isset($alias)) $fullfield['alias'] = $alias;
        return $fullfield;
    }

    function deconstructfield($field)
    {
        return $this->_deconstructfield($field);
    }

/*
// ------ Gets and sets and other public methods --------------------------------------------------------
*/
    function addgroup($x = '')
    {
        if ($x != '') {
            $this->groups[] = array('name' => $x);
        }
    }
    function addorder($x = '', $y = 'ASC')
    {
        if ($x != '') {
            $this->sorts[] = array('name' => $x, 'order' => $y);
        }
    }
    function bindstatement()
    {
        if (!isset($this->dbconn)) $this->dbconn = xarDB::getConn();
        $pieces = explode('?',$this->statement);
        $bound = $pieces[0];
        $limit = count($pieces);
        for ($i=1;$i<$limit;$i++){
            if (gettype($this->bindvars[$i-1]) == 'string') {
                $sqlfield = $this->dbconn->qstr($this->bindvars[$i-1]);
            }
            else {
                $sqlfield = $this->bindvars[$i-1];
            }
            $bound .= $sqlfield . $pieces[$i];
        }
        $this->statement = $bound;
    }
    function clearconditions()
    {
        $this->conditions = array();
        $this->conjunctions = array();
    }
    function clearfield($x)
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
    function clearfields()
    {
        $this->fields = array();
    }
    function clearsorts()
    {
        $this->sorts = array();
    }
    function cleartables()
    {
        $this->tables = array();
    }
    function result()
    {
        return $this->result;
    }
    function clearresult()
    {
        $this->result = NULL;
        $this->output = NULL;
    }
    function getconnection()
    {
        if (!isset($this->dbconn)) $this->dbconn = xarDB::getConn();
        return $this->dbconn;
    }
    function getorder($x='')
    {
        if ($this->sorts == array()) return false;
        if ($x == '') return $this->sorts[0]['name'];
        foreach ($this->sorts as $order) if ($order[0] == $x) return $order;
        return false;
    }
    function getpagerows()
    {
        return $this->pagerows;
    }
    function getrowfields()
    {
        return $this->rowfields;
    }
    function getrows()
    {
        if (isset($this->output) && $this->rowstodo == 0) return count($this->output);
        if ($this->type == 'SELECT' && $this->rowstodo != 0 && $this->limits == 1) {
            if (!isset($this->dbconn)) $this->dbconn = xarDB::getConn();
            if ($this->israwstatement) {
                $temp1 = $this->rowstodo;
                $temp2 = $this->startat;
                $this->rowstodo = 0;
                $this->startat = 0;
                $this->setstatement();
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
                $this->addfield('COUNT(*)');
                $this->setstatement();
                $result = $this->dbconn->Execute($this->statement);
                list($this->rows) = $result->fields;
                $this->fields = $temp1;
                $this->sorts = $temp2;
                $this->usebinding = $temp3;
                $this->setstatement();
            }
        }
        return $this->rows;
    }
    function getrowstodo()
    {
        return $this->rowstodo;
    }
    function getsort($x='')
    {
        if ($this->sorts == array()) return false;
        if ($x == '') return $this->sorts[0]['order'];
        foreach ($this->sorts as $order) if ($order[0] == $x) return $order;
//        $order = $this->getorder($x);
//        if(is_array($order)) return $order['order'];
        return false;
    }
    function getstartat()
    {
        return $this->startat;
    }
    function getstatement()
    {
        if ($this->usebinding) $this->bindstatement();
        return $this->statement;
    }
    function getto()
    {
        return $this->type;
    }
    function gettype()
    {
        return $this->type;
    }
    function getversion()
    {
        return $this->version;
    }
    function lastid($table="", $id="")
    {
        if (!isset($this->dbconn)) $this->dbconn = xarDB::getConn();
        $result = $this->dbconn->Execute("SELECT MAX($id) FROM $table");
        list($id) = $result->fields;
        return $id;
    }
    function nextid($table="", $id="")
    {
        if (!isset($this->dbconn)) $this->dbconn = xarDB::getConn();
        return $this->dbconn->PO_Insert_ID($table,$id);
    }
    function openconnection($x = '')
    {
        if (empty($x)) $this->dbconn = xarDB::getConn();
        else $this->dbconn = $x;
    }
    function qecho()
    {
        echo $this->tostring();
    }
    function sessiongetvar($x)
    {
        $q = xarSession::getVar($x);
        if (empty($q) || !isset($q)) return;
//        $this = unserialize($q);
        $this->open();
        return $this;
    }
    function sessionsetvar($x)
    {
        $q = $this;
        unset($q->dbconn);
        xarSession::setVar($x, serialize($q));
    }
    function setdistinct($x = 1)
    {
        $this->uniqueselect = $x;
    }
    function setgroup($x = '')
    {
        if ($x != '') {
            $this->groups = array();
            $this->addgroup($x);
        }
    }
    function setorder($x = '',$y = 'ASC')
    {
        if ($x != '') {
            $this->sorts = array();
            $this->addorder($x,$y);
        }
    }
    function setrowstodo($x = 0)
    {
        $this->rowstodo = $x;
    }
    function setstartat($x = 0)
    {
        $this->startat = $x;
    }
    function setstatement($statement='')
    {
        if ($statement != '') {
            $this->israwstatement = 1;
            $this->statement = $statement;
            $st = explode(" ",$statement);
            $this->type = strtoupper($st[0]);
        }
        else {
            $this->israwstatement = 0;
            $this->statement = $this->_statement();
        }
    }
    function settable($x)
    {
        $this->cleartables();
        $this->addtable($x);
    }
    function settype($x = 'SELECT')
    {
        $this->type = $x;
    }
    function setusebinding($x = true)
    {
        $this->usebinding = $x;
    }
    function tostring()
    {
        $this->setstatement();
        return $this->getstatement();
    }
    function addconditions($q)
    {
        if ($q->gettype() != $this->gettype()) return false;
        foreach ($q->conditions as $key => $value) $this->conditions[$key] = $value;
        foreach ($q->conjunctions as $key => $value) $this->conjunctions[$key] = $value;
    }
    function unite($q1, $q2)
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
    function getwhereclause()
    {
        $bind = $this->usebinding;
        $this->setusebinding(false);
        $clause = $this->assembledconditions();
        $this->setusebinding($bind);
        return substr($clause, 6);;
    }

    function seteqop($x='=')
    {
        if( in_array($x,array('=','eq'))) $this->eqoperator = $x;
    }
    function setneop($x='!=')
    {
        if(in_array($x, array('!=','ne'))) $this->neoperator = $x;
    }
    function setgtop($x='>')
    {
        if(in_array($x, array('>','gt'))) $this->gtoperator = $x;
    }
    function setgeop($x='>=')
    {
        if(in_array($x, array('>=','ge'))) $this->geoperator = $x;
    }
    function setltop($x='<')
    {
        if(in_array($x, array('<','lt'))) $this->ltoperator = $x;
    }
    function setleop($x='<=')
    {
        if(in_array($x, array('<=','le'))) $this->geoperator = $x;
    }
    function setbinding($x=true)
    {
        $this->usebinding = $x;
    }
    function setorop($x='OR')
    {
        $temp = $this->oroperator;
        if(in_array($x, array('or','OR'))) $this->oroperator = $x;
        if($this->implicitconjunction == $temp) $this->implicitconjunction = $x;
    }
    function setandop($x='AND')
    {
        $temp = $this->andoperator;
        if(in_array($x, array('and','AND'))) $this->andoperator = $x;
        if($this->implicitconjunction == $temp) $this->implicitconjunction = $x;
    }
    function setalphaoperators()
    {
        $this->seteqop('eq');
        $this->setneop('ne');
        $this->setgtop('gt');
        $this->setgeop('ge');
        $this->setltop('lt');
        $this->setleop('le');
    }
    function talktoDD()
    {
        $this->setalphaoperators();
        $this->setandop('and');
        $this->setorop('or');
    }

}
?>
