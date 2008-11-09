<?php
// $Id: routes.php 3 2003-07-17 15:19:15Z will $
//
// this one little script both creates the list view, the edit/create view,
// and performs all the post handling.  I believe it's better to do all
// this functionality in one script: it's faster, it makes better use of
// PHP resources, and the code is easier to maintain.

// where am i?
$self = $PHP_SELF;
if (!$self) { $self = getenv("SCRIPT_URL"); }
if (!$self) { $self = getenv("SCRIPT_NAME"); }
if (!$self) { $self = "/base/network/routes.php"; } // fine!

// where do I live?
$backurl = "/base/network/ethernet.php";

if ($HTTP_GET_VARS["ADD"] 
  || $HTTP_GET_VARS["EDIT"])
{
  edit_route();
} else {
  list_routes();
}

function list_routes ()
{
  include("SimpleList.php");

  global $HTTP_GET_VARS, $HTTP_POST_VARS;
  global $cce, $self;

  list_init("base-network", "routeList", 
    array( "route-target", "route-netmask", 
      	   "route-gateway", "route-device" ) );

  if ($HTTP_GET_VARS["REMOVE"]) {
    // remove route
    $cce->destroy($HTTP_GET_VARS["REMOVE"]);
  }
  
  $routes = $cce->find("Route");
  for ($i = 0; $i < count($routes); $i++) {
    $r = $cce->get($routes[$i]);
    list_add( 
      array( 
	$r["target"], 
	$r["netmask"], 
	($r["gateway"] == "0.0.0.0" ? "&nbsp;" : $r["gateway"]),
	($r["device"] ? $r["device"] : "&nbsp;" ) 
      ), array(
	"edit" => $self . "?EDIT=" . $routes[$i],
	"remove" => $self . "?REMOVE=" . $routes[$i]
      )
    );
  }

  global $factory;
  global $backurl;
  $backButton = $factory->getBackButton($backurl);
  list_append("
<BR>
<table border=0 cellspacing=2 cellpadding=2>
<tr><td nowrap>
	" . $backButton->toHtml() . "
</td></tr>
</table>
");
  
  list_render();
}

function edit_route ()
{
  global $self;
  global $HTTP_GET_VARS, $HTTP_POST_VARS;
  global $sessionId;
  
  include("CobaltUI.php");

  $ui = new CobaltUI($sessionId, "base-network");

  $save = $HTTP_POST_VARS["_save"];
  if ($save) {
    // construct object:
    $obj = array(
      "target" => $HTTP_POST_VARS["route_form_target"],
      "netmask" => $HTTP_POST_VARS["route_form_netmask"],
      "gateway" => $HTTP_POST_VARS["route_form_gateway"],
      "device" => $HTTP_POST_VARS["route_form_device"]
    );

    // save object:
    $oid = $HTTP_GET_VARS["EDIT"] ? $HTTP_GET_VARS["EDIT"] : 0;
    $ok = 1;
    if ($oid > 0) {
      $ok = $ui->Cce->set($oid, "", $obj);
    } else {
      $ok = $ui->Cce->create("Route", $obj);
    }
    
    // report errors:
    $errors = $ui->Cce->raw_errors();
    $ui->report_errors($errors, array(
	"target" => "route_form_target",
	"netmask" => "route_form_netmask",
	"gateway" => "route_form_gateway",
	"device" => "route_form_device",
    ));
    
    if ($ok) {
	$ui->Redirect($self);
	exit();
    } else {
	//echo "<li> ok = $ok";
    }
  }
  
  $edit = $HTTP_GET_VARS["EDIT"];
  if ($edit) {
    $obj = $ui->Cce->get($HTTP_GET_VARS["EDIT"]);
    $ui->Data["route_form_target"] = $obj["target"];
    $ui->Data["route_form_netmask"] = $obj["netmask"];
    $ui->Data["route_form_gateway"] = $obj["gateway"];
    $ui->Data["route_form_device"] = $obj["device"];
  } else {
    $ui->Data["route_form_target"] = "";
    $ui->Data["route_form_netmask"] = "";
    $ui->Data["route_form_gateway"] = "";
    $ui->Data["route_form_device"] = "";
  }
  
  $ui->StartPage("AAS", $edit ? $edit : "Route", "");
  $ui->StartBlock($edit ? "modifyRoute" : "createRoute");
  
  $ui->IpAddress("route_form_target");
  $ui->IpAddress("route_form_netmask");
  $ui->IpAddress("route_form_gateway");
  $ui->SelectField("route_form_device", array("eth0", "eth1", "ppp0"));
  
  $ui->AddButtons($self . "?SAVE= ". ($edit ? $edit : "Route") );
  
  $ui->EndBlock();
  $ui->EndPage();
}
/*
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

