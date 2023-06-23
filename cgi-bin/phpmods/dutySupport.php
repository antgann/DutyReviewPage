<?php

/*
-----------------
TABLE 'dutylist'

id
firstname
lastname

------------------
TABLE 'dutysched'

id
weekstart  "2003-07-01'
*/

// Return an array with full names of people on the duty list.
// They will be sorted by last name
function getDutyList() {
    $db =  connectLocal();

    $sql = "SELECT firstname,lastname,id
              FROM dutylist 
	     WHERE FIND_IN_SET('Seismo',duty)>0
          ORDER BY lastname"; 

    $result = mysql_query($sql, $db);

    $nameArray = array();
    $x = 0;
  
    while ($row = mysql_fetch_array($result)) {
        $id    = $row["id"];
        $first = $row["firstname"];
        $last  = $row["lastname"];

        $name = "$first $last";

        array_push($nameArray, $name);
    }

    mysql_close($db);
    return $nameArray;
}

// Return the CURRENT duty person
function getDutyCurrent() {
    return getDutyByDate(time());
}

// Return the CURRENT duty person
function getDutyNext() {
    $weekOfSecs = 7 * 24 * 60 *60;
    return getDutyByDate(time() + $weekOfSecs);
}

// Return the Duty Seismo Person for the given date
// Duty change takes place at 09:00 on Wednesday.
// The $_epoch arg must be int in Unix epoch seconds like as returned by time()
function getDutyByDate($_epoch) {

    $db =  connectLocal();

    $name = "unknown";
    $weekOfSecs = 7 * 24 * 60 *60;
    $tend =  date("Y-m-d", $_epoch);
    $tstart =  date("Y-m-d", $_epoch - $weekOfSecs);

    $sql = "SELECT dl.firstname,dl.lastname
              FROM dutysched as ds, dutylist as dl
             WHERE ds.seismoid=dl.id
               AND ds.weekstart >= '$tstart'
               AND weekstart <= '$tend'";

    $result = mysql_query($sql, $db);

    while ($row = mysql_fetch_assoc($result)) {
        $name = $row["firstname"] . " " . $row["lastname"];
    }
  
    mysql_close($db);

    return $name;
}

//
function dumpSched() {
    $db =  connectLocal();

    $sql = "SELECT ds.weekstart,dl.firstname,dl.lastname
              FROM dutysched as ds, dutylist as dl
             WHERE ds.seismoid=dl.id
          ORDER BY weekstart";

    $sresult = mysql_query($sql, $db);

    while ($row = mysql_fetch_assoc($sresult)) {
        $weekstart = $row["weekstart"];
        $seismo_name_array[$weekstart] = $row["firstname"] . " " . $row["lastname"];

        echo "$weekstart $seismo_name_array[$weekstart]<br> ";
    }

}

// Connect to the local MySQL cbase
function connectLocal() {
    $db = mysql_connect("localhost", "admin", "beepbeep");
    mysql_select_db("admin", $db);

    return $db;
}

// Return current date in format used by the Duty Tables (e.g. "2002-01-16")
function getNow () {
   return date("Y-m-d");
}
?>
