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

    var $type;
    var $tables;
    var $fields;
    var $conditions;
    var $sorts;
    var $result;
    var $rows;
    var $rowstodo;
    var $startat;
    var $output;
    var $row;
    var $dbconn;

//---------------------------------------------------------
// Constructor
//---------------------------------------------------------
    function xarQuery($type='SELECT',$tables='',$fields='')
    {
        if (in_array($type,array("SELECT","INSERT","UPDATE","DELETE"))) $this->type = $type;
        else {
            $msg = xarML('The operation #(1) is not supported', $type);
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR_QUERY', new SystemMessage($msg));
            return;
        }

        $this->tables = array();
        $this->addtables($tables);
        $this->fields = array();
        $this->addfields($fields);
        $this->conditions = array();
        $this->sorts = array();
        $this->result = array();
        $this->output = array();
        $this->row = array();
        $this->dbconn =& xarDBGetConn();
    }


    function run($statement='')
    {
        if ($statement != '') {
            $st = $statement;
        }
        else {
            $st = $this->statement();
            if (stristr($st,'*MISSING*')) {
                $this->result = array();
                return $this->result;
            }
        }
        $result =& $this->dbconn->Execute($st);
        $this->rows = $result->_numOfRows;
        if($this->startat != 0 && $this->rowstodo != 0) {
            $result =& $this->dbconn->SelectLimit($st, $this->rowstodo, $this->startat-1);
        }
        if (!$result) return;
        $this->output = array();
        if (($result->fields) === false) $numfields = 0;
        else $numfields = $result->_numOfFields;
        $this->result = $result;
        if ($this->type == 'SELECT') {
            if ($this->fields == array() && $numfields > 0) {
                $colnames = array();
                foreach ($this->tables as $table) {
                    $colnames += $this->dbconn->MetaColumnNames($table);
                }
                if (count($colnames) == $numfields) {
                    for ($i=0;$i<$numfields;$i++) {
                        $this->fields[$i]['name'] = $colnames[$i];
                   }
                }
                else {
                    $msg = xarML('SELECT with total of columns different from the number retrieved.');
                    xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR_QUERY', new SystemMessage($msg));
                    echo $msg . var_dump($colnames) . $numfields;exit;
                    return;
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
        return $this->result;

    }

    function row($row=0)
    {
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
                    xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR_QUERY', new SystemMessage($msg));
                    return;
                }
                else {
                    $newtable = explode('as',$table);
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
            $msg = xarML('This function only take 1 or 2 paramters');
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemMessage($msg));
            return;
        }
    $this->tables[] = $argsarray;
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
                    xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR_QUERY', new SystemMessage($msg));
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
            $msg = xarML('This function only take 1 or 2 paramters');
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemMessage($msg));
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
            else {$this->tables[]=$tables;}
        }
        else {
            $this->tables = array_merge($this->tables,$tables);
        }
    }

    function eq($field1,$field2)
    {
        $this->conditions[]=array('field1' => $field1,
                                  'field2' => $field2,
                                  'op' => '=');
    }
    function gt($field1,$field2)
    {
        $this->conditions[]=array('field1' => $field1,
                                  'field2' => $field2,
                                  'op' => '>');
    }
    function ge($field1,$field2)
    {
        $this->conditions[]=array('field1' => $field1,
                                  'field2' => $field2,
                                  'op' => '>=');
    }
    function le($field1,$field2)
    {
        $this->conditions[]=array('field1' => $field1,
                                  'field2' => $field2,
                                  'op' => '<=');
    }
    function lt($field1,$field2)
    {
        $this->conditions[]=array('field1' => $field1,
                                  'field2' => $field2,
                                  'op' => '<');
    }
    function like($field1,$field2)
    {
        $this->conditions[]=array('field1' => $field1,
                                  'field2' => $field2,
                                  'op' => 'like');
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
    function getcondition($mycondition)
    {
        foreach ($this->conditions as $condition)
            if ($condition['field1'] == $mycondition) return $condition['field2'];
        return '';
    }
    function removecondition($mycondition)
    {
        for($i=0;$i<count($this->conditions);$i++)
            if ($this->conditions[$i]['field1'] == $mycondition) {
                unset($this->conditions[$i]);
                break;
            }
    }
    function getconditions()
    {
        $c = "";
        foreach ($this->conditions as $condition) {
            if (is_array($condition)) {
                if (gettype($condition['field2']) == 'string') {
                    $sqlfield = $this->dbconn->qstr($condition['field2']);
                }
                else {
                    $sqlfield = $condition['field2'];
                }
                $c .= $condition['field1'] . " " . $condition['op'] . " " . $sqlfield . " AND ";
            }
            else {
            }
        }
        if ($c != "") $c = substr($c,0,strlen($c)-5);
        return $c;
    }
    function statement()
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
        default :
        }
        $st .= $this->assembledconditions();
        $st .= $this->assembledsorts();
        return $st;
    }

// ------ Private methods -----
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
        switch ($this->type) {
        case "SELECT" :
            if (count($this->fields) == 0) return "*";
            foreach ($this->fields as $field) {
                if (is_array($field)) {
                    $f .= $field['name'];
                    $f .= ($field['alias'] != '' ) ? " AS " . $field['alias'] . ", " : ", ";
                }
                else {
                    $f .= $field . ", ";
                }
            }
            if ($f != "") $f = trim($f," ,");
            break;
        case "INSERT" :
            $f .= " (";
            $names = '';
            $values = '';
            foreach ($this->fields as $field) {
                if (is_array($field)) {
                    if(isset($field['name']) && isset($field['value'])) {
                        if (gettype($field['value']) == 'string') {
                            $sqlfield = $this->dbconn->qstr($field['value']);
                        }
                        else {
                            $sqlfield = $field['value'];
                        }
                        $names .= $field['name'] . ", ";
                        $values .= $sqlfield . ", ";
                    }
                }
                else {
//                    $f .= $field . ", ";
                }
            }
            $names = substr($names,0,strlen($names)-2);
            $values = substr($values,0,strlen($values)-2);
            $f .= $names . ") VALUES (" . $values;
            $f .= ")";
            break;
        case "UPDATE" :
            if($this->fields == array('*')) {
                xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR_QUERY', new SystemMessage(xarML('Your query has no fields.')));
                return;
            }
            foreach ($this->fields as $field) {
                if (is_array($field)) {
                    if(isset($field['name']) && isset($field['value']))
                        if (gettype($field['value']) == 'string') {
                            $sqlfield = $this->dbconn->qstr($field['value']);
                        }
                        else {
                            $sqlfield = $field['value'];
                        }
                    $f .= $field['name'] . " = " . $sqlfield . ", ";
                }
                else {
//                    $f .= $field . ", ";
                }
            }
            if ($f != "") $f = substr($f,0,strlen($f)-2);
            break;
        case "DELETE" :
            break;
        }
        return $f;
    }
    function assembledconditions()
    {
        $c = "";
        if (count($this->conditions)>0) {
            $c = " WHERE ";
            $c .= $this->getconditions();
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

        function addorder($x = '', $y = 'ASC')
        {
            if ($x != '') {
                $this->sorts[] = array('name' => $x, 'order' => $y);
            }
        }
        function setorder($x = '',$y = 'ASC')
        {
            if ($x != '') {
                $this->sorts = array(); $this->addorder($x,$y);
            }
        }
        function setstartat($x = 0)
        {
            $this->startat = $x;
        }
        function setrowstodo($x = 0)
        {
            $this->rowstodo = $x;
        }
        function setconnection($x = '')
        {
            if ($x == '') $this->dbconn =& xarDBGetConn();
            else $this->dbconn = $x;
        }
}
?>