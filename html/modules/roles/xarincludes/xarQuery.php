<?php
/**
 * xarQuery Class for SQL abstraction
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 * @link http://xaraya.com/index.php/release/27.html
 */

  /**************************************************************************\
  * xarQuery class for SQL abstraction                                       *
  * Written by Marc Lutolf (marcinmilan@xaraya.com)                          *
  * -----------------------------------------------                          *
  *  This program is free software; you can redistribute it and/or modify it *
  *  under the terms of the GNU General Public License as published by the   *
  *  Free Software Foundation; either version 2 of the License, or (at your  *
  *  option) any later version.                                              *
  \**************************************************************************/

class xarQuery
{
    // Two unrelated conditions will be inserted into the query as AND or OR
    const implicitconjunction = "AND";
    const version = "1.3";

    public $type;
    public $tables       = array();
    public $fields       = array();
    public $conditions   = array();
    public $conjunctions = array();
    public $bindings     = array();
    public $sorts        = array();
    public $result       = array();
    public $rows         = 0;
    public $rowstodo     = 0;
    public $startat      = 1;
    public $output       = array();
    public $row          = array();
    public $dbconn;
    public $statement;
    public $israwstatement = 0;
    private $bindvars       = array();
    private $limits         = 1;


    //---------------------------------------------------------
    // Constructor
    //---------------------------------------------------------
    function __construct($type='SELECT',$tables='',$fields='')
    {
        // Check if we're called ok
        if (!in_array($type,array("SELECT","INSERT","UPDATE","DELETE")))
            throw new ForbiddenOperationException($type,'This operation is not supported yet. "#(1)"');
        
        // Set the defaults
        $this->type = $type;             // querytype
        $this->key = time();             // ?
        $this->_addtables($tables);      
        $this->_addfields($fields);
        $this->dbconn =& xarDBGetConn();
    }

    //---------------------------------------------------------
    // Execute a query
    //---------------------------------------------------------
    function run($statement='',$display=1)
    {
        //FIXME: PHP5 hack
        $this->open();
        $this->setstatement($statement);
        // Prepare the statement
        $stmt = $this->dbconn->prepareStatement($this->statement);

        // Not a select, execute and return
        if ($this->type != 'SELECT') {
            $this->rows = $stmt->executeUpdate($this->bindvars);
            $this->bindvars = array(); //?
            // TODO: it would be nice to return the nr of rows here, we get that for free 
            //       in the callee then, and it's consistent with creole interface.
            return true; 
        }

        // If there is a limit, configure the statement as such
        if($this->rowstodo != 0 && $this->limits == 1 && $this->israwstatement) {
            $begin = $this->startat-1;
            $stmt->setLimit($this->rowstodo);
            $stmt->setOffset($begin);
            $this->statement .= " LIMIT " . $begin . "," . $this->rowstodo;
        } 

        // Execute the configured statement.
        $result = $stmt->executeQuery($this->bindvars,ResultSet::FETCHMODE_ASSOC);
        $this->rows = $result->getRecordCount();

        $this->output = array();
        if ($display == 1) {
            // Request to fill the output array with the results
            while($result->next()) {
                $this->output[] = $result->getRow();
            }
        }
        return true;
    }

    function close()
    {
        // CHECKME: is this redundant?, since we're only using 1 conn object
        //          unless the next method is used?
        return $this->dbconn->close();
    }

    function open()
    {
        // CHECKME: no reference here? why not?
        $this->openconnection(xarDBGetConn());
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
        // CHECKME: this has a functional dependency with display=1 
        // the middleware could solve this, see seek() method, 
        // so it can also work without fetching the whole resultset)
        if (empty($this->output)) return $this->output;
        return $this->output[$row];
    }

    function output()
    {
        return $this->output;
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
                        if (preg_match("/(.*) as (.*)/i", $field, $match)) {
                            $argsarray = array('name' => trim($match[1]), 'value' => '', 'alias' => trim($match[2]));
                        } else {
                            $argsarray = array('name' => trim($field), 'value' => '', 'alias' => '');
                        }
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

        $this->fields[$argsarray['name']] = $argsarray;
    }

    function addfields($tables)
    {
        $this->_addfields($tables);
    }

    function addtables($tables)
    {
        $this->_addtables($tables);
    }

    function join($field1,$field2)
    {
        $key = $this->_getkey();
        $numargs = func_num_args();
        if ($numargs == 2) {
            $this->bindings[$key]=array('field1' => $field1,
                                      'field2' => $field2,
                                      'op' => 'join');
        }
        elseif ($numargs == 4) {
            $this->bindings[$key]=array('field1' => func_get_arg(0) . "." . func_get_arg(1),
                                      'field2' => func_get_arg(2) . "." . func_get_arg(3),
                                      'op' => 'join');
        }
        return $key;
    }
    function eq($field1,$field2)
    {
        $key = $this->_addcondition();
/*
        $limit = count($this->conditions);
        for ($i=0;$i<$limit;$i++) {
            if ($this->conditions[$i]['field1'] == $field1) {
                $this->conditions[$i]=array('field1' => $field1,
                                            'field2' => $field2,
                                            'op' => '=');
                return;
            }
        }
*/
       $this->conditions[$key]=array('field1' => $field1,
                                  'field2' => $field2,
                                  'op' => '=');
        return $key;
    }

    // CHECKME: for all these condition methods,
    // couldn't we pass $fld1,$fld2 and $op to the addcondition method?
    // like: $key = $this->_addcondition($field1,$field2,'!=');
    function ne($field1,$field2)
    {
        $key = $this->_addcondition();
        $this->conditions[$key]=array('field1' => $field1,
                                  'field2' => $field2,
                                  'op' => '!=');
        return $key;
    }
    function gt($field1,$field2)
    {
        $key = $this->_addcondition();
        $this->conditions[$key]=array('field1' => $field1,
                                  'field2' => $field2,
                                  'op' => '>');
        return $key;
    }
    function ge($field1,$field2)
    {
        $key = $this->_addcondition();
        $this->conditions[$key]=array('field1' => $field1,
                                  'field2' => $field2,
                                  'op' => '>=');
        return $key;
    }
    function le($field1,$field2)
    {
        $key = $this->_addcondition();
        $this->conditions[$key]=array('field1' => $field1,
                                  'field2' => $field2,
                                  'op' => '<=');
        return $key;
    }
    function lt($field1,$field2)
    {
        $key = $this->_addcondition();
        $this->conditions[$key]=array('field1' => $field1,
                                  'field2' => $field2,
                                  'op' => '<');
        return $key;
    }
    function like($field1,$field2)
    {
        $key = $this->_addcondition();
        $this->conditions[$key]=array('field1' => $field1,
                                  'field2' => $field2,
                                  'op' => 'like');
        return $key;
    }
    function in($field1,$field2)
    {
        $key = $this->_addcondition();
        $this->conditions[$key]=array('field1' => $field1,
                                  'field2' => $field2,
                                  'op' => 'in');
        return $key;
    }
    function notin($field1,$field2,$active=1)
    {
        return $this->addcondition(array('field1' => $field1,
                                  'field2' => $field2,
                                  'op' => 'NOT IN'),$active);
    }
    function regex($field1,$field2)
    {
        $key = $this->_addcondition();
        $this->conditions[$key]=array('field1' => $field1,
                                  'field2' => $field2,
                                  'op' => 'regexp');
        return $key;
    }
    function qand()
    {
        $numargs = func_num_args();
        if ($numargs == 2) {
        }
        elseif ($numargs == 1) {
            $field = func_get_arg(0);
            if ($field == array()) return true;
            $key = $this->_addcondition();
            $this->conjunction[$key] = array('conditions' => $field,
                                             'conj' => 'AND');
            if (!is_array($field)) $field = array($field);
            foreach ($field as $conkey) {
                if ($this->conjunction[$conkey]['conj'] == 'IMPLICIT')
                    unset($this->conjunction[$conkey]);
            }
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
            $key = $this->_addcondition();
            $this->conjunctions[$key] = array('conditions' => $field,
                                             'conj' => 'OR');
            if (!is_array($field)) $field = array($field);
            foreach ($field as $conkey) {
                if ($this->conjunctions[$conkey]['conj'] == 'IMPLICIT')
                    unset($this->conjunctions[$conkey]);
            }
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
        for($i=0,$nf=count($this->fields); $i < $nf;$i++)
            if ($this->fields[$i]['name'] == $myfield) {
                unset($this->fields[$i]);
                break;
            }
    }
    function setalias($name='',$alias='')
    {
        if($name == '' || $alias == '') return false;
        for($i=0, $nt=count($this->tables);$i < $nt;$i++) {
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
            if ($this->conditions[$key]['field1'] == $mycondition) {
                unset($this->conditions[$key]);
                foreach($this->conjunctions as $key1 => $value1) {
                    if ($value1['conditions'] == $key) unset($this->conjunctions[$key1]);
                }
                break;
            }
    }

    function getconditions()
    {
        $c = "";
        foreach ($this->conditions as $condition) {
            if (is_array($condition)) {
                if (gettype($condition['field2']) == 'string' && $condition['op'] != 'join') {
                    // FIXME: dont use qstr
                    $sqlfield = $this->dbconn->qstr($condition['field2']);
                }
                else {
                    $sqlfield = $condition['field2'];
                    $condition['op'] = $condition['op'] == 'join' ? '=' : $condition['op'];
                }
                $c .= $condition['field1'] . " " . $condition['op'] . " " . $sqlfield . " AND ";
            }
            else {
            }
        }
        if ($c != "") $c = substr($c,0,-5); // 5 because of len(' AND ') == 5 ?
        return $c;
    }

// ------ Private methods -----
    private function _addfields($fields)
    {
        if (!is_array($fields)) {
            if (!is_string($fields)) {
            //error msg
            }
            else {
                if ($fields != '') {
                    $newfields = explode(',',$fields);
                    foreach ($newfields as $field) $this->addfield($field);
                }
            }
        }
        else {
            if ($this->type == 'SELECT') {
                foreach ($fields as $field) $this->addfield($field);
            }
            else {
                foreach ($fields as $field) $this->addfield($field);
//            $this->fields = array_merge($this->fields,$fields);
            }
        }
    }

    private function _addtables($tables)
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

    private function _getbinding($key)
    {
        $binding = $this->binding[$key];
        if (gettype($binding['field2']) == 'string' && $binding['op'] != 'join') {
            // FIXME: dont use qstr, use bindvars
            $sqlfield = $this->dbconn->qstr($binding['field2']);
        }
        else {
            $sqlfield = $condition['field2'];
            $binding['op'] = $binding['op'] == 'join' ? '=' : $binding['op'];
        }
        return $binding['field1'] . " " . $binding['op'] . " " . $sqlfield;
    }
    private function _getbindings()
    {
        $bstring = "";
        foreach ($this->bindings as $binding) {
           $binding['op'] = $binding['op'] == 'join' ? '=' : $binding['op'];
           $bstring .= $binding['field1'] . " " . $binding['op'] . " " . $binding['field2'] . " AND ";
        }
        if ($bstring != "") $bstring = substr($bstring,0,-5); // len(' AND ') == 5
        return $bstring;
    }
    private function _getcondition($key)
    {
        if (!isset($this->dbconn)) $this->dbconn =& xarDBGetConn();
        $condition = $this->conditions[$key];

        if (!isset($condition['field2']) || $condition['field2'] === 'NULL') {
            return $condition['field1'] . " IS NULL";
        }

        if (eregi('IN', $condition['op'])) {
            // IN (a[,b,c,d]) 
            $condit = is_array($condition['field2']) ? $condition['field2'] : array($condition['field2']);

            $elements = array();
            foreach ($condit as $element) {
                $this->bindvars[] = $element;
                $elements[] = '?';
            }
            $sqlfield = '(' . implode(',',$elements) . ')';
        } elseif (!eregi('JOIN', $condition['op'])) {
            // normal condition fld1 = value
            $this->bindvars[] = $condition['field2'];
            $sqlfield = '?';
        } else {
            // fld1 = fld2
            $sqlfield = $condition['field2'];
        }
        $condition['op'] = eregi('JOIN', $condition['op']) ? '=' : $condition['op'];
        
        return $condition['field1'] . " " . $condition['op'] . " " . $sqlfield;
    }

    private function _getconditions()
    {
        $cstring = "";
        foreach ($this->conjunctions as $conjunction) {
            if (is_array($conjunction['conditions'])) {
                $cstring .= "(";
                $i=0; $count = count($conjunction['conditions']);
                foreach ($conjunction['conditions'] as $condition) {
                    $i++;
                    $cstring .= $this->_getcondition($condition);
                    if ($i < $count) $cstring .= " " . $conjunction['conj'] . " ";
                    else $cstring .= ") ";
                }
            }
            else {
                if ($cstring == "") $conj = "";
                else {
                    if ($conjunction['conj'] == "IMPLICIT") $conj = self::implicitconjunction;
                    else $conj = $conjunction['conj'];
                }
                $cstring .= $conj . " " . $this->_getcondition($conjunction['conditions']) . " ";
            }
        }
        return $cstring;
    }

    private function _addcondition()
    {
        $key = $this->_getkey();
        $this->conjunctions[$key]=array('conditions' => $key,
                                        'conj' => 'IMPLICIT');
        return $key;
    }
    private function _getkey()
    {
        return $this->key++;
    }

    // Magic methods are public
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

    private function _statement()
    {
        $st =  $this->type . " ";
        switch ($this->type) {
        case "SELECT" :
            $st .= $this->assembledfields("SELECT");
            $st .= " FROM ";
            $st .= $this->assembledtables();
            $st .= $this->assembledconditions();
            $st .= $this->assembledsorts();
            break;
        case "INSERT" :
            $st .= " INTO ";
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
        case "DROP" :
        default :
        }
        return $st;
    }

    private function assembledtables()
    {
        if (count($this->tables) == 0) return "*MISSING*"; // FIXME: why *MISSING* (assert maybe?)
        $t = '';
        foreach ($this->tables as $table) {
            if (is_array($table)) {
                $t .= $table['name'] . " " . $table['alias'] . ", ";
            }
            else {
                $t .= $table . ", ";
            }
        }
        if ($t != "") $t = trim($t," ,");
        return $t;
    }
    private function assembledfields($type)
    {
        $f = "";
        $bindstring = "";
        switch ($this->type) {
        case "SELECT" :
            if (count($this->fields) == 0) return "*";
            foreach ($this->fields as $field) {
                if (is_array($field)) {
                    $bindstring .= $field['name'];
                    $bindstring .= (isset($field['alias']) && $field['alias'] != '') ? " AS " . $field['alias'] . ", " : ", ";
                }
                else {
                    $bindstring .= $field . ", ";
                }
            }
            if ($bindstring != "") $bindstring = trim($bindstring," ,");
            break;
        case "INSERT" :
            $bindstring .= " (";
            $names = '';
            $values = '';
            $bindvalues = '';
            foreach ($this->fields as $field) {
                if (is_array($field)) {
                    if(isset($field['name']) && isset($field['value'])) {
                        $names .= $field['name'] . ", ";
                        $bindvalues .= "?, ";
                        $this->bindvars[] = $field['value'];
                    }
                }
                else {
                }
            }
            $names = substr($names,0,-2); // 2 because of ', '
            $bindvalues = substr($bindvalues,0,-2); // just the ', ' stripped
            $bindstring .= $names . ") VALUES (" . $bindvalues . ")";
            break;
        case "UPDATE" :
            if($this->fields == array('*')) {
                throw new BadParameterException(null,'Your query has no fields.');
            }
            foreach ($this->fields as $field) {
                if (is_array($field)) {
                    if(isset($field['name']) && isset($field['value'])) {
                        $bindstring .= $field['name'] . " = ?, ";
                        $this->bindvars[] = $field['value'];
                    }
                }
                else {
                }
            }
            if ($bindstring != "") $bindstring = substr($bindstring,0,-2); // ', ' strip 
            break;
        case "DELETE" :
            break;
        }
        return $bindstring;
    }

    private function assembledconditions()
    {
        $c = "";
        if (count($this->bindings)>0) {
            $c = " WHERE ";
            $c .= $this->_getbindings();
        }
        if (count($this->conditions)>0) {
            if ($c == '') $c = " WHERE ";
            else $c .= " AND ";
            $c .= $this->_getconditions();
        }
        return $c;
    }
    private function assembledsorts()
    {
        $s = "";
        if (count($this->sorts)>0 && count($this->fields) && !isset($this->fields['COUNT(*)'])) {
            $s = " ORDER BY ";
            foreach ($this->sorts as $sort) {
                if (is_array($sort)) {
                    $s .= $sort['name'] . " " . $sort['order']  . ", ";
                }
                else {
                    // error msg
                }
            }
            if ($s != "") $s = substr($s,0,-2); // ', ' strip
        }
        return $s;
    }

    // ------ Gets and sets -----
    function cleartables()
    {
        $this->tables = array();
    }
    function clearfields()
    {
        $this->fields = array();
    }
    function clearfield($x)
    {
        // CHECKME: unsetting should be enough now?
        foreach ($this->fields as $key => $value) {
            if (($key == $x)) {
                unset($this->fields[$key]);
            }
            elseif (isset($value['alias']) && ($value['alias'] == $x)) {
                unset($this->fields[$key]);
            }
        }
    }
    function clearconditions()
    {
        $this->conditions = array();
        $this->conjunctions = array();
    }
    function clearsorts()
    {
        $this->sorts = array();
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
    function gettype()
    {
        return $this->type;
    }
    function getstartat()
    {
        return $this->startat;
    }
    function getto()
    {
        return $this->type;
    }
    function getpagerows()
    {
        return $this->pagerows;
    }
    function getrows()
    {
        return $this->rows;
    }
    function getrowstodo()
    {
        return $this->rowstodo;
    }
    function getversion()
    {
        return self::version;
    }

    function getorder($x='')
    {
        if (empty($this->sorts)) return false;
        if ($x == '') return $this->sorts[0];
        foreach ($this->sorts as $order) if ($order[0] == $x) return $order;
        return false;
    }

    function getsort($x='')
    {
        $order = $this->getorder($x);
        if(is_array($order)) return $order['order'];
        return false;
    }

    function addorder($x = '', $y = 'ASC')
    {
        if ($x != '') {
            $this->sorts[] = array('name' => $x, 'order' => $y);
        }
    }
    function settable($x)
    {
        $this->cleartables();
        $this->addtable($x);
    }
    function setorder($x = '',$y = 'ASC')
    {
        if ($x != '') {
            $this->sorts = array();
            $this->addorder($x,$y);
        }
    }
    function settype($x = 'SELECT')
    {
        $this->type = $x;
    }
    function setstartat($x = 0)
    {
        $this->startat = $x;
    }
    function setrowstodo($x = 0)
    {
        $this->rowstodo = $x;
    }
    function openconnection($x = '')
    {
        if (empty($x)) $this->dbconn =& xarDBGetConn();
        else $this->dbconn = $x;
    }
    function getconnection()
    {
        return $this->dbconn;
    }
    function getstatement()
    {
        return $this->statement;
    }
    function sessiongetvar($x)
    {
        $q = xarSessionGetVar($x);
        if (empty($q) || !isset($q)) return;
        $q = unserialize($q);
        return $q;
    }
    function sessionsetvar($x)
    {
        $q = $this;
        xarSessionSetVar($x, serialize($q));
    }
    function setstatement($statement='')
    {
        if ($statement != '') {
            $this->israwstatement = 0;
            $this->statement = $statement;
            $st = explode(" ",$statement);
            $this->type = strtoupper($st[0]);
        }
        else {
            $this->israwstatement = 1;
            $this->statement = $this->_statement();
        }
    }

    /** These last three can probably be removed **/
    function tostring()
    {
        $this->setstatement();
        $this->bindstatement();
        return $this->getstatement();
    }
    function qecho()
    {
        echo $this->tostring();
    }
    function bindstatement()
    {
        $pieces = explode('?',$this->statement);
        $bound = $pieces[0];
        $limit = count($pieces);
        for ($i=1;$i<$limit;$i++){
            if (gettype($this->bindvars[$i-1]) == 'string') {
                // FIXME: qstr should not be used
                $sqlfield = $this->dbconn->qstr($this->bindvars[$i-1]);
            }
            else {
                $sqlfield = $this->bindvars[$i-1];
            }
            $bound .= $sqlfield . $pieces[$i];
        }
        $this->statement = $bound;
    }
}
?>
