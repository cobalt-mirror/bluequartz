######################################################################
## LCD.pm
##
## LCD and console routines for handling menus, getting 
## ip, netmask, gateway, input, output, yes/no etc.

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

	     lcd_lock
	     lcd_unlock
	     );

use POSIX;
use Term::ReadLine;
use Fcntl;
use FileHandle;
use lib qw(/usr/sausalito/perl);
use I18n;

use vars qw($LCD_GETIP $LCD_WRITE $LCD_FLASH $LCD_READ);
$LCD_GETIP = "/sbin/lcd-getip";
$LCD_WRITE = "/sbin/lcd-write";
$LCD_FLASH = "/sbin/lcd-flash";
$LCD_READ  = "/sbin/readbutton";

my $LCD_LOCKFILE = "/etc/locks/.lcdlock";
my $LCD_LOCKFD = undef;

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
    my($menuhash, $prompt) = (@_);
    my %menu = %$menuhash;
    my($term, $choice);
    my(@items, $item);
    my($index, $menu_item_name);
    my $i18n = new I18n;
    my $selstr = $i18n->get("[[base-lcd.SELECT:         ]]");
    $selstr =~ s/:?[ ]*$//;
    if ($prompt) {
	$selstr = $prompt;
    }

    format MENU_OUT =
@>>>>> - @<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
$index,  $menu_item_name
.
    
    @items = sort { $menu{$a}->{index} <=> $menu{$b}->{index} } keys %menu;
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
          $menu_item_name = $i18n->get("[[base-lcd." . substr($menu_item_name,0,16) . "]]");
            }
	    $menu_item_name =~ s/^[ ]*//;
	    $menu_item_name =~ s/:?[ ]+$//;
	    write();
	}

	$~ = 'STDOUT';
	print "\n";
	
	$term = new Term::ReadLine 'select',(*STDIN),(*STDOUT);
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

	print "\n" . $i18n->get("[[base-lcd.invalidSelection]]") . "\n";
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
	      $menustr = _center_string($menu{$title}{string});
	  } else {
		my $name = _center_string($menu{$title}{name});
		$name =~ s/_/ /g;
		$name = '[[base-lcd.' . substr($name, 0, 16) . ']]';
		$menustr = $i18n->get($name);
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

sub _center_string
{
	my $string = shift;
	my $len = length($string);
	my $fill = int((16 - $len) / 2) + $len;
	my $rest = 16 - $fill;
	return sprintf("%${fill}s%${rest}s", $string, "");
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
    my($line1, $line2, $option) = (@_);

    my @write = ($LCD_WRITE);
    defined($option) && push @write, $option;
    defined($line1) && push @write, $line1;
    defined($line2) && push @write, $line2;
    system(@write);
    system("$LCD_FLASH " . (defined($option) ? $option : '') . '&');

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
		return &get_ipaddr_console($default,$interface);
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
		return &get_netmask_console($default,$interface);
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
  my($default,$interface) = (@_);

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
  	# Enter IP address from the console
  	print "\n$title\n\n";
  	my $term = new Term::ReadLine 'IP address',(*STDIN),(*STDOUT);
  	$ipaddr = $term->readline("[$default]> ");

  	$ipaddr = $default unless $ipaddr;

  	# Check for validity
	my $bad = 0;
	$bad++ if ($ipaddr !~ /^\s*\d+\.\d+\.\d+\.\d+\s*$/);

	my $temp = $ipaddr;
	$temp =~ s/\s+//g;		# Strip whitespace
  	my(@ip) = split(/\./,$temp);

	unless ($bad) {
	    for ($i=0; $i<4; $i++) {
	        $ip[$i] =~ s/^0*(\d+)/$1/;	# Strip leading zeros
	        $bad++ if ($ip[$i] > 255 || $ip[$i] < 0);	
	    }
	    $bad++ if ($ip[0] == 0 || $ip[0] == 127 || $ip[0] > 223);
	    $bad++ if ($#ip != 3);
	}

	if ($bad) {
	    print "\n$error  $ipaddr\n";
	    $ipaddr = "";
	} else {
	    $ipaddr = join '.', @ip;
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
    my($default,$interface) = (@_);
    
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

    $default ||= "255.255.0.0";

    while (!length($netmask))
    {
	# enter netmask from the console
	print "\n$title\n\n";
	my $term = new Term::ReadLine 'netmask',(*STDIN),(*STDOUT);
	$netmask = $term->readline("[$default]> ");

	$netmask = $default unless $netmask;

	# check for validity
	my $bad = 0;
	$bad++ if ($netmask !~ /^\s*\d+\.\d+\.\d+\.\d+\s*$/);

	my $temp = $netmask;
	$temp =~ s/\s+//g;		# Strip whitespace
  	my(@nm) = split(/\./,$temp);

	$bad++ if ($nm[3] == 255 || $#nm != 3);

	for($i=0;$i<4;$i++)
	{
	    $nm[$i] =~ s/^0*(\d+)/$1/;	# Strip leading zeros
	    $mask |= $nm[$i];
	    $mask = $mask << 8 if ($i != 3);
	}
	$mask = ~$mask;

	if ($bad || ($mask & ($mask + 1))) {
	    print "\n$error  $netmask\n";
	    $netmask = "";
	} else {
	    $netmask = join '.', @nm;
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
    my($default) = (@_);
    my $i18n = new I18n;
    my($title) = $i18n->get("[[base-lcd.ENTER GATEWAY:  ]]");
    my($error) = $i18n->get("[[base-lcd.INVALID GATEWAY:]]");
    my($gateway) = "";

    $default ||= "0.0.0.0";

    while (!length($gateway))
    {
	# enter netmask from the console
	print "\n$title\n\n";
	my $term = new Term::ReadLine 'gateway',(*STDIN),(*STDOUT);
	$gateway = $term->readline("[$default]> ");
	$gateway = $default unless $gateway;

	# Check for validity
	my $bad = 0;
	$bad++ if ($gateway !~ /^\s*\d+\.\d+\.\d+\.\d+\s*$/);

	my $temp = $gateway;
	$temp =~ s/\s+//g;		# Strip whitespace
  	my(@gw) = split(/\./,$temp);

	unless ($bad) {
	    for ($i=0; $i<4; $i++) {
	        $gw[$i] =~ s/^0*(\d+)/$1/;	# Strip leading zeros
	        $bad++ if ($gw[$i] > 255 || $gw[$i] < 0);	
	    }
	    $bad++ if ($gw[0] == 0 || $gw[0] == 127 || $gw[0] > 223);
	    $bad++ if ($gw[3] == 0 || $#gw != 3);
	}
	
	# Check ahead of time if we have 0.0.0.0 which is a special case.
	$gwtemp = join '.', @gw;
	return 'none' if ($gwtemp eq '0.0.0.0');

	if ($bad) {
	    print "\n$error  $gateway\n";
	    $gateway = "";
	} else {
	    $gateway = $gwtemp;
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

	# Bad return code probably means it's locked.
	return undef if $?;

	# Check for validity
	my(@ip) = split(/\./,$ipaddr);
	if (($ipaddr =~ /^255/) ||
            ($ip[0] eq 0 || $ip[0] > 223) ||
	    ($ipaddr eq '127.0.0.1'))
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

	# Bad return code probably means it's locked.
	return undef if $?;

	# Check for validity
	my(@nm) = split(/\./,$netmask);

	for($i=0;$i<4;$i++)
	{
	    $mask |= $nm[$i];
	    $mask = $mask << 8 if ($i != 3);
	}
	$mask = ~$mask;
	if (($mask & ($mask + 1)) ||
            ($netmask eq '255.255.255.255'))
        {
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

	# Bad return code probably means it's locked.
	return undef if $?;

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

######################################################################
##
## get yes/no for saving from LCD
##
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

    # Print a message that we're saving the settings just in case...
    if ($result == 256) {
        system("/sbin/lcdstop");
        system("/sbin/lcd-write -s \"Saving settings\" \"Please wait...\"");
        return 1;
    }

    return 0;
}

######################################################################
##
## get yes/no for saving from console
##
sub save_console
{
    my($ip,$nm,$gw) = (@_);
    my $i18n = new I18n;
    my $savestr = $i18n->get("[[base-lcd.SAVE/CANCEL]]");
    $savestr =~ s/[ ]*$//;
    my($result) = "";

    for (;;)
    {
	print "\n";
	print $i18n->get("[[base-lcd.lcd_ipaddr]]") . " $ip\n";
	print $i18n->get("[[base-lcd.netmask]]") . " $nm\n";
	print $i18n->get("[[base-lcd.default_gateway]]") . " $gw\n";
	print "\n";

	my $term = new Term::ReadLine 'save',(*STDIN),(*STDOUT);
	$result = $term->readline("$savestr> ");

	if ($result =~ /^s(ave)?/i) {
	    print $i18n->get("[[base-lcd.saving]]") . "\n\n";
	    return 1;
	}
	elsif ($result =~ /^c(ancel)?/) {
	    print $i18n->get("[[base-lcd.canceled]]") .  "\n\n";
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

########################################
# lock the lcd
# special care should be taken to run lcd-utils with the -s option
# so they don't try to get the lock themselves
#
# returns 0 for failure 1 for success
#
sub lcd_lock
{
	if (defined($LCD_LOCKFD)) {
		return 0;
	}

	$LCD_LOCKFD = new FileHandle;

	my @lock_stat = lstat($LCD_LOCKFILE);
	if (scalar(@lock_stat) == 0) {
		# create the lock file
		if (!sysopen($LCD_LOCKFD, $LCD_LOCKFILE, O_RDWR | O_TRUNC |
			     O_EXCL | O_CREAT, 0600)) {
			print STDERR "ERROR: can't open $LCD_LOCKFILE\n";
			undef($LCD_LOCKFD);
			return 0;
		}
	} else {
		# exists must be a regular file
		if (!S_ISREG($lock_stat[2])) {
			print STDERR "ERROR: $LCD_LOCKFILE has invalid mode\n";
			undef($LCD_LOCKFD);
			return 0;
		}

		# should only have one link
		if ($lock_stat[3] != 1) {
			print STDERR "ERROR: $LCD_LOCKFILE link count is $lock_stat[3]\n";
			undef($LCD_LOCKFD);
			return 0;
		}

		# open for writing
		if (!sysopen($LCD_LOCKFD, $LCD_LOCKFILE, O_RDWR | O_NOFOLLOW,
			     0600)) {
			print STDERR "ERROR: unable to overwrite $LCD_LOCKFILE\n";
			undef($LCD_LOCKFD);
			return 0;
		}

		# stat again and verify inode/owner/link count
		my @file_stat = lstat($LCD_LOCKFD);
		if (($file_stat[1] != $lock_stat[1]) ||
		    ($file_stat[4] != $lock_stat[4]) ||
		    ($file_stat[3] != 1)) {
			print STDERR "ERROR: unable to verify $LCD_LOCKFILE\n";
			close($LCD_LOCKFD);
			undef($LCD_LOCKFD);
			return 0;
		}
	}

	# fcntl the file so only we have access to the lcd
	my $flock = pack "ssLLi", F_WRLCK, SEEK_SET, 0, 0, $$;
	my $ret = fcntl($LCD_LOCKFD, F_SETLK, $flock);
	if (!defined($ret)) {
		print STDERR "ERROR: unable to fcntl lock on $LCD_LOCKFILE\n";
		close($LCD_LOCKFD);
		undef($LCD_LOCKFD);
		return 0;
	}

	if (!truncate($LCD_LOCKFD, 0)) {
		print STDERR "ERROR: can't truncate $LCD_LOCKFILE\n";
		close($LCD_LOCKFD);
		undef($LCD_LOCKFD);
		return 0;
	}

	print $LCD_LOCKFD $$;
	
	return 1;
}

########################################
# unlock the lcd
# release the lock on the lcd
#
sub lcd_unlock
{
	if (defined($LCD_LOCKFD)) {
		my $flock = pack "ssLLi", F_UNLCK, 0, 0, 0, $$;
		if (!defined(fcntl($LCD_LOCKFD, F_SETLK, $flock))) {
			print STDERR "ERROR: couldn't unlock $LCD_LOCKFILE\n";
		}
		close($LCD_LOCKFD);
		undef($LCD_LOCKFD);
		unlink($LCD_LOCKFILE);
	}
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
