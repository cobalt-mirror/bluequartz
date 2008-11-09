#!/usr/bin/perl -I. -I/usr/sausalito/perl
#
#
use Sauce::Service;
use Sauce::Config;
use Sauce::Util;
use CCE;

my $smb_conf = '/etc/samba/smb.conf';

sub edit_global
{
    my ($input, $output, %settings) = @_;
    my ($inblock, $key, $keys);

    while ($_ = <$input>) {
	# skip comments 
	if (/^\s*[;\#]/o) {
	    print $output $_;
	    next;
	}

	if (/\[global\]/) {
		print $output $_;
		foreach $key (keys %settings) {
		    print $output "   $key = $settings{$key}\n" 
			if $settings{$key};
		    delete $settings{$key};
		    $keys .= ":$key:";
		} 
		$inblock = 1;
		next;
	} elsif ($inblock and /^\[/) {
		$inblock = 0;
	}

	if (($inblock) and /\s*([\S\s]+) =/) {
	    next if $keys =~ /:$1:/;
	}
	print $output $_;
    }
    return 1;
}

#sub edit_rc
#{
#    my ($input, $output, $enabled) = @_;
#    
#    while (<$input>) {
#	# skip comments
#	if (/^\s*[\#]/o) {
#	    print $output $_;
#	    next;
#	}
#
#	if (/^[\s]*NMB_ENABLED=/) {
#	    print $output "NMB_ENABLED=$enabled\n";
#	    next;
#	}
#
#	print $output $_;
#    }
#    return 0;
#}


my $cce = new CCE(Namespace => 'WinNetwork');
$cce->connectfd(\*STDIN, \*STDOUT);

my $obj = $cce->event_object();

my (%settings);

# workgroup setting
$settings{'workgroup'} = $obj->{workgroup};

# domain logons
$settings{'domain logons'} = ($obj->{domainLogon}) ? 
    'yes' : 'no';
$settings{'domain master'} = ($obj->{domainLogon}) ?
    'yes' : 'no';

# WINS setting
my $wins = $obj->{winsSetting};

$settings{'wins support'} = 'no';
$settings{'wins server'} = '';

if ($wins eq 'self') {
    $settings{'wins support'} = 'yes';

} elsif ($wins eq 'dhcp') {
    $cce->warn('[[base-winnetwork.dhcpUnsupported]]');
    $cce->bye('FAIL', '[[base-winnetwork.dhcpUnsupported]]');
    exit 1;

} elsif ($wins eq 'others') {
    if (not $obj->{winsIpAddress}) {
	$cce->warn('[[base-winnetwork.missingWinsIP]]');	
	$cce->bye('FAIL', '[[base-winnetwork.missingWinsIP]]');
	exit 1;
    }
    $settings{'wins server'} = $obj->{winsIpAddress};
}

if (%settings) {
    my $err = Sauce::Util::editfile($smb_conf, *edit_global,
				    %settings);
    if (!$err) {
	$cce->warn('[[base-winnetwork.cantConfigFile]]');
	$cce->bye('FAIL', '[[base-winnetwork.cantConfigFile]]');
	exit 1;
    }
}

#if ($obj->{enabled}) {
#    my $err = Sauce::Util::editfile($rc_script, *edit_rc, 
#				    $obj->{enabled} ? 'yes' : 'no');
#    if (!$err) {
#	$cce->warn('[[base-winnetwork.cantToggleSetting]]');
#	$cce->bye('FAIL', '[[base-winnetwork.cantToggleSetting]]');
#	exit 1;
#    }
#}

# restart samba if it's running.
#Sauce::Service::service_run_init('smb', 'restart') 
#    if Sauce::Service::service_get_init('smb');

if($obj->{enabled}){
	`/etc/rc.d/init.d/smb restart`;
}

$cce->bye('SUCCESS');
exit 0;
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
