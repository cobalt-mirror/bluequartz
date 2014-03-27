#!/usr/bin/perl -w -I/usr/sausalito/perl
# $Id: vacation.pl

# usage: vacation.pl [message] [from-address]
 
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
use Encode qw/encode decode/;

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

($ok, my $userEmailObj) = $cce->get($oid, 'Email');

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


# Check start/stop date

my $startDate = $userEmailObj->{vacationMsgStart};
my $stopDate = $userEmailObj->{vacationMsgStop};

if ($startDate ne $stopDate) {
    if ( ! ($startDate < time() && $stopDate > time())) {
	# We will not use vacation!
	$cce->bye('SUCCESS');
	exit;
    }
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
    untie %vacadb;
    exit;
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

    my $subject = $i18n->get("[[base-email.vacationSubject]]");
    my $format = $i18n->getProperty("vacationSubject","base-email");
    my %data = (NAME => $fullname, EMAIL => "<$user_from>", MSG => $subject);
    $format=~s/(NAME|EMAIL|MSG)/$data{$1}/g;

    # If the users locale preference is Japanese, then the Subject is now 
    # in EUC-JP, which we cannot mail with MIME::Lite. We need to convert
    # it into UTF-8 first:
    if ($user->{localePreference} == "ja_JP") {
      $format = decode("euc-jp", $format)
    }

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
        Subject  => encode('MIME-Header', $format),
        Data     => $msg
    );

    # Set content type:
    $send_msg->attr("content-type"         => "text/plain");
    $send_msg->attr("content-type.charset" => "UTF-8");

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

# 
# Copyright (c) 2014 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2014 Team BlueOnyx, BLUEONYX.IT
# Copyright (c) 2003 Sun Microsystems, Inc. 
# All Rights Reserved.
# 
# 1. Redistributions of source code must retain the above copyright 
#   notice, this list of conditions and the following disclaimer.
# 
# 2. Redistributions in binary form must reproduce the above copyright 
#   notice, this list of conditions and the following disclaimer in 
#   the documentation and/or other materials provided with the 
#   distribution.
# 
# 3. Neither the name of the copyright holder nor the names of its 
#   contributors may be used to endorse or promote products derived 
#   from this software without specific prior written permission.
# 
# THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS 
# "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT 
# LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS 
# FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE 
# COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, 
# INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, 
# BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; 
# LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER 
# CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT 
# LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN 
# ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE 
# POSSIBILITY OF SUCH DAMAGE.
# 
# You acknowledge that this software is not designed or intended for 
# use in the design, construction, operation or maintenance of any 
# nuclear facility.
# 