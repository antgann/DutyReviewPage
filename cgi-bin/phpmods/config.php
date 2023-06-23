<?php
/* config.php - central store for all DRP configure options */
// Script has hard-coded host network dependencies
// Network Name used by index.php and trigger.php

$networkName = "SCSN";
$networkCode = "CI";

// Max age of events in catalog 
$hrsBack=72; 
$minMagDefault = -9;
$maxMagDefault = 99;

// Region boundary points for use with $inRegion

$inRegionBoundaryPolygon = array("37.43 -117.76","34.5 -121.25","31.5 -118.5","31.50 -114.00","34.5 -114.0","37.43 -117.76");

$binDir = "/app/aqms/www/review/cgi-bin/";

$logFile = "/app/aqms/www/review/logs/review.log";

// Calcite would only connect to FQDN.  But, some queries only want the
// unqualified name.  These values are used in db_conn.php by way of oracle.php
$db_name = "REDACTED";
$db_host = "REDACTED";

// Script has hard-coded host network dependencies
$domain = "caltech.edu";

// Obfuscate w/ $hex_string = bin2hex($string);
$db_user           = "browser";
$db_password       = pack('H*', "99999999999999");
$db_power_user     = "tpp";
$db_power_password = pack('H*', "88888888888888");

// list of candidate RT systems.  Map database name to host name
// used by db_utils.php/getMasterRThost()
$list['rtdbw'] = 'p.caltech.edu';
$list['rtdbe'] = 'z.caltech.edu';

// used by makeButtonPanel.php/formutils.php/makeTypeOptionList()
// New list as of 3/24/2011
$eventTypes = array('av' => 'snow-ice',
                    'bc' => 'bldg',
                    'ce' => 'calibr',
                    'co' => 'm-collapse',
                    'df' => 'debris',
                    'ex' => 'explosion',
                    'eq' => 'earthquake',
                    'lf' => 'low freq',
                    'lp' => 'longperiod',
                    'ls' => 'landslide',
                    'mi' => 'meteor',
                    'nt' => 'nuclear',
                    'ot' => 'other',
                    'pc' => 'plane',
                    'px' => 'prob explosion',
                    'qb' => 'quarry',
                    'rb' => 'rockburst',
                    'rs' => 'rockslide',
                    'se' => 'slow',
                    'sh' => 'shot',
                    'sn' => 'sonic',
                    'st' => 'trigger',
                    'su' => 'surface event',    
                    'th' => 'thunder',
                    'to' => 'tornillo',
                    'tr' => 'nv-tremor',
                    'uk' => 'unknown',
                    've' => 'eruption',
                    'vt' => 'v-tremor');
// used by makeButtonPanel.php/formutils.php/makeGTypeOptionList()
$eventGTypes = array('l' => 'local', 'r' => 'regional', 't' => 'teleseism');
// where is the solution server? used by dumphypo.php
$solServer = "t.caltech.edu";
$solServerPort = "63000";

$webicorderURL="../../webicorder/";

$USGSRootURL = "https://earthquake.usgs.gov";
$recEqRootURL = "https://earthquake.usgs.gov/earthquakes";
// list of ShakeMap URLs.  makeSnapshotFrame.php will append $evid
$shakeMapURLs = array ("${recEqRootURL}/shakemap/sc/shake",
                       "http://www.cisn.org/shakemap/sc/shake");

// Option for alternate stovepipe RT,PP db pair:
$secondaryAlarmDBS = array ( "rtdbe", "archdbe", "rtdbc1");

// Did You Feel It URL.  makeSnapshotFrame.php will append $evid/ciim_display.html
$dyfiURL = "http://pasadena.wr.usgs.gov/shake/ca/STORE/X";

// Send Email page
$toAddrDuty   = "duty_on@eqinfo.wr.usgs.gov";
$fromAddrDuty = "seismologist@eqinfo.wr.usgs.gov";

// Send Email page
$toAddrFelt   = "felt@eqinfo.wr.usgs.gov";
$fromAddrFelt = "seismologist@eqinfo.wr.usgs.gov";

// Local web URL's for auto focmec
$arFocmecURL = "../../focmec/fmar";
//$mtFocmecURL = "http://www.data.scec.org/MomentTensor/solutions";
// Changed root for mt 20150123 -aww
$mtFocmecURL = "http://service.scedc.caltech.edu/MomentTensor/solutions";
// Changed ;
$fpFocmecURL_H = "http://pasadena.wr.usgs.gov/recenteqs/QuakeAddons";
$fpFocmecURL_A = "../../focmec/fpfit";

// a PDL product client mySQL db on web server?
$havePDL = 1;

// minimum and maximum magnitude values allowed for the "Edit Magnitude" function
$editMagMin = -1;
$editMagMax = 8.5;

// minimum and maximum depth values allowed for the "Edit Depth" function
$editDepthMin = 3;
$editDepthMax = 30;

$GIFarchive = "http://255.255.255.254/display.php";

?>
