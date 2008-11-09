package LCD;

use vars qw(@ISA @EXPORT @EXPORT_OK);

require Exporter;
@ISA = qw(Exporter);
@EXPORT = qw(
	     menu_lcd
	     menu_console

	     put_console
	     put_lcd

	     get_ipaddr
	     get_ipaddr_lcd
	     get_ipaddr_console

	     get_netmask
	     get_netmask_lcd
	     get_netmask_console

	     get_gateway
	     get_gateway_lcd
	     get_gateway_console

	     save_lcd
	     save_console
	     );

use POSIX;
use Locale::gettext;
use Term::ReadLine;
use I18n;

use vars qw($LCD_GETIP $LCD_WRITE $LCD_FLASH $LCD_READ);
$LCD_GETIP = "/sbin/lcd-getip";
$LCD_WRITE = "/sbin/lcd-write";
$LCD_FLASH = "/sbin/lcd-flash";
$LCD_READ  = "/sbin/readbutton";


######################################################################
##
## process menu selections via the console
##
## takes a menu hash/hash as an argument
##
##       keys (%menu) = directory names
##       menu{}{index} = prefix number (for sorting info)
##       menu{}{name}  = menu item name
##       menu{}{type}  = menu item type: s = script, m = menu
##
sub menu_console
{
    my($menuhash)=(@_);
    my %menu = %$menuhash;
    my($term,$choice);
    my(@items,$item);
    my($index,$menu_item_name);
    my $i18n = new I18n;
    my $selstr = $i18n->get("[[base-lcd.SELECT:         ]]");
    $selstr =~ s/:?[ ]*$//;
    
    format MENU_OUT =
@>>>>> - @<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
$index,  $menu_item_name
.
    
    @items = sort keys %menu;
    for (;;)
    {
	print "\n";
	$~ = 'MENU_OUT';

	foreach $item (@items)
	{
	    $index = $menu{$item}{index};
	 
  	    if (defined($menu{$item}{string})) {
	        $menu_item_name = $menu{$item}{string};
	    }
	    else {
	        my $len = length($menu{$item}{name});
	        my $fill = int((16 - $len) / 2) + $len;
	        my $rest = 16 - $fill;
	        $menu_item_name = sprintf("%${fill}s%${rest}s",$menu{$item}{name},"");
	        $menu_item_name =~ s/_/ /;
	        $menu_item_name = gettext(substr($menu_item_name,0,16));
		$menu_item_name = $i18n->get("[[base-lcd." . substr($menu_item_name,0,16) . "]]");
            }
	    $menu_item_name =~ s/^[ ]*//;
	    $menu_item_name =~ s/:?[ ]+$//;
	    write();
	}

	$~ = 'STDOUT';
	print "\n";
	
	$term = new Term::ReadLine 'select';
	$choice = $term->readline("[$selstr]> ");

	foreach $item (@items)
	{
	    if ($menu{$item}{index} == $choice) {
		my $menu_item_name = $menu{$item}{name};
		$menu_item_name =~ s/_/ /g;
		print "$menu_item_name\n";
		return $item;
	    }
	}

	print "\n*** Invalid selection! ***\n";
    }
}

######################################################################
##
## process menu selections via the LCD panel
##
## takes a menu hash/hash as an argument
##
##       keys (%menu) = directory names
##       menu{}{index} = prefix number (for sorting info)
##       menu{}{name}  = menu item name
##       menu{}{type}  = menu item type: s = script, m = menu
##
sub menu_lcd
{
    my($menuhash,$buttonhash)=(@_);

    my %menu = %$menuhash;
    my %buttons = %$buttonhash;

    my $i18n = new I18n;
    my $selstr = $i18n->get("[[base-lcd.SELECT:         ]]");

    # outer loop
    for (;;)
    {
      # inner menu item select loop
      ITEM: foreach my $title (sort keys %menu)
      {
	  my $menustr = "";
	  
	  if (defined($menu{$title}{string})) {
	      $menustr = $menu{$title}{string};
	  }
	  else {
	      my $len = length($menu{$title}{name});
	      my $fill = int((16 - $len) / 2) + $len;
	      my $rest = 16 - $fill;
	      $menustr = sprintf("%${fill}s%${rest}s",$menu{$title}{name},"");
	      $menustr =~ s/_/ /;
print STDERR "Finding label for ".substr($menustr,0,16)." : ";
	      $menustr = $i18n->get("[[base-lcd." . substr($menustr,0,16) . "]]");
print STDERR "Found $menustr\n\n";
	  }
	  
	  system("$LCD_WRITE -s \"$selstr\" \"$menustr\"");
	  while (system("$LCD_READ") != 0){};

	  for (;;)
	  {
	      my $b = 0;
	      my $mytime = time();
	      
	      while (($b = system("$LCD_READ")) == 0) {
		  exit(0) if ((time() - $mytime) > 60);
	      }
	      while (system("$LCD_READ") != 0){};

	      return $title if (scalar(grep(/^\Q$b\E$/, @{ $buttons{Enter} })));
	      next ITEM if (scalar(grep(/^\Q$b\E$/, @{ $buttons{Select} })));
	  }
      }
    }
    return;
}

######################################################################
##
## write to console
##
sub put_console
{
    my($line1,$line2) = (@_);

    print "$line1\n$line2\n";

    return;
}

######################################################################
##
## write to LCD
##
sub put_lcd
{
    my($line1,$line2,$option) = (@_);

    system("$LCD_WRITE $option \"$line1\" \"$line2\"");
    system("$LCD_FLASH $option");

    return;
}

######################################################################
##
## get and verify ip address
##
sub get_ipaddr
{
    my($method,$default,$interface,$option) = (@_);
    for ($method)
    {
	/^lcd/ && do {
		return &get_ipaddr_lcd($default,$interface,$option);
	    };
	/^console/ && do {
		return &get_ipaddr_console($default);
	    };
    }
    return;
}

######################################################################
##
## get and verify netmask
##
sub get_netmask
{
    my($method,$default,$interface,$option) = (@_);
    for ($method)
    {
	/^lcd/ && do {
		return &get_netmask_lcd($default,$interface,$option);
	    };
	/^console/  &&
	    do {
		return &get_netmask_console($default);
	    };
    }
    return;
}

######################################################################
##
## get and verify gateway
##
sub get_gateway
{
    my($method,$default,$option) = (@_);
    for ($method)
    {
	/^lcd/ && do {
		return &get_gateway_lcd($default,$option);
	    };
	/^console/ && do {
		return &get_gateway_console($default);
	    };
    }
    return;
}

######################################################################
##
## read and verify an ip address from the console
##
sub get_ipaddr_console
{
    my($default) = shift;
    my($title)   = gettext("PRIMARY IP ADDR:");
    my($error)   = gettext("INVALID IP:     ");
    my($ipaddr)  = "";

    while (!length($ipaddr))
    {
	# Enter IP address from the console
	print "\n$title\n\n";
	my $term = new Term::ReadLine 'IP address';
	$ipaddr = $term->readline("[$default]> ");

	$ipaddr = $default unless $ipaddr;

	# Check for validity
	my(@ip) = split(/\./,$ipaddr);
	if ($ip[0] == 0 || $ip[0] > 223 || $ip[3] == 0)
	{
	    print "\n$error  $ipaddr\n";
	    $ipaddr = "";
	}
    }
    print "$ipaddr\n";
    return $ipaddr;
}

######################################################################
##
## read and verify netmask from the lcd panel
##
sub get_netmask_console
{
    my($default) = shift;
    my($title) = gettext("PRIMARY NETMASK:");
    my($error) = gettext("INVALID NETMASK:");
    my($netmask) = "";
    $default ||= "255.255.0.0";

    while (!length($netmask))
    {
	# enter netmask from the console
	print "\n$title\n\n";
	my $term = new Term::ReadLine 'netmask';
	$netmask = $term->readline("[$default]> ");

	$netmask = $default unless $netmask;

	# check for validity
	
	my(@nm) = split(/\./,$netmask);
	for($i=0;$i<4;$i++)
	{
	    $mask |= $nm[$i];
	    $mask = $mask << 8 if ($i != 3);
	}
	$mask = ~$mask;

	if ($mask & ($mask + 1)) {
	    print "\n$error  $netmask\n";
	    $netmask = "";
	}
    }
    print "$netmask\n";
    return $netmask;
}

######################################################################
##
## read and verify gateway from the lcd panel
##
sub get_gateway_console
{
    my($default) = shift;
    my($title) = gettext("ENTER GATEWAY:  ");
    my($error) = gettext("INVALID GATEWAY:");
    my($gateway) = "";
    $default ||= "0.0.0.0";

    while (!length($gateway))
    {
	# enter netmask from the console
	print "\n$title\n\n";
	my $term = new Term::ReadLine 'gateway';
	$gateway = $term->readline("[$default]> ");
	$gateway = $default unless $gateway;

	# check for validity
	# Check for validity
	if ($gateway eq "0.0.0.0") {
	    return "none";
	}
	else {
	    my @gw = split(/\./,$gateway);
	    if ($gw[0] == 0 || $gw[0] > 223 || $gw[3] == 0) {
		print "\n$error  $gateway\n";
		$gateway = "";
	    }
	}
    }
    print "$gateway\n";
    return $gateway;
}

######################################################################
##
## read and verify an ip address from the lcd panel
##
sub get_ipaddr_lcd
{
    my($default,$interface,$option) = (@_);

    $interface = ($interface) ? $interface : "eth0";
   
    my $i18n = new I18n;
    my $title = ""; 
    if ($interface eq "eth0") {
        $title = $i18n->get("[[base-lcd.PRIMARY IP ADDR:]]");
  
    } else {
        $title = $i18n->get("[[base-lcd.enter_secondary_ip]]");
    }
    
    my($error) = $i18n->get("[[base-lcd.INVALID IP:     ]]");
    my($ipaddr) = "";

    while (!length($ipaddr))
    {
	# read ip address via lcd panel
	$ipaddr = `$LCD_GETIP $option -1 \"$title\" -i $default`;

	# Check for validity
	my(@ip) = split(/\./,$ipaddr);
	if ($ip[0] == 0 || $ip[0] > 223 || $ip[3] == 0)
	{
	    system("$LCD_WRITE $option \"$error\" \"$ipaddr\"");
	    system("$LCD_FLASH $option");
	    $ipaddr = "";
	}
    }
    return $ipaddr;
}

######################################################################
##
## read and verify netmask from the lcd panel
##
sub get_netmask_lcd
{
    my($default,$interface,$option) = (@_);
    
    $interface = ($interface) ? $interface : "eth0";

    my $i18n = new I18n; 
    my $title = "";
    if ($interface eq "eth0") {
        $title = $i18n->get("[[base-lcd.PRIMARY NETMASK:]]");
    } else {
        $title = $i18n->get("[[base-lcd.enter_secondary_nm]]");
    }

    my($error) = $i18n->get("[[base-lcd.INVALID NETMASK:]]");
    my($netmask) = "";

    while (!length($netmask))
    {
	my($mask) = 0;
	my($i);

	# read ip address via lcd panel
	$netmask = `$LCD_GETIP $option -1 \"$title\" -i $default`;

	# Check for validity
	my(@nm) = split(/\./,$netmask);

	for($i=0;$i<4;$i++)
	{
	    $mask |= $nm[$i];
	    $mask = $mask << 8 if ($i != 3);
	}
	$mask = ~$mask;
	if ($mask & ($mask + 1)) {
	    system("$LCD_WRITE $option \"$error\" \"$netmask\"");
	    system("$LCD_FLASH $option");
	    $netmask = "";
	}
    }
    return $netmask;
}

######################################################################
##
## read and verify gateway from the lcd panel
##
sub get_gateway_lcd
{
    my($default,$option) = (@_);
    my $i18n = new I18n;
    my($title) = $i18n->get("[[base-lcd.ENTER GATEWAY:  ]]");
    my($error) = $i18n->get("[[base-lcd.INVALID GATEWAY:]]");
    my($gateway) = "";

    while (!length($gateway))
    {
	# read ip address via lcd panel
	$gateway = `$LCD_GETIP $option -1 \"$title\" -i $default`;


	# Check for validity
	if ($gateway eq "0.0.0.0") {
	    $gateway = "none";
	}
	else {
	    my @gw = split(/\./,$gateway);
	    if ($gw[0] == 0 || $gw[0] > 223 || $gw[3] == 0) {
		system("$LCD_WRITE $option \"$error\" \"$gateway\"");
		system("$LCD_FLASH $option");
		$gateway = "";
            }
	}
    }
    return $gateway;
}

######################################################################
##
## get yes/no for saving
##
sub save
{
    my($method,$ipaddr,$netmask,$gateway) = (@_);
    for ($method)
    {
	/^lcd/ && do {
		return &save_lcd();
	    };
	/^console/ && do {
		return &save_console($ipaddr,$netmask,$gateway);
	    };
    }
    return;
}


sub save_lcd
{
    my $i18n = new I18n;
    my $savestr = $i18n->get("[[base-lcd.SAVE/CANCEL]]");
    my $yes_pos = 1; my $no_pos = 8;
    my $str_positions = $savestr;
    if($str_positions =~ s/\[\S\][^\[]+$//) {
	$no_pos = length($str_positions)+1;
    }
    if($str_positions =~ s/\[\S\][^\[]+$//) {
	$yes_pos = length($str_positions)+1;
    }

    my $result = system("/sbin/lcd-yesno -s -y $yes_pos -n $no_pos -1 \"$savestr\" -2 \"                \"");

    return 1 if ($result == 256);
    return 0;
}

sub save_console
{
    my($ip,$nm,$gw) = (@_);
    my($savestr) = gettext("SAVE/CANCEL ");
    $savestr =~ s/[ ]*$//;
    my($result) = "";

    for (;;)
    {
	print "\n";
	print "IP ADDR: $ip\n";
	print "NETMASK: $nm\n";
	print "GATEWAY: $gw\n";
	print "\n";

	my $term = new Term::ReadLine 'save';
	$result = $term->readline("$savestr> ");

	if ($result =~ /^s(ave)?/i) {
	    print "SAVE\n\n";
	    return 1;
	}
	elsif ($result =~ /^c(ancel)?/) {
	    print "CANCEL\n\n";
	    return 0;
	}
    }
    return 0;
}


######################################################################
##
## Gets the locale to be used for the LCD.
## Uses the locale preference of the admin user, if defined.
## Otherwise, returns undef
##
sub get_locale
{
	my $locale = eval { I18n::i18n_getSystemLocale(); };	
	return $locale ? $locale : 'en';
}

1;

# Copyright (c) 2003 Sun Microsystems, Inc. All  Rights Reserved.
# 
# Redistribution and use in source and binary forms, with or without 
# modification, are permitted provided that the following conditions are met:
# 
# -Redistribution of source code must retain the above copyright notice, 
# this list of conditions and the following disclaimer.
# 
# -Redistribution in binary form must reproduce the above copyright notice, 
# this list of conditions and the following disclaimer in the documentation  
# and/or other materials provided with the distribution.
# 
# Neither the name of Sun Microsystems, Inc. or the names of contributors may 
# be used to endorse or promote products derived from this software without 
# specific prior written permission.
# 
# This software is provided "AS IS," without a warranty of any kind. ALL EXPRESS OR IMPLIED CONDITIONS, REPRESENTATIONS AND WARRANTIES, INCLUDING ANY IMPLIED WARRANTY OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE OR NON-INFRINGEMENT, ARE HEREBY EXCLUDED. SUN MICROSYSTEMS, INC. ("SUN") AND ITS LICENSORS SHALL NOT BE LIABLE FOR ANY DAMAGES SUFFERED BY LICENSEE AS A RESULT OF USING, MODIFYING OR DISTRIBUTING THIS SOFTWARE OR ITS DERIVATIVES. IN NO EVENT WILL SUN OR ITS LICENSORS BE LIABLE FOR ANY LOST REVENUE, PROFIT OR DATA, OR FOR DIRECT, INDIRECT, SPECIAL, CONSEQUENTIAL, INCIDENTAL OR PUNITIVE DAMAGES, HOWEVER CAUSED AND REGARDLESS OF THE THEORY OF LIABILITY, ARISING OUT OF THE USE OF OR INABILITY TO USE THIS SOFTWARE, EVEN IF SUN HAS BEEN ADVISED OF THE POSSIBILITY OF SUCH DAMAGES.
# 
# You acknowledge that  this software is not designed or intended for use in the design, construction, operation or maintenance of any nuclear facility.
