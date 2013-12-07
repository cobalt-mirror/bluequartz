// Author: Kevin K.M. Chiu
// $Id: updateLight.js

// check if we need to run update light code
updateLight_scheduleAccessCheck();

//
// private variables
//

var _updateLight_hasUpdates = false;

//
// public functions
//


// description: we need to setup the initial check AFTER the UI
// finishes loading. We do it with a scheduler job.
function updateLight_scheduleAccessCheck() {
  name = "updateLight_checkAccess";
  var job = new Object();
  job["function"] = updateLight_checkAccess;
  job["name"] = name;
  top.code.scheduler_addJob(name, job);
}

// description: check if we should run update light code
function updateLight_checkAccess() {
  // only check if we have access
  var tRoot = top.code.tab_getRoot();
  if(tRoot != null && tRoot.getItem("base_updateLight")) {
    // check update now and every 2 hours
    updateLight_checkUpdate();
    setInterval("updateLight_checkUpdate()", 2*60*60*1000);
  }
  return true;
}


// description: check if updates exist
function updateLight_checkUpdate() {
  top.code.comm_scheduleLoad("/base/swupdate/updateLight.php");
}

// description: repaint the update light
function updateLight_repaintLight() {
  var updateLight = top.siteMap.base_updateLight;

  // site map item updateLight exists?
  if(updateLight == null)
    return;

  // set item type appropriately
  if(_updateLight_hasUpdates == true)
    updateLight.setType("updateOn");
  else
    updateLight.setType("updateOff");

  // repaint tab if the light is on it
  var tabRoot = top.code.tab_getRoot();
  if(tabRoot != null && tabRoot.getItem("base_updateLight"))
    top.code.tab_repaint();

  // repaint collapsible list if the light is on it
  var cListRoot = top.code.cList_getRoot();
  if(cListRoot != null && cListRoot.getItem("base_updateLight", true))
    top.code.cList_repaint(true);
}
