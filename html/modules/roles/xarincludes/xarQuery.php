<?php
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

    var $version = "1.2";
    var $id;
    var $type;
    var $tables;
    var $fields;
    var $conditions;
    var $conjunctions;
    var $bindings;
    var $sorts;
    var $result;
    var $rows = 0;
    var $rowstodo = 0;
    var $startat = 1;
    var $output;
    var $row;
    var $dbconn;
    var $statement;
    var $israwstatement = 0;
    var $bindvars;
    var $bindstring;
    var $limits = 1;

// Flags
// Set to true to use binding variables supported by some dbs
    var $usebinding = true;
// Two unrelated conditions will be inserted into the query as AND or OR
    var $implicitconjunction = "AND";

//---------------------------------------------------------
// Constructor
//---------------------------------------------------------
    function xarQuery($type='SELECT',$tables='',$fields='')
    {
        if (in_array($type,array("SELECT","INSERT","UPDATE","DELETE"))) $this->type = $type;
        else {
            $msg = xarML('The operation #(1) is not supported', $type);
            xarErrorSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR_QUERY', new SystemMessage($msg));
            return;
        }

        $this->key = mktime();
        $this->tables = array();
        $this->addtables($tables);
        $this->fields = array();
        $this->addfields($fields);
        $this->conditions = array();
        $this->conjunctions = array();
        $this->bindings = array();
        $this->sorts = array();
        $this->result = array();
        $this->output = array();
        $this->row = array();
        $this->bindvars = array();
        $this->dbconn =& xarDBGetConn();
    }


    function run($statement='',$pretty=1)
    {
        $this->setstatement($statement);
        if ($this->type != 'SELECT') {
            if ($this->usebinding) {
                $result = $this->dbconn->Execute($this->statement,$this->bindvars);
            }
            else {
                $result = $this->dbconn->Execute($this->statement);
            }
            if(!$result) return;
            $this->rows = $result->_numOfRows;
            return true;
        }
        if($this->rowstodo != 0 && $this->limits == 1 && $this->israwstatement) {
            $begin = $this->startat-1;
            $result = $this->dbconn->SelectLimit($this->statement,$this->rowstodo,$begin);
            $this->statement .= " LIMIT " . $begin . "," . $this->rowstodo;
        }
        else {
            if ($this->usebinding) {
                $result = $this->dbconn->Execute($this->statement,$this->bindvars);
            }
            else {
                $result = $this->dbconn->Execute($this->statement);
            }
            $this->rows = $result->_numOfRows;
        }
        if (!$result) return;
        $this->result =& $result;

        if (($result->fields) === false) $numfields = 0;
        else $numfields = $result->_numOfFields;
        $this->output = array();
        if ($pretty == 1) {
            if ($statement == '') {
                if ($this->fields == array() && $numfields > 0) {
                    for ($i=0;$i<$numfields;$i++) {
                        $o =& $result->FetchField($i);
                        if (!isset($o) || !isset($o->name)) {
                            $msg = xarML('SELECT with total of columns different from the number retrieved.');
                            xarErrorSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR_QUERY', new SystemMessage($msg));
                            return;
                        }
                        $this->fields[$i]['name'] = strtolower($o->name);
                    }
                }
                while (!$result->EOF) {
                    for ($i=0;$i<$numfields;$i++) {
                        $line[$this->fields[$i]['name']] = $result->fields[$i];
                    }
                    $this->output[] = $line;
                    $result->MoveNext();
                }
            }
            else {
                while (!$result->EOF) {
                    $line = array();
                    for ($i=0;$i<$numfields;$i++) {
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
        if ($this->output == array()) return array();
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
                    $msg = xarML('The table #(1) you are trying to add needs to be a string or an array.', $table);
                    xarErrorSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR_QUERY', new SystemMessage($msg));
                    return;
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
        else {
            $msg = xarML('This function only take 1 or 2 parameters');
            xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemMessage($msg));
            return;
        }
        $notdone = true;
        $limit = count($this->tables);
        for ($i=0;$i<$limit;$i++) {
            if ($this->tables[$i]['name'] == $argsarray['name']) {
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
                if (!is_string($field)) {
                    $msg = xarML('The field #(1) you are trying to add needs to be a string or an array.', $field);
                    xarErrorSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR_QUERY', new SystemMessage($msg));
                    return;
                }
                else {
                    if ($this->type == 'SELECT') {
                        $argsarray = array('name' => trim($field), 'value' => '', 'alias' => '');
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
        else {
            $msg = xarML('This function only take 1 or 2 parameters');
            xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemMessage($msg));
            return;
        }
        $done = false;
        for ($i=0;$i<count($this->fields);$i++) {
            if ($this->fields[$i]['name'] == $argsarray['name']) {
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

    function join($field1,$field2)
    {
        $key = $this->key;
        $this->key++;
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
        $limit = count($this->conditions);
       for ($i=0;$i<$limit;$i++) {
/*             if ($this->conditions[$i]['field1'] == $field1) {
                $this->conditions[$i]=array('field1' => $field1,
                                          'field2' => $field2,
                                          'op' => '=');
                return;
            }
*/         }
       $this->conditions[$key]=array('field1' => $field1,
                                  'field2' => $field2,
                                  'op' => '=');
        return $key;
    }
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
        $key = $this->_addcondition();
        $numargs = func_num_args();
        if ($numargs == 2) {
        }
        elseif ($numargs == 1) {
            $field = func_get_arg(0);
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
        $key = $this->_addcondition();
        $numargs = func_num_args();
        if ($numargs == 2) {
        }
        elseif ($numargs == 1) {
            $field = func_get_arg(0);
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
        if ($c != "") $c = substr($c,0,strlen($c)-5);
        return $c;
    }

// ------ Private methods -----
    function _getbinding($key)
    {
        $binding = $this->binding[$key];
        if (gettype($binding['field2']) == 'string' && $binding['op'] != 'join') {
            $sqlfield = $this->dbconn->qstr($binding['field2']);
        }
        else {
            $sqlfield = $condition['field2'];
            $binding['op'] = $binding['op'] == 'join' ? '=' : $binding['op'];
        }
        return $binding['field1'] . " " . $binding['op'] . " " . $sqlfield;
    }
    function _getbindings()
    {
        $this->bstring = "";
        foreach ($this->bindings as $binding) {
           $binding['op'] = $binding['op'] == 'join' ? '=' : $binding['op'];
           $this->bstring .= $binding['field1'] . " " . $binding['op'] . " " . $binding['field2'] . " AND ";
        }
        if ($this->bstring != "") $this->bstring = substr($this->bstring,0,strlen($this->bstring)-5);
        return $this->bstring;
    }
    function _getcondition($key)
    {
        $condition = $this->conditions[$key];
        if (gettype($condition['field2']) == 'string' && $condition['op'] != 'join') {
            $sqlfield = $this->dbconn->qstr($condition['field2']);
        }
        else {
            $sqlfield = $condition['field2'];
            $condition['op'] = $condition['op'] == 'join' ? '=' : $condition['op'];
        }
        if ($condition['op'] == 'in') $sqlfield = '(' . $sqlfield . ')';
        return $condition['field1'] . " " . $condition['op'] . " " . $sqlfield;
    }

    function _getconditions()
    {
        $this->cstring = "";
        foreach ($this->conjunctions as $conjunction) {
            if (is_array($conjunction['conditions'])) {
                $this->cstring .= "(";
                $count = count($conjunction['conditions']);
                $i=0;
                foreach ($conjunction['conditions'] as $condition) {
                    $i++;
                    $this->cstring .= $this->_getcondition($condition);
                    if ($i<$count) $this->cstring .= " " . $conjunction['conj'] . " ";
                    else $this->cstring .= ") ";
                }
            }
            elseif (!is_array($conjunction['conditions'])) {
                if ($this->cstring == "") $conj = "";
                else {
                    if ($conjunction['conj'] == "IMPLICIT") $conj = $this->implicitconjunction;
                    else $conj = $conjunction['conj'];
                }
                $this->cstring .= $conj . " " . $this->_getcondition($conjunction['conditions']) . " ";
            }
        }
        return $this->cstring;
    }

    function _addcondition()
    {
        $key = $this->_getkey();
        $this->conjunctions[$key]=array('conditions' => $key,
                                        'conj' => 'IMPLICIT');
        return $key;
    }
    function _getkey()
    {
        $key = $this->key;
        $this->key++;
        return $key;
    }

    function _statement()
    {
        $st =  $this->type . " ";
        switch ($this->type) {
        case "SELECT" :
            $st .= $this->assembledfields("SELECT");
            $st .= " FROM ";
            $st .= $this->assembledtables();
            break;
        case "INSERT" :
            $st .= " INTO ";
            $st .= $this->assembledtables();
            $st .= $this->assembledfields("INSERT");
            break;
        case "UPDATE" :
            $st .= $this->assembledtables();
            $st .= " SET ";
            $st .= $this->assembledfields("UPDATE");
            break;
        case "DELETE" :
            $st .= " FROM ";
            $st .= $this->assembledtables();
            break;
        case "CREATE" :
        case "DROP" :
        default :
        }
        $st .= $this->assembledconditions();
        $st .= $this->assembledsorts();
        return $st;
    }

    function assembledtables()
    {
        if (count($this->tables) == 0) return "*MISSING*";
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
    function assembledfields($type)
    {
        $f = "";
        $this->bindstring = "";
        switch ($this->type) {
        case "SELECT" :
            if (count($this->fields) == 0) return "*";
            foreach ($this->fields as $field) {
                if (is_array($field)) {
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
                xarErrorSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR_QUERY', new SystemMessage(xarML('Your query has no fields.')));
                return;
            }
            foreach ($this->fields as $field) {
                if (is_array($field)) {
                    if(isset($field['name']) && isset($field['value'])) {
                        if ($this->usebinding) {
                            $this->bindstring .= $field['name'] . " = ?, ";
                            $this->bindvars[] = $field['value'];
                        }
                        else {
                            if (gettype($field['value']) == 'string') {
                                $sqlfield = $this->dbconn->qstr($field['value']);
                            }
                            else {
                                $sqlfield = $field['value'];
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
    function assembledsorts()
    {
        $s = "";
        if (count($this->sorts)>0) $s = " ORDER BY ";
        foreach ($this->sorts as $sort) {
            if (is_array($sort)) {
                $s .= $sort['name'] . " " . $sort['order']  . ", ";
            }
            else {
                // error msg
            }
        }
        if ($s != "") $s = substr($s,0,strlen($s)-2);
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
        $count = count($this->fields);
        for ($i=0;$i<$count;$i++) {
            if (($this->fields[$i]['name'] == $x)) unset($this->fields[$i]);
            elseif (isset($this->fields[$i]['alias']) && ($this->fields[$i]['alias'] == $x))
            unset($this->fields[$i]);

        }
    }
    function clearconditions()
    {
        $this->conditions = array();
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
        if ($this->type == 'SELECT' && $this->rowstodo != 0 && $this->limits == 1 && $this->israwstatement) {
            $temp = $this->fields;
            $this->clearfields();
            $this->addfield('COUNT(*)');
            $this->setstatement();
            $result = $this->dbconn->Execute($this->statement);
            list($this->rows) = $result->fields;
            $this->fields = $temp;
            $this->setstatement();
        }
        return $this->rows;
    }
    function getrowstodo()
    {
        return $this->rowstodo;
    }
    function getversion()
    {
        return $this->version;
    }

    function getorder($x='')
    {
        if ($this->sorts == array()) return false;
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
        if ($x == '') $this->dbconn =& xarDBGetConn();
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
    function qecho()
    {
        $this->setstatement();
        if ($this->usebinding) $this->bindstatement();
        echo $this->getstatement();
    }
    function bindstatement()
    {
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
}
?>