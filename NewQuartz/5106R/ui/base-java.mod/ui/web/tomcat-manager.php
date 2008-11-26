<?php
        // Author: Brian N. Smith
        // Copyright 2007, NuOnce Networks, Inc.  All rights reserved.
        // $Id: tomcat-manager.php,v 1.0 2007/11/17 21:04:00 Exp $

        include("ServerScriptHelper.php");
        include("Product.php");

        $serverScriptHelper = new ServerScriptHelper();
        $cceClient = $serverScriptHelper->getCceClient();

        if (!$serverScriptHelper->getAllowed('adminUser')) {
                header("location: /error/forbidden.html");
                return;
        }

        $sysConfig = $cceClient->getObject("System", array());

        $adminURL = "http://" . $sysConfig["hostname"] . "." . $sysConfig["domainname"] . ":8080/admin";
        $managerURL = "http://" . $sysConfig["hostname"] . "." . $sysConfig["domainname"] . ":8080/manager/html";
        $hostManagerURL = "http://" . $sysConfig["hostname"] . "." . $sysConfig["domainname"] . ":8080/host-manager/html";
        $managerStatusURL = "http://" . $sysConfig["hostname"] . "." . $sysConfig["domainname"] . ":8080/manager/status";

?>
<table width="100%">
  <tr> 
    <td>
      <div style="font-size:18px; text-align:center">
        <br><br>
        <img src="/base/java/asf-logo-wide.gif">
        <p><br><br></p>
        <a target="_blank" href="<?=$adminURL;?>"> Launch Tomcat Admin Interface </a>
        <div style="font-size:10px;">(<?=$adminURL;?>)</div>
        <a target="_blank" href="<?=$managerURL;?>"> Launch Tomcat Manager Interface </a>
        <div style="font-size:10px;">(<?=$managerURL;?>)</div>
        <a target="_blank" href="<?=$hostManagerURL;?>"> Launch Tomcat Host Manager Interface </a>
        <div style="font-size:10px;">(<?=$hostManagerURL;?>)</div>
        <a target="_blank" href="<?=$managerStatusURL;?>"> Launch Tomcat Manager Status </a>
        <div style="font-size:10px;">(<?=$managerStatusURL;?>)</div>
        <p><br><br></p>
        <img src="/base/java/tomcat.gif">
      </div>
    </td>
  </tr> 
</table>
