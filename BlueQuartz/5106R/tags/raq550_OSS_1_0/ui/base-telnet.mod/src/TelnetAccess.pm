package TelnetAccess;

use lib qw( /usr/sausalito/perl );
use Sauce::Service;

$TelnetAccess::Lockdir          = "/var/lock";
$TelnetAccess::Inetd_conf       = "/etc/inetd.conf";
$TelnetAccess::Inetd_pid        = "/var/run/inetd.pid";
$TelnetAccess::SecretTelnetPort = "telnet";
$TelnetAccess::ShellSymlink     = "/bin/usersh";
$TelnetAccess::GoodShell        = "/bin/bash";
$TelnetAccess::BadShell         = "/bin/badsh";
$TelnetAccess::SuspendShell     = "/bin/false";
#
# changeAccess (code)
# returns a message string describing what happened.
#
sub changeAccess
{
  my $code = shift;
  my $rate = shift;
  my $state = shift;
  $enabled = $state ? 'on' : 'off';

  my %accessTable =
  (
    'none' => 0,
    'root' => 0,
    'reg'  => 1,
  );
  $accessTable{$code} ||= 'none';

  my $ret = set_telnet_server_on($enabled, $rate);
  $ret .= set_telnet_server_open($accessTable{$code});

  return $ret;
}

#
# set_telnet_server_on
#
# Arguments: 1 for on, 0 for off
# Return value: a status string
# Side effects: modifies inetd.conf
#
sub set_telnet_server_on
{
	my $state = shift;
	my $rate = shift;
	$rate ||= 40;

open(DB, ">>/tmp/telnet.debug");

	my $ret = service_set_inetd('telnet', $state, $rate);
print DB "service_set_inetd telnet $state $rate:\n$ret\n";
	service_send_signal('inetd', 'HUP');
print DB "service_send_signal inetd, HUP\n$ret\n";

close(DB);
	
	return "Telnet server " . $state?"started":"stopped";
}

#
# get_telnet_server_on
#
# Is the telnet server set to be on?
# Returns 1 if the telnet server is activated, 0 if deactivated
# Arguments: none
# Side effects: none
#
sub get_telnet_server_on
{
  open(INETD,$Inetd_conf) || return "Cannot read $Inetd_conf: $!";
  while (<INETD>)
  {
    if (/^$SecretTelnetPort\s/o)
    {
      close INETD;
      return 1;
    }
  }
  close INETD;
  return 0;
}

#
# set_telnet_server_open
#
# Set whether only root can telnet in, or anyone at all
# Arguments: 1 for anyone, 0 for root only
#
sub set_telnet_server_open
{
  my $newState = shift;

  unlink($ShellSymlink);
  symlink($newState?$GoodShell:$BadShell, $ShellSymlink) ||
      return "Error creating shell symlink, go in and fix manually";
}

#
# get_telnet_server_open
#
# Check whether telnet access is open
# return 0 if only root can log in, 1 if any user can, -1 if the symlink
# doesn't exist!
#
sub get_telnet_server_open
{
  my $whichShell = readlink($ShellSymlink);
  return 1 if ($whichShell eq $GoodShell);
  return 0 if ($whichShell eq $BadShell);
  return -1;
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
