<?php
/* 
   Dbase Utility Routines
*/

include_once "db_conn.php";
include_once "oracle.php";
include_once "config.php";

// //////////////////////////////////////////////////////////////////////
// Return the fully qualified HOST name of the primary RT system
function getMasterRThost() {
// $list[] from phpmods/config.php !!!!!
    global $list;

    $host = $list[getMasterRTdb()];

    return $host;
}

// //////////////////////////////////////////////////////////////////////
// TPP utility 'masterrt' keeps masterdb updated so we just call it 
// to learn the master rt db alias
function getMasterRTdb() {
    global $list;
    global $binDir;

    $master = exec("$binDir/masterWrapper.sh 2>&1");
    if ($master == "")
        $master = "unknown";

    return $master;
}

// ///////////////////////////////////////
// DEPRECATED-- getMasterRTdb now uses 'masterrt' so this function is obsolete
// Returns 'true' if passed dbase is primary, else 'false'
function isPrimary($_db) {

    $sql_query= "SELECT primary_system \"primary\"
                   FROM RT_ROLE@${_db}
                  WHERE MODIFICATION_TIME = (SELECT max(MODIFICATION_TIME)  
                                               FROM RT_ROLE@${_db} )" ;

    $str = 'false';

    db_connect_glob();
    // execute the query
    $result = db_query_glob($sql_query);

    // for each row returned - should only be one
    while (OCIFetch($result)) {
        $str = OCIResult($result, "primary");
    }
    db_logoff();

    return $str; 
}

function hasCancelledAlarms($_db, $_evid) {

    $sql_query= "SELECT count(*) \"count\" FROM ALARM_ACTION@${_db} WHERE EVENT_ID=${_evid} and ACTION_STATE LIKE 'CANCEL%'" ;

    $str = 'false';

    db_connect_glob();
    // execute the query
    $result = db_query_glob($sql_query);

    // for each row returned - should only be one
    while (OCIFetch($result)) { 
        $str = OCIResult($result, "count");
    }

    if ( $str != '0' ) { $str = 'true'; }
    else { $str = 'false'; }

    db_logoff();

    return $str; 
}

// ///////////////////////////////////////
// Returns the next sequence number of the given sequencer
// Returns -1 if not in the list:
//'AMPSEQ', 'ARSEQ', 'COMMSEQ', 'EVSEQ', 'MAGSEQ', 'MECSEQ', 'ORSEQ', 'WFSEQ'
// These are the seismological table sequences in the CISN schema.
function getNextSeq($_seqname) {
    $seqlist = array('AMPSEQ', 'ARSEQ', 'COMMSEQ', 'EVSEQ', 'MAGSEQ',
                     'MECSEQ', 'ORSEQ', 'WFSEQ');

    $val = -1;
    $_seqname = strtoupper($_seqname);

    db_connect_glob();
    // Only hit dbase if its a valid seq name
    if (in_array ($_seqname, $seqlist) ) {
        $sql_query= "Select ${_seqname}.NEXTVAL \"nextval\" from DUAL" ;
        $result = db_query_glob($sql_query);  // execute the query

        // for each row returned - should only be one
        while (OCIFetch($result)) {
           $val = OCIResult($result, "nextval");
        }
    }
    db_logoff();

   return $val; 
}
?>
