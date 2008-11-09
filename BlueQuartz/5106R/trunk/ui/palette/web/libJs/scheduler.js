// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: scheduler.js 3 2003-07-17 15:19:15Z will $

//
// private variables
//

var _scheduler_jobs = new Object();
var _scheduler_jobIntervalHandle = -1;

//
// public functions
//

function scheduler_addJob(name, job) {
  _scheduler_jobs[name] = job;
}

function scheduler_doJob() {
  // for each job
  for(var jobName in _scheduler_jobs) {
    // get job
    var job = _scheduler_jobs[jobName];

    // cleared before?
    if(job == null)
      continue;

    var isFinished = job["function"](job);

    if(isFinished)
      // clear up
      job = null;

    // save
    _scheduler_jobs[jobName] = job;
  }

  // see if there are jobs left
  var isDone = true;
  for(var jobName in _scheduler_jobs) {
    var job = _scheduler_jobs[jobName];
    if(job != null) {
      isDone = false;
      break;
    }
  }

  // need to use setInterval instead of setTimeout because of a NN bug
  clearInterval(_scheduler_jobIntervalHandle);

  //if(!isDone)
    _scheduler_jobIntervalHandle = setInterval("scheduler_doJob()", 1000);
}
