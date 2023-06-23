<?php
/*
  Make and process the form for editing event parameters

  SYNTAX: locEditPanel.php?EVID=<evid>
*/

include_once "phpmods/db_conn.php";
include_once "phpmods/oracle.php";
include_once "phpmods/formutils.php";
include_once "phpmods/config.php"; // aww ?

$errorMsg = "";

extract($_REQUEST);

// ARGS passed by GET the first time, by POST thereafter
$evid   = $_GET["EVID"];

// FORM will be process by this file - 
// only way to repaint form with user entered values if there's an invalid field
$processor = $_SERVER['PHP_SELF'];

// "$submit" will only have a value when the SUBMIT button posts the form

if (!$submit) {    // 1st time called
    $oldVals = $_REQUEST;  // remember default values so we can reset on error

    // lookup current event info
    getOneEvent($evid, $dbhost, $dbname, $db_user, $db_password);
    printForm("");
} else {
    $oldVals = $_REQUEST;  // remember default values so we can reset on error
    if (isValid($oldVals)) {
        if (updateDb()) {
            $errorMsg = "Could not update dbase.<p>";
            printForm($errorMsg);	     
        } else {
            $errorMsg = "Update succeeded. <p>";
            printSuccess($errorMsg);
        }
    } else {
        printForm($errorMsg);
    }

    dumpArray($HTTP_POST_VARS);
// process results

}

// -------------------------------
function getOneEvent ($_evid, $_dbhost, $_dbname, $db_user, $db_password) {
// must make vars global to use them outside the function.
    global $evid, $magid, $mag, $magtype, $datetime, 
           $lat, $lon, $latd, $latm, $lond, $lonm, $depth, 
           $ndef, $wrms, $gap, $etype, $gtype, $rflag, $subsource, $remark, 
           $yr, $mo, $dy, $hr, $mn, $sc, $fsec, $mo3, $lenmo, $jday, $dow;
	
    db_connect_glob();

$sql_query="SELECT Event.evid \"evid\", Event.etype \"etype\", 
                   NetMag.magnitude \"mag\", NetMag.magtype \"magtype\", 
                   origin.datetime \"datetime\", 
                   Origin.lat \"lat\", Origin.lon \"lon\",
                   Origin.depth \"depth\", Origin.ndef \"ndef\",
                   Origin.wrms \"wrms\", Origin.gap \"gap\", 
                   Origin.gtype \"gtype\", Origin.rflag \"rflag\", 
                   Origin.subsource \"subsource\", Origin.auth \"auth\",
                   Origin.prefmag \"magid\", Remark.remark\"remark\" 
              FROM Event, Origin, NetMag, Remark 
             WHERE Event.evid = ${_evid}
               AND (Event.prefor = Origin.orid(+))
               AND (Event.prefmag = NetMag.magid(+)
               AND ( Event.commid = remark.commid(+)) ) ";

//        TrueTime.nominal2string(origin.datetime) \"ot\", 

    $result = db_query_glob($sql_query);

// for each row returned (should only be one)
    while (OCIFetch($result)) {
        // parse the individual attributes by name
	$evid     = OCIResult($result, "evid");
	$magid    = OCIResult($result, "magid");
	$mag      = OCIResult($result, "mag");
	$magtype  = OCIResult($result, "magtype");
	$datetime = OCIResult($result, "datetime");
	$lat      = OCIResult($result, "lat");	
	$lon      = OCIResult($result, "lon");
	$depth    = OCIResult($result, "depth");
	$ndef     = OCIResult($result, "ndef");
	$wrms     = OCIResult($result, "wrms");
	$gap      = OCIResult($result, "gap");
	$etype    = OCIResult($result, "etype");
	$gtype    = OCIResult($result, "gtype");
	$rflag    = OCIResult($result, "rflag");
	$subsource= OCIResult($result, "subsource");
	$auth     = OCIResult($result, "auth");
	
        if (OCIcolumnisnull($result, "remark")) {
            $remark = "hand-entered location from DRP";
        } else {
            $remark     = OCIResult($result, "remark");	
        }

	$latd = (int) $lat;    // int part
	$latm = abs($lat - $latd)*60.0;

	$lond = (int) $lon;
	$lonm = abs($lon - $lond)*60.0;

  // convert OT to set of presentable strings
	$frac = $datetime - (int)$datetime;

	$yr  = gmdate("Y", $datetime);   // 4 digit year
	$mo  = gmdate("m", $datetime);   // # of month 1-12
	$dy  = gmdate("d", $datetime);   // day of month with leading zero

	$hr  = gmdate("H", $datetime);   // 00-24 hr with leading zero
	$mn  = gmdate("i", $datetime);   // minutes with leading zero
	$sc  = gmdate("s", $datetime) + $frac;   // integer seconds with leading zero
	$fsec = sprintf("%5.2f", $sc + $frac);

    // extra stuff
	$mo3 = gmdate("M", $datetime);   // 3-char month (e.g. JAN)
	$lenmo = gmdate("t", $datetime); // # of days in the month of the $datetime
	$jday  = gmdate("z", $datetime); // julian day 1-365 (leap day not handled)
        $dow   = gmdate("l", $datetime); // Day of week (e.g. Monday)

    } // end while

    db_logoff();

}

/* already defined in formutils.php

function dumpArray ($_array) {

    $keys = array_keys($_array);
    for($index=0;$index<count($keys);$index++){

    $temp_key=$keys[$index];

    $temp=$_array[$temp_key];

    echo "name=\"$temp_key\" value=\"$temp\" <br>" ;

    }
}
 */


function arrayToString ($_array) {
    $str;
    $keys = array_keys($_array);
    for($index=0;$index<count($keys);$index++){
        $temp_key=$keys[$index];
        $temp=$_array[$temp_key];
        $str .="name=\"$temp_key\" value=\"$temp\" <br>" ;
    }

    return $str;
}

function isValid($_oldVals) {
    $status = TRUE;
    $status = $status && locIsValid($_oldVals);
    $status = $status && timeIsValid($_oldVals);
    return $status;
}


function locIsValid($_oldVals) {
    global $errorMsg;   // will contain HTML error messages

  // needed to access the global variables
    extract($GLOBALS);

    $errorMsg = $GLOBALS['lon']."  ".$_oldVals['lon'];

    $status = TRUE;

    if ($lat < -90.0 || $lat > 90.0) {
        $errorMsg .= "Latitude must be between -90 & 90<br>";
        $GLOBALS['lat'] = $_oldVals['lat'];   // reset
        $status = $status && false;
    }

    if ($lon < -180.0 || $lon > 180.0) {
        $errorMsg .= "Longitude must be between -180 & 180<br>";
        $GLOBALS['lon'] = $_oldVals['lon'];
        $status = $status && false;
    }

    if ($depth < -1 || $depth > 200) {
        $errorMsg .= "Depth must be between -1 & 200 km<br>";
        $GLOBALS['depth'] = $_oldVals['depth'];
        $status = $status && false;
    }
    return $status;
}

function timeIsValid($_oldVals) {
    global $errorMsg;   // will contain HTML error messages

  // needed to access the global variables
    extract($GLOBALS);

    $status = TRUE;

    if (! dateIsValid ($yr, $mo3, $dy) ) {
        $errorMsg .= "Date is invalid. <br>";
        $GLOBALS['yr'] = $_oldVals['yr'];
        $GLOBALS['mo'] = $_oldVals['mo'];
        $GLOBALS['dy'] = $_oldVals['dy'];
        $status = $status && false;     
    }

    if ($hr < 0 || $hr > 23) {
        $errorMsg .= "Hour must be between 0 & 24<br>";
        $GLOBALS['hr'] = $_oldVals['hr'];
        $status = $status && false;
    }

    if ($mn < 0 || $mn > 59) {
        $errorMsg .= "Minute must be between 0 & 59<br>";
        $GLOBALS['mn'] = $_oldVals['mn'];
        $status = $status && false;
    }

    if ($fsec < 0 || $fsec >= 60.0 ) {
        $errorMsg .= "Seconds must be between 0 & 60<br>";
        $GLOBALS['fsec'] = $_oldVals['fsec'];
        $status = $status && false;
    }
    return $status;
}

// Note 3char month
function dateIsValid ($yr, $mo3, $dy) {
    $str =  $mo3." ".$dy." ".$yr;
    if (strtotime($str) == -1) return FALSE;

    return TRUE;
}

function updateDb () {
    $result = 555;
    extract($_POST);

    $dt = new scsn_DateTime(0);
    $month = $dt -> getIntMo($mo3);

    $unixTime = mktime($hr, $mn, $fsec, $month, $dy, $yr);
    date_default_timezone_set('UTC');
    $formattedDate = date("Y-m-d h:i:s", $unixTime);
    

    print "<h2>Inside updateDb: $evid</h2>";
    print "<h2>dateNum: $unixTime</h2>";
    print "<h2>dateTxt: $formattedDate</h2>";

    $conn = db_connect_write();
    $oldOrId = getPrefOrid($evid);
    $orid = getNextOrid();

    print "\n\nDEBUG: got orid: $orid\n";
    $sql = "INSERT INTO origin (orid, evid, datetime, lat, lon, depth,
                                auth, subsource, prefmag)
                 VALUES ($orid, $evid,
                        TrueTime.putString('$formattedDate'),
                         $lat, $lon, $depth, 'DRP', 'DRP', $magid)";
    print "\n\nDEBUG about to execute:$sql\n";
    $result = db_query_glob($sql);

    $sql = "BEGIN :rtn := EPREF.setprefor_event($evid, $orid); END;";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ":rtn", &$result, 20) or die("ERR: bindVars");
    print "\n\nDEBUG about to execute:$sql\n";
    oci_execute($stmt) or die("Err: updateDb: execute_2\n");

    print "\n<h3>AFTER setprefor_event, return: $result</h3>\n";

    $magid = getMagid($evid);
    print "\n<h3>magid: $magid</h3>\n";
    $sql = "BEGIN :rtn := EPREF.set_magorid($orid, $magid); END;";
    $stmt = oci_parse($conn, $sql);

    /*
    // Disable clone -aww 2012/02/08
    $sql = "BEGIN :rtn := EPREF.cloneAssocAmO($oldOrId, $orid); END;";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ":rtn", &$result, 20) or die("ERR: bindVars");
    print "\n\nDEBUG about to execute:$sql\n";
    oci_execute($stmt) or die("Err: updateDb: execute_2\n");
    print "CLONE RESULT: $result\n";
     */

    return false;
}

function getNextOrid() {
// db connection already open
    $sql = "SELECT orseq.nextval \"orid\" FROM dual";
    $result = db_query_glob($sql);
    oci_fetch($result);
    $orid = oci_result($result, "orid");
    return $orid;
}

function getPrefOrid($evid) {
// db connection already open
    $sql = "SELECT prefor \"orid\" FROM event WHERE evid = $evid";
    $result = db_query_glob($sql);
    oci_fetch($result);
    $orid = oci_result($result, "orid");
    return $orid;
}

function getMagid($evid) {
// db connection already open
    $sql = "SELECT prefmag \"magid\" FROM event WHERE evid = $evid";
    $result = db_query_glob($sql);
    oci_fetch($result);
    $result = oci_result($result, "magid");
    return $result;
}

// /////////////////////////////////////////////////////////////////////////////
function printSuccess($_message) {
$str = <<< EOF
    <html>
        <head>
            <title>Success</title>
        </head>

$_message

    </html>
EOF;

    echo $str;
}

// ///////////////////////////////////////////////////////////////////////
// passes the assoc array (seems they weren't global so must pass)
function printForm ($_message) {
    // needed to access the global variables
    extract($GLOBALS);

    $etstr = eventTypeChooserStr($etype, $evid);

    $gtstr = eventGTypeChooserStr($gtype, $evid);

    $dateTimeChooserStr = getDateTimeChooser($yr, $mo3, $dy, $hr, $mn,
                                             $fsec, $evid);

    $latLonComboChooser = getLatLonComboChooser($lat, $lon, $depth, $evid);

echo <<< EOF

<html>
    <head>
        <title>Edit Location</title>
    </head>

    <body>
        <h2>Editing $evid</h2>

        <FONT color=RED>
            $_message
            $debug
        </FONT>

<hr Size="4">
<FORM name="mainForm" ACTION="$processor" METHOD="POST" >

<input type=hidden name=evid value="$evid" />
<input type=hidden name=magid value="$magid" />


<FIELDSET>
<LEGEND>Origin Time</LEGEND><P>
<Table>

$dateTimeChooserStr

</Table>
</FIELDSET>

<FIELDSET>
<LEGEND> Location </LEGEND><P>
<Table>

$latLonComboChooser

</Table>
</FIELDSET>

<FIELDSET>
<LEGEND> Quality, etc. </LEGEND><P>
<Table>
<TR>
 <TD> # ph <TD>
    <INPUT TYPE=text NAME=ndef ID=ndef  Size=3  Value="$ndef"   AUTOCOMPLETE=OFF >
 </TD>
<TR>
 <TD> RMS <TD>
    <INPUT TYPE=text NAME=wrms ID=wrms  Size=3  Value="$wrms"   AUTOCOMPLETE=OFF >
 </TD>
</TR>
</Table>
<TR> Event Type </TR>
<TR> $etstr

</TR>
</FIELDSET>

<FIELDSET>
<LEGEND> Remark </LEGEND><P>
<Table>
 <TD>
    <INPUT TYPE=text NAME=remark ID=remark Size=40 MaxLength=80 ColSpan="2" 
           Value="$remark"  AUTOCOMPLETE=OFF >
 </TD>
</Table>
</FIELDSET>

<hr Size="4">

<Table border="0" Width="100%">
<tr>
  <td>
      <INPUT TYPE="SUBMIT" NAME="submit" VALUE="Submit" STYLE="background: Green">
  </TD>
  <td>
      <INPUT TYPE="RESET" NAME="reset" VALUE="Reset" STYLE="background: Yellow">
  </TD>
  <TD>
      <INPUT TYPE="button" VALUE="Cancel editing" STYLE="background: Red" onClick="window.close()" >
  </TD>
</Table>

</FORM >

  </body>


</html>

EOF;

}
?>
