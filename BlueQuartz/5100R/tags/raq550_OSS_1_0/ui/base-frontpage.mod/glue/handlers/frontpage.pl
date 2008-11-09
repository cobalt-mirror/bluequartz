#!/usr/bin/perl -w -I. -I/usr/sausalito/perl/ -I/usr/sausalito/handlers/base/frontpage/
# $Id: frontpage.pl 259 2004-01-03 06:28:40Z shibuya $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.

use strict;
umask(002);

use CCE;
use Sauce::Validators qw( password );

my $cce = new CCE( Namespace => "Frontpage",
                   Domain    => "base-frontpage" );

my $homeGroup = 'home';
my $home_rootweb = '/usr/local/frontpage/we80.cnf';
my $htPassword = '/usr/bin/htpasswd';
my $home_basedir = '/home/groups/home';
my $home_htpwd = "$home_basedir/web/_vti_pvt/service.pwd"; 
my $fp_webmaster = 'webmaster';
my $httpd_conf = '/etc/httpd/conf/httpd.conf';
my $httpd_access = '/etc/httpd/conf/access.conf';
my $Fpx_admsrvexe = '/usr/local/frontpage/currentversion/bin/fpsrvadm.exe';
my $Lockdir = '/etc/locks';
my $Fpx_fpexec = '/usr/sbin/fpexec';

$cce->connectfd();

my $fpx = $cce->event_object();

if( fpx_update($cce, $fpx) ) {
        $cce->bye("SUCCESS");
        exit 0;
} else {
        $cce->bye("FAIL");
        exit 1;
}

sub fpx_update {
	my $cce = shift;
	my $fpx = shift;

	if( $fpx->{enabled} && fpx_get_homeweb() ) {
		fpx_set_homepasswordWebmaster( $fpx->{passwordWebmaster} ) || return 0; 
        } elsif( $fpx->{enabled} ) {
		fpx_enable_home( $fpx->{passwordWebmaster} ) || return 0;
	} else {
		fpx_disable_home() || return 0;
	}

	return 1;	
}

sub fpx_enable_home {
	my $passwordWebmaster = shift;

	my($user)='nobody';
	my $could_not_install;

	# Call fpadmsrv.exe (ugh)
#ROLLBACK SPECIAL
	system("$Fpx_admsrvexe -o install -p 80 -t apache-fp -m \"\" -u \"webmaster\" -pw \"$passwordWebmaster\" -s \"$httpd_conf\" -xu \"$user\" -xg \"$homeGroup\" >> /var/log/fpx.log") && ($could_not_install = $!);

	# Bail!
	if ($could_not_install) {
		$cce->warn('fpx_failed');
		return 0;
	}

	# Update Apache access levels (pooch security)
	fpx_change_access($home_basedir,1);

	# Add SMTP capability to FPX forms
      	if (-w $home_rootweb) {
        	my($orig_cnf);
		open(CNF, $home_rootweb);
		while(<CNF>) { $orig_cnf .= $_; }
		close(CNF);

		unless($orig_cnf =~ /smtphost/i) {
			Sauce::Util::modifyfile($home_rootweb);
			open(NUCNF,">> $home_rootweb");
			print NUCNF "SMTPHost: 127.0.0.1\n";
			close(NUCNF);
		}

        # Must be readable by nobody
        Sauce::Util::chmodfile(0644,$home_rootweb);
	}

	return 1;
}

sub fpx_disable_home {
#ROLLBACK SPECIAL
	system("/bin/rm $home_rootweb > /dev/null 2>&1; /bin/rm -rf `/usr/bin/find $home_basedir -name \"_vti_*\"` > /dev/null 2>&1");
	
	# fpadmsrv chown -R's web/ instead of web/* so we need to patch ownership of the web dir back to root
	Sauce::Util::chownfile( (getpwnam('root'))[2], (getgrnam('home'))[2], "$home_basedir/web");
	Sauce::Util::chmodfile(02775, "$home_basedir/web");

	return 1;
}

sub fpx_get_homeweb {
	return 1 if( -e $home_rootweb);
	return 0;
}

sub fpx_set_homepasswordWebmaster {
	my $passwordWebmaster = shift;

#ROLLBACK SPECIAL
	system("$htPassword -b $home_htpwd $fp_webmaster $passwordWebmaster > /dev/null 2>&1") && return 0;
	return 1;
} 
	
sub fpx_change_access {
	my( $basedir, $status ) = @_;

    	my ($inentry, $found_web, $conf);
    	my ($on,$ret);

	# watch locks
	sleep 1 if (-e '/etc/httpd/conf/access.conf');
	sleep 1 if (-e '/etc/httpd/conf/access.conf');
	sleep 1 if (-e '/etc/httpd/conf/access.conf');
	return 0 if (-e '/etc/httpd/conf/access.conf');

    	# We only AllowOverride All and Options All sites/groups that have frontpage enabled.
    	# You're only shooting yourself in the foot if you're into that sort of thing.

    	# Track first/last site FP usage to tweak systee
    	# security.  No need to have suid 0 unless we're using it
    	my($other_use) = 0;

    	# update the current access configuration
    	open(HTAX,$httpd_access) ||
        	return 0;

#ROLLBACK SPECIAL
    	open (HTCTMP, ">$Lockdir/access.conf") ||
        	return 0;

    	$found_web = $inentry = 0;
    	# fix the httpd.conf file
    	while (<HTAX>) {
		if (/^<Directory $basedir/ ... /^<\/Directory/) {
			$found_web = 1;
			print HTCTMP if ($status);
			next;
		} else {
			$other_use = 1 if (/AllowOverride All/);
            	}
        	print HTCTMP;
	}
	close (HTAX);

    
	if (!$found_web && $status) {
        	print HTCTMP <<EOF;
<Directory $basedir>
AllowOverride All
Options All
</Directory>
EOF
        }
	close (HTCTMP);

	Sauce::Util::renamefile("$Lockdir/access.conf", $httpd_access) ||
		return vsite_abort(MSG_error("4585"));
    
	# Enable/disable suid 0 access
	if ($status) {
		Sauce::Util::chmodfile(04755, $Fpx_fpexec);
	} elsif (!$other_use) {
		Sauce::Util::chmodfile(00755, $Fpx_fpexec);
        }
    
	return 1;
}
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
