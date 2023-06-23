<?php
/* 
   Event action routines for deleting, accepting and cancelling events, etc.
   called by doAction.php

   Require write priv to dbase
*/
include_once "config.php";
include_once "db_conn.php";
include_once "oracle.php";

// -------------------------------------------
// Call the dbase function to do the delete the event
// 1) set eventflag = 0
// 2) post to CANCELLED state - sends cancel messages
// returns > 0 (# rows changed) on success
function deleteInDb($_evid) {
    $conn = db_connect_write();

    // this sets selectflag = 0 AND posts to 'DELETED' which
    // causes alarm cancellation
    // returns > 0 (# rows changed) on success
    $sql = "BEGIN :rtn := EPREF.delete_event (:evid); END;"; 
    $stmt = OCIParse($conn, $sql) or die ('Can not parse statement'); 

    // bind variables
    OCIBindByName($stmt,":rtn",  $res1,  20) or die ('Can not bind variable'); 
    OCIBindByName($stmt,":evid", $_evid, 20) or die ('Can not bind variable'); 

    OCIExecute($stmt) or die ('deleteEvent can not Execute statement'); 

    OCIFreeStatement($stmt); 
    OCILogoff($conn); 

    if ($res1 <= 0) {
        echo "Warning: $_evid NOT deleted, return code: $res1<br>";
        return $res1;
    }

    echo "Success: $_evid deleted, return code: $res1<br>";

    return $res1;
}

// -------------------------------------------
// Call the dbase function to do the delete the event
// 1) set eventflag = 0
// 2) post to CANCELLED state - sends cancel messages
// returns > 0 (# rows changed) on success
function deleteEvent($_evid) {

// Step 1: delete in dbase (should also cancel alarms)
    $res1 = deleteInDb($_evid);

// Step 2: cancel alarms
// The stored proc should do this but
// there's no harm in doing it twice (belt & suspenders)
    if ($res1 > 0) {
        $res2 = cancelEvent($_evid);

        if ($res2 <= 0) {
      	    echo "Warning: $_evid NOT deleted, return code: $res1<br>";
        } else {
            echo "Success: $_evid deleted, return code: $res1<br>";
        }
    }

    return $res1;
}

// -------------------------------------------
// Call the dbase function to do the cancel 
// 1) post to DELETED state (which will cause alarm cancellation)
// does NOT set selectflag
// Returns 1 on success, <= 0 failure.
function cancelEvent($_evid) {

    $result = 1;

    // first write to database
    $res2 = cancelPublicEvent($_evid);

    return $result;
}

function uncancelEvent($_evid) {

    $result = 1;

    // first write to database
    $res2 = uncancelPublicEvent($_evid);

    return $result;
}

// -------------------------------------------
// Accept event:
//    uses EPREF.accept_event stored procedure
// returns > 0 (# rows changed) on success
function acceptEvent($_evid) {
    $conn = db_connect_write();

    // returns > 0 (# rows changed) on success
    $sql = "BEGIN :rtn := EPREF.accept_event (:evid); END;";

    $stmt = OCIParse($conn, $sql) or die ('Can not parse statement'); 
    OCIBindByName($stmt,":rtn",  $res1,  20) or die ('Can not bind variable'); 
    OCIBindByName($stmt,":evid", $_evid, 20) or die ('Can not bind variable'); 

    OCIExecute($stmt) or die ('acceptEvent can not Execute statement'); 

    OCIFreeStatement($stmt); 
    OCILogoff($conn); 

    if ($res1 <= 0) {
        echo "Warning: $_evid NOT accepted, return code: $res1<br>";
        return $res1;
    }
    echo "Success: $_evid accepted, return code: $res1<br>";

    // FINALIZE/DISTRIBUTE process takes care of sending alarms QDDS/EIDS
    //quickPost($_evid, "FINALIZE", 0);
    if ($res1 > 1) { quickPost($_evid, "DISTRIBUTE", 0); }

    return $res1;
}

function sendAlarms($_evid) {
    //return quickPost($_evid, "FINALIZE", 0);
    return quickPost($_evid, "DISTRIBUTE", 0);
}

function remakeGif($_evid) {
    global $sys_dbName;
    return post("EventStream", "$sys_dbName", $_evid, "MakeGif", 90, 0);
}
function remakeGifTrg($_evid) {
    global $sys_dbName;
    return post("EventStream", "$sys_dbName", $_evid, "MakeGifTrg", 90, 0);
}

// -------------------------------------------
// Call the dbase function to do the delete the trigger.
// 1) set eventflag = 0
// Don't really need to cancel alarm since there should be none but
// the dbase delete_event function is beyond our control (from here).
// returns > 0 (# rows changed) on success
function deleteTrigger($_evid) {
    return deleteInDb($_evid);
}

// -------------------------------------------
// Accept trigger
// 1) Set rFlag = "H" (human reviewed)
// 2) Set selectFlat = 1
// 3) resend messages
// returns > 0 (# rows changed) on success
function acceptTrigger($_evid) {
    $conn = db_connect_write();

    // returns > 0 (# rows changed) on success
    $sql = "BEGIN :rtn := EPREF.accept_Trigger (:evid); END;";  
    $stmt = OCIParse($conn, $sql) or die ('Can not parse statement'); 

    // bind variables
    OCIBindByName($stmt,":rtn",  $res1,  20) or die ('Can not bind variable'); 
    OCIBindByName($stmt,":evid", $_evid, 20) or die ('Can not bind variable'); 

    OCIExecute($stmt) or die ('acceptTrigger can not Execute statement'); 

    OCIFreeStatement($stmt); 
    OCILogoff($conn); 

    if ($res1 <= 0) {
        echo "Warning: $_evid NOT accepted, return code: $res1<br>";
    } else {
        echo "Success: $_evid accepted, return code: $res1<br>";
    }

    return $res1;
}


// -------------------------------------------
// Returns 1 on success, <= 0 failure.
// You can use this to post to any state.
// Current standard states are:
// "FINALIZE" = refresh products and resend notifications
// "CANCELLED" = send notifications to cancel a bad event
// function quickPost($_evid, $_state) added "result" arg to permit "transitions"
function quickPost($_evid, $_state, $_resultin) {
// default these values - for PCS alarm processes
    $groupin = "TPP";
    $tablein = "TPP";
    $rankin  = 100;

    $res = post ($groupin, $tablein, $_evid, $_state, $rankin, $_resultin);
    return $res;
}

// -------------------------------------------
// Returns 1 on success, <= 0 failure.
function post ($groupin, $tablein, $evidin, $statein, $rankin, $resultin) {
    $conn = db_connect_write();

   $query = "BEGIN :rv := PCS.putState(:p1, :p2, :p3, :p4, :p5, :p6); END; ";   

   $stmt = OCIParse($conn, $query) or die ('Can not parse statement'); 

    // bind variables
    OCIBindByName($stmt,":p1", $groupin,  20) or die ('Can not bind variable');
    OCIBindByName($stmt,":p2", $tablein,  20) or die ('Can not bind variable');
    OCIBindByName($stmt,":p3", $evidin,   20) or die ('Can not bind variable');
    OCIBindByName($stmt,":p4", $statein,  20) or die ('Can not bind variable');
    OCIBindByName($stmt,":p5", $rankin,   20) or die ('Can not bind variable');
    OCIBindByName($stmt,":p6", $resultin, 20) or die ('Can not bind variable');
    OCIBindByName($stmt,":rv", $rv,       20) or die ('Can not bind variable');

    OCIExecute($stmt) or die ('Can not Execute statment'); 

    OCIFreeStatement($stmt); 
    OCILogoff($conn); 

    echo "post($groupin, $tablein, $evidin, $statein, $rankin, $resultin) Result= $rv<br>";

    if ($rv < 0) {
      echo "Warning: $evidin is NOT posted.<br>";
    }

    return $rv;
}

// -------------------------------------------
// Set event type.  Allowed types = 'eq','qb','ex','sh','sn','th'
// returns > 0 (# rows changed) on success
// 
function setEventType($_evid, $_type) {
    $conn = db_connect_write();

    $sql = "BEGIN :rtn := EPREF.setEventType (:evid, :etype); END;";
    $stmt = OCIParse($conn, $sql) or die ('Can not parse statement');

  // bind variables
    OCIBindByName($stmt,":rtn",   $res1,  20) or die ('Cannot bind variable');
    OCIBindByName($stmt,":evid",  $_evid, 20) or die ('Cannot bind variable');
    OCIBindByName($stmt,":etype", $_type, 20) or die ('Cannot bind variable');

    OCIExecute($stmt) or die ('setEventType cannot Execute statement');

    OCIFreeStatement($stmt); 
    OCILogoff($conn); 

    if ($res1 <= 0) {
        echo "Warning: $_evid type may not have been set to $_type, return code: $res1<br>";
        return $res1;
    }
    echo "Success: $_evid type set to $_type, return code: $res1<br>";

    // FINALIZE/DISTRIBUTE process takes care of sending alarms QDDS/EIDS
    // only for certain changes, send update 
    if ($_type == "eq" or $_type == "qb") {
        //quickPost($_evid, "FINALIZE", 0);
        if ($res1 > 1) { quickPost($_evid, "DISTRIBUTE", 0); }
    }    	

    return $res1;
}

// Set event prefor gtype.  Allowed gtypes ='l','r','t' 
// returns > 0 (# rows changed) on success
// 
function setEventGType($_evid, $_type) {
    $conn = db_connect_write();

    $sql = "BEGIN :rtn := EPREF.setPreforGType (:evid, :gtype); END;";
    $stmt = OCIParse($conn, $sql) or die ('Can not parse statement');

  // bind variables
    OCIBindByName($stmt,":rtn",   $res1,  20) or die ('Cannot bind variable');
    OCIBindByName($stmt,":evid",  $_evid, 20) or die ('Cannot bind variable');
    OCIBindByName($stmt,":gtype", $_type, 20) or die ('Cannot bind variable');

    OCIExecute($stmt) or die ('setEventGType cannot Execute statement');

    OCIFreeStatement($stmt); 
    OCILogoff($conn); 

    if ($res1 <= 0) {
        echo "Warning: $_evid gtype may not have been set to $_type, return code: $res1<br>";
        return $res1;
    }
    echo "Success: $_evid gtype set to $_type, return code: $res1<br>";

    // FINALIZE/DISTRIBUTE process takes care of sending alarms QDDS/EIDS
    // only for certain changes, send update 
    if ($_type == "l") {
        //quickPost($_evid, "FINALIZE", 0);
        if ($res1 > 1) { quickPost($_evid, "DISTRIBUTE", 0); }
    }    	

    return $res1;
}

// ------------------------------------------
// CAN'T SEND QDDS FROM WEB SERVERS!!!!!!!!!!!!!!!!!!!!!!!!!!
// THIS IS VESTIGIAL??????
// QDDS is sent by FINALIZE/DISTRIBUTE process
function sendPublicEvent ($_evid) {
    // this used to send QDDS messages.
    return;
}

// ------------------------------------------
// Send a public cancellation message
// QDDSdelete couldn't send messages as user 'nobody' changed to
// EPREF.cancel_event 04/03/07 B.F.
function cancelPublicEvent ($_evid) {
    $conn = db_connect_write();

    $sql = "BEGIN :rtn := EPREF.cancel_event (:evid); END;";
    $stmt = OCIParse($conn, $sql) or die ('Can not parse statement');

    // bind variables
    OCIBindByName($stmt,":rtn",   $res1,  20) or die ('Can not bind variable');
    OCIBindByName($stmt,":evid",  $_evid, 20) or die ('Can not bind variable');

    OCIExecute($stmt) or die ('cancelPublicEvent can not Execute statement');

    OCIFreeStatement($stmt);
    OCILogoff($conn);

    return $res1;
}

// ------------------------------------------
// Finalize an event
// EPREF.finalize 09/29/14 AGG
function finalizeEvent ($_evid) {
    $conn = db_connect_write();

    $sql = "BEGIN :rtn := EPREF.finalize_event (:evid); END;";
    $stmt = OCIParse($conn, $sql) or die ('Can not parse query');

    // bind variables
    OCIBindByName($stmt,":rtn",   $res1,  20) or die ('Can not bind variable');
    OCIBindByName($stmt,":evid",  $_evid, 20) or die ('Can not bind variable');

    OCIExecute($stmt) or die ('finalizeEvent can not Execute statement');

    OCIFreeStatement($stmt);
    OCILogoff($conn);

    return $res1;
}

function uncancelPublicEvent ($_evid) {
    global $secondaryAlarmDBS;

    $conn = db_connect_write();

    $sql = "BEGIN :rtn := EPREF.uncancel_event (:evid); END;";
    $stmt = OCIParse($conn, $sql) or die ('Can not parse statement');

    // bind variables
    OCIBindByName($stmt,":rtn",   $res1,  20) or die ('Can not bind variable');
    OCIBindByName($stmt,":evid",  $_evid, 20) or die ('Can not bind variable');

    OCIExecute($stmt) or die ('uncancelPublicEvent can not Execute statement');

    OCIFreeStatement($stmt);
    OCILogoff($conn);

    if ($res1 < 0) {
        echo "Warning: $_evid uncancel return code: $res1<br>";
        return $res1;
    }
    echo "Success: $_evid uncancelled, return code: $res1<br>";

    $altDbName = "";
    foreach ( $secondaryAlarmDBS as $db ) {
      if ( preg_match('/arch/',$db) ) {
        $altDbName = $db; 
      }
    }
    if ( $altDbName ) {
      $conn = db_connect_write_db( $altDbName );
      $sql = "delete from alarm_action where event_id=? and action_state like 'CANCEL%'";
      $stmt = OCIParse($conn, $sql);
      OCIBindByName($stmt,":evid",  $_evid, 20);
      $res2 = OCIExecute($stmt);
      OCIFreeStatement($stmt);
      OCILogoff($conn);
      if ($res2 < 0) {
        echo "Warning: $_evid uncancel on $altDbName returned code: $res1<br>";
      }
    }

    // FINALIZE/DISTRIBUTE process takes care of sending alarms QDDS/EIDS
    //quickPost($_evid, "FINALIZE", 0);
    if ($res1 > 1) { quickPost($_evid, "DISTRIBUTE", 0); }

    return $res1;
}

?>
