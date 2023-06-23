<html>
<?php
// Dump the roster of duty people

$db = mysql_connect("localhost", "admin", "beepbeep");
mysql_select_db("admin", $db);

//$sql = "select * from dutylist where FIND_IN_SET('Seismo',duty)>0"; 
$sql = "select firstname,lastname,id from dutylist where FIND_IN_SET('Seismo',duty)>0"; 

$result = mysql_query($sql, $db);

if ($dutyslist = mysql_fetch_array($result))  {

printf ("<OPTION VALUE=\"none\">----</OPTION>\n");

do {
//echo "$dutyslist[\"id\"]  $dutyslist[\"firstname\"], $dutyslist[\"lastname\"] $dutyslist[\"id\"]";
$id = $dutyslist["id"];
$first = $dutyslist["firstname"];
$last = $dutyslist["lastname"];
$user = $dutyslist[1];

echo "$id $first $last  $dutylist<br>";

} while ($dutyslist = mysql_fetch_array($result));
}

?>
</html>
