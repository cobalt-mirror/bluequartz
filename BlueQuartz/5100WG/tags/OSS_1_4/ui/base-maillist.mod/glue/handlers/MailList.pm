# MailList.pm
# $Id: MailList.pm 3 2003-07-17 15:19:15Z will $
#
# common functions shared between MailList handlers.  Fun fun fun.
#
# FIXME: Replace Majordomo with something that isn't such a hack.

package MailList;

use lib qw( /usr/sausalito/perl );
use Sauce::Util;
use FileHandle;
use CCE;

# configuration stuff:
my $Majordomo_dir = '/usr/local/majordomo';
my $Lists_dir = $Majordomo_dir . '/lists';
my $Majordomo_user = 'mail';
my $Majordomo_group = 'daemon';
my $Majordomo_uid = scalar (getpwnam($Majordomo_user));
my $Majordomo_gid = scalar (getgrnam($Majordomo_group));

sub get_listfile {
  my $obj = shift;
  return ${Lists_dir} . '/' . $obj->{name};
}

# my ($ok) = rewrite_members($obj)
# create subscriber list from the MailList object
sub rewrite_members
{
  my $obj = shift;

  # extract information about the list...
  my $list = $obj->{name};
  # sorry about the ugly perl code here, it's efficient:
  my @local_recips = CCE->scalar_to_array($obj->{local_recips});
  my @remote_recips = CCE->scalar_to_array($obj->{remote_recips});
  my $listfile = ${Lists_dir} . '/' . $list;

  # lock the members list:
  Sauce::Util::lockfile($listfile);

  # rewrite the members list:
  my $fh = new FileHandle(">$listfile");
  my %members = map { $_ => 1 } (
	@local_recips, 
	@remote_recips,
  );
  my @members = sort keys %members;
  if ($obj->{group}) {
	push (@members, $obj->{group} . "_alias");
  }
  if ($#members >= 0) {
	  print $fh join("\n",@members),"\n";
  } else {
  	print $fh "nobody\n"; # every club needs a member! even a loser like nobody
  }
  $fh->close();

  chmod 0664, $listfile;
  chown $Majordomo_uid, $Majordomo_gid, $listfile;

  # unlock the members list:
  Sauce::Util::unlockfile($listfile);
}

# my ($ok) = rewrite_config($obj)
# create subscriber list from the MailList object
sub rewrite_config
{
  my $ret = 1;
  my $obj = shift;

  my $starttag = "#AUTOSTART";
  my $stoptag = "#AUTOSTOP";
  my $data;
  
  # configure the list password:
  $password = $obj->{apassword};
  if (!$password) {
  	my @l = ('a' .. 'z', 'A' .. 'Z', '1' .. '9');
    my $l = sub { return int(rand($#l+1)); };
  	$password = join("",@l[(&$l(), &$l(), &$l(), &$l(), &$l(), &$l(), &$l(), &$l())]);
  }
  push (@data, "admin_passwd = $password");
  push (@data, "approve_passwd = $password");
  
  # configure some standard stuff
  push (@data, 
    "advertise << END\n/.*/\nEND", # always advertise
    "noadvertise << END\nEND", # never don't advertise
    "who_access = list", # only list members may run 'who'
    "subject_prefix = [\$LIST] ", # prefix listname to subject
    "message_headers << END\nX-Majordomo-Version: \$VERSION\nEND",
  );
  
  # configure subscription policy
  {
    my $policy = "auto+confirm"; # default
    if ($obj->{subPolicy} =~ m/closed/) {
  	  $policy = 'closed';
    }
    if ($obj->{subPolicy} =~ m/open/) {
  	  $policy = 'open';
    }
    if ($obj->{subPolicy} =~ m/confirm/) {
  	  $policy = 'auto+confirm';
    }
    push (@data, "subscribe_policy = $policy");
  }
  
  # configure unsubscription policy
  push (@data, "unsubscribe_policy = open");

  # configure posting policy
  $_ = $obj->{postPolicy};
  if (m/moderated/) {
    my $mod = $obj->{moderator} || 'admin';
    push (@data,
      "moderate = yes",
      "moderator = $mod",
      );
  }
  elsif (m/any/) {
  	# do nothing
	push (@data, "restrict_post = "); # majordomo needs this. :-b
  }  
  else {
  	# policy = members
	my $str = "restrict_post = $obj->{name}:$obj->{name}.administrator";
	if ($obj->{group}) {
	  $str .= ':/etc/group.d/' . $obj->{group};
	}
    	push (@data, $str);
  }

  # enable mungedomain
  push(@data, "mungedomain = yes");

  # maxlength
  if ($obj->{maxlength}) {
	push (@data, "maxlength = ".$obj->{maxlength});
  }

  if ($obj->{replyToList}) {
	# do nothing, it should reply to the mailing list automatically.
  } else {
	push (@data, "reply_to = \$SENDER");
  }

  # fix the stupid majordome dir permissions cause the rpm doesn't
  chown($Majordomo_uid, $Majordomo_gid, "/usr/local/majordomo");
  chmod(0700, "/usr/local/majordomo"); # else majordomo no workee.
  
  # edit the file:
  my $name = $obj->{name};
  mkdir("/usr/local/majordomo/lists",0700);
  chmod(0700, "/usr/local/majordomo/lists");
  # this is a cheap trick, but it works and keeps the evil warnings away
  system ("/bin/touch", "${Lists_dir}/${name}.config");
  chown($Majordomo_uid, $Majordomo_gid, "${Lists_dir}/${name}.config");
  chmod(0660, "${Lists_dir}/${name}.config");
  $ret = Sauce::Util::replaceblock(
	"${Lists_dir}/${name}.config",
  	$starttag, join("\n",@data), $stoptag);

  # update the admin file
  {
  	my $fn = "${Lists_dir}/${name}.administrator";
	my $mod = $obj->{moderator} || "";
	Sauce::Util::editfile( $fn, 
		sub { 
			my ($fin, $fout) = (shift, shift);
			print $fout $mod,"\n"; 
			print $fout "root\n";
		} );
	chown($Majordomo_uid, $Majordomo_gid, $fn);
	chmod(0660, $fn);
  };

  return $ret;
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
