<?php

// Author: Michael Stauber <mstauber@solarspeed.net>

include_once("ServerScriptHelper.php");

$serverScriptHelper = new ServerScriptHelper();
//Only users with adminUser capability should be here
if (!$serverScriptHelper->getAllowed('adminUser')) {
    header("location: /error/forbidden.html");
    return;
}
$cceClient = $serverScriptHelper->getCceClient();

if ($_PagedBlock_selectedId_mysql_head == "MySQL_TAB_TWO") {
//if ($MySQL_TAB_TWO == "true") {
    $whichtab = "two";
    $quelle_zwo = $my_cnf;
    $cfg_file_zwo = "/tmp/my.cnf";
    $fp = fopen($cfg_file_zwo,"w");
    flock($fp,2);
    $array_zwo = $quelle_zwo;
    fputs($fp, $quelle_zwo);
    flock($fp,3);
    fclose($fp);
}
else {
    $whichtab = "one";
}

$cceClient->setObject("System",
        array(
              "soltab" => $whichtab,
              "solsitemysql" => $udb_switch,
              "SELECT" => $SELECT,
              "INSERT" => $INSERT,
              "UPDATE" => $UPDATE,
              "DELETE" => $DELETE,
              "FILE" => $FILE,
              "CREATE" => $CREATE,
              "ALTER" => $ALTER,
              "INDEX" => $INDEX,
              "DROP" => $DROP,
              "TEMPORARY" => $TEMPORARY,
              "CREATE_VIEW" => $CREATE_VIEW,
              "SHOW_VIEW" => $SHOW_VIEW,
              "CREATE_ROUTINE" => $CREATE_ROUTINE,
              "ALTER_ROUTINE" => $ALTER_ROUTINE,
              "EXECUTE" => $EXECUTE,
              "MAX_QUERIES_PER_HOUR" => $MAX_QUERIES_PER_HOUR,
              "MAX_CONNECTIONS_PER_HOUR" => $MAX_CONNECTIONS_PER_HOUR,
              "MAX_UPDATES_PER_HOUR" => $MAX_UPDATES_PER_HOUR,
              "force_update" => $force_update),
	      "MYSQLUSERS_DEFAULTS");

$errors = $cceClient->errors();

print($serverScriptHelper->toHandlerHtml("/base/mysql/mysqlusers.php", $errors));

$serverScriptHelper->destructor();

/*
Copyright (c) 2013 Michael Stauber, SOLARSPEED.NET
Copyright (c) 2013 Team BlueOnyx, BLUEONYX.IT
Copyright (c) 2003 Sun Microsystems, Inc. All  Rights Reserved.

Redistribution and use in source and binary forms, with or without modification, 
are permitted provided that the following conditions are met:

-Redistribution of source code must retain the above copyright notice, this  list of conditions and the following disclaimer.

-Redistribution in binary form must reproduce the above copyright notice, 
this list of conditions and the following disclaimer in the documentation and/or 
other materials provided with the distribution.

Neither the name of Sun Microsystems, Inc. or the names of contributors may 
be used to endorse or promote products derived from this software without 
specific prior written permission.

This software is provided "AS IS," without a warranty of any kind. ALL EXPRESS OR IMPLIED CONDITIONS, REPRESENTATIONS AND WARRANTIES, INCLUDING ANY IMPLIED WARRANTY OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE OR NON-INFRINGEMENT, ARE HEREBY EXCLUDED. SUN MICROSYSTEMS, INC. ("SUN") AND ITS LICENSORS SHALL NOT BE LIABLE FOR ANY DAMAGES SUFFERED BY LICENSEE AS A RESULT OF USING, MODIFYING OR DISTRIBUTING THIS SOFTWARE OR ITS DERIVATIVES. IN NO EVENT WILL SUN OR ITS LICENSORS BE LIABLE FOR ANY LOST REVENUE, PROFIT OR DATA, OR FOR DIRECT, INDIRECT, SPECIAL, CONSEQUENTIAL, INCIDENTAL OR PUNITIVE DAMAGES, HOWEVER CAUSED AND REGARDLESS OF THE THEORY OF LIABILITY, ARISING OUT OF THE USE OF OR INABILITY TO USE THIS SOFTWARE, EVEN IF SUN HAS BEEN ADVISED OF THE POSSIBILITY OF SUCH DAMAGES.

You acknowledge that  this software is not designed or intended for use in the design, construction, operation or maintenance of any nuclear facility.
*/
?>