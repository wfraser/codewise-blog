<?php

/*
** L1 MySQL Driver version 1.6.3
** for CodewiseBlog Multi-User
**
** by Bill R. Fraser <bill.fraser@gmail.com>
** Copyright (c) 2004 - 2005 Codewise.org
*/

/*
** This file is part of CodewiseBlog
**
** CodewiseBlog is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** CodewiseBlog is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with CodewiseBlog; if not, write to the Free Software
** Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if(!defined("L1SQL_ASSOC")) define("L1SQL_ASSOC",1);
if(!defined("L1SQL_NUM"))   define("L1SQL_NUM",  2);
if(!defined("L1SQL_BOTH"))  define("L1SQL_BOTH", 3);

if(!class_exists("L1_MySQL")) { class L1_MySQL
{
    var $set = false; //true if connected and database set

    var $hostname = "";
    var $username = "";
    var $password = "";
    var $database = "";
    var $session = null;

    var $error_no = 0;
    var $error_str = "";
    var $halt_on_error = true; //if true, stop on an error

    var $results = array();  //array of query results by index
    var $num_rows = array(); //array of row numbers by index
    var $queries = array();  //array of query strings by index

    var $error_callback;   // function for displaying fatal errors
    var $warning_callback; // function for displaying warnings

    var $pconnect = false; // use persistent connections?

    /*
    ** Return version string.
    */
    function version()
    {
        return("1.6.4");
    }

    function get($var)
    {
        return($this->$var);
    }

    function set($var,$value)
    {
        $this->$var = $value;
        return($value);
    }

    /*
    ** Escape and prepare values for inclusion in a MySQL query.
    **
    ** Backslashes single slashes and adds single slashes around values.
    ** eg: "O'Reilly's" => "'O\'Reilly\'s'"
    ** Also converts php null value to "null"
    */
    function prepare_value($data, $use_slashes = TRUE)
    {
        if($use_slashes)
            $slash = "'";
        else
            $slash = "";

        if(is_array($data))
        {
            $a_sql = array();
            foreach($data as $field => $value)
            {
                $a_sql[ $this->prepare_value($field,FALSE) ] = ($value === null ? "null" : $slash.mysql_real_escape_string($value,$this->session).$slash);
            }
            return($a_sql);
        } else {
            $sql = ($data === null ? "null" : $slash.mysql_real_escape_string($data,$this->session).$slash);
            return($sql);
        }
    }

    /*
    ** Format an array of data into an INSERT query
    ** Data is in the form of array(columname=>value,...)
    **
    ** It will pass the query to issue_query() unless the third parameter is TRUE,
    ** in which case it returns the SQL statement generated.
    **
    ** Otherwise it returns the index of the query or FALSE if the query failed.
    */
    function insert($table,$a_values,$return = false)
    {
        if(!$this->set && !stristr($table,"."))
        {
            if(!$this->trycode("database"))
                $this->error("Could not change database, database not specified in table parameter");
        }

        $a_sql = $this->prepare_value($a_values);
        $fields = implode(",",array_keys($a_sql));
        $values = implode(",",array_values($a_sql));

        $sql = "INSERT INTO $table ($fields) VALUES($values)";
        if($return)
            return($sql);
        $index = $this->trycode("issue_query",$sql);
        if(!$index)
            $this->error("Error issuing INSERT query.");
        return($index);
    }

    /*
    ** Format an array of data into an UPDATE query
    ** Data is in the form of array(columname=>value,...)
    **
    ** The $condition argument is an array in the form of
    ** array(columnname=>value), and it causes the statement to be applied where
    ** columnname has a value equal to value.
    **
    ** It will pass the query to issue_query() unless the fourth parameter is
    ** TRUE, in which case it returns the SQL statement generated.
    **
    ** Otherwise it returns the index of the query or FALSE if the query failed.
    */
    function update($table,$a_values,$condition,$return = false)
    {
        if(!$this->set && !stristr($table,"."))
        {
            if(!$this->trycode("database"))
                $this->error("Could not change database, database not specified in table parameter");
        }

        $a_sql = array();
        $a_sql = $this->prepare_value($a_values);
        $condition = $this->prepare_value($condition);
        foreach($a_sql as $col => $val)
            $set .= "$col = $val, ";
        $set = substr($set,0,-2);

        foreach($condition as $col => $val)
            $cond .= "$col = $val AND ";
        $cond = substr($cond,0,-5);

        $sql = "UPDATE $table SET $set WHERE $cond";
        if($return)
            return($sql);
        $index = $this->trycode('issue_query',$sql);
        if(!$index)
            $this->error("Error issuing UPDATE query.");
        return($index);
    }

    /*
    ** Issue a mysql query.
    **
    ** Returns the array index that will be used to store results and rows,
    ** returns FALSE on query failure
    */
    function issue_query($query)
    {
        list($usec,$sec) = explode(" ",microtime()); // this may be changed in the future
        $index = (string) $sec . substr($usec,1);    //
        if(!@mysql_get_server_info($this->session))
        {
            if(!$this->trycode("connect")) // try to connect with current params
            {
                $this->error("Driver not connected, connection tried and failed.");
                return(false);
            }
        }
        $this->queries[$index] = $query;
        $this->num_rows[$index] = 0;
        $this->results[$index] = @mysql_query($query,$this->session);
        // hmm, should we generate error or leave it up to scripts? For now, yes.
        if($this->check_error("Query error",$php_errormsg)) return(false);
        if(!$this->results[$index])
        {
            $this->error("Query error");
            return(false);
        }
        $this->num_rows[$index] = @mysql_affected_rows($this->session);
        return $index;
    }

    /*
    ** Returns an array of all the tables in the current database as a numeric
    ** array
    **
    ** Returns FALSE on any kind of error.
    */
    function fetch_tables()
    {
        if(!$this->set)
            $this->error("Database connection is not set.");

        list($usec,$sec) = explode(" ",microtime()); // same method as issue_query()
        $index = (string) $sec . substr($usec,1);    //

        $this->results[$index] = @mysql_list_tables($this->database,$this->session);
        $this->queries[$index] = "L1_MySQL::fetch_tables()"; // special query
        $this->num_rows[$index] = 0;

        if($this->check_error("Error listing tables")) return(false);
        if(!$this->results[$index])
        {
            $this->error("Error listing tables",$php_errormsg);
            return(false);
        }

        $this->num_rows[$index] = @mysql_affected_rows($this->session);

        $return = array();
        while($row = @mysql_fetch_array($this->results[$index],L1SQL_NUM))
        {
            if($this->check_error("Error fetching table listing")) return(false);
            $return = array_merge($return,$row);
        }
        return($return);
    }

    /*
    ** Fetch all the rows of a query
    **
    ** This will return a multidimensional array of the form
    ** $return[row][column]
    ** You must give the index returned by issue_query.
    ** Specify the format of the column as the first argument (it is a constant
    ** passed to mysql_fetch_array ;) and if you want the rows indexed by a
    ** particular field, list that as the second argument, otherwise they'll be
    ** numeric in the order returned by MySQL.
    ** The results are also stored in $this->row[$index]
    */
    function fetch_all($index, $type = L1SQL_BOTH, $index_by = "")
    {
        if(($type != L1SQL_ASSOC) && ($type != L1SQL_NUM) && ($type != L1SQL_BOTH))
        {
            $this->error("Invalid type specified for L1_MySQL::fetch_all");
            return(false);
        }
        $return = array();
        for($i=0;$row = @mysql_fetch_array($this->results[$index],$type);$i++)
        {
            if($index_by !== "")
            {
                $field = $row[$index_by];
                $return[$field] = $row;
            } else {
                $return[$i] = $row;
            }
        }
        //$this->free_result($index);
        return($return);
    }

    /*
    ** Fetch the result as an array of rows in a column
    **
    ** For use when the result is many rows containing one column each
    ** It will return a numeric array based on the order the rows are returned.
    */
    function fetch_column($index, $col_offset = 0, $index_by = "")
    {
        $return=array();
        for($i=0;$row = @mysql_fetch_array($this->results[$index],L1SQL_BOTH);$i++)
        {
            $this->check_error("Error fetching column",$php_errormsg);
            $php_errormsg = false;
            if(!isset($row[$col_offset]))
            {
                $this->error("Column offset too high: column $col_offset requested, ".
                    count($row)." columns in result");
                return(false);
            }
            $idx = $index_by === "" ? $i : $row[$index_by];
            $return[$idx]=$row[$col_offset];
            //$this->free_result($index);
        }
        return($return);
    }

    /*
    ** Fetch one row of the result of a query.
    **
    ** You must give the index returned by issue_query().
    ** The return value can be an associative array (column => value),
    ** numeric ([0] => value), or both.
    ** Specify the argument to mysql_fetch_array.
    */
    function fetch_row($index, $row_offset = 0, $type = L1SQL_BOTH)
    {
        if(($type != L1SQL_ASSOC) && ($type != L1SQL_NUM) && ($type != L1SQL_BOTH))
        {
            $this->error("Invalid type specified for L1_MySQL::fetch_all");
            return(false);
        }

        if($this->num_rows[$index] < $row_offset)
        {
            $this->error("Row offset too high: row $row_offset requested, ".
                $this->num_rows[$index]." rows in result.");
            return(false);
        }

        // loop through specified # of rows
        for($i=0;$i<$row_offset;$i++)
            @mysql_fetch_array($this->results[$index]);

        $row = @mysql_fetch_array($this->results[$index],$type);
        if($this->check_error("Error fetching row",$php_errormsg)) return(false);
        if(is_array($row))
        {
            return($row);
        } else {
            //$this->free_result($index);
            return(false);
        }
    }

    /*
    ** Fetch a single variable from a query
    **
    ** You must give the index returned by issue_query().
    **
    ** Use this to get a single value from a query at the specified row and column
    ** offsets. The column offset can be a string row name or numeral row number.
    **
    ** On error, a message is generated and the function returns FALSE.
    */
    function fetch_var($index, $row_offset = 0, $col_offset = 0)
    {
        if($this->num_rows[$index] < $row_offset)
        {
            $this->error("Row offset too high: row $row_offset requested, ".
                "{$this->num_rows[$index]} rows in result");
            return(false);
        }

        // loop through the specified # of rows
        for($i=0;$i<$row_offset;$i++)
            @mysql_fetch_array($this->results[$index]);

        $row = @mysql_fetch_array($this->results[$index],L1SQL_NUM);
        if($this->check_error("Error fetching row",$php_errormsg)) return(false);

        //if(isset($row[$col_offset]))
        if(in_array($col_offset, array_keys($row)))
        {
            $return = $row[$col_offset];
        } else {
            $this->error("Column offset too high: column $col_offset requested, ".
                count($row)." columns in result");
            $return = false;
        }

        return($return);
    }

    /*
    ** Free result of a query
    **
    ** You must give the index of the results to free
    ** This function isn't actually ever used here; all results are saved.
    ** You can use this if you want, but it isn't really necessary.
    */
    function free_result($index)
    {
        if(@is_resource($this->results[$index]))
        {
            $ok = @mysql_free_result($this->results[$index]);
            if(!$ok)
                $this->error("Error freeing result",$php_errormsg);
        } else {
            $this->error("Result is not a resource",$php_errormsg);
        }
        unset($this->results[$index]);
        // might as well unset these too
        unset($this->query[$index]);
        unset($this->num_rows[$index]);

        return(true);
    }

    /*
    ** Print an error
    **
    ** This is the origional error message function that only prints one line of
    ** backtrace info (it tries to determine the most relevant line)
    */
    function errorMsg($message,$backtrace,$severity)
    {
        if(empty($backtrace))
        {
            $out = "$message<br /><i>no backtrace info available</i><br />\n";
        } else {
            for($i=count($backtrace);$i>-1;$i--)
            {
                if(!stristr($backtrace[$i]['file'],__FILE__))
                {
                    $traceline = $backtrace[$i+1];
                }
            }

            $line = isset($traceline['line'])?$traceline['line']:"unknown";
            $file = isset($traceline['file'])?$traceline['file']:"unknown";
            $function = isset($traceline['function'])?$traceline['function']."()":"unknown";
            $class = isset($traceline['class'])? " in class <b>".$traceline['class']."</b>" : "";
            $out = "$message<br />in fule <b>$file</b> on line <b>$line</b> in function <b>$function</b>$class<br />";
        }
        switch($severity)
        {
case E_USER_ERROR:
            if($this->error_callback !== null)
                call_user_func($this->error_callback,$out);
            else
                print($out);
            exit;

case E_USER_WARNING:
            if($this->warning_callback !== null)
                call_user_func($this->warning_callback,$out);
            else
                print($out);

case E_USER_NOTICE:
default:
            break;
        }
    }

    function multiErrorMsg($message,$backtrace,$severity)
    {
        switch($severity)
        {
case E_USER_ERROR:
case E_USER_WARNING:
                $out = $message."<br />\n";
                if(empty($backtrace))
                {
                    $out .= "<i>no backtrace info available</i><br />\n";
                } else {
                    foreach($backtrace as $entry)
                    {
                        $line = isset($entry['line']) ? $entry['line'] : "unknown";
                        $file = isset($entry['file']) ? $entry['file'] : "unknown";
                        $function = isset($entry['function']) ? $entry['function']."()" : "unknown";
                        $class = isset($entry['class']) ? " in class <b>".$entry['class']."</b>" : "";
                        $out .= "in file <b>".$file."</b> on line <b>".$line."</b> in function <b>".$function."</b>".$class."<br />\n";
                    }
                }
                if($severity == E_USER_ERROR)
                {
                    if($this->error_callback !== null)
                    {
                        call_user_func($this->error_callback,$out."<b>FATAL</b>\n");
                        exit;
                    } else {
                        print($out."<b>FATAL</b>\n");
                        exit;
                    }
                } else {
                    if($this->warning_callback !== null)
                    {
                        call_user_func($this->warning_callback,$out);
                    } else {
                        print($out);
                    }
                }

case E_USER_NOTICE:
default:
                break;
        }
    }

    /*
    ** Handle an error
    **
    ** Use this when you're sure there is an error, and you want
    ** it cleanly handled.
    ** It will display the error code and explanation as well as
    ** the message you set.
    **
    ** You MUST pass $php_errormsg as the second parameter for it to be used!
    **
    ** It will obey $halt_on_error unless behavior
    ** is otherwise specified in the third parameter.
    **
    ** The fourth parameter should only be set when being called by check_error().
    **
    ** If the fifth parameter is true, a shorter error message is printed
    ** (with only one line of backtrace instead of the default all).
    */
    function error($message = "", $php = "", $force_abort = "", $check_error = "", $short_error = false)
    {
        if($check_error === "") // we're being called from a script
        {
            if(mysql_errno()) //MySQL error
            {
                $type = "MySQL";
                $this->error_no = mysql_errno();
                $this->error_str = mysql_error();
            } elseif($php) { //PHP error
                $type = "Script";
                $this->error_no = -1;
                $this->error_str = $php;
            } else { //some other error
                $type = "Special";
                $this->error_no = -1;
                $this->error_str = "L1 MySQL Driver special error";
            }
        } else { // check_error() has already diagnosed the problem
            $type = $check_error;
        }

        $msg_func = ($short_error) ? "errorMsg" : "multiErrorMsg";

        if(function_exists("debug_backtrace"))
            $backtrace = debug_backtrace();
        else
            $backtrace = null;

        if($force_abort !== "" ? $force_abort : $this->halt_on_error)
        {
            $this->disconnect();
            $this->$msg_func(nl2br(htmlspecialchars(
                "$message\n$type error #".$this->error_no." (".$this->error_str.")"
            )),$backtrace,E_USER_ERROR);
        } else {
            $this->$msg_func(nl2br(htmlspecialchars(
                "$message\n$type warning #".$this->error_no." (".$this->error_str.")"
            )),$backtrace,E_USER_WARNING);
        }
    }

    /*
    ** Check for an error
    **
    ** This is for use when you aren't sure there's an error
    ** or it's not very easy to test for it. This will catch it.
    **
    ** You MUST pass $php_errormsg as the second parameter for it to be used!
    **
    ** Set the message to give if an error is found,
    ** and if it's necessary to stop on error, set the third parameter.
    **
    ** This function returns TRUE if an error was detected, otherwise FALSE;
    ** this makes it easy to do things like:
    **   if(check_error("message")) return(false);
    */
    function check_error($message = "", $php = "", $force_abort = "", $short_error = false)
    {
        if(mysql_errno()) //mysql error
        {
            $type = "MySQL";
            $this->error_no = mysql_errno();
            $this->error_str = mysql_error();
        } elseif($php) { //php error
            $type = "Script";
            $this->error_no = -1;
            $this->error_str = $php;
        } else { //no error
            $type = "";
            $this->error_no = 0;
            $this->error_str = "";
        }

        if($type != "")
        {
            if($message !== "")
            {
                $this->error($message,$php,$force_abort,$type,$short_error);
                return(true);
            } else {
                return(true);
            }
        } else {
            $php_errormsg = null; //clear any errors generated by this function.
            return(false); //all quiet on the western front ;)
        }
    }

    /*
    ** Switch to the selected database
    */
    function database($database)
    {
        if(!$this->session)
        {
            $conn_success = $this->trycode("connect");
            if(!$conn_success)
            {
                $this->error("Could not connect");
                return(false);
            }
        }

        $ok = @mysql_select_db($database, $this->session);
        if(!$ok)
        {
            $this->error("Error selecting database",$php_errormsg);
            return(false);
        }

        $this->database = $database;
        $this->set = true;
        return(true);
    }

    /*
    ** Connect to database server
    **
    ** It will use given connection settings or use ones already in the class.
    ** It returns true on success.
    ** Host should be either "localhost[:/path/to/sock]", "1.2.3.4[:port]" or
    **   "hostname[:port]". Only use "localhost..." for local socket connections.
    */
    function connect($hostname = "", $username = "", $password = "")
    {
        if($hostname === "")
            $hostname = $this->hostname;
        else
            $this->hostname = $hostname;

        if($username === "")
            $username = $this->username;
        else
            $this->username = $username;

        if($password === "")
            $password = $this->password;
        else
            $this->password = $password;

        if($this->pconnect)
        {
            $this->session = @mysql_pconnect($hostname,$username,$password);
        } else {
            $this->session = @mysql_connect($hostname,$username,$password);
        }

        if(!$this->session)
        {
            $this->error("Could not connect to database server",$php_errormsg);
            return(false);
        } else {
            return(true);
        }
    }

    /*
    ** Disconnect from database
    ** and optionally reset the class
    */
/* #666# LINE OF THE BEAST... }:-(< ######################################### */
    function disconnect($reset = false)
    {
        if(!@mysql_get_server_info($this->session))
            return(false);
        $ok = @mysql_close($this->session);
        if(!$ok)
        {
            $this->error("Couldn't disconnect from database server",$php_errormsg);
            return(false);
        }
        $this->session = "";
        $this->set = false;
        if($reset)
        {
            $this->hostname = "";
            $this->username = "";
            $this->password = "";
            $this->database = "";

            $this->error_no = 0;
            $this->error_str = "";

            $this->results = array();
            $this->num_rows = array();
            $this->queries = array();

            $this->error_callback = null;
            $this->warning_callback = null;
            $this->pconnect = false;
        }
        return(true);
    }

    /*
    ** Instantiater function
    **
    ** If given the proper values, will set up class and connect to database server.
    ** If also given a database, will switch to the database.
    */
    function L1_MySQL($hostname = "", $username = "", $password = "", $database = "", $halt_on_error = true, $pconnect = false)
    {
        $this->halt_on_error = $halt_on_error; //override the default
        $this->pconnect = $pconnect; // override the default
        if(($hostname !== "") && ($username !== "") && ($password !== ""))
        {
            $this->session = @mysql_connect($hostname,$username,$password);
            if(!@mysql_get_server_info($this->session))
                $this->error("Could not connect to database server",$php_errormsg);
            $this->hostname = $hostname;
            $this->username = $username;
            $this->password = $password;

            if($database !== "") //just to be safe; database could be "false" or "0"
            {
                $this->database = $database;
                $ok = @mysql_select_db($database,$this->session);
                if(!$ok)
                    $this->error("Could not select database",$php_errormsg);
                $this->set = true;
            } else {
                $this->set = false;
            }
        }
    }

    /*
    ** Class dumper
    **
    ** This is a good function to use for debugging
    ** WARNING!! This will reveal the password field of the class unless
    ** you specify otherwise!
    */
    function dump($no_password = false)
    {
        if(!$no_password)
        {
            print("<pre>\n");
            print_r($this);
            print("</pre>\n");
        } else {
            ob_start();
            print("<pre>\n");
            print_r($this);
            print("</pre>\n");
            $out = ob_get_contents();
            ob_clean();
            $out = ereg_replace("    \[password\] => [^\n]*\n","    [password] => *****\n",$out);
            print($out);
        }
    }

    /*
    ** Executes code in an environment where errors will be completely ignored.
    ** Error messages will be suppressed, and execution will continue thru errors.
    **
    ** To execute code, pass the name of the function in this class to execute,
    ** and pass the remaining arguments in order.
    */
    function trycode($func)
    {
        ob_start();
        $params = func_get_args();
        array_shift($params);

        $old_halt = $this->halt_on_error;
        $this->halt_on_error = false;
        $old_warning_callback = $this->warning_callback;
        $this->warning_callback = null;
        $old_error_callback = $this->error_callback;
        $this->error_callback = null;

        $retval = call_user_func_array(array(&$this,$func),$params);
        //eval('$retval = '.$eval_code);

        $this->error_callback = $old_error_callback;
        $this->warning_callback = $old_warning_callback;
        $this->halt_on_error = $old_halt;

        ob_end_clean();
        return($retval);
    }

    function register_error_func($callback,$type = "")
    {
        switch($type)
        {
case E_USER_ERROR:
            if(function_exists($callback))
                $this->error_callback = $callback;
            break;

case E_USER_WARNING:
            if(function_exists($callback))
                $this->warning_callback = $callback;
            break;

default:        if(function_exists($callback))
                $this->error_callback = $this->warning_callback = $callback;
            break;
        }
    }

    function unregister_error_func($type = "")
    {
        switch($type)
        {
case E_USER_ERROR:
            $this->error_callback = null;
            break;

case E_USER_WARNING:
            $this->warning_callback = null;
            break;

default:        $this->error_callback = $this->warning_callback = null;
            break;
        }
    }

}}

/*
** Simple replacement function for pre PHP 4.3.0
*/
if(!function_exists("mysql_real_escape_string"))
{
    function mysql_real_escape_string($string, $link = "")
    {
        $search = array
        (
            "\\",
            "\x00",
            "\n",
            "\r",
            "'",
            "\"",
            "\x1a",
        );
        $replace = array
        (
            "\\\\",
            "\\\x00",
            "\\\n",
            "\\\r",
            "\\'",
            "\\\"",
            "\\\x1a",
        );
        return(str_replace($search,$replace,$string));
    }
}

?>
