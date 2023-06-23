<?php

//
// Seismological objects
//
// should probalby break into seperate files.
//

// required libraries for dbase access
include_once "phpmods/db_conn.php";
include_once "phpmods/oracle.php";
include_once "phpmods/formutils.php";
include_once "phpmods/db_utils.php";

//
// EVENT OBJECT
//
class Event {

// Variable names are the same as dbase column names unless there is
// duplication in the joined tables

// From Origin
var $orid;      // NOT NULL
var $evid;      // NOT NULL
var $auth;      // NOT NULL
var $datetime;  // NOT NULL    // origin time in Epoch seconds
var $lat;       // NOT NULL
var $lon;       // NOT NULL
var $depth;
var $ndef;
var $wrms;
var $gap;
var $gtype;
var $rflag;
// var $bogusflag;
var $subsource;

// secondary attributes (not likely to be human edited)
  
var $type;      // H = hypocenter (this is NOT Event.etype (le, qb, ts, etc.)
var $bogusflag;
var $algorithm;
var $datumhor;
var $datumver;
var $fdepth;
var $fepi;
var $ftime;

// From Event
var $etype;        // Event.etype (le, qb, ts, etc.)
var $selectflag;   // not read

var $remark = "";

var $lastError;

// this is an object
var $mag;

// ---------

var $inDbase;  // set TRUE if object matches dbase contents, else FALSE

//-------------------------------------
// constructor
//
function Event () {
  $this->mag = new Magnitude();

// some default values (in case the caller neglects them)
// These will be overprinted if you read event from the dbase

  $this->type = 'H';  // Hypocenter
  $this->bogusflag = 0;
  $this->algorithm = "HAND";
  $this->subsource = "WEB";
  $this->datumhor  = "WGS84";
  $this->datumver  = "AVERAGE";
  $this->fdepth    = 'n';
  $this->fepi      = 'n';
  $this->ftime     = 'n';
  $this->gtype     = 'l';

  $this->inDbase = FALSE;
}

//-------------------------------------
// Serialize this object : STATIC
// base64_encode() makes string with NO quotes so you can pass with POST
function serialize ($obj) {
  return base64_encode( serialize ($obj) );
}

//-------------------------------------
// Unserialize this object : STATIC
//
function unserialize ($serialString) {
  return unserialize ( base64_decode ($serialString) );
}

//-------------------------------------
function setDateTime ($yr, $mo3, $dy, $hr, $mn, $fsec) {

  $dt = new scsn_DateTime(0); 
  $dt->set($yr, $mo3, $dy, $hr, $mn, $fsec);

  $this->setEpochTime($dt->getEpochSecs());

  $this->inDbase = FALSE;

}

//-------------------------------------
function setEpochTime ($_epoch) {

  $this->datetime = $_epoch;

  $this->inDbase = FALSE;

}
// -------------------------------------
function setDLat ($_lat) {
   $this->lat = $_lat;
   $this->inDbase = FALSE;
}
//-------------------------------------
 function setDLon ($_lon) {
   $this->lon = $_lon;
   $this->inDbase = FALSE;
}
// -------------------------------------
function setLat ($_latd, $_latm) {
   $this->lat = $this->DM2dec($_latd, $_latm);
   $this->inDbase = FALSE;
}
//-------------------------------------
 function setLon ($_lond, $_lonm) {
   $this->lon = $this->DM2dec($_lond, $_lonm);
   $this->inDbase = FALSE;
}
// -------------------------------------
function setLatLon ($_latd, $_latm, $_lond, $_lonm) {
   $this->setLat($_latd, $_latm);
   $this->setLon($_lond, $_lonm);
   $this->inDbase = FALSE;
}
// -------------------------------------
function setLatLonZ ($_latd, $_latm, $_lond, $_lonm, $_depth) {
   $this->setLatLon ($_latd, $_latm, $_lond, $_lonm);
   $this->setDepth($_depth);
   $this->inDbase = FALSE;
}
// -------------------------------------
function setDepth($_depth) {
   $this->depth = $_depth;
   $this->inDbase = FALSE;
}

// -------------------------------
// Convert decimal degrees to degrees minutes.
// Return result in global variables
function dec2DM ($_lat, $_lon) {
  global $latd, $latm, $lond, $lonm;

	$latd = (int) $_lat;            // int part - truncated
	$latm = abs($_lat - $latd)*60.0;

	$lond = (int) $_lon;
	$lonm = abs($_lon - $lond)*60.0;
}

// -------------------------------
// Convert degrees/minutes to decimal degrees
function DM2dec ($deg, $min) {

  	$ldec = 0;	

	if ($deg < 0) {
	  $ldec = $deg - ($min / 60.0);
	} else {
	  $ldec = $deg + ($min / 60.0);
	}	

	return $ldec;
}
// -----------------------------------------
// Return the mag object of this event
function getMag () {
  return $this->mag;
}

// -----------------------------------------
/*
 Write everything about the event out to the dbase.

Returns TRUE on success, FALSE not written (either because no changes or error)

The rules:

1) Change Event only ($this->inDbase == FALSE)

  - Insert new origin row
  - Update Event.prefor to new origin
    (Note that perferred NetMag.orid will not be correct)

2) Change Mag only ($this->getMag()->inDbase == FALSE)

  - Insert new NetMag row (set NetMag.orid to current)
  - Update Event.prefmag to new NetMag

3) Change Event and Mag (combine actions above)

  - Insert new origin row
  - Update Event.prefor to new origin
  - Insert new NetMag row (set NetMag.orid to new one)
  - Update Event.prefor to new Origin
  - Update Event.prefmag to new NetMag

4) If REMARK is non-blank

  - Insert Remark row
  - Update Event.commid

*/
function writeToDbase() {

  global $lastError;

// <1> write origin & set it as pref
// returns orid if successful, else 0
  $status = $this->writeOrigin();

  if ($status == 0) {
     $this->lastError = db_getError();
     return FALSE;
  }

// <2> write mag & set it as pref
  $mag = $this->getMag();

  $status &= $mag->writeToDb();
  if (!$status) {
     $this->lastError = db_getError();
     return FALSE;
  }

  $status &= $mag->setAsPrefered($this->evid);

//<3> Write Remark
  $rtn = $this->writeRemark();

  return $status;
}

// ----------------------------------------------------------------------
// Returns last error message
function getLastError() {

  global $lastError;

  return $this->lastError();
}
// ----------------------------------------------------------------------
// Put this event into the default dbase.
// Inserts a new Origin object and sets it as prefered in the Event row.
// Only works to ADD an Origin to an exiting Event.
//
// Returns 'orid' on success, '0' on error.
//
// Assumes a WRITABLE connection is already made and passed via globals
//
// !! Not all dbase fields are represented!

function writeOrigin () {

	// get the next Origin sequence number 
	 $this->orid = getNextSeq('ORSEQ');

	if ($this->orid == -1) {return FALSE;}   // didn't get a valid seq number

// Notes: LDDATE will get handled by the dbase
//        etype is in Event not Origin

// There is no Oracle stored procedure for this
   $sql = "INSERT INTO ORIGIN 
	(orid, evid, auth, datetime,
	 lat, lon, depth,
	 ndef, wrms, gap,
	 gtype, rflag, subsource,
	 type, bogusflag, algorithm, 
	 datumhor, datumver, fdepth, fepi, ftime) 
	VALUES 
	('$this->orid', '$this->evid', '$this->auth', '$this->datetime', 
	 '$this->lat', '$this->lon', '$this->depth', 
	 '$this->ndef', '$this->wrms', '$this->gap', 
	 '$this->gtype', '$this->rflag', '$this->subsource',
	 '$this->type', '$this->bogusflag', '$this->algorithm',
	 '$this->datumhor', '$this->datumver', '$this->fdepth', '$this->fepi', 
	 '$this->ftime' )";

// Insert the new row (does autocommit)
	$status = db_execute_glob($sql);

	if ($status) {

		echo "wrote status = $status<p>";   // 19 is bad??
		echo "wrote orid = $this->orid<p>";

		// Set as prefor
    		$rtn = setPrefOr($this->evid, $this->orid);

		return $this->orid;
	}

  return 0;

}
// -------------------------------------------
// Set this evid's PrefOr to this orid
// returns > 0 (# rows changed) on success

function setPrefOr($_evid, $_orid) {

  $sql = "BEGIN :rtn := EPREF.setPrefor_Event (:evid, :orid); END;"; 
  $stmt = oci_parse($conn, $sql) or die ('Can not parse query'); 

  // bind variables
  oci_bind_by_name($stmt,":rtn",  $res1,  20) or die ('Cannot bind variable');
  oci_bind_by_name($stmt,":evid", $_evid, 20) or die ('Cannot bind variable');
  oci_bind_by_name($stmt,":orid", $_orid, 20) or die ('Cannot bind variable');

  oci_execute($stmt) or die ('setEventType can not Execute statement');

  oci_free_statement($stmt);
  oci_close($conn);

  return $res1;
}

// -------------------------------------------
// Write remark, if there is one
// Returns commid if one written, else 0

function writeRemark () {

  $str = Trim($this->remark);  // trim whitespace

  if ($str != "") {

    // get the next REMARK sequence number 
    $commid = getNextSeq('COMMSEQ');

    if ($commid == -1) {return 0;}   // didn't get a valid seq number
  
    $sql = "INSERT into REMARK (COMMID, LINENO, REMARK) ". 
  	               "VALUES ($commid, 1, $str)";
    $rtn = db_query_glob($sql);

    // on success....
    $sql = "Update Event set COMMID = $this->commid where evid = $this->evid";
    $rtn = db_query_glob($sql);

    return $commid; 
	
  }

  return 0;
}

// -----------------------------------------
// Get the given event from the default dbase
// Assumes connection already made and passed via globals
//
function getFromDbase ($_evid) {

// Be explicit about resulting column names because:
// 1) we use them to map to variable names
// 2) names that appear in multiple tables will need to be aliased (e.g. auth)

// the column names = internal variable names

// The dbase query
// Note "m_" prefix so we can distinguish same-named columns in Origin & Netmag
$sql_query="SELECT /*+ FIRST_ROWS */ 
	Event.evid \"evid\", 
	NetMag.magid \"m_magid\", 
	 NetMag.magnitude \"m_magnitude\", NetMag.magtype \"m_magtype\", 
	 NetMag.nsta \"m_nsta\", NetMag.quality \"m_quality\", 
 	 NetMag.uncertainty \"m_uncertainty\", NetMag.gap \"m_gap\", 
	 NetMag.magalgo \"m_magalgo\", NetMag.rflag \"m_rflag\", 
	 NetMag.subsource \"m_subsource\", NetMag.auth \"m_auth\",
	Origin.auth \"auth\", 
         Origin.datetime \"datetime\", 
         Origin.lat \"lat\", Origin.lon \"lon\", Origin.depth \"depth\",
	 Origin.ndef \"ndef\", Origin.gtype \"gtype\", Origin.rflag \"rflag\", 
         Origin.wrms \"wrms\", Origin.gap \"gap\", 
	 Origin.subsource \"subsource\",
         Origin.bogusflag \"bogusflag\", Origin.type \"type\", 
	Event.etype \"etype\",

	Remark.remark\"remark\" 
         FROM Event, Origin, NetMag, Remark 
         WHERE Event.evid = $_evid and
	 selectflag = 1 and
        ( (Event.prefor = Origin.orid(+)) and (Event.prefmag = NetMag.magid(+) )
         and ( origin.commid = remark.commid(+) ) )
       Order by Origin.lddate desc ";

// execute the query
   $result = db_query_glob($sql_query);

// if more than one only last is used :. Order by could be important
 while (OCIFetchInto ($result, $row, OCI_ASSOC)) {

// quick way to parse
//   extract($row);  // convert array variables:e.g. $row['magtype'] => $magtype
//   dumpArray ($row) ;	 // debug

   $this->getEventFromRow($row);

   $this->inDbase = TRUE;  // flag that Object = dbase contents

   $this->mag->getMagFromRow($row);
 
 }

  return $this;

} // end of getFromDbase()

// -----------------------------------------
//  Suck row contents into object variables
// (Isn't there an extract-like method for this?)
//
// NOTE: Not all fields are read from Origin

function getEventFromRow ($row) {

   $this->evid = $row['evid'];
   $this->auth = $row['auth'];
   $this->datetime = $row['datetime'];
   $this->lat = $row['lat'];
   $this->lon = $row['lon'];
   $this->depth = $row['depth']; 
   $this->ndef = $row['ndef']; 
   $this->wrms = $row['wrms'];
   $this->gap = $row['gap']; 
   $this->etype = $row['etype']; 
   $this->gtype = $row['gtype'];
   $this->rflag = $row['rflag'];
   $this->subsource = $row['subsource'];
   $this->type = $row['type']; 
   $this->bogusflag = $row['bogusflag'];
}

// rough
function toString() {

   $indb = "FALSE";
   if ($this->inDbase) {$indb = "TRUE";}
   $dstr = gmdate('M j, Y G:i:s', $this->datetime);
   return "$this->evid $this->lat $this->lon $this->depth @ $dstr inDb=$indb";
}
// -----------------------------------------
function dump() {
   $str = $this->toString();
   echo "$str<br>";
}

} // end of Event class

// ///////////////////////////////////////////////////////////////////////////////
// Event mag 
//
class Magnitude {

// Variable names are the same as dbase column names
// required
var $evid;
var $magid;       // NOT NULL

var $magtype;     // NOT NULL
var $magnitude;   // NOT NULL
var $auth;        // NOT NULL

// optional
var $subsource;
var $magalgo;
var $quality;
var $nsta;
var $uncertainty;
var $gap;
var $distance;
var $rflag;


var $inDbase = FALSE;  // set TRUE if object matched dbase contents, else FALSE

//
// Constructor
//
function Magnitude() {

}

// -----------------------------------------
// Parse out the mag part of a row which may contain other stuff
//
// If there are duplicate names we must be consistent in our naming 
//
function getMagFromRow ($row) {

  $this->magnitude 	= $row['m_magnitude'];
  $this->magtype  	= $row['m_magtype'];
  $this->magid   	= $row['m_magid']; 
  $this->subsource	= $row['m_subsource'];
  $this->auth 		= $row['m_auth'];

  $this->nsta 		= $row['m_nsta'];
  $this->quality 	= $row['m_quality'];
  $this->uncertainty	= $row['m_uncertainty'];
  $this->gap 		= $row['m_gap'];
  $this->magalgo 	= $row['m_magalgo'];
  $this->rflag 		= $row['m_rflag']; 

//echo "---: $this->magid $this->magtype $this->magnitude $this->auth $this->subsource $this->magalgo $this->quality $this->nsta $this->uncertainty $this->gap $this->distance $this->rflag";

 $this->$inDbase = TRUE;  // flag that Object = dbase contents

}
// --------------------------------------------------------------------
//  Write new NetMag row and set as the prefmag for this Event
// Use Event.prefor as the orid for the new NetMag row
// :. new origin, if any, must we written and Event.prefor updated first.
//
// Assumes a WRITABLE connection is already made and passed via globals
// call EPREF.setPrefMag (938274, 1.2, 'l', 'CI', 'TEST', 'BS', 0.0, 'H') ;

function writeToDb () {

	// get the next Origin sequence number 


// Notes: LDDATE will get handled by the dbase
//        etype is in Event not Origin

/* *********************
$magid = getNextSeq('MAGSEQ');
if ($magid == -1) {return FALSE;}   // didn't get a valid seq number
$sql = "INSERT INTO NETMAG 
	(magid, orid, auth, subsource, 
	 magnitude, magtype, magalgo ) 
	VALUES 
	( '$this->magid','$this->orid', '$this->auth', '$this->subsource',
	 '$this->magnitude', '$this->magtype', '$this->magalgo')";
************************ */
//
//
//   USE EPREF. HERE -- JASI IS OBSOLETE!! DDG 6/2006
//
//

// Stored proc creates a new NetMag row with these values 
// then sets the premag of this evid to the new mag.
$sql = "call EPREF.setPrefMag ($this->evid, $this->magnitude, '$this->magtype',
	 '$this->auth', '$this->subsource', '$this->magalgo',
	  $this->quality, '$this->rflag')";

// Insert the new row (does autocommit)
// TODO: errors??
	$rtn = db_query_glob($sql);

echo "wrote rtn = $rtn<p>";  

   return TRUE;   // success

}

//
//
// ------------------------------------------
function setAsPrefered ($_evid) {

   $sql = "Update Event set prefmag = $magid where evid = $_evid";

// Insert the new row (does autocommit)
   $rtn = db_query_glob($sql);
}

// --------------------------------------------
function toString() {
  return  "MAG: $this->magid $this->magtype $this->magnitude $this->auth $this->subsource $this->magalgo $this->quality $this->nsta $this->uncertainty $this->gap $this->distance $this->rflag";
}

} // end of Magnitude

// ////////////////////////////////////////////////////////////////////////////////////////////
class scsn_DateTime {

 var $epochSecs;
 var $month3;   // month string array

// -----------------------------------------
// constructor
function scsn_DateTime ($_epochSec) {
   $this->epochSecs = $_epochSec;

   $this->month3 = array ("JAN", "FEB", "MAR", "APR", "MAY", "JUN",
                          "JUL", "AUG", "SEP", "OCT", "NOV", "DEC");
}

function set ($yr, $mo3, $dy, $hr, $mn, $fsec) {

   // 'gmmktime' only deals with int seconds - must handle fraction explicitly
   $frac = $fsec - (int) ($fsec);  // truncates

   $this->epochSecs = gmmktime($hr, $mn, $fsec, $this->getIntMo($mo3), $dy, $yr) + $frac;

}

// -----------------------------------------
function getEpochSecs () {
  return $this->epochSecs;
}
// -----------------------------------------
// Given an month number return a 3-char string for the month
// e.g. 1 = "JAN"
function get3charMo ($imo) {
  return $this->month3[$imo - 1];
}
// -----------------------------------------
// Given a 3-char month string return the month number. Returns -1 if no match.
// e.g. "JAN" = 1
// Case does not matter: "jan" = 1, "Jan" = 1
// You may also pass a longer string - only the 1st 3 chars will be considered.
function getIntMo ($mo3) {

  $cmo = strtoupper(substr($mo3, 0, 3));  // upcase 1st 3 chars
  
  $i = 1;
  foreach ($this->getMonthArray() as $moStr) {

    if ($moStr == $cmo) return $i;
    $i++;
  }
  return -1;
}
// -----------------------------------------
function getMonthArray () {
  return $this->month3;
}
// -----------------------------------------
function getYear() {
  return  gmdate("Y", $this->epochSecs);   // 4 digit year
}
// -----------------------------------------
function getMonth () {
  return gmdate("m", $this->epochSecs) ;   // # of month 1-12
}
// -----------------------------------------
function getMonth3Char () {
  return gmdate("M", $this->epochSecs) ;   // 3-char month e.g. "Jan"
}
// -----------------------------------------
function getDay () {
  return gmdate("d", $this->epochSecs) ;   // day of month with leading zero
}
// -----------------------------------------
function getHour () {
  return gmdate("H", $this->epochSecs) ;   // 00-24 hr with leading zero

}
// -----------------------------------------
function getMinute () {
  return gmdate("i", $this->epochSecs) ;   // minutes with leading zero
}
// -----------------------------------------
function getSecond () {
  $frac = $this->epochSecs - (int)$this->epochSecs;
  return gmdate("s", $this->epochSecs) + $frac ;   // seconds with leading zero
}
// -----------------------------------------
//
function toString() {
  return gmdate('M j, Y G:i:s', $datetime) ;
}

//	$lenmo = gmdate("t", $epochSecs) ; // # of days in the month of the $epochSecs
//	$jday  = gmdate("z", $epochSecs) ; // julian day 1-365 (leap day not handled)
//      $dow   = gmdate("l", $epochSecs) ; // Day of week (e.g. Monday)

} // end of DateTime class
?>
