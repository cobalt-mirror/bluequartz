// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: collapsibleList.js 201 2003-07-18 19:11:07Z will $

//
// private variables
//

var _cList_elementId = "listDiv";
var _cList_frame = new Object();
var _cList_indentWidth = 12;
var _cList_items = new Array();
var _cList_root = null;
var _cList_selected = null;
var _cList_style = new Object();
var _cList_x = 0;
var _cList_y = 5;

//
// public functions
//

function cList_setFrame(name, frame) {
  _cList_frame[name] = frame;
}

function cList_setStyle(name, value) {
  _cList_style[name] = value;
}

function cList_getRoot() {
  return _cList_root;
}

function cList_setRoot(root) {
  if(root == null)
    return;

  _cList_root = root;

  _cList_selected = null;

  var items = new Array();

  items[0] = root;

  // initialize _cList_items hash
  while(items.length > 0) {
    // pop an item
    var item = items[items.length-1];
    items.length--;

    _cList_items[item.id] = item;

    // search all the children
    for(var i = 0; i < item.subItems.length; i++)
      items[items.length] = item.subItems[i];
  }
}

// description: select an item and expand parents along the path
//     If there are multiple parents found, only the first one is expanded
// param: itemId: the ID of the item
function cList_selectPath(itemId) {
  var item = _cList_items[itemId];

  // no such item?
  if(item == null)
    return;

  // select
  _cList_selected = item;
  cList_showSelected();

  // expand all parents
  var node = item;
  do {
    parents = node.getParents();

    if(parents != null && parents.length > 0) {
      node = parents[0];
      node.setExpanded(true);
    }
  }
  while(parents != null && parents.length > 0);

  cList_repaint(true);
}

// description: repaint the list
// param: isNoFx: true if no special effect, false otherwise
function cList_repaint(isNoFx) {
  _cList_repaint();

  // get the document
  var doc = _cList_frame["list"].document;

  if(isNoFx) {
    // make it visible
    top.code.css_setVisibility(doc, _cList_elementId, "visible");

    // skip special effects
    return;
  }

  var startX = -120;
  var startY = top.code.css_getY(doc, _cList_elementId);

  // move to start location
  top.code.css_setX(doc, _cList_elementId, startX);
  top.code.css_setY(doc, _cList_elementId, startY);

  // make it visible
  top.code.css_setVisibility(doc, _cList_elementId, "visible");

  top.code.css_slide(doc, _cList_elementId, startX, _cList_x, startY, startY, 92);
}

// description: show the URL of the selected item in the content frame
function cList_showSelected() {
  if(_cList_selected != null && _cList_selected.isVisible()) {
    var itemUrl = _cList_selected.getUrl();
    var itemWindow = _cList_selected.getWindow();
    
    if(itemUrl != null && itemUrl != "") {
       if(itemWindow != null && itemWindow != "") {  // if window specified, open new or re-use window
          ref = window.open (itemUrl, itemWindow, "menubar=yes,scrollbars=yes,status=yes,hotkeys=yes,toolbar=yes,location=yes,resizable=yes,personalbar=yes");
          ref.focus(); 
       } else {
          _cList_frame["content"].location = itemUrl;
       }
    } else {
      _cList_frame["content"].location = "blank.php";
    }
  }
}

//
// private functions
//

function _cList_display(item, depth) {
  if(_cList_frame["list"] == null || _cList_frame["list"].document == null)
    return;

  if(!item.isVisible())
    return;

  var doc = _cList_frame["list"].document;

  // do not show root
  if(item != _cList_root) {
    // get the right style
    var backgroundStyle = "";
    var textStyle = "";
    if(_cList_selected == item) {
      backgroundStyle = _cList_style["backgroundStyleListSelected"];
      textStyle = _cList_style["textStyleSelected"];
    }
    else if(_cList_selected == null) {
      backgroundStyle = _cList_style["backgroundStyleListNormal"];
      textStyle = _cList_style["textStyleNormal"];
    }
    // same group with the selected item
    else {
      var parents = item.getParents();
      for(var i = 0; i < parents.length; i++)
	// if not top level item and sibling is selected
	if(parents[i] != _cList_root && parents[i].getItem(_cList_selected.getId(), false) != null) {
	  // see if any sibling has children
	  var isSiblingHasChild = false;
	  var siblings = parents[i].getItems();
	  for(var j = 0; j < siblings.length; j++)
	    if(siblings[j].getItems().length > 0) {
	      isSiblingHasChild = true;
	      break;
	    }

	  if(!isSiblingHasChild) {
	    backgroundStyle = _cList_style["backgroundStyleListNear"];
	    textStyle = _cList_style["textStyleNear"];
	    break;
	  }
	}

      if(backgroundStyle == "") {
	backgroundStyle = _cList_style["backgroundStyleListNormal"];
	textStyle = _cList_style["textStyleNormal"];
      }
    }

    // log activity if necessary
    var logClick = "";
    var logMouseOver = "";
    var logMouseOut = "";
    if(top.isLogUi) {
      var name = top.code.string_backslashEscape(item.getName());
      logClick = "top.code.uiLog_log('click', 'cList', '"+name+"');";
      logMouseOver = "top.code.uiLog_log('mouseOver', 'cList', '"+name+"');";
      logMouseOut = "top.code.uiLog_log('mouseOut', 'cList', '"+name+"');";
    }

    // build the A tag
    var description = top.code.string_backslashEscape(item.getDescription());
    var onClick = "javascript: "+logClick+"top.code._cList_toggle('"+item.getId()+"', event);"; 
    var onMouseOver = logMouseOver+"return top.code.info_mouseOver('"+description+"');";
    var onMouseOut = logMouseOut+"return top.code.info_mouseOut();";

    // A tags around table rows activates the whole row including space around text
    // However, this only works on IE, not NN. Not on Mac as well
    var isRowActivated = document.all && navigator.appVersion.indexOf("Mac") == -1;

    var clickStuff = "onClick=\""+onClick+"\" onMouseOver=\""+onMouseOver+"\" onMouseOut=\""+onMouseOut+"\"";
    var aTagStart = "<A HREF=\"javascript: void 0;\" " + (isRowActivated ? "" : clickStuff ) + "STYLE=\""+textStyle+"\">";



    doc.write("<TR><TD STYLE=\""+backgroundStyle+"\" NOWRAP " + (!isRowActivated ? "" : clickStuff ) + ">");

    doc.write("<table border=\"0\" cellspacing=\"0\" cellpadding=\"0\"><tr><td nowrap>");


    // indent
    var space = depth*_cList_indentWidth;
    if(space > 0)
      doc.write("<IMG BORDER=\"0\" SRC=\"/libImage/spaceHolder.gif\" WIDTH=\""+space+"\" HEIGHT=\"5\">");
    doc.write("<IMG BORDER=\"0\" SRC=\"/libImage/spaceHolder.gif\" WIDTH=\"5\" HEIGHT=\"5\">");

    doc.write(aTagStart);

    // get the right icon
    var icon = "";
    var childNum = item.getItems().length;
    if(childNum > 0 && item.isExpanded())
      icon = _cList_style["expandedIcon"];
    else if(childNum > 0)
      icon = _cList_style["collapsedIcon"];
    else if(_cList_selected == item)
      icon = _cList_style["selectedIcon"];
    else
      icon = _cList_style["unselectedIcon"];

    // draw the icon
    doc.write("<IMG BORDER=\"0\" SRC=\""+icon+"\"></a></td>");

    // add spacing
    doc.write("<td nowrap><IMG BORDER=\"0\" SRC=\"/libImage/spaceHolder.gif\" WIDTH=\"5\" HEIGHT=\"5\">");

    doc.write(aTagStart);

    // draw item name
    doc.write("<FONT STYLE=\""+textStyle+"\">"+top.code.string_htmlEscape(item.getName())+"</FONT>");

    doc.write("</A>");

    doc.write("</td></tr></table>");

    doc.write("</TD></TR>");

    doc.write("\n");

    var divider = _cList_style["dividerImage"];
    if(divider != "")
      doc.write("<TR><TD><IMG SRC=\""+divider+"\"></TD></TR>\n");
  }

  var subItems = item.getItems();
  if(item == _cList_root || (item.isExpanded() && subItems.length > 0))
    for(var i = 0; i < subItems.length; i++)
      _cList_display(subItems[i], depth+1);
}

function _cList_repaint() {
  if(_cList_frame["list"] == null || _cList_frame["list"].document == null)
    return;

  var doc = _cList_frame["list"].document;

  doc.open();
  doc.write("<HTML><BODY ALINK=\""+_cList_style["aLinkColor"]+"\" STYLE=\""+_cList_style["backgroundStylePage"]+"\">");
  doc.write("<DIV ID=\""+_cList_elementId+"\" STYLE=\"position:absolute; left:"+_cList_x+"; top:"+_cList_y+";\">");
  doc.write("<TABLE BORDER=\"0\" CELLPADDING=\"0\" CELLSPACING=\"0\" BGCOLOR=\""+_cList_style["borderColor"]+"\" WIDTH=\""+_cList_style["width"]+"\"><TR><TD>");
  doc.write("<TABLE BORDER=\"0\" CELLPADDING=\"0\" CELLSPACING=\""+_cList_style["borderThickness"]+"\" WIDTH=\"100%\">");
  _cList_display(_cList_root, -1);
  doc.write("</TABLE>");
  doc.write("</TD></TR></TABLE>");
  doc.write("</DIV>");
  doc.write("</BODY></HTML>");
  doc.close();
}

function _cList_toggle(id, event) {
  // save scrolling position
  var scrollY = 0;
  // if IE
  if(document.all)
    scrollY = event.y-event.clientY;
  // if NN
  if(document.layers)
    scrollY = _cList_frame["list"].pageYOffset;

  var item = _cList_items[id];

  // collapse/expand
  item.setExpanded(!item.isExpanded());

  var children = item.getItems();
  var url = item.getUrl();
  // end node or node with URL?
  if(children.length == 0 || (url != null && url != "")) {
    _cList_selected = item;
    cList_showSelected();
  }
  // no need to show page if it is a collapse action
  else if(item.isExpanded()) {
    _cList_selected = children[0];
    cList_showSelected();
  }

/*
  // unselect item if it is within a collapsed tree
  var stack = new Array();
  var found = false;
  stack[0] = item;
  while(stack.length > 0 && !found) {
    var currentItem = stack[stack.length-1];
    stack.length--;

    var children = currentItem.getItems();
    for(var i = 0; i < children.length; i++) {
      if(children[i] == _cList_selected) {
	_cList_selected = null;
	cList_showSelected();
	found = true;
	break;
      }

      stack[stack.length] = children[i];
    }
  }
*/

  _cList_repaint();

  // restore scrolling position
  _cList_frame["list"].scroll(0, scrollY);

  var doc = _cList_frame["list"].document;
  top.code.css_setVisibility(doc, _cList_elementId, "visible");
}

function cList_check_username(serverLoginName) {
	var allcookies = document.cookie;
	var pos = allcookies.indexOf("loginName=");
	if (pos != -1) {
		var start = pos + 10;
		var end = allcookies.indexOf(";",start);
		if (end == -1) end = allcookies.length;
		var value = allcookies.substring(start, end);
		if (value != serverLoginName){
			top.location = "/login.php?expired=true";
		}
	}
}

