// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: title.js 3 2003-07-17 15:19:15Z will $

//
// private variables
//

var _title_root = null;
var _title_frame = null;
var _title_style = new Object();

//
// public functions
//

function title_setFrame(frame) {
  _title_frame = frame;
}

function title_setStyle(name, value) {
  _title_style[name] = value;
}

function title_getRoot() {
  return _title_root;
}

function title_setRoot(root) {
  if(root == null)
    return;

  _title_root = root;
}

function title_repaint() {
  // no need to repaint?
  if(_title_frame == null)
    return;

  var name = top.code.string_htmlEscape(_title_root.getName());
  var description = _title_root.getDescription();

  doc = _title_frame.document;

  doc.open();
  doc.write("<HTML><BODY STYLE=\""+_title_style["backgroundStyle"]+"\">\n");
  doc.write("<TABLE BORDER=\"0\" CELLPADDING=\"0\" CELLSPACING=\"0\"><TR>\n");
  doc.write("<TD><IMG BORDER=\"0\" SRC=\""+_title_style["logo"]+"\"></TD>\n");
  doc.write("<TD><FONT STYLE=\""+_title_style["titleStyle"]+"\">"+name+"</FONT><IMG SRC=\"/libImage/spaceHolder.gif\" WIDTH=\"10\"></TD>\n");
  doc.write("<TD><FONT STYLE=\""+_title_style["descriptionStyle"]+"\">"+description+"</FONT></TD>\n");
  doc.write("</TR></TABLE>\n");
  doc.write("</BODY></HTML>");
}
