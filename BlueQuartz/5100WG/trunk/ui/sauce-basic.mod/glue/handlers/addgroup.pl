#!/usr/bin/perl -w -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/sauce-basic

use CCE;
#use Workgroup;
use Sauce::Util;
use I18n;

my $i18n = new I18n;

my $PERM_HOME_GROUPS = 0755;
my $PERM_HOME_GROUPS_GROUP = 02771;
my $PERM_HOME_GROUPS_GROUP_WEB = 02775;

my $cce = new CCE;
$cce->connectfd(\*STDIN,\*STDOUT);

my $oid = $cce->event_oid();
my $obj = $cce->event_object();
my $new = $cce->event_new();

print STDERR "$0: creating group ",$obj->{name},"\n";

my $members = $obj->{members} || "";
my @members = CCE->scalar_to_array($members);
$members = join(",", @members);

# Workgroup::validate($cce);

my $name = $obj->{name};
my $fun = sub {
	my ($fin, $fout) = (shift, shift);
	my %used = ();
	my $error = 0;
	while ($_ = <$fin>)
	{
		if (m/^([^:]+):x:(\d+):/) {
			if ($1 eq $name) { 
				$cce->baddata(0,'name', '[[base-workgroup.groupNameAlreadyTaken]]');
				$error++;
			};
			$used{$2} = $1;
		}
		print $fout $_;
	}
	my $gid = 500;
	while (defined($used{$gid})) { $gid++; }
	if (!$error) {
		print $fout $obj->{name},":x:",$gid,":",$members,"\n";
		return 1;
	} else {
		return 0; # failure
	}
};
my $ret = Sauce::Util::editfile("/etc/group", $fun);
chmod(0644, '/etc/group');

if (!$ret) {
	$cce->bye('FAIL');
	exit 1;
} 

my $groupdir = '/home/groups/' . $obj->{name};

if (! -d "/home/groups") {
	mkdir("/home/groups", $PERM_HOME_GROUPS);
	chown((getpwnam('admin'))[2], 
		(getgrnam('admin'))[2], "/home/groups");
}
chmod($PERM_HOME_GROUPS,'/home/groups'); # just to be safe.

mkdir("/home/groups/".$obj->{name}, $PERM_HOME_GROUPS_GROUP);
chown( (getpwnam('admin'))[2], (getgrnam($obj->{name}))[2], "/home/groups/".$obj->{name});
chmod($PERM_HOME_GROUPS_GROUP,"/home/groups/".$obj->{name});

mkdir("/home/groups/".$obj->{name}."/web", $PERM_HOME_GROUPS_GROUP_WEB);
chown( (getpwnam('admin'))[2], (getgrnam($obj->{name}))[2], "/home/groups/".$obj->{name}."/web");
chmod($PERM_HOME_GROUPS_GROUP_WEB,"/home/groups/".$obj->{name}."/web");

# for beta to make sure groups have a default index.html
# mostly copied from handle_user.pl creation of default user index.html

# check to see if there is already and index.html file, if not, create one 
my $target_filename = "${groupdir}/web/index.html";
if (-e $target_filename && $obj->{name} eq 'home') {
	$target_filename = "/tmp/wizard80"; # ugly setup wizard hack
}

my $skelindex;

if(not -e $target_filename){
	print STDERR "Creating default home page for $obj->{name}...\n";
	$skelindex = '/etc/skel/group/'.I18n::i18n_getSystemLocale().'/web/index.html';
	if ($obj->{name} eq 'home') {
	  $skelindex = 0;
	  
	  foreach(`ls -1 /usr/sausalito/ui/web/base/workgroup/default_home*`){
		chomp;
		/\.([^\.]+)$/;
		my $lang=$1;
		system("cp $_ ${groupdir}/web/index.html.$lang");
		chown( (getpwnam('admin'))[2], (getgrnam($obj->{name}))[2], "${groupdir}/web/index.html.$lang"); 
		chmod (0664, "${groupdir}/web/index.html.$lang");
	  }

	  system("/bin/cp /usr/sausalito/ui/web/base/workgroup/splashDefaultWeb.jpg ${groupdir}/web/.");
        chown( (getpwnam('admin'))[2], (getgrnam($obj->{name}))[2],
		"${groupdir}/web/splashDefaultWeb.jpg");
	chmod 0664, "${groupdir}/web/splashDefaultWeb.jpg";

	}
if($skelindex){
	if(-e $skelindex) {
		print STDERR "Using skeleton page...\n";
		my $fin = new FileHandle("<$skelindex");
      		my $fout = new FileHandle(">$target_filename");
      		if ($fin && $fout) {
			my $fullname = $obj->{name};
        		while (defined($_ = <$fin>)) {
          			s/\[GROUPNAME\]/$fullname/g;
          			print $fout $_;
        		}
        		$fin->close();
        		$fout->close();
      		}
	} else {
		print STDERR "Creating from scratch...\n";
    		open(INDEX, ">$target_filename");
      		print INDEX "<HTML><BODY>Default group home page.</BODY></HTML>";
      		close(INDEX);
	}
	chmod (0644, $target_filename);
}
	print STDERR "Done creating default group home page.\n\n";
}

# always make sure proper ownerships and permissions are set
if($skelindex){
	chown( (getpwnam('admin'))[2], (getgrnam($obj->{name}))[2], "${groupdir}/web/index.html");   
	chmod (0664, "${groupdir}/web/index.html");
}

$cce->bye('SUCCESS');
exit 0;

# done.
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
