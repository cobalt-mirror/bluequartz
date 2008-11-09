// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: tab.js 706 2006-03-09 08:31:25Z shibuya $

//
// private variables
//

var _tab_frame = new Object();
var _tab_items = new Object();
var _tab_root = null;
var _tab_selected = null;
var _tab_style = new Object();
var sslEnabled = null;

//
// public functions
//

function tab_setFrame(name, frame) {
  _tab_frame[name] = frame;
}

function tab_setStyle(name, value) {
  _tab_style[name] = value;
}

function tab_getRoot() {
  return _tab_root;
}

function tab_setRoot(root) {
  if(root == null)
    return;

  _tab_root = root;

  _tab_selected = null;

  var items = new Array();

  items[0] = root;

  // initialize _tab_items hash
  while(items.length > 0) {
    // pop an item
    var item = items[items.length-1];
    items.length--;

    _tab_items[item.id] = item;

    // search all the children
    for(var i = 0; i < item.subItems.length; i++)
      items[items.length] = item.subItems[i];
  }
}

function tab_repaint() {
  if(_tab_frame["tab"] == null || _tab_frame["tab"].document == null)
    return;

  var doc = _tab_frame["tab"].document;

  var logo = "";
  if( _tab_style["logo"] != "")
    logo = "<IMG BORDER=\"0\" SRC=\""+_tab_style["logo"]+"\">";

  doc.open();
  doc.write("<HTML><BODY ALINK=\""+_tab_style["aLinkColor"]+"\" STYLE=\""+_tab_style["backgroundStyle"]+"\">\n");
  doc.write("<TABLE BORDER=\"0\" CELLPADDING=\"0\" CELLSPACING=\"0\" WIDTH=\"100%\"><TR>\n");
  doc.write("<TD VALIGN=\"TOP\">"+logo+"</TD>\n");

  var typedItems = new Array();

  var items = new Array();
  if(_tab_root != null)
    items = _tab_root.getItems();
  for(var i = 0; i< items.length; i++) {
    var item = items[i];
    var type = item.getType();

    if(type != null && type != "") {
      typedItems[typedItems.length] = item;
      continue;
    }

    // find out the right display attributes
    var href;
    var imageLeft;
    var imageRight;
    var imageFill;
    var style;
    if(item == _tab_selected) {
      href = "javascript: void 0;";
      imageLeft = _tab_style["selectedImageLeft"];
      imageRight = _tab_style["selectedImageRight"];
      imageFill = _tab_style["selectedImageFill"];
      style = _tab_style["textStyleSelected"];
    }
    else {
      href = "javascript: top.code.tab_select('"+item.getId()+"');";
      imageLeft = _tab_style["unselectedImageLeft"];
      imageRight = _tab_style["unselectedImageRight"];
      imageFill = _tab_style["unselectedImageFill"];
      style = _tab_style["textStyleUnselected"];
    }

    // get description
    var description = top.code.string_backslashEscape(item.getDescription());

    // the A tag
    var aTagStart = "<A HREF=\""+href+"\" onMouseOver=\"return top.code.info_mouseOver('"+description+"')\" onMouseOut=\"return top.code.info_mouseOut();\" STYLE=\""+style+"\">";

    // find out number of rows
    var rowNum = 1;

    var leftCell = (imageLeft != "") ? "<TD ROWSPAN=\""+rowNum+"\" VALIGN=\"MIDDLE\">"+aTagStart+"<IMG BORDER=\"0\" SRC=\""+imageLeft+"\"></A></TD>\n" : "";
    var rightCell = (imageRight != "") ? "<TD ROWSPAN=\""+rowNum+"\" VALIGN=\"MIDDLE\">"+aTagStart+"<IMG BORDER=\"0\" SRC=\""+imageRight+"\"></A></TD>\n" : "";
    var centerCell = (imageFill != "") ? "<TD NOWRAP STYLE=\""+style+"\" BACKGROUND=\""+imageFill+"\" VALIGN=\"MIDDLE\">"+aTagStart+"<IMG BORDER=\"0\" SRC=\"/libImage/spaceHolder.gif\" WIDTH=\"5\" HEIGHT=\"5\">"+top.code.string_trim(top.code.string_htmlEscape(item.getName()))+"<IMG BORDER=\"0\" SRC=\"/libImage/spaceHolder.gif\" WIDTH=\"5\" HEIGHT=\"5\"></A></TD>\n" : "<TD NOWRAP STYLE=\""+style+"\" VALIGN=\"MIDDLE\">"+aTagStart+"<IMG BORDER=\"0\" SRC=\"/libImage/spaceHolder.gif\" WIDTH=\"5\" HEIGHT=\"5\">"+top.code.string_trim(top.code.string_htmlEscape(item.getName()))+"<IMG BORDER=\"0\" SRC=\"/libImage/spaceHolder.gif\" WIDTH=\"5\" HEIGHT=\"5\"></A></TD>\n";

    // draw the tab
    doc.write("<TD VALIGN=\"TOP\">\n");

    doc.write("<TABLE BORDER=\"0\" CELLPADDING=\"0\" CELLSPACING=\"0\">\n");

    // spacer
    doc.write("<TR><TD COLSPAN=\"3\"><IMG SRC=\"/libImage/spaceHolder.gif\" HEIGHT=\""+_tab_style["top"]+"\"></TD></TR>\n");

    // if center only
    if(rowNum == 1)
      doc.write("<TR>"+leftCell+centerCell+rightCell+"</TR>");

    doc.write("</TABLE></TD>");
  }

  // padding
  doc.write("<TD WIDTH=\"99%\"><IMG BORDER=\"0\" SRC=\"/libImage/spaceHolder.gif\" WIDTH=\"5\"></TD>");

  for(var i = 0; i< typedItems.length; i++) {
    var item = typedItems[i];
    var type = item.getType();
    var imageOff = item.getImageOff();
 	var imageOn = item.getImageOn();   
    var description = top.code.string_backslashEscape(item.getDescription());

    // find icon
    var icon = "";

	// image specified in menu.xml file
	if(imageOff != null) { 
		icon = imageOff; 
	} else if(imageOn != null) { 
		icon = imageOn
	} 

	    if(type == "logout")
	      icon = _tab_style["logoutImage"];
	    else if(type == "monitorOff")
	      icon = _tab_style["monitorOffImage"];
	    else if(type == "monitorOn")
	      icon = _tab_style["monitorOnImage"];
	    else if(type == "updateOff")
	      icon = _tab_style["updateOffImage"];
	    else if(type == "manualOff")
	      icon = _tab_style["manualOffImage"];
	    else if(type == "updateOn")
	      icon = _tab_style["updateOnImage"];
    

    doc.write("<TD NOWRAP VALIGN=\"TOP\"><A HREF=\""+item.getUrl()+"\" onMouseOver=\"return top.code.info_mouseOver('"+description+"')\" onMouseOut=\"return top.code.info_mouseOut();\"><IMG NAME=\""+type+"\" ALT=\""+top.code.string_trim(top.code.string_htmlEscape(item.getName()))+"\" BORDER=\"0\" SRC=\""+icon+"\"></A></TD>\n");
  }

  doc.write("</TR></TABLE>");
  doc.write("</BODY></HTML>");
  doc.close();
}

function tab_select(id, isNoFx) {
  // disable Fx
  isNoFx = true;

  var item = _tab_items[id];

  // update visibility
  if(_tab_selected != null)
    _tab_selected.setVisible(false, true);
  item.setVisible(true, true);

  _tab_selected = item;

  if(item.getUrl() != "")
    // perform action
    _tab_frame["content"].location = item.getUrl();

  // point collapsible list to the right place
  top.code.cList_setRoot(item);
  // select the first item on the list if item does not have URL
  var children = item.getItems();
  if(item.getUrl() == "" && children.length > 0)
    top.code.cList_selectPath(children[0].getId());

  // paint everything
  if (top.commFrame.location!="blank.html") {
    top.code.cList_repaint(isNoFx);
  }
  top.code.cList_showSelected();

  tab_repaint();
}

// description: select an item and expand parents along the path
//     If there are multiple parents found, only the first one is expanded
// param: itemId: the ID of the item
function tab_selectPath(itemId) {
  var item = _tab_items[itemId];

  // no such item?
  if(item == null)
    return;

  // nothing on tab?
  if(_tab_root == null)
    return;

  // select tab if item is under it
  var tabItems = _tab_root.getItems();
  for(var i = 0; i < tabItems.length; i++) {
    var tabItem = tabItems[i];

    // if item is on tab
    if(tabItem == item || tabItem.getItem(itemId, true) != null) {
      if(_tab_selected != tabItem) {
	_tab_selected = tabItem;
	tab_repaint();

	// update visibility
	tabItem.setVisible(true, true);

	// point collapsible list to the right place
	top.code.cList_setRoot(tabItem);
      }
      return;
    }
  }
}
