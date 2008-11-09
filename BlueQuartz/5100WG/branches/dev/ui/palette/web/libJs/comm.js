// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: comm.js 201 2003-07-18 19:11:07Z will $

//
// private variables
//

var _comm_lockOwner = null;

//
// public functions
//

// description: use the scheduler to schedule a job to loading an URL to the commFrame
//     if the scheduler is not running already, you need to call scheduler_doJob() to
//     start it
// param: url: the URL to load
function comm_scheduleLoad(url) {
  // make a unique name
  var name = "load"+(new Date()).getTime()+Math.random();

  // make a new job to schedule for
  var job = new Object();
  job["function"] = _comm_doLoad;
  job["name"] = name;
  job["url"] = url;
  job["startTime"] = -1;
  // maximum time allowed to load
  job["maxTime"] = 5000;

  top.code.scheduler_addJob(name, job);
}

//
// private functions
//

function _comm_doLoad(job) {
  // wait for another job to finish
  if(_comm_lockOwner != null && _comm_lockOwner != job["name"])
    return false;

  // get the lock
  _comm_lockOwner = job["name"];

  // start the load if necessary
  if(job["startTime"] == -1) {
    job["startTime"] = (new Date()).getTime();
    top.commFrame.location.replace(job["url"]);
  }

  // terminate if done loading
  if(top.commFrame.isLoaded != null && top.commFrame.isLoaded) {
    // release lock
    _comm_lockOwner = null;

    return true;
  }

  var currentTime = (new Date()).getTime();
  // terminate job if time limit is excceed
  if(currentTime-job["startTime"] > job["maxTime"]) {
    // release lock
    _comm_lockOwner = null;

    return true;
  }

  return false;
}
