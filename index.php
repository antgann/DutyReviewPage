<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN" "http://www.w3.org/TR/html4/frameset.dtd"> 
<!-- This defines the frames for the Duty Review Page
     The waves & buttons frames are event specific and created by catalog.php  -->

<html>
<head>
<?php
    include_once "cgi-bin/phpmods/config.php";
    print "<title>$networkName Event Review Page</title>"
?>
</head>

<frameset cols="85%,*" frameborder="1" framespacing="2" border="2" bordercolor="blue">
  <frameset rows="50%,*" frameborder="1" framespacing="2" border="2" bordercolor="blue">
      <frame src="cgi-bin/blankPage.html" name="waves" scrolling="yes">
      <frame src="cgi-bin/catalog.php?LIMIT=0&NARROW=0" name="catalog" scrolling="yes" marginheight=0; marginwidth=0;>
  </frameset>
  <frame src="cgi-bin/blankPage.html" name="buttons" scrolling="yes">
</frameset>

</html>

<!--
+---------------------------------+-------------+
| [waves]                         | [buttons]   |
|                                 |             |
|                                 |             |
|                                 |             |
|                                 |             |
|                                 |             |
|                                 |             |
+---------------------------------+-------------+
| [catalog]                                     |
|                                               |
|                                               |
+-----------------------------------------------+
-->
