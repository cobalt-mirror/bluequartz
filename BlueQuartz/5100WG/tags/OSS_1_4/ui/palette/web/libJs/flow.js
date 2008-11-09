// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: flow.js 3 2003-07-17 15:19:15Z will $

//
// private variables
//

var _flow_buttons = new Array();
var _flow_isLocked = false;
var _flow_mainFrame = null;
var _flow_navFrame = null;
var _flow_nextItemId = null;
var _flow_path = new Array();
var _flow_root = null;
var _flow_root = null;
var _flow_showNavigation = true;
var _flow_showForwardNavigation = true;
var _flow_style = new Object();

//
// public functions
//

function flow_setFrame(mainFrame, navFrame) {
  _flow_mainFrame = mainFrame;
  _flow_navFrame = navFrame;
}

function flow_setStyle(name, value) {
  _flow_style[name] = value;
}

function flow_getRoot() {
  return _flow_root;
}

function flow_setRoot(root) {
  if(root == null)
    return;

  _flow_root = root;

  _flow_path = new Array();
  _flow_path[0] = root;
}

function flow_goBack() {
  _flow_path.length--;

  flow_repaint();
}

function flow_goNext() {
  if(_flow_isLocked)
    return;
  _flow_isLocked = true;
  // use a timed lock because there is no easy way to tell if a page is done loaded
  // or not
  setTimeout("_flow_isLocked = false;", 2000);

  if(!_flow_areFormsValid())
    return;

  _flow_nextItemId = _flow_getNextItemId();

  // if form is not empty
  var forms = _flow_mainFrame.document.forms;
  if(forms.length > 0 && forms[0].isActionAvailable) {
    forms[0].submit();
    _flow_submitedHandler = _flow_goNextOk;
    _flow_waitForSubmit();
  }
  else
    _flow_goNextOk();
}

// this method _overrides_ the timed _lock_ used to prevent
// users from clicking too fast before a page is loaded fully and
// advances to the next item in the flow. use with care.
// it is useful when you need to automatically advance the flow without
// waiting for the lock to expire.
function flow_forceGoNext() {
  _flow_isLocked = false;
  flow_goNext();
}

function flow_goFinish() {
  if(!_flow_areFormsValid())
    return;

  _flow_nextItemId = _flow_getNextItemId();

  // if form is not empty
  var forms = _flow_mainFrame.document.forms;
  if(forms.length > 0 && forms[0].isActionAvailable) {
    forms[0].submit();
    _flow_submitedHandler = _flow_goFinishOk;
    _flow_waitForSubmit();
  }
  else
    _flow_goFinishOk();
}

function flow_repaint() {
  var item = _flow_path[_flow_path.length-1];

  // show the right content on main frame
  _flow_mainFrame.location = item.getUrl();

  // repaint title
  top.code.title_setRoot(item);
  top.code.title_repaint();

  // repaint navigation
  _flow_repaintNavigation();
}

function flow_showNavigation(isShown) {
  _flow_showNavigation = isShown;

  // repaint navigation
  _flow_repaintNavigation();
}

//
// private functions
//

// description: get the ID of the next item to go to from the main frame
// returns: the ID in string or null if
function _flow_getNextItemId() {
  if(_flow_mainFrame.flow_getNextItemId != null)
    return _flow_mainFrame.flow_getNextItemId();
  else
    return null;
}

function _flow_goNextOk() {
  var item = _flow_path[_flow_path.length-1];
  var items = item.getItems();

  // find next item
  var nextItem = items[0];
  for(var i = 0; i < items.length; i++)
    if(items[i].getId() == _flow_nextItemId) {
      nextItem = items[i];
      break;
    }

  // if nextitem has no children, we should be finishing...
  if ( nextItem.getItems().length == 0 ) {
    top.location = nextItem.getUrl();
    return;
  }

  _flow_path[_flow_path.length] = nextItem;

  flow_repaint();
}

function _flow_goFinishOk() {
  var item = _flow_path[_flow_path.length-1];
  var items = item.getItems();

  // find terminal item
  var nextItem = null;
  if(_flow_nextItemId == null) {
    // terminal item is the first child without children
    for(var i = 0; i < items.length; i++)
      if(items[i].getItems().length == 0) {
	nextItem = items[i];
	break;
      }
  }
  else {
    // terminal item is the first child with the right ID
    // if no children has the right ID, the first children is the terminal
    nextItem = items[0];
    for(var i = 0; i < items.length; i++)
      if(items[i].getId() == _flow_nextItemId) {
	nextItem = items[i];
	break;
      }
  }

  top.location = nextItem.getUrl();
}

function _flow_repaintNavigation() {
  // no need to repaint?
  if(_flow_navFrame == null)
    return;

  // find out what to show
  var back;
  var finish;
  var next;

  // set constants
  var CLICKABLE = 0;
  var GRAYOUT = 1;
  var INVISIBLE = 2;

  if(!_flow_showNavigation) {
    back = GRAYOUT;
    finish = INVISIBLE;
    next = GRAYOUT;
  }
  else {
    // show back?
    var lastItem = _flow_path[_flow_path.length-2];
    if(lastItem == null)
      back = GRAYOUT;
    else
      back = CLICKABLE;

    var item = _flow_path[_flow_path.length-1];

    // can go next if there are grandchildren
    // can finish if there is a child with no children
    next = INVISIBLE;
    finish = INVISIBLE;
    var nextItems = item.getItems();
    for(var i = 0; i < nextItems.length; i++) {
      var grandChildCount = nextItems[i].getItems().length;
      if(grandChildCount == 0)
	finish = CLICKABLE;

      if(grandChildCount > 0)
	next = CLICKABLE;
    }
    if( (next==CLICKABLE) && (finish==CLICKABLE) ) {
      finish = INVISIBLE;
    }

    if((next==INVISIBLE) && (finish==INVISIBLE)){
      next=CLICKABLE;
    }
	

    // show forward?
    if(!_flow_showForwardNavigation) {
      next = GRAYOUT;
    }
  }

  doc = _flow_navFrame.document;

  doc.open();
  doc.write("<HTML><HEAD><STYLE type=\"text/css\">BODY {"+_flow_style["controlBackgroundStyle"]+"}</STYLE></HEAD><BODY><CENTER>");

  // add spacer
  doc.write("<IMG SRC=\"/libImage/spaceHolder.gif\" HEIGHT=\"4\"><BR>");

  if(back == CLICKABLE)
    doc.write("<A HREF=\"javascript: top.code.flow_goBack()\"><IMG BORDER=\"0\" ALT=\"&lt;--\" SRC=\""+_flow_style["backImage"]+"\"></A>");
  else if(back == GRAYOUT)
    doc.write("<IMG SRC=\""+_flow_style["backImageDisabled"]+"\">");

  if(next == CLICKABLE) 
    doc.write("<A HREF=\"javascript: top.code.flow_goNext()\"><IMG BORDER=\"0\" ALT=\"--&gt;\" SRC=\""+_flow_style["nextImage"]+"\"></A>");
  else if(next == GRAYOUT)
    doc.write("<IMG SRC=\""+_flow_style["nextImageDisabled"]+"\">");

  if(finish == CLICKABLE)
    doc.write("<A HREF=\"javascript: top.code.flow_goFinish()\"><IMG BORDER=\"0\" ALT=\"--&gt;\" SRC=\""+_flow_style["finishImage"]+"\"></A>");
  else if(finish == GRAYOUT)
    doc.write("<IMG SRC=\""+_flow_style["finishImageDisabled"]+"\">");

  doc.write("</CENTER></BODY></HTML>");
  doc.close();
}

function _flow_waitForSubmit() {
  if(_flow_mainFrame.flow_success == null)
    setTimeout("_flow_waitForSubmit()", 50);
  else if(_flow_mainFrame.flow_success)
    _flow_submitedHandler();
  else
    // submit failed, so go back to the same page
    _flow_mainFrame.location = _flow_path[_flow_path.length-1].getUrl();
}

// description: verify all the forms
// returns: true if all forms valid, false otherwise
function _flow_areFormsValid() {
  var forms = _flow_mainFrame.document.forms;
  for(var i=0; i < forms.length; i++)
    if(forms[i].onsubmit != null && !forms[i].onsubmit())
      return false;

  return true;
}

// description: jumps to a position in a page flow
//              used for accessing setup wizard step 2 directly
function flow_select(itemId) {
  // do a depth 1st search
  var items = new Array();
  items[0] = _flow_root;
  var parentOf = new Array();
  var foundItem = null;
  while(items.length > 0) {
    // pop item from the end
    var currentItem = items[items.length-1];
    items.length--;

    // found?
    if(currentItem.getId() == itemId) {
      foundItem = currentItem;
      break;
    }

    var children = currentItem.getItems();

    // no children to inspect?
    if(children == null)
      next;

    // inpect all children
    for(var i = 0; i < children.length; i++) {
      parentOf[children[i].getId()] = currentItem;
      items[items.length] = children[i];
    }
  }

  // nothing is selected if not found
  if(foundItem == null)
    return;

  // save the path
  _flow_path = new Array();
  var currentItem = foundItem;
  do {
    _flow_path[_flow_path.length] = currentItem;
    currentItem = parentOf[currentItem.getId()];
  }
  while(currentItem != null);
  _flow_path = _flow_path.reverse();
}


