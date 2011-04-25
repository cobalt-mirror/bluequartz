# MailMan.pm
# $Id: MailMan.pm,v 1.0.0-1 Sun 24 Apr 2011 07:03:56 PM CEST
# Copyright 2011 Team BlueOnyx. All rights reserved.
#
# common functions shared between MailMan handlers.  Fun fun fun.
#

package MailMan;

use lib qw( /usr/sausalito/perl );
use Sauce::Util;
use CCE;

# configuration stuff:
my $MailMan_user = 'mailman';
my $MailMan_group = 'mailman';
my $MailMan_uid = scalar (getpwnam($MailMan_user));
my $MailMan_gid = scalar (getgrnam($MailMan_group));

sub get_listfile {
  my $obj = shift;

  my $listfile = $obj->{name};
  $listfile = $obj->{name};

  return $listfile;
}

# my ($ok) = rewrite_members($obj)
# create subscriber list from the MailMan object
sub rewrite_members
{
  my $obj = shift;

  # extract information about the list...
  my $list = $obj->{name};

  # sorry about the ugly perl code here, it's efficient:
  my @local_recips = CCE->scalar_to_array($obj->{local_recips});
  my @remote_recips = CCE->scalar_to_array($obj->{remote_recips});
  my @remote_recips_digest = CCE->scalar_to_array($obj->{remote_recips_digest});
  my $listfile = &get_listfile($obj);

  $listname = $obj->{internal_name};
  $rand = int(rand(10000000));
  $memberlist = "/tmp/mailman-$listname-$rand";
  $memberlist_digest = "/tmp/mailman-$listname-digest-$rand";

  # lock the members list:
  Sauce::Util::lockfile($memberlist);

  # rewrite the members list:
  Sauce::Util::modifyfile("$memberlist");
  unlink($memberlist);
  system('rm', '-rf', $memberlist) if (-e $memberlist);
  open(LIST, ">$memberlist");

  my %members = map { $_ => 1 } (
	@local_recips, 
	@remote_recips
  );
  my @members = sort keys %members;
  if ($obj->{group}) {
	push (@members, $obj->{group} . "_alias");
  }
  if ($#members >= 0) {
	print LIST join("\n",@members),"\n";
  } else {
	print LIST "nobody\n"; # every club needs a member even nobody
  }
  close(LIST);

  # lock the members_digest list:
  Sauce::Util::lockfile($memberlist_digest);

  # rewrite the members_digest list:
  Sauce::Util::modifyfile("$memberlist_digest");
  unlink($memberlist_digest);
  system('rm', '-rf', $memberlist_digest) if (-e $memberlist_digest);
  open(LISTDIG, ">$memberlist_digest"); 
  
  my %members_digest = map { $_ => 1 } (
        @remote_recips_digest
  );
  my @members_digest = sort keys %members_digest;
  if ($obj->{group}) {
        push (@members_digest, $obj->{group} . "_alias");
  }
  if ($#members_digest >= 0) {
        print LISTDIG join("\n",@members_digest),"\n";
  } else {
        print LISTDIG "nobody\n"; # every club needs a member even nobody
  }
  close(LISTDIG);

  system("/usr/lib/mailman/bin/remove_members -a --nouserack $listname");
  system("/usr/lib/mailman/bin/add_members --welcome-msg=n --admin-notify=n -r $memberlist $listname");
  system("/usr/lib/mailman/bin/add_members --welcome-msg=n --admin-notify=n -d $memberlist_digest $listname");

  # unlock the members list:
  Sauce::Util::unlockfile($memberlist);
  system("/bin/rm -f $memberlist");
  Sauce::Util::unlockfile($memberlist_digest);
  system("/bin/rm -f $memberlist_digest");

}

# my ($ok) = rewrite_config($cce, $obj)
# create subscriber list from the MailMan object
sub rewrite_config
{
  my ($cce, $obj) = @_;
  my $ret = 1;
  my $fqdn = '';
  
  if ($obj->{site}) {
    my ($vs_oid) = $cce->find('Vsite', { 'name' => $obj->{site} });
    my ($ok, $vsite) = $cce->get($vs_oid);
    if (!$ok) {
      $cce->bye('FAIL', '[[base-mailman.systemError]]');
      exit(1);
    }
    $fqdn = $vsite->{fqdn};
    # Ok, this is potentially ugly:
    # We need to specify an email host that the list receives email at. Usually
    # we'd use the FQDN for that. But who wants list email addresses like
    # mylist@www.mysite.com?
    #
    # So we assume that if the hostname is www, then the user has set up an MX 
    # record that points www.mysite.com to mysite.com:
    
    if ($vsite->{hostname} eq "www") {
	$domain = $vsite->{domain};
    }
    else {
	# If the hostname is not "www", then we use the FQDN instead.
	# This should cover cases where there are sites which all
	# have the same domain name:
	$domain = $vsite->{fqdn};
    }
  }
  
  my $starttag = "#AUTOSTART";
  my $stoptag = "#AUTOSTOP";
  my $data;

  $listname = $obj->{internal_name};

  # Find all list administrators
  my $listadmins = `/usr/lib/mailman/bin/list_admins $listname`;
  $listadmins =~ s/List: $listname, 	Owners: //gi;
  $listadmins =~ s/No such list: $listname//gi;
  $listadmins =~ s/, /\n/gi;
  chomp($listadmins);
  $listadmins = `/bin/echo \"$listadmins\" | grep \"@\" | sort -u`;
  $listadmins =~ s/\n/',h '/gi;
  chomp($listadmins);

  # configure the list password:
  $password = $obj->{apassword};
  if (!$password) {
  	my @l = ('a' .. 'z', 'A' .. 'Z', '1' .. '9');
    my $l = sub { return int(rand($#l+1)); };
  	$password = join("",@l[(&$l(), &$l(), &$l(), &$l(), &$l(), &$l(), &$l(), &$l())]);
  }
  push (@data, "admin_passwd = $password");
  push (@data, "approve_passwd = $password");
  
  $mod = $obj->{moderator};

  system("/usr/lib/mailman/bin/newlist -q --urlhost=$fqdn --emailhost=$domain $listname $mod $password");

  system("/bin/ln -f -s /var/lib/mailman/archives/private/$listname /home/sites/$fqdn/mailman/private/$listname");
  system("/bin/ln -f -s /var/lib/mailman/archives/public/$listname /home/sites/$fqdn/mailman/public/$listname"); 

  open(CONFIG, ">/var/lib/mailman/lists/$listname.cfg");
  print CONFIG "# -*- python -*-\n";
  print CONFIG "# -*- coding: us-ascii -*-\n\n";
  print CONFIG "# Configuration for list $listname\n";
  print CONFIG "mlist.description = \"$obj->{description}\"\n";
  print CONFIG "mlist.web_page_url = \"http://$fqdn/mailman\"\n";
  print CONFIG "mlist.listinfo_url = \"http://$fqdn/mailman/listinfo\"\n\n";  
  print CONFIG "import sha\n\n";
  print CONFIG "# Set Password\n";
  print CONFIG "mlist.password = sha.new('$password').hexdigest()\n";
  print CONFIG "mlist.mod_password = sha.new('$password').hexdigest()\n\n";
  print CONFIG "del sha\n\n";
  print CONFIG "# Set owner and moderator\n";
  print CONFIG "owner = ['$mod', '$listadmins']\n";
  print CONFIG "moderator = ['$mod', '$listadmins']\n";
  print CONFIG "# Set misc settings\n";
  if($obj->{postPolicy} eq "any") {
    print CONFIG "generic_nonmember_action = 0\n";
    print CONFIG "default_member_moderation = 0\n";
  } elsif($obj->{postPolicy} eq "members") {
    print CONFIG "default_member_moderation = 0\n";
    print CONFIG "generic_nonmember_action = 2\n";
  } elsif($obj->{postPolicy} eq "moderated") {
    print CONFIG "default_member_moderation = 1\n";
    print CONFIG "member_moderation_action = 1\n";
    print CONFIG "member_moderation_notice = 'Sorry, subscribers cannot post to this list.'\n";
    print CONFIG "generic_nonmember_action = 1\n";
  }
  if($obj->{subPolicy} eq "open") {
    print CONFIG "subscribe_policy = 0\n";
  } elsif($obj->{subPolicy} eq "confirm") {
    print CONFIG "subscribe_policy = 1\n";                
  } elsif($obj->{subPolicy} eq "closed") {
    print CONFIG "subscribe_policy = 2\n";                
  } 
  print CONFIG "max_message_size = $obj->{maxlength}\n";
  print CONFIG "reply_goes_to_list = $obj->{replyToList}\n";
  print CONFIG "require_explicit_destination = 0\n";
  print CONFIG "host_name = $domain\n";
  close(CONFIG);

  system("/usr/lib/mailman/bin/config_list -i /var/lib/mailman/lists/$listname.cfg $listname");
  system("/usr/lib/mailman/bin/change_pw --listname=$listname --password=$password");

  my @contents;

  open(CONFIG, "/var/lib/mailman/lists/$listname.cfg");
  @CONFIG = <CONFIG>;
  close(CONFIG);

  for (@CONFIG) {
	s/$password/PASSWORD/;
	push(@contents,$_);
  }
  seek(CONFIG2,0,0);
  open(CONFIG2,">/var/lib/mailman/lists/$listname.cfg");
  print CONFIG2 (@contents);
  close(CONFIG2);

}

# munge the local_recips and moderator properties to make sure they
# are in the form user@fqdn
sub munge_members
{
	my ($cce, $mail_list) = @_;

	my $fqdn = '';
	if ($mail_list->{site})
	{
		my ($vs_oid) = $cce->find('Vsite', { 'name' => $mail_list->{site} });
		my ($ok, $vsite) = $cce->get($vs_oid);
		if (!$ok)
		{
			$cce->bye('FAIL', '[[base-mailman.systemError]]');
			exit(1);
		}
		$fqdn = $vsite->{fqdn};
	}
	else
	{
		my ($sys_oid) = $cce->find('System');
		my ($ok, $sys) = $cce->get($sys_oid);
		if (!$ok)
		{
			$cce->bye('FAIL', '[[base-mailman.systemError]]');
			exit(1);
		}
	
		$fqdn = $sys->{hostname} . '.' . $sys->{domainname};
	}
	
	my @locals = $cce->scalar_to_array($mail_list->{local_recips});
	# munge the moderator too
	push @locals, $mail_list->{moderator};

	for (my $i = 0; $i < scalar(@locals); $i++)
	{
		# skip blank entries
		if ($locals[$i] =~ /^\s*$/) { next; }
	
		# check to see if it is already correct
		if ($locals[$i] =~ /\@/) { next; }
	
		$locals[$i] .= "\@$fqdn";
	}
	
	$mail_list->{moderator} = pop(@locals);
	$mail_list->{local_recips} = $cce->array_to_scalar(@locals);
}

1;
