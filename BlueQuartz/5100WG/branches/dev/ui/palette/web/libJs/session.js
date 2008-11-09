// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: session.js 201 2003-07-18 19:11:07Z will $

//
// private variables
//

// must not be bigger than CCE session timeout period
var _session_interval = 10;
var _session_connectTimes = -1;
var _session_timeoutId = -1;

//
// public functions
//

// description: keep the session alive for at least the specified period
// param: minutes: number of minutes
function session_keepAlive(minutes) {
  _session_connectTimes = Math.floor(minutes/_session_interval);
  _session_doConnect();
}

// description: end any ongoing keep alive efforts
function session_endKeep() {
  _session_connectTimes = 0;

  if(_session_timeoutId != -1) {
    clearTimeout(_session_timeoutId);
    _session_timeoutId = -1;
  }
}

//
// private functions
//

function _session_doConnect() {
  // do CCE connection
  top.commFrame.location = "/nav/blank.php";

  _session_connectTimes--;

  if(_session_connectTimes > 0)
    _session_timeoutId = setTimeout("_session_doConnect()", _session_interval*60000);
}
