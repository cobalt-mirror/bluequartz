// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: css.js 3 2003-07-17 15:19:15Z will $

//
// private variables
//

// last event happened on the page
var _css_eventType = "";
var _css_eventWindow = null;
var _css_eventX = -1;
var _css_eventY = -1;

var _css_jobs = new Object();
var _css_jobIntervalHandle = -1;

//
// public functions
//

// description: capture events for a window
// param: win: the window to capture event from
function css_captureEvents(win) {
  _css_eventWindow = win;
  _css_initialize();
  var doc = win.document;


  // capture event for NN
  if(!(document.all))
    doc.captureEvents(Event.MOUSEMOVE | Event.MOUSEUP);

  // register handlers
  doc.onmousemove = _css_mouseMove;
  doc.onmouseup = _css_mouseUp;
}

// description: get the last happened event
// returns: an object with the properties:
//     type - type of event. e.g. "mousemove"
//     x - x coordinate of where the event occured
//     y - x coordinate of where the event occured
function css_getEvent() {
  var event = new Object();
  event.type = _css_eventType;
  event.x = _css_eventX;
  event.y = _css_eventY;

  return event;
}

function css_getPageX(doc, elementId) {
  // if NN
  if(document.layers)
    return doc[elementId].pageX;

  // if IE
  if(document.all)
    return doc.all[elementId].offsetLeft;
}

function css_getPageY(doc, elementId) {
  // if NN
  if(document.layers)
    return doc[elementId].pageY;

  // if IE
  if(document.all)
    return doc.all[elementId].offsetTop;
}

function css_getHeight(doc, elementId) {
  // if NN
  if(document.layers)
    return doc[elementId].clip.height;

  // if IE
  if(document.all)
    return doc.all[elementId].clientHeight;
}

function css_getWidth(doc, elementId) {
  // if NN
  if(document.layers)
    return doc[elementId].clip.width;

  // if IE
  if(document.all)
    return doc.all[elementId].clientWidth;
}

function css_getWindowHeight(frame) {
  // if NN
  if(document.layers)
    return frame.innerHeight;

  // if IE
  if(document.all)
    return frame.document.body.clientHeight;
}

function css_getWindowWidth(frame) {
  // if NN
  if(document.layers)
    return frame.innerWidth;

  // if IE
  if(document.all)
    return frame.document.body.clientWidth;
}

function css_getX(doc, elementId) {
  // if NN
  if(document.layers)
    return doc[elementId].left;

  // if IE
  if(document.all)
    return doc.all[elementId].style.pixelLeft;
}

function css_setX(doc, elementId, x) {
  // if NN
  if(document.layers)
    doc[elementId].left = x;

  // if IE
  if(document.all)
    doc.all[elementId].style.pixelLeft = x;
}

function css_getY(doc, elementId) {
  // if NN
  if(document.layers)
    return doc[elementId].top;

  // if IE
  if(document.all)
    return doc.all[elementId].style.pixelTop;
}

function css_setY(doc, elementId, y) {
  // if NN
  if(document.layers)
    doc[elementId].top = y;

  // if IE
  if(document.all)
    doc.all[elementId].style.pixelTop = y;
}

// description: set the visibility of an element
// param: doc: the document object the element is in
// param: elementId: id of the element
// param: visibility: "visible", "hidden" or "inherit"
function css_setVisibility(doc, elementId, visibility) {
  // if NN
  if(document.layers)
    doc[elementId].visibility = visibility;

  // if IE
  if(document.all)
    doc.all[elementId].style.visibility = visibility;
}

function css_clip(doc, elementId, top, right, bottom, left) {
  // if NN
  if(document.layers) {
    doc[elementId].clip.top = top;
    doc[elementId].clip.right = right;
    doc[elementId].clip.bottom = bottom;
    doc[elementId].clip.left = left;
  }

  // if IE
  if(document.all)
    doc.all[elementId].style.clip = "rect("+top+" "+right+" "+bottom+" "+left+")";
}

// description: reveal an element covered with a mask in many steps
//   both element and mask are DIVs. mask DIV should contain an image mask as
//   its first image
//   both element and mask must include styles "position:absolute;" and
//   "visibility:hidden;". element must also include "left:<something>;"
// param: speed: the speed of revealing. Can be 1 to infinity.
function css_reveal(doc, elementId, maskId, speed) {
  // find the width of the mask
  // cannot refer image by name here because of a NN bug
  var maskWidth = 0;
  // if NN
  if(document.layers)
    maskWidth = doc[maskId].document.images[0].width;
  // if IE
  if(document.all)
    maskWidth = doc.images[0].width;

  // save reveal info
  var job = new Object();
  job["function"] = _css_doReveal;
  job["doc"] = doc;
  job["elementId"] = elementId;
  job["fromX"] = 0;
  job["toX"] = css_getWidth(doc, elementId);
  job["maskId"] = maskId;
  job["maskWidth"] = maskWidth;
  job["currentX"] = 0;
  job["stepX"] = speed;

  top.code.scheduler_addJob("reveal", job);
  top.code.scheduler_doJob();
}

// description: slide an element in many steps
// param: speed: the speed of sliding. Can be 1 to 100 (fastest)
function css_slide(doc, elementId, fromX, toX, fromY, toY, speed) {
  // make slide job
  var job = new Object();
  job["function"] = _css_doSlide;
  job["doc"] = doc;
  job["elementId"] = elementId;
  job["fromX"] = fromX;
  job["toX"] = toX;
  job["fromY"] = fromY;
  job["toY"] = toY;
  job["currentStep"] = 0;
  job["totalSteps"] = 101-speed;

  top.code.scheduler_addJob("slide", job);
  top.code.scheduler_doJob();
}

//
// private functions
//

function _css_doReveal(job) {
  var doc = job["doc"];
  var elementDoc = job["elementDoc"];
  var elementId = job["elementId"];
  var fromX = job["fromX"];
  var toX = job["toX"];
  var maskId = job["maskId"];
  var maskWidth = job["maskWidth"];
  var currentX = job["currentX"];
  var stepX = job["stepX"];

  // find out element width
  var width = currentX;

  // find out mask X
  var maskX = width-maskWidth;

  // clip element
  css_clip(doc, elementId, 0, width, 2000, 0);

  // clip mask in case part of it is outside the element
  if(maskX < 0)
    css_clip(doc, maskId, 0, maskWidth, 2000, -maskX);

  // move mask to the right place
  css_setX(doc, maskId, maskX+css_getPageX(doc, elementId));
  css_setY(doc, maskId, css_getPageY(doc, elementId));

  // show element after it is covered by the mask
  css_setVisibility(doc, maskId, "visible");
  css_setVisibility(doc, elementId, "visible");

  // done when mask scrolls to the end
  if(currentX >= toX) {
    css_setVisibility(doc, maskId, "hidden");
    return true;
  }

  // next step
  job["currentX"]+=stepX;
  if(job["currentX"] > toX)
    job["currentX"] = toX;

  return false;
}

function _css_doSlide(job) {
  var doc = job["doc"];
  var elementId = job["elementId"];
  var fromX = job["fromX"];
  var toX = job["toX"];
  var fromY = job["fromY"];
  var toY = job["toY"];
  var currentStep = job["currentStep"];
  var totalSteps = job["totalSteps"];

  var currentX = fromX+(toX-fromX)*currentStep/totalSteps;
  var currentY = fromY+(toY-fromY)*currentStep/totalSteps;

  css_setX(doc, elementId, Math.round(currentX));
  css_setY(doc, elementId, Math.round(currentY));

  // next step
  job["currentStep"]++;

  // done?
  if(currentStep == totalSteps)
    return true;

  return false; 
} 

function _css_initialize() { 
var _css_eventType =""; 
var _css_eventWindow = null; 
var _css_eventX = -1; 
var _css_eventY = -1; 
}
 

function _css_mouseMove(event) {
  // mouseMove event is fired even the cursor stays at the same place,
  // so we need to monitor if coordinates changed
  if(document.all) {
    event = _css_eventWindow.event;

    if(event != null && (event.clientX != _css_eventX || event.clientY != _css_eventY)) {
      _css_eventType = "mousemove";
      _css_eventX = event.clientX;
      _css_eventY = event.clientY;
    }
  } else {
    if(event.pageX != _css_eventX || event.pageY != _css_eventY) {
      _css_eventType = "mousemove";
      _css_eventX = event.pageX;
      _css_eventY = event.pageY;
    }
  }
}

function _css_mouseUp() {
  _css_eventType = "mouseup";
}
