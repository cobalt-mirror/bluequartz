// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: z_monitorLight.js 201 2003-07-18 19:11:07Z will $

// check monitor now and every hour 15 minutes
monitorLight_checkAlert();
setInterval("monitorLight_checkAlert()", 75*60*1000);

//
// private variables
//

var _monitorLight_isAlert = false;

//
// public functions
//

// description: check if updates exist
function monitorLight_checkAlert() {
  top.code.comm_scheduleLoad("/base/am/monitorLight.php");
}

// description: repaint the monitor light
function monitorLight_repaintLight() {
  var monitorLight = top.siteMap.base_monitorLight;

  // site map item monitorLight exists?
  if(monitorLight == null)
    return;

  // set item type appropriately
  if(_monitorLight_isAlert)
    monitorLight.setType("monitorOn");
  else
    monitorLight.setType("monitorOff");

  // repaint tab if the light is on it
  var tabRoot = top.code.tab_getRoot();
  if(tabRoot != null && tabRoot.getItem("base_monitorLight"))
    top.code.tab_repaint();

  // repaint collapsible list if the light is on it
  var cListRoot = top.code.cList_getRoot();
  if(cListRoot != null && cListRoot.getItem("base_monitorLight", true))
    top.code.cList_repaint(true);
}
