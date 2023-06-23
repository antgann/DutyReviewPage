<?php
//
//  Port of the database abstraction layer found on www.phpbuilder.com
//  All old code is commented out
//  Must have $sys_dbHost,$sys_dbUser,$sys_dbPasswd declared in some include file (db_conn.php)
//
//  By: yavor
//  Date: 31/08/00
//  E-mail:iavka@usa.net
//
// W/ modifications by DDG

// //////////////////////////////
// Gets user/pwd & db are Global variables from db_conn.php
// Resulting connection is also passed to other functions by a global

function db_connect_read_db( $_dbName ) {
    global $sys_dbUser, $sys_dbPasswd, $_conn;

    $url = getDbServerString($_dbName); // for some hosts, connection only works with FQDN
    $_conn = db_connect($sys_dbUser, $sys_dbPasswd, $url);
    return $_conn;
}

// //////////////////////////////
function db_connect_write_db( $_dbName ) {
    global $db_power_user, $db_power_password, $_conn;

    $url = getDbServerString($_dbName); // get FQDN
    $_conn = db_connect($db_power_user, $db_power_password, $url);
    return $_conn;
}

function db_connect_glob() {
    global $sys_dbUser, $sys_dbPasswd, $_conn, $sys_dbName;

    $url = getDbServerString($sys_dbName); // for some hosts, connection only works with FQDN
    $_conn = db_connect($sys_dbUser, $sys_dbPasswd, $url);
    return $_conn;
}

// //////////////////////////////
function db_connect_write() {
    global $db_power_user, $db_power_password, $sys_dbName, $_conn;

    $url = getDbServerString($sys_dbName); // get FQDN
    $_conn = db_connect($db_power_user, $db_power_password, $url);
    return $_conn;
}

// //////////////////////////////
// Doesn't depend on db_conn.php for  user/pwd & db via Global variables
function db_connect($_dbuser,$_dbpasswd,$_dbhost) {
    $_conn = oci_connect($_dbuser, $_dbpasswd, $_dbhost);

    if (!$_conn) {
        echo "Error connecting to the database:<br>\nDBServer:$_dbhost<br>";
//\nUsername:$_dbuser<br>\nPassword:$_dbpasswd<br>
    }
    return $_conn;
}

// //////////////////////////////
function db_logoff(){
    global $_conn;
    return oci_close($_conn);
}

function db_close($_conn) {
    return oci_close($_conn);
}

// //////////////////////////////
// Gets connection via Global variables from db_conn.php
// Does autocommit on a delete/insert 
function db_query_glob($qstring) {
     global $_conn;
     $stmt = db_query($_conn, $qstring);
     return $stmt;
}

// //////////////////////////////
// Doesn't depend on db_conn.php for connection via Global variables
// Does autocommit on a delete/insert 
function db_query($_conn, $_qstring) {
    $stmt = oci_parse($_conn, $_qstring);
    $didExecute = oci_execute($stmt);   // mode not specified :. autocommit
    if ($didExecute) {
        return $stmt;
    } else {
        return null;
    }
}



////////////////////////////////
// Gets connection via Global variables from db_conn.php
// Does autocommit on a delete/insert 
// Returns TRUE on success or FALSE on error
function db_execute_glob($qstring) {
    global $_conn,$_stmt;
    return db_execute($_conn, $qstring);
}

////////////////////////////////
// Doesn't depend on db_conn.php for connection via Global variables
// Does autocommit on a delete/insert 
// Returns TRUE on success or FALSE on error
function db_execute($_conn, $_qstring) {
    $stmt = oci_parse($_conn,$_qstring);
    return oci_execute($stmt);   // mode not specified :. autocommit
}

////////////////////////////////
// Return ARRAY of OCI error of the last statement executed.
// Array members are: message, offset and sqltext 
// Print with:      printf("OCIEXECUTE error:%s", $error["message"]);
// Returns FALSE if no error
function db_getError() {
    global $_stmt;
    return oci_error($_stmt);   // could be blank for last Oracle error or $_conn
}

////////////////////////////////
// Gets connection via Global variables from db_conn.php
function db_commit_glob() {
    global $_conn;
    db_commit($_conn);
}
// //////////////////////////////
// Doesn't depend on db_conn.php for connection via Global variables
function db_commit($_conn) {
    oci_commit($_conn);
}

// //////////////////////////////
function db_numrows($_qstring) {
    global $_conn;
    $sql_query = 'SELECT COUNT(*) AS NUMBER_OF_ROWS FROM (' . $_qstring . ')';
    $stmt= oci_parse($_conn, $sql_query);
    oci_define_by_name($stmt, 'NUMBER_OF_ROWS', $number_of_rows);
    oci_execute($stmt);
    $didExecute = oci_fetch($stmt);
    if ($didExecute) {
        return $number_of_rows;
    } else {
        return null;
    }
}

// //////////////////////////////
function db_result($qhandle,$row,$field) {
    echo "This function is not supported in this port";
    return 0;
}

// //////////////////////////////
function db_result_tab($stmt) {
    $ncols = OCINumCols($stmt);
    echo "<table border=1>\n";
    echo "<tr>";
    for ($i = 1; $i <= $ncols; $i++ ) {
        echo "<td><b><font size=2>";
        $column_name = OCIColumnName($stmt, $i);
        echo "$column_name";
        echo "</font></b></td>";
    }
    echo "</tr>\n";
    while (OCIFetch($stmt)) {
        echo "<tr>";
        for ($i = 1; $i <= $ncols; $i++ ) {
            echo "<td><font size=2>";
            $column_value = OCIResult($stmt, $i);
            if (OCIcolumnisnull($stmt,$i)) {
                echo "&nbsp\n";
            } else {
                echo "$column_value\n";
            }
            echo "</font></td>";
        } // end for
        echo "</tr>\n";
    } // end while
    echo "</table>\n";
}

// //////////////////////////////
function db_result_tab_link($stmt,$col,$link) {
    // returns the results in table form, one column being a link
    $ncols = OCINumCols($stmt);
    echo "<table border=1>\n";
    echo "<tr>";
    for ($i = 1; $i <= $ncols; $i++ ) {
        echo "<td><b><font size=2>";
        $column_name = OCIColumnName($stmt, $i);
        if(strcasecmp($column_name, $col) == 0) {
            $j = $i;}
            echo "$column_name";
            echo "</font></b></td>";
         }

         echo "</tr>\n";

        while (OCIFetch($stmt)) {
            echo "<tr>";
            for ($i = 1; $i <= $ncols; $i++ ) {
                echo "<td><font size=2>";
                $column_value = OCIResult($stmt, $i);
                if($i == $j) {
                    echo "<a href=$link?$col=$column_value>$column_value</a>";
                } else {
                    echo "$column_value";
                }
                echo "</font></td>";
             } // end for
             echo "</tr>\n";
        }
    echo "</table>\n";
}

// //////////////////////////////
function db_result_tab_link_dev($stmt,$col,$link) {
        // returns the results in table form, one column being a link
        $results=array();
        $ncols = OCINumCols($stmt);
        echo "<table border=1>\n";
        echo "<tr>";
         for ($i = 1; $i <= $ncols; $i++ )
         {
           echo "<td><b>";
          $column_name = OCIColumnName($stmt, $i);
          if(strcasecmp($column_name, $col) == 0)
          {$j = $i;}
          echo "$column_name";
          echo "</b></td>";
         }
         echo "</tr>\n";
        while (OCIFetch($stmt))
         {
         echo "<tr>";
         for ($i = 1; $i <= $ncols; $i++ )
         {
          echo "<td><font size=2>";
          $column_value = OCIResult($stmt, $i);
	  array_push($results,$column_value);

          if($i == $j)
          {
          echo "<a href=$link>$col</a>";
          }
          else
          {
          echo "$column_value";
          }
          echo "</font></td>";
         }
         echo "</tr>\n";
        }
        echo "</table>\n";
return $results;
}


// //////////////////////////////
function db_numfields_glob($lhandle=0) {
//        return @mysql_numfields($lhandle);
    global $_stmt;
    return OCINumCols($_stmt);
}

// //////////////////////////////
function db_fieldname_glob($lhandle=0,$fnumber) {
    global $_stmt;
    return OCIColumnName($_stmt,$fnumber);
}

// //////////////////////////////
function db_affected_rows($qhandle) {
    return OCIRowCount($qhandle);
}

// //////////////////////////////
function db_fetch_array($qhandle,$assoc=1,$get_lobs=0) {
    $param = OCI_RETURN_NULLS;
    echo $param;
    if($assoc)
        $param = $param + OCI_ASSOC;
    if($get_lobs)
        $param = $param + OCI_RETURN_LOBS;

    echo $param;

    ocifetchinto($qhandle,$data,$param);
    return $data;
}

// //////////////////////////////
function db_insertid($qhandle) {
    echo "This function is not supported in this port";
    return(0);
}

// //////////////////////////////
function db_error() {
    return "\n\n<P><B>Oracle error:".OCIError()."</B><P>\n\n";
}

?>
