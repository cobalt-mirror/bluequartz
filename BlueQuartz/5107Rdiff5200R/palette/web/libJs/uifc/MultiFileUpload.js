function MultiFileUpload_QueryAttachment(element, maxFileSize) {
	if (top.attachWindow != null && top.attachWindow.close != null)
		top.attachWindow.close();
	attachWindow = window.open("/nav/single.php?root=palette_MultiFileUpload", "", "width=575,height=250,resizable=yes");
	top.attachWindow = attachWindow;
	if (attachWindow.opener == null) 
		attachWindow.opener = self;
	attachWindow.fileList = element;
	self.fileList = element;
	attachWindow.maxFileSize = maxFileSize;
}
function MultiFileUpload_RemoveAttachment(element) {
	var options = element.options;
	var count = top.code.select_getLength(element, element.emptyLabel);
	for (var i = 0; i < count; i++) 
		if (options[i].selected)
			select_removeOption(element, element.emptyLabel, element.parentDocument, i);
	//history.go(0);
}
function MultiFileUpload_SubmitHandler(element) {
	var baseFieldName = element._fieldName;
	var form = element.form;
	var options = element.options;
	var base = new Array();
	var base_size = new Array();
	var base_name = new Array();
	var base_type = new Array();
	var tmpArray;
	var count = top.code.select_getLength(element, element.emptyLabel);
	for (var i = 0; i<count; i++) {
		base[base.length] = options[i].text;
		tmpArray = top.code.arrayPacker_stringToArray(options[i].value);	
		base_size[base_size.length] = tmpArray[0];
		base_name[base_name.length] = tmpArray[1];
		base_type[base_type.length] = tmpArray[2];
	}
	
	form[baseFieldName].value = top.code.arrayPacker_arrayToString(base);
	form[baseFieldName + "_size"].value = top.code.arrayPacker_arrayToString(base_size);
	form[baseFieldName + "_name"].value = top.code.arrayPacker_arrayToString(base_name);
	form[baseFieldName + "_type"].value = top.code.arrayPacker_arrayToString(base_type);
	return true;
}

function MultiFileUpload_addToList(fileList, upload_name, upload, upload_size, upload_type) {
	
	select_addOption(fileList, fileList.emptyLabel, fileList.parentDocument, upload_name, top.code.arrayPacker_arrayToString( new Array(upload_size, upload, upload_type)), false, false);
	return true;
}
