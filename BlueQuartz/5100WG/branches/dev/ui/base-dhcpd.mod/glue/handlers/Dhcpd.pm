#!/usr/bin/perl
# Copyright 1998, 2002 Sun Microsystems, Inc.
# DHCPD control library
#
# Written by
#   Will DeHaan  null@sun.com
#   Modified for CCE by Andrew Bose andrew@cobalt.com
#

package Dhcpd;

use lib '/usr/sausalito/perl';
use Sauce::Util;
use CCE;

$Dhcpd::Lockfile = "/etc/dhcpd.conf.lck";
$Dhcpd::Dhcpd_conf       = "/etc/dhcpd.conf";
$Dhcpd::Dhcpd_pidfile    = "/var/run/dhcpd.pid";
$Dhcpd::Dhcpd_leases     = "/var/state/dhcp/dhcpd.leases";
$Dhcpd::Dhcpdscript      = "/etc/rc.d/init.d/dhcpd";

sub dhcpd_get_options
{
	my %options;

	open(FILE, "$Dhcpd_conf");
	while (<FILE>) {
		$options{$1} = $2 if /^option\s+(\S+)\s+(.*?)[\s#]*/;
	}
	close(FILE);
	return %options;
}


sub dhcpd_set_options
{
	my $err = Sauce::Util::editfile($Dhcpd_conf, *edit_options, @_);
	return $err;
}


sub dhcpd_del_macs
# Wrapper to dhcpd_set_macs to delete mac address and ip pairs for
# static dhcpd naming
# Arguments: hash of the form mac->name
# Return value: return code and descriptive string of the normal sort
{
	my @delmax = @_;
	my(%max) = Dhcpd::dhcpd_get_macs();

	my($mac);
	foreach $mac (@delmax)
	{
		delete $max{$mac};
	}

	return Dhcpd::dhcpd_set_macs(%max);
}

sub dhcpd_add_macs
# Wrapper to dchpd_set_macs to add mac address and ip pairs for
# static dhcpd naming
# Arguments: hash of the form mac->name
# Return value: return code and descriptive string of the normal sort
{
	my(%newmax) = @_;
	my(%max) = Dhcpd::dhcpd_get_macs();

	return Dhcpd::dhcpd_set_macs( (%newmax,%max) );
}

sub dhcpd_get_macs
# Obtains a listing of mac address associated names/ips
# Arguments: none
# Return value: hash of the form mac->name
#   mac address is of the form 55:66:77:AA:BB:CC
#   name may be a computer name or an ip address
{
	my($mac,$host,%max);
	# it's not an error to have no config file; it just means
	# we haven't made one yet.
	return if (! -e $Dhcpd_conf);
	open(DHCPcnf,"$Dhcpd_conf") || return "[[base-dhcpd.noConfigFile]]";
	while(<DHCPcnf>)
	{
		$mac = $1 if (/^\s*hardware ethernet ([^\;]+)\;/o);
		$host = $1 if (/^\s*fixed-address ([^\;]+)\;/o);
		if ($mac && $host)
		{
			$max{$mac} = $host;
			$mac = $host = '';
		}
	}
	close(DHCPcnf);

	return(%max);
}

sub dhcpd_set_macs
# Saves a list of fixed ip addresses by mac address
# Arguments: hash of mac->ip/name
# Return value: Assuring string of the form "2121 Every little thing, gonna' be alright now"
{
	my(%max) = @_;
	sleep 2 if (-e "$Lockfile"); # Avoid race
	return "[[base-dhcpd.cannotLock]]" if (-e "$Lockfile");

	my($ignore); # Hats off for all the lost code out there..
	open(DHCPcnf,"$Dhcpd_conf") 
          || return "[[base-dhcpd.noConfigFile]]";
	open(DHCPnew,"> $Lockfile") 
	  || return "[[base-dhcpd.cantUpdateConf]]";
	while(<DHCPcnf>)
	{
		print DHCPnew unless (/^host /o ... /^}\s*$/o);
	}
	close(DHCPcnf);

	my($mac);
	foreach $mac (keys %max)
	{
	  print STDERR "Adding $mac and ", $max{$mac}, "\n" if ($Debug);

	  my $hname; # Must respect both IP and name arguments
	  if ($max{$mac} =~ /[a-z]/io)
	  {
	    $hname = $max{$mac};
	  } else # Try to resolve the name of this host
	  {
	    $hname = gethostbyaddr(pack('C4',split('\.',$max{$mac})),2);
	    $hname = $max{$mac} unless ($hname);  # default to IP address
	  }
	  
	  print DHCPnew <<EOF if ($max{$mac} && $mac);
host $hname {
  hardware ethernet $mac;
  fixed-address $max{$mac};
}
EOF
	}

	close(DHCPnew);

	rename("$Lockfile","$Dhcpd_conf") 
            || return "[[base-dhcpd.cantUpdateConf]]";
	
	Dhcpd::dhcpd_hup(); # Kick it
}

sub dhcpd_get_range
# Obtains a list of dhcp service ranges
# Arguments: none
# Return value: hash of the form (low ip -> high ip)
{
	my(%range);
	
	# it's not an error to have no config file; it just means
	# we haven't made one yet.
	return if (! -e $Dhcpd_conf);
	open(DHCPcnf,"$Dhcpd_conf") || return "[[base-dhcpd.noConfigFile]]";
	while(<DHCPcnf>)
	{
		$range{$1}=$2 if (/^\s*range ([\d\.]+) ([\d\.]+)\;/o);
	}
	close(DHCPcnf);

	return(%range);
}

sub dhcpd_set_range
# Allocate a range of addresses to give out dynamically
# Arguments: a hash table which lists a set of ranges, in the format
#          %hash = ($low1 => $high1, $low2 => $high2, ...);
# Return value: usual status string of form: "4819 Don't sleep in the subway"
# Side effects: modifies the DHCPD config file, HUPs dhcpd
{
	my(%range) = @_;
	my($setRange,$low);
	sleep 2 if (-e "$Lockfile"); # Avoid race
	return "[[base-dhcpd.cannotLock]]" if (-e "$Lockfile");

	# Get the network parameters for the primary interface
	# get network object id
	my $cce = new CCE;
	my ($network_oid) = $cce->find("Network", { 'device' => "eth0" });

	# get network object:
	my ($okn, $netobj) = $cce->get($network_oid);
	if (!$okn) {
		return "[[base-dhcpd.noNetworkObj]]";
	}
	my $ipaddr = $netobj->{ipaddr};
	my $nm = $netobj->{netmask};

	foreach $low (keys %range) {
	  unless (Dhcpd::net_network_ismember($low,$ipaddr,$nm)) {
	    return "[[base-dhcpd.rangeMismatch]]";
	  }
	  unless (Dhcpd::net_network_ismember($range{$low},$ipaddr,$nm)) {
	    return "[[base-dhcpd.rangeMismatch]]";
	  }
	  my @low = split(/\./o,$low);
	  my @hi  = split(/\./o,$range{$low});
	  unless (($low[3] <= $hi[3]) &&
		  ($low[2] <= $hi[2]) &&
		  ($low[1] <= $hi[1]) &&
		  ($low[0] <= $hi[0])) {
	    return "[[base-dhcpd.wrongOrder]]";
	  }
	}

	open(DHCPcnf,"$Dhcpd_conf") 
	  || return "[[base-dhcpd.noConfigFile]]";
	open(DHCPnew,"> $Lockfile") 
	  || return "[[base-dhcpd.cantUpdateConf]]";
	while(<DHCPcnf>)
	{
		print DHCPnew  unless (/^\s*subnet /o ... /^\s*\}\s*$/o);
	}
	close(DHCPcnf);

	# Create a subnet block if necessary
	my ($subnet) = Dhcpd::net_get_network($ipaddr,$nm);
	print DHCPnew "subnet $subnet netmask $nm {\n";
	foreach $low (keys %range)
	{
		print DHCPnew "  range $low ".$range{$low}.";\n";
	}
	print DHCPnew "}\n";
	close(DHCPnew);

	rename("$Lockfile","$Dhcpd_conf")
	  || return "[[base-dhcpd.cantUpdateConf]]";

	Dhcpd::dhcpd_hup(); # Kick it
}

sub dhcpd_set_name
# Set the server identifier for the DHCP server
# Arguments: hostname
# Side effects: modifies dhcpd.conf, restarts dhcpd
# Return value: status string like "2867 The pizza attacks!"
{
  my ($name) = @_;
  sleep 2 if (-e "$Lockfile"); # Avoid race
  return "[[base-dhcpd.cannotLock]]" if (-e "$Lockfile");
  open(DHCPcnf,$Dhcpd_conf) 
    || return "[[base-dhcpd.cantUpdateConf]]";
  open(DHCPnew,"> $Lockfile") 
    || return "[[base-dhcpd.cantUpdateConf]]";
  while (<DHCPcnf>) {
    if (/^server-identifier /o) {
      print DHCPnew "server-identifier $name;\n";
    } else {
      print DHCPnew;
    }
  }
  close DHCPcnf;
  close DHCPnew;
  rename("$Lockfile","$Dhcpd_conf")
    || return "[[base-dhcpd.cantUpdateConf]]";
  Dhcpd::dhcpd_hup();
}



sub dhcpd_set_parameters
# Set the dhcpd server paramters
# Arguments: domain name, dns servers,
#   gateway, netmask, maximum lease time, broadcast
#   hostname, sysdomain
#   Any argument except the netmask can be empty.
# Side Effects: writes $Dhcpd_conf; creates a new one if none exists
#   This function does *not* restart dhcpd; it assumes that
#   dhcpd_clean_ranges() will be called next, and that function
#   takes care of restarting the daemon.
{

my $cce = new CCE;

        my($domain,$gw,$nm,$maxlease,$broadcast,@dns) = @_;
	my ($subnet) = Dhcpd::net_get_network($gw,$nm);	

	$dns = join ', ', @dns;
        sleep 2 if (-e "$Lockfile"); # Avoid race
        return "[[base-dhcpd.cannotLock]]" if (-e "$Lockfile");

        open(DHCPnew,"> $Lockfile")
          ||  return "[[base-dhcpd.cannotLock]]";
        if (-r $Dhcpd_conf) {
          open(DHCPcnf, "$Dhcpd_conf") || return "[[base-dhcpd.noConfigFile]]";
          while(<DHCPcnf>) {
                # All arguments are considered optional...if aren't given an
                # argument, then we comment out the corresponding line.
                # We leave the line around as a placeholder so it's easy
                # to add back in later.
                if (/^\#?option domain-name\s+/o)
                {
                  print DHCPnew "#" unless ($domain); # comment it out
                  print DHCPnew "option domain-name \"$domain\";\n";
                } elsif (/^\#?option domain-name-servers/o)
                {
                  # if no nameservers are defined, leave it commented out
                  print DHCPnew "#" unless ($dns);
                  print DHCPnew "option domain-name-servers $dns;\n";
                } elsif (/^\#?option routers /o)
                {
                        print DHCPnew "#" unless ($gw); # comment out
                        print DHCPnew "option routers $gw;\n";
                } elsif (/^\#?option subnet-mask /o)
                {
                        print DHCPnew "#" unless ($nm);  # comment out
                        print DHCPnew "option subnet-mask $nm;\n";
                } elsif (/^\#?default-lease-time /o)
                {
                        print DHCPnew "#" unless ($maxlease);  # comment out
                        print DHCPnew "default-lease-time $maxlease;\n";
                } elsif (/^\#?max-lease-time /o)
                {
                        print DHCPnew "#" unless ($maxlease);  # comment out
                        print DHCPnew "max-lease-time $maxlease;\n";
                } elsif (/^\#?option broadcast-address /o)
                {
                        print DHCPnew "#" unless ($broadcast);  # comment out
                        print DHCPnew "option broadcast-address $broadcast;\n";
                } elsif (/^subnet ([\d\.]+) netmask ([\d\.]+)/o)
                {
                  # Set subnet parameters based on the new netmask and gateway
                  print DHCPnew "subnet $subnet netmask $nm {\n";
                } else
                {
                        print DHCPnew;
                }
              }
          close(DHCPcnf);
        } else {
          # Creating a fresh new config file

          print DHCPnew "# Configuration file for ISC dhcpd\n";
          print DHCPnew "# Created by the Cobalt user interface\n\n";
          print DHCPnew "#server-identifier ", "", ";\n\n";
          print DHCPnew "# common option definitions\n";
          if ($domain) {
            print DHCPnew "option domain-name \"$domain\";\n";
          } else {
            print DHCPnew "#option domain-name \n";
          }
          if ($dns) {
            print DHCPnew "option domain-name-servers $dns;\n"
          } else {
            print DHCPnew "#option domain-name-servers\n"
          }
          if ($gw) {
            print DHCPnew "option routers $gw;\n";
          } else {
            print DHCPnew "#option routers \n";
          }
          if ($nm) {
            print DHCPnew "option subnet-mask $nm;\n";
          } else {
            print DHCPnew "#option subnet-mask \n";
          }
          if ($broadcast) {
            print DHCPnew "option broadcast-address $broadcast;\n";
          } else {
            print DHCPnew "#option broadcast-address \n";
          }
          if ($maxlease) {
            print DHCPnew "default-lease-time $maxlease;\n";
            print DHCPnew "max-lease-time $maxlease;\n";
          } else {
            print DHCPnew "#default-lease-time \n";
            print DHCPnew "#max-lease-time \n";
          }
          print DHCPnew "use-host-decl-names on;\n\n";
        }
        close(DHCPnew);

        rename("$Lockfile","$Dhcpd_conf")
          || return "[[base-dhcpd.cantUpdateConf]]";

Dhcpd::dhcpd_hup();

return 0;

}


sub dhcpd_create_conf
# Create the configuration file for dhcpd server with defaults
#   which are largely from current network parameters
# Arguments: none
# Side Effects: writes $Dhcpd_conf; creates a new one if none exists
# Return value: status string like '4000 S.O.S.'
{
    if( -e $Dhcpd_conf ) { return "[[base-dhcpd.noConfigFile]]"; }

	my $cce = new CCE;
	# get system object ids
	my ($system_oid) = $cce->find("System");

	# get system object:
	my ($ok, $obj) = $cce->get($system_oid);
	if (!$ok) {
		return "[[base-dhcpd.noSystemObj]]";
	}
	my $hostname = $obj->{hostname};
	my $sysdomainname = $obj->{domainname};
	my $gateway = $obj->{gateway};
	my $dns = $obj->{dns};

        my ($network_oid) = $cce->find("Network", { 'device' => "eth0" });

        # get network object:
        my ($okn, $netobj) = $cce->get($network_oid);
        if (!$okn) {
		return "[[base-dhcpd.noNetworkObj]]";
        }
        my $ipaddr = $netobj->{ipaddr};
        my $gw = $ipaddr;
        my $nm = $netobj->{netmask};

	my $broadcast = Dhcpd::net_get_broadcast($gateway, $nm);

	my @dns = $cce->scalar_to_array($obj->{dns});
	my $maxlease = "86400";

	my $err=Dhcpd::dhcpd_set_parameters( $sysdomainname, $gw, $nm, $maxlease, $broadcast, @dns );
	if ($err) {
		return $err;
	}

	# create null subnet declaration
	my $ret2=Dhcpd::dhcpd_set_range();

	if ($ret2) {
		return $ret2;
	}
	exit 0;

}


sub dhcpd_clean_ranges
# Clean out the list of addresses to give out dynamically; should be called
#   after dhcpd_set_parameters().
# Arguments: none
# Return value: a hash table of the ranges which were deleted in the form:
#    %hash = ($low1 => $high1, $low2 => $high2, ...);
# Side effects: calls dhcpd_get_params() to figure out what subnet
#    eth0 is on, and removes all ranges that are not on that subnet;
#    then restarts dhcpd.
{
  # Get the current settings
  # get DhcpParam object id
  my $cce = new CCE;
  my ($param_oid) = $cce->find("DhcpParam");
  
  # get DhcpParam object:
  my ($ok, $paramobj) = $cce->get($param_oid);
  if (!$ok) {
	return "[[base-dhcpd.noParamObj]]";
  }
  my $gateway = $paramobj->{gateway};
  my $netmask = $paramobj->{netmask};
  my %wiped; # our hash of what ranges we wiped

  return "[[base-dhcpd.cannotLock]]" if (-e "$Lockfile");
  open(LOCK,"> $Lockfile") 
    || return "[[base-dhcpd.cannotLock]]";
  open(CONF, "$Dhcpd_conf") || return "[[base-dhcpd.cantUpdateConf]]";
  while(<CONF>) {
    if (/range ([\d\.]+) ([\d\.]+);/o) {
      # if we just changed the netmask and/or gateway, we need
      # to clear any dynamic ranges that will no longer work.
      if (Dhcpd::net_network_ismember($1,$gateway,$netmask) &&
	  Dhcpd::net_network_ismember($2,$gateway,$netmask)) {
	# This range is out of...range.
	$wiped{$1} = $2;
      }
      else {
	# This range still on the same subnet as eth0
	print LOCK;
      }
    } else {
      # Not a range statement, leave it alone
      print LOCK;
    }
  }
  close LOCK;
  close CONF;
  rename("$Lockfile","$Dhcpd_conf") || return "[[base-dhcpd.cantUpdateConf]]";

  Dhcpd::dhcpd_hup(); # Kick it
  exit 0;
}

# = = Local subroutines = = = = = = = = = = = = = = = = = = = = = = = =

sub dhcpd_hup
# Restart the DHCP daemon
# Arguments: none
# Return value: none
# Side effect: calls the DHCP init script to stop and start the daemon,
#   if the daemon is currently running.
{
  return if (! -e $Dhcpd_pidfile); # Let sleeping dogs lie

  system("$Dhcpdscript restart 2>&1");
}

sub set_dhcp_server_on
# Turn the dhcp server on or off
# Arguments: 1 for on, 0 for off
# Return value: a status string of the form MSG_error("4325")
# Side effects: creates or breaks symlink
{
    my $Lcdshowscript    = "/etc/rc.d/init.d/lcd-showip";
    my ($state) = @_;
    my $istate = service_get_init("dhcpd");

    return "[[base-dhcpd.noDhcpConf]]" if ($state && !(-e $Dhcpd_conf));

    if ($state && (! $istate))
    {
        unless (-e $Dhcpd_leases)
        {
            open(LEES,"> $Dhcpd_leases") || return "[[base-dhcpd.noLeaseFile]]";
            close(LEES);
        }
        open(DHCPSTART,"$Dhcpdscript start 2>&1 |") ||
          return "[[base-dhcpd.initScriptError]]"; 
        # The error message comes right before the "exiting" line...
        # so we remember the error and then return it if we see
        # "exiting"
        my ($line,$line1,$line2);
        while (<DHCPSTART>) {
          if (/exiting/o) {
            close DHCPSTART;
            $line = "$line1 $line2";
	     return "$line";
          }
          unless (/^[\s\^]*$/o) {  # ignore blank lines
            $line1 = $line2;
            chomp($line2 = $_);
          }
        }
        close DHCPSTART;

        system ("$Lcdshowscript start 2>&1")
          if (-x $Lcdshowscript);

        service_set_init_on("dhcpd")
	     || return "[[base-dhcpd.initLinkOnFail]]";
    }
    elsif (!$state) # Disable
    {
      service_set_init_off("dhcpd"); # ignore return value if we're already off
      system ("$Dhcpdscript stop >/dev/null 2>&1") &&
        die  "[[base-dhcpd.offFail]]";

      # Clear LCD of dhcp server messages
      system ("$Lcdshowscript start 2>&1") if (-x $Lcdshowscript);
    }

    return 0;
}
sub get_dhcp_server_on
# Is the dhcp server set to be on?
# Returns 1 if the dhcp server is activated, 0 if deactivated
#   This reflects what ought to be, *not* what is
# Arguments: none
# Side effects: none
{
    return service_get_init("dhcpd");
}



# NETWORK TOOLS

sub net_network_ismember
# Is the given IP within the given network?
# Arguments: IP, network address, network mask (either decimal or dotted)
# Side effects: none
# Return value: 1 for yes, 0 for no
{
  my ($ip,$net,$mask) = @_;

  # deal with things properly whatever form the netmask is in
  $mask = Dhcpd::net_convert_netmask($mask) if ($mask =~ /^\d+$/o);
  return (Dhcpd::net_get_broadcast($ip,$mask) eq Dhcpd::net_get_broadcast($net,$mask)) ?
    (1) : (0);
}


sub net_convert_netmask
# Convert netmask from decimal notation to dotted quad (16 => 255.255.0.0) or vice versa
# Arguments: netmask in decimal, or netmask in dotted quad
# Return value; netmask in dotted quad if decimal argument given, or netmask in
#      decimal if dotted quad argument is given
# Example: $quadmask = net_convert_netmask(26);
{
  my ($mask) = @_;
  # I should be able to do this in some cool mathematical way.
  # If only I knew how.
  my %netmasks = (
                  32 => "255.255.255.255", 31 => "255.255.255.254",
                  30 => "255.255.255.252", 29 => "255.255.255.248",
                  28 => "255.255.255.240", 27 => "255.255.255.224",
                  26 => "255.255.255.192", 25 => "255.255.255.128",
                  24 => "255.255.255.0",   23 => "255.255.254.0",
                  22 => "255.255.252.0",   21 => "255.255.248.0",
                  20 => "255.255.240.0",   19 => "255.255.224.0",
                  18 => "255.255.192.0",   17 => "255.255.128.0",
                  16 => "255.255.0.0",     15 => "255.254.0.0",
                  14 => "255.252.0.0",     13 => "255.248.0.0",
                  12 => "255.240.0.0",     11 => "255.224.0.0",
                  10 => "255.192.0.0",      9 => "255.128.0.0",
                   8 => "255.0.0.0",        7 => "254.0.0.0",
                   6 => "252.0.0.0",        5 => "248.0.0.0",
                   4 => "240.0.0.0",        3 => "224.0.0.0",
                   2 => "192.0.0.0",        1 => "128.0.0.0",
                   0 => "0.0.0.0",

                  "0.0.0.0" => 0,          "128.0.0.0" => 1,
                  "192.0.0.0" => 2,        "224.0.0.0" => 3,
                  "240.0.0.0" => 4,        "248.0.0.0" => 5,
                  "252.0.0.0" => 6,        "254.0.0.0" => 7,
                  "255.0.0.0" => 8,        "255.128.0.0" => 9,
                  "255.192.0.0" => 10,     "255.224.0.0" => 11,
                  "255,240.0.0" => 12,     "255.248.0.0" => 13,
                  "255.252.0.0" => 14,     "255.254.0.0" => 15,
                  "255.255.0.0" => 16,     "255.255.128.0" => 17,
                  "255.255.192.0" => 18,   "255.255.224.0" => 19,
                  "255.255.240.0" => 20,   "255.255.248.0" => 21,
                  "255.255.252.0" => 22,   "255.255.254.0" => 23,
                  "255.255.255.0" => 24,   "255.255.255.128" => 25,
                  "255.255.255.192" => 26, "255.255.255.224" => 27,
                  "255.255.255.240" => 28, "255.255.255.248" => 29,
                  "255.255.255.252" => 30, "255.255.255.254" => 31,
                  "255.255.255.255" => 32,
                 );

  return $netmasks{$mask};
}

sub net_get_broadcast
# Returns the standard broadcast address which would go with this network
#   We assume that we should use the high address, because everyone does now.
# Arguments: IP address and netmask, in dotted quad notation
# Return value: broadcast address, in dotted quad notation
{
    my ($addr,$netmask) = @_;
    my ($a1,$a2,$a3,$a4) = map(pack('C',$_),split(/\./o,$addr));
    my ($m1,$m2,$m3,$m4) = map(pack('C',$_),split(/\./o,$netmask));
    my ($n1,$n2,$n3,$n4,$b1,$b2,$b3,$b4,$f,$bcast);
    $n1 = unpack('C',($m1 & $a1));
    $n2 = unpack('C',($m2 & $a2));
    $n3 = unpack('C',($a3 & $m3));
    $n4 = unpack('C',($a4 & $m4));
    $f = pack('C',0xff);   # 0xff = 255
    $b1 = $n1 + unpack('C',($m1 ^ $f));
    $b2 = $n2 + unpack('C',($m2 ^ $f));
    $b3 = $n3 + unpack('C',($m3 ^ $f));
    $b4 = $n4 + unpack('C',($m4 ^ $f));
    $bcast = "$b1.$b2.$b3.$b4";
    return $bcast;
}

sub net_get_network
# Returns the network address which would go with this IP and netmask
# Arguments: IP address and netmask, in dotted quad notation
# Return value: network address, in dotted quad notation
{
    my ($addr,$netmask) = @_;
    my ($a1,$a2,$a3,$a4) = map(pack('C',$_),split(/\./o,$addr));
    my ($m1,$m2,$m3,$m4) = map(pack('C',$_),split(/\./o,$netmask));
    my ($n1,$n2,$n3,$n4,$network);
    $n1 = unpack('C',($m1 & $a1));
    $n2 = unpack('C',($m2 & $a2));
    $n3 = unpack('C',($a3 & $m3));
    $n4 = unpack('C',($a4 & $m4));
    $network = "$n1.$n2.$n3.$n4";
    return $network;
}

sub service_get_init
# get the state of the service in the given runlevel
# arguments: state, runlevel (defaults to 3 if not specified)
{
    my ($service,$state) = @_;
    my ($s);

    ($state) ? ($s=$state) : ($s=3);
    $result = `/sbin/chkconfig --list $service`;

    $result =~ /^$service [\s\S]*$s:(\S+)/;

    ($1 eq "on") ? ($state = 1) : ($state = 0);
    return $state;
}

sub service_set_init_on
# set the given service state to on or of
# arguments: service name, list of runlevels
{
    my ($service,@states,$level);
    $service = shift;
    @states = @_;

    if (@states) {
        $level = " --level ";
        $level .= join('',@states);
    }

    system("/sbin/chkconfig $level $service on 2>&1") &&
        return;

     return 1;
}

sub service_set_init_off
# set the given service state to on or of
# arguments: service name, list of runlevels
{
    my ($service,@states,$level);
    $service = shift;
    @states = @_;

    if (@states) {
        $level = " --level ";
        $level .= join('',@states);
    }

    system("/sbin/chkconfig $level $service off 2>&1") &&
        return;

    return 1;

}

sub edit_options
{
	my ($input, $output, %options) = @_;
	my $opt;

	# search for the start of options
	while (<$input>) {
		if (/^option\s+(\S+)/) {
			print $output $_ unless defined($options{$1});
			last;
		}
		print $output $_;
	}

	# print out our options
	foreach $opt (keys %options) {
		print $output "option $opt $options{$opt};\n" if $options{$opt};
	}

	# spit out the rest
	while (<$input>) {
		if (/^option\s+(\S+)/) {
			next if defined($options{$1});
		}
		print $output $_;
	}
	return 0;
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
