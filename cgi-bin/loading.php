<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
   "http://www.w3.org/TR/html4/strict.dtd">
<html>
  <head>
    <title>Loading</title>
  </head>

  <body onload="loadCurrent()">
    <h1>Loading...</h1>

<?php
/*
  Loading page for DRP, placed after user selects new event

*/


include_once "phpmods/db_conn.php";
include_once "phpmods/formutils.php";
include_once "phpmods/config.php";

$scroll   = $_GET["SCROLL"];

echo "\n<script type=\"text/javascript\">\n";
echo "<!-- Hide if browser can not cope with JavaScript \n";
echo "function loadCurrent() {\n";
echo "top.catalog.selectDefaultEvidAndScroll($scroll)\n";
echo "}";
echo "</SCRIPT>\n";

?>



  </body>
</html>
