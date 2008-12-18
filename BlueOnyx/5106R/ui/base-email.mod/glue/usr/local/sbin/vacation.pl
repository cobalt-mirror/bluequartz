#!/usr/bin/perl -w -I/usr/sausalito/perl
# $Id: vacation.pl Thu Dec 18 11:00:02 2008 mstauber $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.

# usage: vacation.pl [message] [from-address]

# Version 1.1.1.2.stable.sendmail.08
# modified by mstauber@solarspeed.net 20081218
# The internal mailer as provided by Sauce::I18nMail is (and always has been!) a piece
# of garbage that's fubare'ed beyond believe. That bloody mailer doesn't even know how
# to build email headers right. This interfered with vacation messages in a quite messy
# way. Patricko tried to solve this in 2005 with a work around, but I just went in
# and switched to something that works instead. So we now mail through MIME::Lite
#
# Version 1.1.1.2.stable.sendmail.07
# modified by mstauber@solarspeed.net 20081206
# Added group and user quota check. Vacation messages are not send if user or his group
# are over quota. Reason: We cannot handle the bounces from joe-jobs that terribly well. 
#
# modified by patricko@staff.singnet.com.sg 20071219
# Changelog: Try 1: fixed local loop. eg: auto-reply to mailer-daemon
# Changelog: Try 2: Fixed compat issue with MS Outlook 2003 webmail
# Changelog: Try 3: Drop invalid from: entries
# Changelog: Try 4: Parse mailto: entries, let .db handle 1 notice for n days
# Changelog: Try 5: Detection changed to 'From ' instead of 'From: ', try 4 is void
# Changelog: Try 6: Move STDIN code section up
# Changelog: Try 7: Reduce one CCE lockup See: 1.0
#################### Special, custom NON RFC only for Sendmail ###################
# ps: By doing so, no changes to existing CCE schema and sendmail build
#     This script will reply via RCPT TO:(derived) from the 'for' field
#     *** In another word, this version taken care of email/domain aliases ***
#
# Changelog: Try 8: Factor in Sendmail >= 8.12 log format, /for/
# Changelog: Try 9: Use Sendmail 'for' TAG to reply mail 
##################################################################################
# Changelog: Try 10: If 'for' TAG doesnt exist then revert back to OLD CODE
# Changelog: Try 11: Speed up email <header> passing as <body> is dropped 
# Changelog: Try 12: Set 'for' TAG to null when address is invalid 
# Changelog: Try 13: Re-Commented and adjusted some whitespace 
# Changelog: Try 14: Unbuffered output for STDIN
# Changelog: Try 15: Commented out Breakloop and use proper loop exit
# Changelog: Try 16: Fixed Cannot send out vacation msg coz sendmail permission on some platforms
#                    - dsn=5.6.0, stat=Data format error, from=<username>@<DOMAIN is missing>
#                    Workaround: HARDCODED the Envelope, From: root and To: Receipent on $Sendmail -froot -oi $sendto
#                    NOTE: add 'root' to /etc/mail/trusted-users
# Changelog: Try 17: Add Log4perl perl module for debugging - COMMENTED OUT
#                    NOTE: you have to install Log-Dispatch-2.20.tar.gz, Log-Log4perl-1.14.tar.gz
 
use strict;
use lib qw( /usr/sausalito/perl );
use Sauce::Config;
use CCE;
use I18n;
use Jcode;
use DB_File;
use Fcntl qw(O_RDWR O_CREAT F_SETLKW F_UNLCK);
use FileHandle;
use I18nMail;
use Quota;
use MIME::Lite;

# Declare DEBUGGING
#use Log::Log4perl;

#my $log_conf = q/
#    log4perl.category = INFO, Logfile, Screen
#
#    log4perl.appender.Logfile = Log::Log4perl::Appender::File
#    log4perl.appender.Logfile.filename = debug-vacation-pl.log
#    log4perl.appender.Logfile.mode = append
#    log4perl.appender.Logfile.layout = Log::Log4perl::Layout::SimpleLayout
#
#    log4perl.appender.Screen        = Log::Log4perl::Appender::Screen
#    log4perl.appender.Screen.layout = Log::Log4perl::Layout::SimpleLayout
#/;

#Log::Log4perl::init( \$log_conf );
#my $logger = Log::Log4perl::get_logger();

# TESTING - Test variables
#$logger->info("Starting $0");
#$logger->error("Bad thing happened");

### Add by patricko@staff.singnet.com.sg 20060725
my @ignores = (
           'mailer-daemon',
           'mailer',
           'daemon',
           'postmaster',
           'root',
           );

my ($opt_d)=(0);
### End Add by patricko@staff.singnet.com.sg 20060725

my ($message_file,$user_from) = @ARGV;

my $Sendmail = Sauce::Config::bin_sendmail;

my @pwent = getpwnam($user_from);
my $Vaca_dir = $pwent[7];
my $uname = $pwent[0];

my $i18n=new I18n;

##### READ from STDIN and parse for variables, patricko

# set up variables for below
my ($sendto,$sender,$returnpath,$from,$replyto,$precedence,$for);
my $crlf = qr/\x0a\x0d|\x0d\x0a|\x0a|\x0d/; # We are liberal in what we accept.
                                            # But then, so is a six dollar whore.

# Chop email message into <header> portion and discard the <body>
#
# RFC 822 states that the 1st blank line is start of message body
# RFC 2822 ie.
# (optional)  From:
# (optional)  Sender:
# (optional)  To:
# (optional)  Subject:
# (Mandatory) Date:
#
# or reverse
#

$|=1; # Use unbuffered output for STDIN
while (<STDIN>)
{
   
    #if    (/^From:\s*(.+)/)        { $from = $1;       }
    if    (/^From\s+(\S+)/)        { $from = $1;       }
    elsif (/^Reply-To:\s*(.+)/)    { $replyto = $1;    }
    elsif (/^Sender:\s*(.+)/)       { $sender = $1;     }
    elsif (/^Return-path:\s*(.+)/)  { $returnpath = $1; }
    elsif (/^Precedence:\s*(.+)/)   { $precedence = $1; }
    elsif (/^\tfor\s+(\S+)/)        { $for = $1;        }
    #elsif (/^$crlf/)                { goto breakloop    } 
    elsif (/^$crlf/)                { last;             } 

}

# Dirty way of breaking a loop
# 100% confirmed that variables after this line dont have <body>
breakloop:

# Discard <precedence> mail, no (auto-)reply
exit if (defined $precedence && $precedence =~ /bulk|junk/oi);

# Pass variables to crafted (auto-)reply
if    ($replyto)     { $sendto = $replyto;     }
elsif ($from)        { $sendto = $from;        }
elsif ($sender)      { $sendto = $sender;      }
elsif ($returnpath)  { $sendto = $returnpath;  }
else                 { exit;                   }

   # Super safe - email address malform checks
   # Error control - Fuzzy logic, FROM:  MUST be valid else exit 
   ## Extract <for> value: address, if any
   ### DONT EXIT below condtion 'See 1.0' check again
   if ($for !~ /@/i)                             { $for = ""; }
   elsif ($for =~ /[\w_\.\-]+[@%][\w_\.\-]+/)    { $for = $&; }
   else                                          { $for = ""; }

   # Super safe - email address malform checks
   # Error control - Fuzzy logic, TO:  MUST be valid else exit
   ## Check for @ and extract email address, if any 
   if ($sendto !~ /@/i)                          { exit;         }
   elsif ($sendto =~ /[\w_\.\-]+[@%][\w_\.\-]+/) { $sendto = $&; }
   else                                          { exit;         }

   # Prevent local mail loop
   ## Ignore local email users, prevent loop
   for (@ignores) {if ($sendto =~ /^$_/i)        { exit;         }}


##### END READ from STDIN and parse for variables, patricko


### START CCE Session, patricko

# gather info from cce
my $cce = new CCE;
$cce->connectuds();

my $username = $user_from;

my ($oid) = $cce->find("User", { 'name' => $user_from });
my ($ok, $user) = $cce->get($oid);

if( not $ok ) { 
	$cce->bye('FAIL', '[[base-email.cantGetUserInfo]]'); 
	exit(255);
}

#### See 1.0
if ($for) {$user_from = $for;}
else
{
 if ($user->{site} ne '') 
 {
	my ($v_oid) = $cce->find('Vsite', { 'name' => $user->{site} });
	my ($v_ok, $vsite) = $cce->get($v_oid);
	
	$user_from .= '@' . $vsite->{fqdn};
 }
}

# set locale for i18n
my $locale = $user->{localePreference};
if( not -d "/usr/share/locale/$locale" && not -d "/usr/local/share/locale/$locale" ) {
	$locale = I18n::i18n_getSystemLocale($cce);
}

my $fullname = $user->{fullName};
$fullname ||= $user_from;

$cce->bye('SUCCESS');

$i18n->setLocale($locale);

### End CCE Session and related, patricko

#
# Snip and move up
#

my %vacadb;

my $vacadb = tie(%vacadb,'DB_File',"$Vaca_dir/.$username.db",O_RDWR|O_CREAT,0666)
    || die "Cannot open vacation database: $!\n";

$vacadb{$sendto} ||= 0;

if ($vacadb{$sendto} >= ($^T - 604800))
{
    # They've been given a reply recently
#    untie %vacadb;
#    exit;
}
else
{
    # lock the db just to be safe, this returns a filehandle that needs
    # to be closed after vacadb is untied
    my $fh = &lock($vacadb);

    $vacadb{$sendto} = $^T;

    &unlock($vacadb, $fh);  # this also undefines $vacadb
    untie %vacadb;
    $fh->close();
}


## Start Quota checks: mstauber
my $uid   = $pwent[2];
my $gid   = $pwent[3];

# lookup dev
my $dev_a = Quota::getqcarg($Vaca_dir);
my $is_group = "1";

# do query for group's quota:
my ($group_used, $group_quota) = Quota::query($dev_a, $gid, $is_group);

# do query for user's quota:
my $dev = Quota::getqcarg($Vaca_dir);
my ($used, $quota) = Quota::query($dev, $uid);

my $diff = "20";

my $is_overquota = "0";
if (($group_used + $diff >= $group_quota) || ($used + $diff >= $quota)) {
    my $is_overquota = "1";
    
    # User or group is over quota. Exit silently.
    $cce->bye('FAIL', "The recipient mailbox is full or the site he belongs to is over the allocated quota. Message delivery terminated."); 
}

## End Quota checks

if ($is_overquota eq "0") {

    my $mail = new I18nMail;
    $mail->setLang($locale);

    my $subject=$i18n->get("[[base-email.vacationSubject]]");
    my $format=$i18n->getProperty("vacationSubject","base-email");
    my %data=(NAME=>$fullname,EMAIL=>"<$user_from>",MSG=>$subject);
    $format=~s/(NAME|EMAIL|MSG)/$data{$1}/g;

    $mail->setSubject($format);
    $mail->setFrom("$fullname <$user_from>");
    $mail->addRawTo($sendto);

    open (INMESSAGE, "$message_file") || die "Can't open message file $!\n";
    my $msg;
    {local $/=undef;$msg=<INMESSAGE>};
    close INMESSAGE;

#    No, thanks. The internal email function is (and always has been!) fubar.
#    $mail->setBody($msg);
#    open (OUT, "|$Sendmail -froot -oi $sendto") || die "Can't open sendmail $!\n";
#    print OUT $mail->toText();
#    close OUT;

    # Build the message using MIME::Lite instead:
    my $send_msg = MIME::Lite->new(
        From     => "$fullname <$user_from>",
        To       => $sendto,
        Subject  => $format,
        Data     => $msg
    );

    # Set content type:
    $send_msg->attr("content-type"         => "text/plain");
    $send_msg->attr("content-type.charset" => "ISO-8859-1");

    # Out with the email:
    $send_msg->send;

}

#DEBUGGING
#$logger->info("Sendmail: $Sendmail");
#$logger->info("User_from: $user_from");
#$logger->info("Fullname: $fullname");
#$logger->info("Send to: $sendto");
#$logger->info("Subject: $format");
#$logger->info("Body: $msg");


# database locking sub-routine
# returns a filehandle that will need to be closed after unlock is called
sub lock {
	my $db = shift;
	my $fd = $db->fd;
	my $fh = new FileHandle("+<&=$fd");

	my $return_buffer;
	fcntl($fh, F_SETLKW, $return_buffer);

	return $fh;
}

# database unlocking sub-routine
sub unlock {
	my $db = shift;
	my $fh = shift;

	$db->sync;  # just in case

	# remove the lock on the filehandle
	my $return_buffer;
	fcntl($fh, F_UNLCK, $return_buffer);
	
	undef $db;
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
