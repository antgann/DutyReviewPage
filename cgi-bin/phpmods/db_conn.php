<?php
// Default values moved inside phpmods/config.php
include_once("phpmods/config.php");

global $db_user,$db_password,$db_power_user,$db_power_password, 
       $sys_dbDescr,$sys_dbUser, $sys_dbPasswd,$sys_dbHost,$sys_dbName;

// used by Oracle.php, assign default values from config.php
$sys_dbUser   = $db_user;
$sys_dbPasswd = $db_password;
$sys_dbName   = $db_name;
$sys_dbHost   = $db_host;

// ---------------------------------------------------
function getDefDbhost() {
    global $sys_dbHost;
    return $sys_dbHost;
}

// ---------------------------------------------------
function getDefDbname() {
    global $sys_dbName;
    return $sys_dbName;
}

// ---------------------------------------------------
function getDefDbuser() {
    global $sys_dbUser;
    return $sys_dbUser;
}

// ---------------------------------------------------
function getDefDbpwd() {
    global $sys_dbPasswd;
    return $sys_dbPasswd;
}

// ---------------------------------------------------
function getDefDbDescription () {
    global $sys_dbDescr;
    return $sys_dbDescr;
}
// ---------------------------------------------------
function getDbServerString($_dbname) {
/* returns Fully Qualified Domain Name (FQDN)
 * possibly misapropriating this function
 */

    global $domain; // from config.php

    $url = $_dbname; 

    if ( ! empty($domain) ) {
      if ( preg_match("/^\./", $domain) ) {
        $url .= $domain;
      }
      else {
        $url .=  "." . $domain;
      }
    }

    return $url;
}

// ---------------------------------------------------
function formatDbServerString($_url, $_dbname, $_port) {

    $str = "(DESCRIPTION = (ADDRESS_LIST = 
    (ADDRESS = (PROTOCOL = TCP)(HOST = ${_url})(PORT = ${_port}))) 
    (CONNECT_DATA = (SERVICE_NAME = ${_dbname}.${_url})))";

    return $str;
}

?>
