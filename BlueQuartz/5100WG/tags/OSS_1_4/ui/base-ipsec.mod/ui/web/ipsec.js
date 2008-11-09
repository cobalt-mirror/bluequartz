function Ipsec_LaunchKeyWindow( ref) {
        top.AddressBookWindow = window.open("/nav/single.php?root=base_IpsecKey", "KeyWindow", "width=650,height=200,resizable");
        //if (!top.AddressBookWindow.opener)
                top.KeyWindow.opener = top;
        top.Refer = ref;
        top.KeyWindow.Refer = ref;
} 
