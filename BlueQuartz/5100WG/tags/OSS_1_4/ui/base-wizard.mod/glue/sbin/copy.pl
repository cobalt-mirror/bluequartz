#!/usr/bin/perl	-w

use File::Copy;

my $splash_dir = '/usr/sausalito/ui/web/base/workgroup';
my $home_dir = '/home/groups/home/web';

$ARGV[0] ||= '';
if($ARGV[0] =~ /splash/) {
	my $locale;
	open(LOCALE, "/etc/cobalt/locale");
	chomp($locale = <LOCALE>);
	close(LOCALE);
	
	my $locale_fallback = $locale;
	$locale_fallback ||= 'en';
	my $page = "$splash_dir/default_home.html.$locale_fallback";
	if(-r $page) {
		my $target = $home_dir.'/index.html';
		copy($page, $target);
		chown( (getpwnam('admin'))[2], (getgrnam('home'))[2], $target );
		chmod(0664, $target);
	}

	my $page = "/etc/skel/group/$locale/web/index.html";
	if($locale && (-r $page)) {
		my $target = '/home/groups/guest-share/web/index.html';
		copy($page, $target);
		chown( (getpwnam('admin'))[2], (getgrnam('guest-share'))[2], $target );
		chmod(0664, $target);
	}
	if($locale && (-r $page)) {
		my $target = '/home/groups/restore/web/index.html';
		copy($page, $target);
		chown( (getpwnam('admin'))[2], (getgrnam('restore'))[2], $target );
		chmod(0664, $target);
	}
	$page = "/etc/skel/user/$locale/web/index.html";
	if($locale && (-r $page)) {
		my $target = '/home/users/admin/web/index.html';
		copy($page, $target);
		chown( (getpwnam('admin'))[2], (getgrnam('users'))[2], $target );
		chmod(0644, $target);
	}

	# the wrong place for the wrong fix 
	system('/bin/rm -rf /home/groups/guest-share/user') if (-d '/home/groups/guest-share/user/en');
	system('/bin/rm -rf /home/groups/guest-share/group') if (-d '/home/groups/guest-share/group/en');

} elsif(@ARGV < 2) {
	print STDERR "Usage:  $0 <filename> <destination filename>\n";
	exit(1);
} else {

	my $file = shift @ARGV;
	my $dest = shift @ARGV;

	copy($file, $dest);

}
exit(0);

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
