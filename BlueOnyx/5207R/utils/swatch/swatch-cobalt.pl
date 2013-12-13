#!/usr/bin/perl -I/usr/sausalito/perl
# Copyright (c) Turbolinux, inc.
# Modified by Michael Stauber <mstauber@solarspeed.net> 

use strict;
use CCE;
use Getopt::Long;
use I18n;
use SendEmail;
use Sys::Hostname;
use POSIX qw(isalpha);
use MIME::Lite;
use Encode::Encoder;

my $host = hostname();
my $now = localtime time;

###
# Fix for /etc/mtab issue when Bind is running chrooted in a VPS:
my $mtabissues = `cat /etc/mtab|grep deleted -c`;
if ($mtabissues =~ /^0(.*)$/) {
}
else {
        system("cat /etc/mtab |grep -v 'deleted' > /etc/mtab.new");
        system("mv /etc/mtab.new /etc/mtab");
}
###

my @statecodes = ("N", "G", "Y", "R"),
my %params;
&GetOptions("conf|c=s"  => \$params{'conf'});

my $conf = $params{'conf'};

open(CONF, "< $conf");

my @email_list;
my $body = "";

my $lang = "en";
my $enabled = "true";

while (<CONF>) {
  chomp;
  my($key, $val) = split /\s*=\s*/, $_, 2;
  if ($key eq "email_list") {
    @email_list = split /\s*,\s*/, $val;
  } elsif ($key eq "lang") {
    $lang = $val;
  } elsif ($key eq "enabled") {
    $enabled = $val;
  }
}

if ($enabled eq "false") {
  exit 0;
}

close(CONF);

my $object;
my $i18n = new I18n();

my $DEBUG_ME = 0;

my $cce = new CCE;
$cce->connectuds();

my @sysoid = $cce->find ('System');
my ($ok, $sysobj) = $cce->get($sysoid[0]);
my $system_lang = $sysobj->{productLanguage};
my $platform = $sysobj->{productBuild};

# We can't email in Japanese yet, as MIME:Lite alone doesn't support it. We'd need MIME::Lite:TT:Japanese
# and a hell of a lot of dependencies to sort that out. So for now we hard code them to 'en_US' or 'en'
# for emailing purpose from within this script:

if (($system_lang eq "ja") || ($system_lang eq "ja_JP")) {
  $i18n->setLocale("en_US");

}
else {
  $i18n->setLocale($system_lang);
}

#system("export LANGUAGE=$system_lang.UTF-8");
#system("export LANG=$system_lang.UTF-8");
#system("export LC_ALL=$system_lang.UTF-8");

my $body_head = $i18n->get('[[swatch.emailBody]]') . "\n\n";

my @oid = $cce->find ('ActiveMonitor');
my ($ok, @names, @info) = $cce->names ('ActiveMonitor');
#print $oid[0], "\n";
#print @names, "\n";
my @states = ("N", "G", "Y", "R");

my %stats = {};

my @tcp_ports;
open TCP, "/proc/net/tcp";
while (<TCP>) {
  my @fields = split / +/, $_;
  my ($laddr, $lport) = split /:/, $fields[2];
  my ($raddr, $rport) = split /:/, $fields[3];
  if ($raddr eq "00000000") {
    push @tcp_ports, $lport;
  }
}
close TCP;

my @udp_ports;
open UDP, "/proc/net/udp";
while (<UDP>) {
  my @fields = split / +/, $_;
  my ($laddr, $lport) = split /:/, $fields[2];
  my ($raddr, $rport) = split /:/, $fields[3];
  if ($raddr eq "00000000") {
    push @udp_ports, $lport;
  }
}
close UDP;

while ( defined (my $name = <@names>) ) {
  (my $ok, $object, my $old, my $new) = $cce->get ($oid[0], $name);
  if ( $object->{enabled} && $object->{monitor} ) {
    my $msg;
    my $agg_msg;
    my $state = 0;
    if ($object->{type} eq "aggregate") {
      print "$name aggregates $object->{typeData}\n" if ($DEBUG_ME);
      my $oldState = $object->{currentState};
      my @aggregates = split / +/, $object->{typeData};
      foreach my $aggregate (@aggregates) {
 
        my ($ok, $obj, $old, $new) = $cce->get ($oid[0], $aggregate);
        if ($obj->{enabled} && $obj->{monitor}) {
          ($stats{"$aggregate"}, my $ret, $agg_msg) = do_monitor($obj);
          if (defined($agg_msg)) {
            $msg .= "\n - $agg_msg";
          }
        }
        if ($state < $stats{"$aggregate"}) {
          $state = $stats{"$aggregate"};
        }
        $stats{"$name"} = $state;
      }

      if ($state > 0) {
        my @msgs = ("", "", "", "");
        $msgs[1] = $object->{greenMsg} if (defined ($object->{greenMsg}));
        $msgs[2] = $object->{yellowMsg} if (defined ($object->{yellowMsg}));
        $msgs[3] = $object->{redMsg} if (defined ($object->{redMsg}));
        $cce->set ($oid[0], $name, {
                 currentState => $states[$state],
                 lastChange=>time(),
                 currentMessage => $msgs[$state],
                 });
        if (($oldState ne $statecodes[$state]) &&
            !($oldState eq "N" && $statecodes[$state] eq "G")) {
          print "Got MSG start \n" if ($DEBUG_ME);
          $msg = $i18n->get($msgs[$state]) . $msg;

        } else {
          $msg = "";
        }
        print "[$name] state : $statecodes[$state]\n" if ($DEBUG_ME);
      }

    } elsif (!$object->{aggMember}) {
      ($stats{"$name"}, my $ret, $msg) = do_monitor($object);
    }

    if ($msg) {
      $body .= "* " . $msg . "\n\n";
    }
  }
}

if ($body) {

  $body = $body_head . $body;
  my $subject = $host . ": " . Encode::encode("MIME-B", $i18n->get("[[swatch.emailSubject]]"));
  my $to;
  foreach $to (@email_list) {
  
    # No, we don't want to use Sauce::SendEmail:
    #SendEmail::sendEmail($to, "root <root>", $subject, $body);

    # Build the message using MIME::Lite instead:
    my $send_msg = MIME::Lite->new(
        From     => "root <root>",
        To       => $to,
        Subject  => $subject,
        Data     => $body,
        Charset => 'UTF-8'
    );

    # Set content type:
    $send_msg->attr("content-type"         => "text/plain");
    $send_msg->attr("content-type.charset" => "UTF-8");

    # Out with the email:
    $send_msg->send;
    print "Sending mail \n" if ($DEBUG_ME);
  }
}

$cce->bye();

sub do_monitor
{
  my ($object) = @_;

  my $name = $object->{NAMESPACE};
  my $oldState = $object->{currentState};

  print "monitor [${name}]\n" if ($DEBUG_ME);
  my $type = $object->{type};
  my $am = $object->{typeData};
  my ($ret, $state) = ("", -1);
  $ENV{greenMsg} = $object->{greenMsg} if (defined ($object->{greenMsg}));
  $ENV{yellowMsg} = $object->{yellowMsg} if (defined ($object->{yellowMsg}));
  $ENV{redMsg} = $object->{redMsg} if (defined ($object->{redMsg}));
  if ($type eq "tcp") {
    my $hex;
      $hex = sprintf("%04X", 0 + $am);
      if (grep(/$hex/, @tcp_ports) > 0) {
          $state = 1; # green
          $ret = $ENV{greenMsg};
      } else {
        my $restart;
          $state = 3; # red
          $ret = $ENV{redMsg};
          $restart = $object->{restart} if (defined ($object->{restart}));
          system($restart);
      }
  } elsif ($type eq "udp") {
    my $hex;
      $hex = sprintf("%04X", 0 + $am);
      if (grep(/$hex/, @udp_ports) > 0) {
          $state = 1; # green
          $ret = $ENV{greenMsg};
      } else {
        my $restart;
          $state = 3; # red
          $ret = $ENV{redMsg};
          $restart = $object->{restart} if (defined ($object->{restart}));
          system($restart);
      }
  } elsif ( -x $am ) {
    print "running $am\n" if ($DEBUG_ME);
    $ret = `PATH=/bin:/sbin:/usr/sbin:/usr/bin LANG=$system_lang $am`;
    $state = $? >> 8;
  }
  if ($state >= 0) {
    $cce->set ($oid[0], $name, {
             currentState => $states[$state],
             lastChange=>time(),
             currentMessage => $ret,
             });
  }

  print "OldState:$oldState\tNewState:$statecodes[$state]\n" if ($DEBUG_ME);

  my $msg;
  if (($oldState ne $statecodes[$state]) &&
      !($oldState eq "N" && $statecodes[$state] eq "G")) {
    print "Status changed\n" if ($DEBUG_ME);
    $msg = $i18n->get($ret);

    ### Start: Append 'top' output if CPU is/was in moderate or heavy useage:
    if (($object->{nameTag} eq "[[base-am.amCPUName]]") && ($object->{monitor} == "1")) {
	if ($object->{currentMessage}) {
		print "CPU is/was in moderate or heavy useage - generating 'top' report\n" if ($DEBUG_ME);
		my $TOP = `top -b -n 1`;
		my @procs = split(/\n/, $TOP);
		$msg .= "\n\n-------------------------------------------\n";
		$msg .= "System snapshot:\n";
		$msg .= "-------------------------------------------\n";
		foreach my $top (@procs) {
			$top =~ s/ *$//g;
			$msg .= $top . "\n";
		}
		$msg .= "\n\n";
		print "TOP: \n $TOP \n" if ($DEBUG_ME);
	}
    }
    ### End: Append 'top' output if CPU is/was in moderate or heavy useage:

  }

  return ($state, $ret, $msg);
}
