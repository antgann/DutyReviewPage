<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN" "http://www.w3.org/TR/html4/frameset.dtd"> 
<html>
<head>
    <link rel="stylesheet" title="catalogstyle" type="text/css" href="cataloghdr.css">
    <SCRIPT LANGUAGE="JAVASCRIPT"> 
<!-- 
        function pageScroll(position) {
           parent.catalog.focus(); 
           if ( position == 'top' ) { 
             parent.catalog.scrollTo(0, 0); // horizontal and vertical scroll increments
           }
           else {
             parent.catalog.scrollTo(0, parent.catalog.document.body.scrollHeight);
           }
        }

        function scrollToSelected() {
          // Note below scrolls item to top which is hidden below fixed cat header
          var oldid = parent.catalog.getId(parent.catalog.getCurrentIndex())
          var str = "sel-" + oldid;
          var item = document.getElementById(str);
          item.scrollIntoView();
          // TODO: Fix kludge below that moves id element to below the catalog header at top of scroll window
          var item2 = document.getElementById("catHdr");
          //alert("item " + item.offsetTop + " " + item.offsetHeight + " " + item.scrollHeight + " " + item.scrollTop);
          if (item.offsetTop < (item.offsetParent.scrollHeight-item.offsetParent.offsetHeight)+item2.offsetHeight) {
              window.scrollBy(0, item.scrollHeight- 2*item2.scrollHeight);
          }
        }
-->
    </SCRIPT>
</head>
<body> 
<?php
include_once "phpmods/config.php";
$narrow=0;
if ( $_GET["NARROW"] ) {
  $narrow = $_GET["NARROW"];
}
if ( $narrow ) {
  $break="<br>";
}
else {
  $break = "";
}
global $havePDL;
// Make the header
$reload="<a href=\"#\" onclick=\"top.document.location.reload(true);return false;\">refresh</a>";
$bottom="<a id=\"rlink\" href=\"javascript:pageScroll('bottom')\">bottom</a>";
$top="<a id=\"rlink\" href=\"javascript:pageScroll('top')\">top</a>";
$selected="<a id=\"rlink\" href=\"javascript:scrollToSelected()\">selected</a>";
echo "<pre>";
echo "<b>";
echo "<div id=\"noborder\">";
echo "  evid       mag     date      time   et gt pm$break";
if ( $havePDL ) {
  echo "  src r  v  p  lat      lon      z    #ph rms$break  > location (catalog: $reload $bottom $top $selected)";
}
else {
  echo "  src r  v  lat      lon      z    #ph rms$break  > location (catalog: $reload $bottom $top $selected)";
}
echo "</b>";
echo "</pre>";
echo "</div>";
?>
</body>
</html>
