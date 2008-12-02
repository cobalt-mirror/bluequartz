<?php

  header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); 
  header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");

header("Cache-Control: post-check=1,pre-check=2?");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");


  $password = $_POST["password"];

  $dictionary = crack_opendict('/usr/share/dict/pw_dict') or die('Unable to open CrackLib dictionary');
  $check = crack_check($dictionary, $password);
  $diag = crack_getlastmessage();
  crack_closedict($dictionary);

  print $diag;

?>
