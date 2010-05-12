function ScrollList_selectAllSwitch(element) {
  var form = element.form;
  var entryIdsString = form.elements._entryIds.value;
  var entryIds = entryIdsString.split(',');
  for (var i = 0; i<entryIds.length; i++) {
    if (form[entryIds[i]] != null) 
      form[entryIds[i]].checked = element.checked;
  }
}
