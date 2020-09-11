<?php

/**
 *  Copyright (c) 2018. Nicolas Choukroun.
 *  Copyright (c) 2018. The PHPSnipe Developers.
 *  This program is free software; you can redistribute it and/or modify it
 *  under the terms of the Attribution 4.0 International License as published by the
 *  Creative Commons Corporation; either version 2 of the License, or (at your option)
 *  any later version.  See COPYING for more details.
 *
 * **************************************************************************** */
$con_gb = 0; //  global to avoid duplicating the class too much

/**
 * Class Database
 */
class Database {

    /**
     * @var string
     */
    var $Host = DB_HOST;        // Hostname of our MySQL server.

    /**
     * @var string
     */
    var $Database = DB_NAME;         // Logical database name on that server.

    /**
     * @var string
     */
    var $User = DB_USER;             // User and Password for login.

    /**
     * @var string
     */
    var $Password = DB_PASSWORD;

    /**
     * @var string
     */
    var $table = "settings";

    /**
     * @var
     */
    var $con;                  // Result of mysql_connect().

    /**
     * @var
     */
    var $result;                  // Result of most recent mysql_query().

    /**
     * @var array
     */
    var $Record = array();            // current mysql_fetch_array()-result.

    /**
     * @var array
     */
    var $rs = array();            // current mysql_fetch_array()-result.

    /**
     * @var
     */
    var $Row;                           // current row number.

    /**
     * @var string
     */
    var $LoginError = "";

    /**
     * @var int
     */
    var $Errno = 0;                  // error state of query...

    /**
     * @var string
     */
    var $Error = "";

    /**
     *  Queries the database
     *
     * @param $sql
     * @param string $cond
     * @return bool|mysqli_result
     */
    function query($sql, $cond = "") {
        Global $settings, $user;
        $sql1 = $sql;
        //$sql1="";
        //if ($user->userid>0) {
        $t = time();
        //}
        $sql = str_replace("[", " ", $sql);
        $sql = str_replace("]", " ", $sql);

        // specific anti- zeor days injections...yep hacker we know these.
        //if (strpos($sql,"%20")>0) die("SQL Injection attempt, IP logged.".$sql1);
        if (strpos($sql, "%2C0x") > 0)
            die("SQL Injection attempt, IP logged 1." . $sql1);
        if (strpos(strtolower($sql), "[") > 0)
            die("SQL Injection attempt, IP logged 2." . $sql1);
        if (strpos(strtolower($sql), "]") > 0)
            die("SQL Injection attempt, IP logged 3." . $sql1);
        //if (strpos(strtolower($sql),"(char")>0) die("SQL Injection attempt, IP logged 4.".$sql1);
        if (strpos(strtolower($sql), "%2funi") > 0)
            die("SQL Injection attempt, IP logged 5." . $sql1);
        if (strpos(strtolower($sql), "(sleep") > 0)
            die("SQL Injection attempt, IP logged 6." . $sql1);
        if (strpos(strtolower($sql), "(%2fon") > 0)
            die("SQL Injection attempt, IP logged 7." . $sql1);
        if (strpos(strtolower($sql), "%2Fselect") > 0)
            die("SQL Injection attempt, IP logged 8." . $sql1);
        if (strpos(strtolower($sql), "%20unhex") > 0)
            die("SQL Injection attempt, IP logged 9." . $sql1);
        if (strpos($sql, "sElEcT") > 0)
            die("SQL Injection attempt, IP logged 10." . $sql1);
        if (strpos($sql, "cOnCaT") > 0)
            die("SQL Injection attempt, IP logged 11." . $sql1);
        if (strpos($sql, "!ABC145ZQ62DWQAFPOIYCFD!") > 0)
            die("SQL Injection attempt, IP logged 12." . $sql1);
        if (strpos(strtolower($sql), "information_schema") > 0)
            die("SQL Injection attempt, IP logged 13." . $sql1);

        $this->connect();

        if ($sql == "")
            return false;
        $this->result = @mysqli_query($this->con, $sql);
        if ($this->result === false) {
            $this->Row = 0;
            $this->Errno = mysqli_errno($this->con);
            if (!$this->result)
                $this->halt("Invalid SQL: " . $sql);
        }

        $tt = time() - $t;
        if ($tt > 1) {
            $log = "\nuser=" . $user->userid . " - " . $tt . " seconds sql=" . $sql . " - referer: " . $_SERVER['HTTP_REFERER'];
            logtofile($log);
        }
        return $this->result;
    }

    /**
     * Database connection
     */
    function connect() {
        if (!isset($this->con)) {
            $this->con = mysqli_connect($this->Host, $this->User, $this->Password, $this->Database);
            // check connection
            if (mysqli_connect_errno()) {
                printf("Database Connect failed: %s\n", mysqli_connect_error());
                exit();
            }
        }
        mysqli_set_charset($this->con, 'utf8');
    }

    /**
     * If error, halts the program
     *
     * @param $msg
     */
    function halt($msg) {
        printf("<strong>Database error:</strong> %sn", $msg);
        printf(" <strong>MySQL Error</strong>: %s (%s)n", $this->Errno, $this->Error);
        die("Session halted.");
    }

    /**
     * prepare the SQL depending on the type of the data
     *
     * @param $theValue
     * @param $theType
     * @param string $theDefinedValue
     * @param string $theNotDefinedValue
     * @return int|mixed|string
     */
    function prepSQL($theValue, $theType, $theDefinedValue = "", $theNotDefinedValue = "") {
        $theValue = str_replace('\'', ' ', $theValue);
        $theValue = str_replace('\r\n', '<br>', $theValue);
        if ($theType == "date") {
            $theValue = str_replace('/', '-', $theValue);
            if (!$this->valid_date($theValue)) {
                trim($theValue);
                list($d, $m, $y) = explode('-', $theValue);
                $mk = mktime(0, 0, 0, $m, $d, $y);
                $theValue = strftime('%Y-%m-%d', $mk);
            }
        }
        switch ($theType) {
            case "text":
                $theValue = ($theValue != "") ? "'" . $theValue . "'" : "NULL";
                break;
            case "long":
            case "int":
                $theValue = ($theValue != "") ? intval($theValue) : 0;
                break;
            case "double":
                $theValue = ($theValue != "") ? "'" . floatval($theValue) . "'" : "NULL";
                break;
            case "date":
                $theValue = ($theValue != "") ? "'" . $theValue . "'" : "NULL";
                break;
            case "defined":
                $theValue = ($theValue != "") ? $theDefinedValue : $theNotDefinedValue;
                break;
        }
        return $theValue;
    }

    /**
     * Check date validity
     *
     * @param $date
     * @param string $format
     * @return bool
     */
    function valid_date($date, $format = 'YYYY-MM-DD') {
        if (strlen($date) >= 8 && strlen($date) <= 10) {
            $separator_only = str_replace(array('M', 'D', 'Y'), '', $format);
            $separator = $separator_only[0];
            if ($separator) {
                $regexp = str_replace($separator, "\\" . $separator, $format);
                $regexp = str_replace('MM', '(0[1-9]|1[0-2])', $regexp);
                $regexp = str_replace('M', '(0?[1-9]|1[0-2])', $regexp);
                $regexp = str_replace('DD', '(0[1-9]|[1-2][0-9]|3[0-1])', $regexp);
                $regexp = str_replace('D', '(0?[1-9]|[1-2][0-9]|3[0-1])', $regexp);
                $regexp = str_replace('YYYY', '\d{4}', $regexp);
                $regexp = str_replace('YY', '\d{2}', $regexp);
                if ($regexp != $date && preg_match('/' . $regexp . '$/', $date)) {
                    foreach (array_combine(explode($separator, $format), explode($separator, $date)) as $key => $value) {
                        if ($key == 'YY')
                            $year = '20' . $value;
                        if ($key == 'YYYY')
                            $year = $value;
                        if ($key[0] == 'M')
                            $month = $value;
                        if ($key[0] == 'D')
                            $day = $value;
                    }
                    if (checkdate($month, $day, $year))
                        return true;
                }
            }
        }
        return false;
    }

    /**
     * Retrieves the next record in a recordset
     *
     * @return bool
     */
    function nextRecord() {
        @$this->Record = mysqli_fetch_array($this->result, MYSQLI_ASSOC);
        $this->rs = $this->Record;
        $this->Row += 1;
        $this->Errno = mysqli_errno($this->con);
        $this->Error = mysqli_error($this->con);
        $stat = is_array($this->Record);
        if (!$stat) {
            @mysqli_free_result($this->result);
            $this->result = 0;
        }
        return $stat;
    }

    /**
     *  Retrieves the next record in a recordset
     * @return bool
     */
    function next() {
        @$this->Record = mysqli_fetch_array($this->result, MYSQLI_ASSOC);
        $this->rs = $this->Record;
        $this->Row += 1;
        $this->Errno = mysqli_errno($this->con);
        $this->Error = mysqli_error($this->con);
        $stat = is_array($this->Record);
        if (!$stat) {
            @mysqli_free_result($this->result);
            $this->result = 0;
        }
        return $stat;
    }

    /**
     * Retrieves a single record
     *
     * @return bool
     */
    function singleRecord() {
        $this->Record = mysqli_fetch_array($this->result, MYSQLI_ASSOC);
        $this->rs = $this->Record;
        $stat = is_array($this->Record);
        return $stat;
    }

    /**
     * Retrieves a single record
     *
     * @return bool
     */
    function single() {
        $this->Record = mysqli_fetch_array($this->result, MYSQLI_ASSOC);
        $this->rs = $this->Record;
        $stat = is_array($this->Record);
        return $stat;
    }

    /**
     * Returns the number of rows  in a recordset
     *
     * @return int
     */
    function numRows() {
        return mysqli_num_rows($this->result);
    }

    /**
     * Returns the number of rows  in a recordset
     *
     * @return int
     */
    function nbrRows() {
        return mysqli_num_rows($this->result);
    }

    /**
     * @return int
     */
    function nbr() {

        return mysqli_num_rows($this->result);
    }

    /**
     * Returns the Last Insert Id
     *
     * @return int|string
     */
    function lastId() {
        return mysqli_insert_id($this->con);
    }

    /**
     * Returns Escaped string
     *
     * @param $inp
     * @return array|mixed
     */
    function mysqlEscapeMimic($inp) {
        if (is_array($inp))
            return array_map(__METHOD__, $inp);
        if (!empty($inp) && is_string($inp)) {
            return str_replace(['\\', "\0", "\n", "\r", "'", '"', "\x1a"], ['\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'], $inp);
        }
        return $inp;
    }

    /**
     * Returns Escaped string
     *
     * @param $inp
     * @return array|mixed
     */
    function escapeMimic($inp) {
        if (is_array($inp))
            return array_map(__METHOD__, $inp);
        if (!empty($inp) && is_string($inp)) {
            return str_replace(['\\', "\0", "\n", "\r", "'", '"', "\x1a"], ['\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'], $inp);
        }
        return $inp;
    }

    /**
     * Clean a string
     *
     * @param $inp
     * @return array|mixed
     */
    function clean($inp) {
        if (is_array($inp))
            return array_map(__METHOD__, $inp);
        if (!empty($inp) && is_string($inp)) {
            $inp = str_replace(['\\', "\0", "\n", "\r", "'", '"', "\x1a"], ['\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'], $inp);
        }

        return $inp;
    }

    /**
     * Escape a string
     *
     * @param $text
     * @return string
     */
    function escape($text) {
        return mysqli_real_escape_string($this->con, $text);
    }

    /**
     * Returns the number of fields in a recordset
     *
     * @return int
     */
    function numFields() {
        return mysqli_num_fields($this->result);
    }

// end function numRows

    /**
     * Clean exis
     */
    function close() {
        mysqli_close($this->con);
    }

}

?>
