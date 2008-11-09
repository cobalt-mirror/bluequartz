function EmailAddressList_LaunchAddressBook( ref) {
	top.AddressBookWindow = window.open("/nav/single.php?root=base_addressbookimport", "AddressBookWindow", "width=650,height=400,resizable");
	//if (!top.AddressBookWindow.opener)
		top.AddressBookWindow.opener = top;
	top.Refer = ref;
	top.AddressBookWindow.Refer = ref;
}

function EmailAddressList_AddAddress(ref, address) {
   field = top.opener.mainFrame.document.form.elements[ref];
   if (field.value != "") {
     seperator = ", ";
   } else {
     seperator = "";
   }
   field.value = field.value + seperator + address;
   //top.opener.mainFrame.document.form[ref].textArea =& top[document.form.elements[i].name];
}

