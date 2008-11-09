#
# $Id: Modem.pm 3 2003-07-17 15:19:15Z will $
#

package Modem;

use lib qw( /usr/sausalito/perl );
use Sauce::Util;
use Net::Ping;

use strict;

my $PppCfg = "/etc/sysconfig/network-scripts/ifcfg-ppp0";
my $Chat_script   = "/etc/sysconfig/network-scripts/chat-ppp0";
my $Pap_Secrets = "/etc/ppp/pap-secrets";
my $Chap_Secrets = "/etc/ppp/chap-secrets";
my $PppWindower = "/usr/sausalito/handlers/base/modem/pppwindower.sh";
my $PppWindowLink = "/etc/cron.hourly/pppwindower.sh";

sub ppp_cfg { $PppCfg; }
sub chatscript { $Chat_script; }

sub ppp0_free {
	my $pidfile = '/var/run/ppp0.pid';

	# check if pppd is running already
	if ( -f $pidfile ) {
		my $pid = `/bin/cat $pidfile`;

		if (`/usr/bin/pstree $pid` =~ /^pppd/) {
			# pppoe must be running
			return 0;
		}
	}

	return 1;
}

sub test_modem {
	my $modem = shift;

	# modify the init string entered by user to ensure echoing is on for thetest
	$modem->{initStr} =~ /^AT(.*)$/;
	my $init_string = 'ATE1' . $1;

	my $cmd = 	'/usr/sbin/pppd ' .
			"$modem->{port} 115200 nodetach lcp-max-configure 1 connect " .
			"\"/usr/sbin/chat -v TIMEOUT 5 \'\' \'$init_string\' OK\" " .
			"&>/dev/null";

	my $ret = system($cmd);
	
	if (($ret/256) == 8) {
		return 0;
	} else {
		return 1;
	}
}
	
sub set_modem_cfg {
	my $cce = shift;
	my $modem = shift;

	my $conf_file;
	my $boot = ($modem->{connMode} eq 'off') ? "no" : "yes";

	$conf_file .=
		"PERSIST=yes\n".
		"DEVICE=ppp0\n".
		"ONBOOT=$boot\n".
		"LINESPEED=$modem->{speed}\n".
		"DEBUG=yes\n".
		"ESCAPECHARS=yes\n".
		"DEFROUTE=yes\n".
		"REMIP=0.0.0.0\n".
		"COMPRESSION=\"noaccomp nobsdcomp noccp nodeflate nopcomp nopredictor1 novj novjccomp\"\n".
		"PPPOPTIONS=\"\$PPPOPTIONS \$COMPRESSION noauth\"\n".
		"USERCTL=no\n";

	if ( defined ( $modem->{localIp} ) ) {
		$conf_file .= "IPADDR=".$modem->{localIp}."\n";
	} else {
		$conf_file .= "IPADDR=0.0.0.0\n";
	}
		

	$conf_file .= "MTU=". $modem->{mtu} . "\n";
	$conf_file .= "MRU=". $modem->{mru} . "\n";
	$conf_file .= "MODEMPORT=/dev/". $modem->{port} . "\n";
	$conf_file .= "PAPNAME=" . $modem->{userName} . "\n";

	
	if ( $modem->{connMode} eq 'demand' ) {
		$conf_file .= "PPPOPTIONS=\"\$PPPOPTIONS demand idle $modem->{idle} holdoff 10\"\n";
	}

	return Sauce::Util::replaceblock($PppCfg, "#Cobalt Begin -- Do not remove this line.", $conf_file, '#Cobalt End -- Do not remove this line. Add any customizations below.');
}



sub set_modem
{
	my($cce,$modem) = @_;

	# get new values so password doesn't get lost
	my $new = $cce->event_new();

	set_modem_cfg($cce, $modem) ||
		return 0;

	edit_chatscript($cce, $modem->{initStr},$modem->{phone},$modem->{pulse})||
		return 0;

	
	if ($new->{userName} || $new->{password} || $new->{serverName}) {
		Sauce::Util::editfile( $Pap_Secrets, *edit_pap_secrets,
					$modem->{userName}, $modem->{password} )
			|| return 0;

        	Sauce::Util::editfile( $Chap_Secrets, *edit_chap_secrets, 
                	                $modem->{userName}, $modem->{password}, $modem->{serverName} )
                	|| return 0;
	}

	return set_modem_on( $cce, $modem );
}

sub edit_pap_secrets {
	
	my $in = shift;
	my $out = shift;
	my $username = shift;
	my $password = shift;
	if($password =~ /\s/) {
		$password = '"'.$password.'"';
	}

	while(<$in>) {
		if (not(/start base-modem/)) {
			print $out $_;
		} else {
			last;
		}
	}

	print $out "# start base-modem.mod. Do not edit. Do not remove this line.\n";

	# escape data
	$username = escape_me($username);
	$password = escape_me($password);
	
	# read next line to preserve password if a new one was not given
	$_ = <$in>;
	s/(\".*)\s+(.*\")/$1C_C$2/g; # obliterate spaces in passwords
	
	my @fields = split /\s+/, $_;
			
	if ($fields[1] eq '*') {
		$fields[0] = "\"$username\"";
		if ($password ne "") {
			$fields[2] = "\"$password\"";
		}

		my $new = join(' ', @fields)."\n";
		$new =~ s/C_C/ /g;

		print $out $new;
	} else {
		my $new = "\"$username\" * \"$password\"\n";
		$new =~ s/C_C/ /g;
		print $out $new;
	}

	print $out "# end base-modem.mod. Do not edit. Do not remove this line\n";

	# print out rest of file if there is any
	while(<$in>) {
		if(not(/end base-modem/)) {
			print $out $_;
		}
	}

	return 1;
}

sub edit_chap_secrets {

	# chap-secrets format: client server secret
        
	my $in = shift;
	my $out = shift;
	my $username = shift;
        my $password = shift;
        my $server = shift;
	$server ||= '*';
        
	while(<$in>) {
                if (not(/start base-modem/)) {
                        print $out $_;
                } else {
                        last;
                }
        }

        print $out "# start base-modem.mod. Do not edit. Do not remove this line.\n";

	# escape all new data
	$username = escape_me($username);
	$password = escape_me($password);
	$server   = escape_me($server);

        # read next line to preserve password if a new one was not given
        $_ = <$in>;
	s/(\".*)\s+(.*\")/$1C_C$2/g; # obliterate spaces in passwords

        my @fields = split /\s+/, $_;

        if ($fields[0] eq '*') {
                $fields[1] = "\"$server\"";
                if ($password ne "") {
                        $fields[2] = '"'.$password.'"';
                }

		my $new = join(' ', @fields)."\n";
		$new =~ s/C_C/ /g;
                print $out $new;
        } else {
		my $new = "* \"$server\" \"$password\"\n";
		$new =~ s/C_C/ /g;
		print $out $new;
        }

	print $out "# end base-modem.mod. Do not edit. Do not remove this line\n";
  	
	# discard any extra lines between our tags
	while(<$in> && not(/end base-modem/)) { }

	# print out rest of file
	while(<$in>){
		print $out $_;
	}

	return 1;
}

sub escape_me {
	my $foo = shift;
	$foo =~ s/\\/\\\\/g;
	$foo =~ s/\n/\\n/g;
	$foo =~ s/"/\\"/g;
	return $foo;
}

sub edit_chatscript {
	my $cce = shift;
	my $initStr = shift;
	my $phone = shift;
	my $pulse = shift;

	# always remove whitespace from phone number
	$phone =~ s/\s//g;

	my $output;

	$output = "TIMEOUT 5\n";

	if ( $initStr ne "" ) {
		# init string needs to be surrounded by quotes, so shell
		# doesn't interpet meta-chars and spaces get sent as spaces
		$output .= "\"\" \'$initStr\'\n";
	}

	if( $pulse ) {
		$output .= "OK 'ATDP$phone'\n";
	} else {
		$output .= "OK 'ATDT$phone'\n";
	}
	$output .= "ABORT \"NO CARRIER\"\n";
	$output .= "ABORT BUSY\n";
	$output .= "ABORT \"NO DIALTONE\"\n";
	$output .= "ABORT \"NO DIAL TONE\"\n";
	$output .= "ABORT WAITING\n";
	$output .= "TIMEOUT 60\n";
	$output .= "CONNECT \"\"\n";

	

	return Sauce::Util::replaceblock($Chat_script,"# base-modem start -- do not remove this line", $output, '# base-modem stop -- do not remove this line')
}

sub set_modem_on {
	my $cce = shift;
	my $modem = shift;
	my $old_modem = $cce->event_old();
	my $new_modem = $cce->event_new();

	# only stop ppp if our old connMode was not off, and our connMode has changed 
	if (($old_modem->{connMode} ne 'off') && $new_modem->{connMode}) {
		my $ret = 0;

		# have to kill the connection this way because in always on mode ifdown ppp0
		# will only work if the link is up
		$ret += system("/usr/bin/killall ifup-ppp &>/dev/null");
		$ret += system("/usr/bin/killall chat &>/dev/null");
		$ret += system("/usr/bin/killall pppd &>/dev/null");

		unlink($PppWindowLink);

		if( $ret % 256 != 0 ) {
			$cce->warn("couldntStopPpp");
		}
	}

	if ( $modem->{connMode} ne 'off' ) {

		# check to make sure ppp0 is not in use if we were'nt using it previously
		if (($old_modem->{connMode} eq 'off') && not &ppp0_free()) {
			$cce->warn("ppp0_in_use");
			return 0;
		}
	
		# test to see if modem is connected and working
		# ok don't fail on this, but don't start connection and set mode to off
		# otherwise ppp will start even though the modem isn't working and rebooting
		# will not help the situation
		if (not test_modem($modem)) {
                	$cce->warn("modemProblem");
		
			my ($ok) = $cce->set($cce->event_oid(), "Modem", { 'connMode' => 'off' });

			if (not $ok) {
				# something very bad has happened
				$cce->bye('FAIL');
				exit(1);
			}
		} elsif( system('/sbin/ifup ppp0 > /dev/null') ) {
			$cce->warn("couldntStartPpp");
			return 0;
		}

		symlink($PppWindower, $PppWindowLink);
	}
	return 1;
}

# tries to ping a remote host to see if it is reachable
# test_net($device[, $ping_protocol, $timeout ])
# $device is the ppp interface you want to test the network connection for (ie ppp0)
# $ping_protocol is tcp, udp, icmp. icmp is the default protocol
# $timeout sets the timeout value. default timeout is 5
#
sub test_net {
	my $device = shift;
	my $prot = 'icmp';
	my $timeout = 5;

	if(@_) {
		if ($_[0] =~ /tcp|udp|icmp/) {
			$prot = shift;
		} else {
			# unknown protocol
			return -1;
		}
	}

	if(@_) {
		if ($_[0] > 0) {
			$timeout = shift;
		} else {
			# invalid timeout
			return -2;
		}
	}

	my $remote_host = &find_ptp($device);

	my $ping = Net::Ping->new($prot);

	my $status = 0;

	# try to ping remote_host
	if ($ping->ping($remote_host, $timeout)) {
		$status = 1;
	}

	$ping->close();
	
	return $status;
}

sub find_ptp {
        my $device = shift;

        my $if_info = `/sbin/ifconfig $device`;

        $if_info =~ /\sp\-t\-p\:([\d\.]+)\s/i;

        return $1;
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
