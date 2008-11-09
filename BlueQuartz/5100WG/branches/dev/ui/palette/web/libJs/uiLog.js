// Author: Kevin K.M. Chiu
// Copyright 2001, Cobalt Networks.  All rights reserved.
// $Id: uiLog.js 201 2003-07-18 19:11:07Z will $

//
// private variables
//

// initialize frontend log
var _uiLog_entries = new Array();
var _uiLog_flushSet = null;

//
// public functions
//

// description: log an event in the frontend
//   if the log grows big, uiLog_flush is called
// param: event: a non-tabbed string that specifies the event
// param: target: a non-tabbed string that specifies the event target
// param: id: a non-tabbed string that specifies the instance of the target
// param: ...: optional parameters of the event in non-tabbed strings
function uiLog_log(event, target, id) {
  // creates an entry
  var entry = new Array();
  entry["event"] = event;
  entry["target"] = target;
  entry["id"] = id;
  entry["time"] = (new Date()).getTime();

  // adds all extra parameters to the entry
  var paramNumber = arguments.length-3;
  entry["paramNumber"] = paramNumber;
  for(var i = 0; i < paramNumber; i++)
    entry["param"+i] = arguments[i+3];

  // add the entry to the log variable
  _uiLog_entries[_uiLog_entries.length] = entry;

  // flush log 15 seconds later if no flush has been set
  if(_uiLog_flushSet == null)
    _uiLog_flushSet = setTimeout("uiLog_flush()", 15000);
}

// description: write frontend logs to the backend log file
//   and clear the frontend log
function uiLog_flush() {
  _uiLog_flushSet = null;

  // nothing to flush?
  if(_uiLog_entries.length == 0)
    return;

  // prepare log for transfer
  var log = "";
  for(var i = 0; i < _uiLog_entries.length; i++) {
    // get an entry
    var entry = _uiLog_entries[i];

    // add the entry to the log
    log += entry["time"] + "\t" +
      entry["event"] + "\t" +
      entry["target"] + "\t" +
      entry["id"];
    for(var j = 0; j < entry["paramNumber"]; j++)
      // parameters can have new lines and other special characters
      log += "\t" + escape(entry["param"+j]);

    log += "\n";
  }

  // use commFrame to communicate to the backend
  top.code.comm_scheduleLoad("/uiLog.php?log="+escape(log));

  // clean up the log
  _uiLog_entries = new Array();
}
