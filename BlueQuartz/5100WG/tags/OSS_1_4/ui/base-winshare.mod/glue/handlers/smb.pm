#!/usr/bin/perl -I. -I/usr/sausalito/perl

package smb;

use Sauce::Config;

sub smb_getscript
{
    return '/etc/rc.d/init.d/smb';
}

sub smb_getconf
{
    return '/etc/samba/smb.conf';
}

sub smb_getpasswd
{
    return '/etc/samba/smbpasswd';
}

sub share_comment
{
    my ($when, $group) = @_;

    return ";;$group $when -- do not edit this line";
}

sub share_block
{
    my ($group, $guestok) = @_;
    my $groupdir = Sauce::Config::groupdir_base;
    my $gueststr;
    my $validusers;
    unless ($guestok) {
       $gueststr = ';;';
       $validusers = "valid users = admin \@$group";
    }

    my $string = <<END;
[$group]
available = 1
comment = share for $group
$validusers
create mask = 664
directory mask = 775
path = $groupdir/$group
writeable = yes
${gueststr}guest ok = true
;
END

   return $string;
}

# edit smb.conf. this wipes out any current block. it adds in a new
# one if desired.
sub edit_group
{
    my ($input, $output, $ogroup, $ngroup) = @_;
    my ($inblock, $guestok);
    my $invalid_groups = '(?:homes|printers)';

    return 0 if ($ngroup =~ /$invalid_groups/) or 
		 ($ogroup =~ /$invalid_groups/);

    my $end = share_comment('end', $ogroup);
    my $begin = share_comment('begin', $ogroup);
    $begin =~ /^$begin/;
    $end =~ /^$end/;
    while (<$input>) {
	if ($inblock) {
	    if ($_ =~ /^guest ok/) {
	        $guestok = 1;
	    } elsif ($_ =~ $end) {
		$inblock = 0;
	    }
	    next;
	}

	# look for for the beginning of the appropriate block 
	if ($_ =~ $begin) {
	    $inblock = 1;
	    next;
	}
	print $output $_;
    }

    if ($ngroup) {
	print $output share_comment('begin', $ngroup) . "\n";
	print $output share_block($ngroup, $guestok);
	print $output share_comment('end', $ngroup) . "\n";
	
    }

    return 1;
}

# edit smbpass. this wipes out any current block. it adds in a new
# one if desired.
sub edit_smbpass
{
    my ($input, $output, $user, $uid, 
	$passwd, $descr, $shell, $ouser) = @_;
    my $smbhash = '/usr/sbin/gethash';

    # get rid of old password
    while ($_ = <$input>) {
	next if /^$ouser:/;
	print $output $_;
    }

    my $hash;
    if ($user) {
	$ENV{'SMBPASSWD'} = $passwd;
	$hash = `$smbhash`;
	$ENV{'SMBPASSWD'} = undef;

    	my $homedir = Sauce::Config::homedir_base . '/' . $user;

	chomp($hash);
	$hash ||= 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX:XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX';

        my $lct = "LCT-" . sprintf("%X", time());
	print $output "$user:$uid:$hash:[U          ]:$lct:\n";
    }

    # fallback to the system smbpasswd utility
    return -1 if ($hash =~ /^X+:X+$/);
    
    return 1;
}

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

sub edit_guest 
{
    my ($input, $output, $old, $new, $enabled) = @_;
    $new="" unless defined $new;
    $old="" unless defined $old;
    
    # we're not sure when we get called, so search for both the 
    # old and new share names
    while (<$input>) {
	print $output $_;
	next unless /^\[$old\]/ or /^\[$new\]/;
	last;
    }

    while (<$input>) {
	next if (/^valid users =/);
	if (/guest ok/) {
	    print $output ';;' unless $enabled;
	    print $output "guest ok = true\n";
	    next;
	}
	print $output $_;
	last if (/\[/);
    }

    while (<$input>) {
	print $output $_;
    }

    return 1;

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
