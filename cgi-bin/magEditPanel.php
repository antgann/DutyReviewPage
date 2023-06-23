<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML//EN">
<HTML>
    <HEAD>
        <title>Magnitude Input</title>
    </HEAD>

<?php
session_start();
/*
  make the form for editing magnitude parameters

  SYNTAX: makeMagPanel.php?EVID=<evid>

  This script is also the FORM submit target - i.e. script processes itself.
  This is done so the form input can be checked and error messages
  printed and the user given a second chance to get it right before the
  script that actually writes to the dbase is called.

  All form variables have name f_xxxx in the $HTTP_POST_VARS array
  To check that a value was changed compare $xxx to $f_xxx.

*/

include_once "phpmods/db_conn.php";
include_once "phpmods/oracle.php";
include_once "phpmods/formutils.php";
include_once "phpmods/config.php";
include_once "phpmods/event_actions.php";

extract($_REQUEST);

global $evid, $dbhost;

// from the dbase
global $d_evid, $d_mag, $d_magtype, $d_auth, $d_subsrc, $d_algo, 
       $d_nsta, $d_uncert, $d_rflag, $d_qual, $d_orid, $d_magid;

// from the form input
global $f_mag, $f_magtype, $f_auth, $f_subsrc, $f_algo;

global $errorMessage;

// FORM will process itself - 
// only way to repaint form with user entered values if there's an invalid field
$processor = $_SERVER['PHP_SELF'];


// "$submit" will only have a value when the SUBMIT button posts the form


if ( ! isset($submit) ) {    // 1st time called
    $_SESSION["confirmMagEdit"] = NULL;
    $evid   = $_GET["EVID"];
    // lookup current event info
    getMagData($evid);   // get fresh mag data
    printForm("New magnitude edit for submittal", false);
} 
else {
    // validate form data
    // if invalid repaint form preserving good user changes
   
    if ( validMag($f_mag, $editMagMin, $editMagMax) ) {
        if ( !(isset($_SESSION['confirmMagEdit'])) && isset($submit) ) {
            $_SESSION["confirmMagEdit"] = "confirmed";
            printForm("Confirm magnitude edit for submittal", true);
        }
        else {
            $noChange = magUnchanged();
            if ($noChange) {
                printSuccess("No changes made in form, dbase not updated.");
            } 
            else {
                echo "<b>Creating new M$f_magtype</b><br>evid&nbsp&nbsp: $evid<br>prefor: $d_orid<br>";
               $retv = updateDb();
                if ( $retv ) {
                    //printSuccess("Update succeeded.<br>Press 'Resend Alarms' button to alarm<br>");
                    printSuccess("Update succeeded.<br> Press 
                      <span style=\"font-size:20;color:white;background-color:DeepPink\">Resend Alarms</span>
                      in button panel<br>to run alarm scripts (e.g. notify NEIC).");
                    //quickPost($evid, 'FINALIZE', 0);
                } 
                else {
                    $errorMsg = "<FONT color=RED>ERROR: Could not update dbase returned: $retv.</FONT>";
                    printForm($errorMsg, false);
                }
                $_SESSION["confirmMagEdit"] = NULL;
            }
        }
    } 
    else {
        $_SESSION["confirmMagEdit"] = NULL;
        $msg = "<FONT color=RED>$errorMessage </FONT>";
        printForm($msg, false);
    }
}

// -------------------------
// Is mag valid?
// All but mag value and Authority are constrained by drop-downs
function validMag($_val, $_editMagMin, $_editMagMax) {

  global $errorMessage;
    
      if (($_val < $_editMagMin) || ($_val > $_editMagMax)) {
          $errorMessage .= "min val: $_editMagMin<BR>max val: $_editMagMax<BR>Invalid mag value: $_val<BR>";
          return false;
        }
        return true;
}
// --------------------------------
function printSuccess($_message) {
$str = <<< EOF
<HTML>
  <HEAD>
    <TITLE>Success</TITLE>
  </HEAD
<H2>
$_message
</H2>
</HTML>
EOF;

echo $str;
}

// ---------------
function magUnchanged () {
// returns 1 = true

  // from the dbase
  global $d_mag, $d_magtype, $d_auth, $d_subsrc, $d_algo, 
         $d_nsta, $d_uncert, $d_rflag, $d_qual;

  // from the form input
  global $f_mag, $f_magtype, $f_auth, $f_subsrc, $f_algo;

  return 
     (  $f_mag ==     $d_mag &&
        $f_magtype == $d_magtype &&
        $f_auth ==    $d_auth &&
        $f_subsrc ==  $d_subsrc &&
        $f_algo ==    $d_algo);
}

// -----------------------------------------
function getMagData ($_evid) {
  
// Values read from the dbase
  global $d_mag, $d_magtype, $d_auth, $d_subsrc, $d_algo, 
         $d_nsta, $d_uncert, $d_rflag, $d_qual, $d_orid, $d_magid;

  db_connect_glob();   // connect to dbase using info from db_conn.php

  $sql_query="SELECT DISTINCT e.evid \"d_evid\",
                     e.prefor \"d_orid\",
                     n.magid \"d_magid\",
                     n.magnitude \"d_mag\", 
                     n.magtype \"d_magtype\", 
                     n.auth \"d_auth\",
                     n.subsource \"d_subsrc\",
                     n.magalgo \"d_algo\",
                     n.nsta \"d_nsta\",
                     n.uncertainty \"d_uncert\",
                     n.quality \"d_qual\",
                     n.rflag \"d_rflag\"
               FROM Event e, NetMag n WHERE
                     e.Evid = $_evid and
                     n.magid(+) = e.prefmag"; 

   $result = db_query_glob($sql_query);

// fetch result into array: $dbvals['d_evid'], $dbvals['d_mag'], etc.
   $dbvals = array ();
   OCIFetchInto ($result, $dbvals, OCI_ASSOC);

   db_logoff();

// You can't pass an array thru POST, so use individual variables
// which will be passed as Hidden variables during the post
   extract($dbvals);  // convert array variables:e.g. $dbvals['d_mag'] => $d_mag

   //print "<P>DEBUG: auth: $d_auth ; orid: $d_orid ; prefmag: $d_magid</P>";
}


function updateDb () {
    global $d_mag, $d_magtype, $d_auth, $d_subsrc, $d_algo,
           $d_nsta, $d_uncert, $d_rflag, $d_qual, $d_orid, $d_magid, $evid;

    // from the form input
    global $f_mag, $f_magtype, $f_auth, $f_subsrc, $f_algo;

    $return = 0;
    $magid = 0;
    $zeroValue = 0;
    $hValue = "H";

    $conn = db_connect_write();
    $sql = "BEGIN :rtn := EPREF.insertNetMag(:orid, :magVal, :magType, :authCode, :subsrc, :algo, :nsta, :unc, :gap, :dist, :qual, :rflag); UPDATE origin SET rflag = :rflag WHERE evid = :evid; END;";

    $stmt = oci_parse($conn, $sql) or die('error parsing query in updateDb');

    oci_bind_by_name($stmt, ":rtn", $return, 20) or die("ERR: updateDb: bindvars");
    oci_bind_by_name($stmt, ":orid", $d_orid, 20)
                                         or die("ERR: updateDb: bindvars");
    oci_bind_by_name($stmt, ":magVal", $f_mag, 20)
                                         or die("ERR: updateDb: bindvars");
    oci_bind_by_name($stmt, ":magType", $f_magtype, 20)
                                         or die("ERR: updateDb: bindvars");
    oci_bind_by_name($stmt, ":authCode", $f_auth, 20)
                                         or die("Err: updateDb: bindvars");
    oci_bind_by_name($stmt, ":subsrc", $f_subsrc, 20)
                                         or die("err: updateDb: bindvars");
    oci_bind_by_name($stmt, ":algo", $f_algo, 20)
                                         or die("err: updateDb: bindvars");
    oci_bind_by_name($stmt, ":nsta", $zeroValue, 20)
                                         or die("Err: updateDB: bindvars");
    oci_bind_by_name($stmt, ":unc", $zeroValue, 20)
                                         or die("Err: updateDB: bindvars");
    oci_bind_by_name($stmt, ":gap", $zeroValue, 20)
                                         or die("Err: updateDB: bindvars");
    oci_bind_by_name($stmt, ":dist", $zeroValue, 20)
                                         or die("Err: updateDB: bindvars");
    oci_bind_by_name($stmt, ":qual", $zeroValue, 20)
                                         or die("Err: updateDB: bindvars");
    oci_bind_by_name($stmt, ":rflag", $hValue, 20)
                                         or die("Err: updateDB: bindvars");
    oci_bind_by_name($stmt, ":evid", $evid, 20)
                                         or die("Err: updateDB: bindvars");

    oci_execute($stmt) or die("Err: updateDb: execute_1");
    
    print "magid&nbsp: $return (new)<br>";
    if ($return) {
        // if execute is successfull, the return value is the new magid
        $magid = $return;
        $return = 0;
    }

    print "Setting event prefmag...<br>";
    $sql = "BEGIN :return := EPREF.setprefmag_magtype(:evid, :magid, :magtype, 1, 1, 1, 0); END;";
    $stmt = oci_parse($conn, $sql) or die('error parsing query in updateDb');

    oci_bind_by_name($stmt, ":return", $return, 20)
                        or die("ERR: updateDb: bind");
    oci_bind_by_name($stmt, ":evid", $evid, 20) or die("ERR:updateDb: bind");
    oci_bind_by_name($stmt, ":magid", $magid, 20) or die("Err: updateDb: bind");
    oci_bind_by_name($stmt, ":magtype", $f_magtype, 20) or die("Err: updateDb: bind");

    oci_execute($stmt) or die("Err: updateDb: execute_2");

    if ($return > 0) {
        // success
        print "Success return: $return<br>";
    } else {
        print "<P>err setprefmag_magtype failed: $return</P>";
    }

/*
    $sql = "BEGIN :return := EPREF.setPrefMag_MagType(:evid, :magid); END;";
    $stmt = oci_parse($conn, $sql) or die('error parsing query in updateDb');

    oci_bind_by_name($stmt, ":return", $return, 20)
                        or die("ERR: updateDb: bind");
    oci_bind_by_name($stmt, ":evid", $evid, 20) or die("ERR:updateDb: bind");
    oci_bind_by_name($stmt, ":magid", $magid, 20) or die("Err: updateDb: bind");

    oci_execute($stmt) or die("Err: updateDb: execute_2");

    if ($return > 0) {
        // success
        print "Setting event prefmag...<br>";
        $return = 0;
        $sql = "BEGIN :rtn := MAGPREF.setPrefMagOfEvent($evid); END;";
        $stmt = oci_parse($conn, $sql)
                       or die('error parsing query in updateDb');
        oci_bind_by_name($stmt, ":rtn", $return, 20);
        oci_execute($stmt) or die("Err: updateDb: execute_3");
    } else {
        print "<P>err setPrefMagOfEvent: $return</P>";
    }
*/       
    return $return;
}

// ----------------------------
// Dump an assoc array as html hidden variable so they can be passed via POST
/*
function dumpAsHidden ($_array) {

    $keys = array_keys($_array);
    for($index=0;$index<count($keys);$index++){
        $temp_key=$keys[$index];
        $temp=$_array[$temp_key];
        $str .="<Input Type=Hidden Name=$temp_key value=\"$temp\" >\n" ;
    }
    return $str;
}
*/
// ---------------------------------------------
function printForm ($_message, $_confirm) {
    global $evid, $dbhost, $processor;
    global $d_mag, $d_magtype, $d_auth, $d_subsrc, $d_algo, $d_nsta, $d_uncert, $d_rflag, $d_qual, $d_orid, $d_magid, 
           $f_mag, $f_magtype, $f_auth, $f_subsrc, $f_algo;
    global $networkCode;

//$f_auth = 'CI';
$f_auth = $networkCode;
$f_magtype = 'h';
$f_algo = 'HAND'; 
$f_subsrc = 'DRP';
$_submit_form = "";
$_force_click = "";
$fmag_input = "<INPUT TYPE=\"text\" NAME=\"f_mag\" SIZE=4 Value=\"$d_mag\" AUTOCOMPLETE=OFF\">";

if($_confirm) {
    $_force_click = "onload=\"document.getElementById('magSubmit').click()\"";    
    $_submit_form = "onClick=\"if(confirm('You are attempting to change magnitude $d_mag -> $f_mag, is this correct?'))
            return true;
            else { window.close(); return false; }\"";
    $fmag_input = "";
}

// <TD><B>M=</B><INPUT TYPE="text" NAME="f_mag" SIZE=4 Value="$d_mag" AUTOCOMPLETE=OFF">&nbsp&nbsp<SELECT NAME=f_mag> 
echo <<< EOF1

<BODY $_force_click>

<h3>Edit Magnitude For Event $evid</h3>

$_message

<H4>Existing db prefmag=$d_magid M$d_magtype=$d_mag</H4>

<HR style="height:4px" />

<FORM name="mainForm" ACTION="$processor" METHOD="POST" >

<INPUT Type=hidden Name=evid Value="$evid" >
<Input Type=Hidden Name=d_mag value="$d_mag" >
<Input Type=Hidden Name=d_magtype value="$d_magtype" >
<Input Type=Hidden Name=d_auth value="$d_auth" >
<Input Type=Hidden Name=d_subsrc value="$d_subsrc" >
<Input Type=Hidden Name=d_algo value="$d_algo" >
<Input Type=Hidden Name=d_nsta value="$d_nsta" >
<Input Type=Hidden Name=d_uncert value="$d_uncert" >
<Input Type=Hidden Name=d_rflag value="$d_rflag" >
<Input type=hidden name=d_orid value="$d_orid" >
<Input type=hidden name=d_magid value="$d_magid" >

<Input Type=Hidden Name=f_mag value="$f_mag" >
<Input Type=Hidden Name=f_magtype value="$f_magtype" >
<Input Type=Hidden Name=f_auth value="$f_auth" >
<Input Type=Hidden Name=f_subsrc value="$f_subsrc" >
<Input Type=Hidden Name=f_algo value="$f_algo" >

<FIELDSET>
   <LEGEND><b>Enter New Mh Magnitude</b></LEGEND><P>
      <TABLE>
        <TR>
          <TD>
             <B>Mh</B>
EOF1;

//                <SELECT NAME=f_magtype> 
//$magTypeList = array ('h','l','w');
//$magTypeList = array ('h');
//foreach ($magTypeList as $type) {
//  if ($type == 'h') {
//    echo "<Option Selected> $type </Option>"."\n";
//  } else {
//    echo "<Option> $type </Option>"."\n";
//  }
//}
//         </SELECT>
//       <TR>
//          <TD><B>M=</B><SELECT NAME=f_mag> 
//$magValList = array ('0.0','0.5','1.0','1.5','2.0','2.5','3.0','3.5','4.0','4.5','5.0','5.5','6.0','6.5','7.0','7.5','8.0','8.5','9.0');
//foreach ( $magValList as $val ) {
//    if ($val == $d_mag) {
//        echo "<Option Selected> $val </Option>"."\n";
//    } else {
//        echo "<Option> $val </Option>"."\n";
//    }
//}
//      </SELECT>
//      <INPUT TYPE="SUBMIT" NAME="submit" VALUE="CommitA" STYLE="background: lime">
//        </TD>
//      </TR>

echo <<<EOF3

$fmag_input
          </TD>
        </TR>
      </TABLE>
</FIELDSET>
<BR>
EOF3;

echo <<< EOF4

<b>
WARNING: Commit of Mh overrides priority of other magtypes! Jiggle can be used to set event prefmag to another magtype.
After committing a new magnitude press the "Resend Alarms" button to re-alarm event with the new magnitude"
</b>

<TABLE border="0" width="100%">
  <TR>
  <TD>
      <INPUT ID="magSubmit" TYPE="SUBMIT" NAME="submit" VALUE="Commit" STYLE="background: Lime" $_submit_form>      
  </TD>
  <TD>
      <INPUT TYPE="RESET" NAME="reset" VALUE="Reset" STYLE="background: Yellow">
  </TD>
  <TD>
      <INPUT TYPE="button" VALUE="Cancel" STYLE="background: Red" onClick="window.close()">
  </TD>
  </TR>
</TABLE>
</FORM >
</BODY>
</HTML>
EOF4;
}
?>
