#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: vacation.pl

# usage: vacation.pl [message] [from-address]
 
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
use MIME::Base64;
use utf8;
use Encode qw/encode decode/;

# Debugging switch:
$DEBUG = "0";
if ($DEBUG) {
        use Sys::Syslog qw( :DEFAULT setlogsock);
}

### Add by patricko@staff.singnet.com.sg 20060725
@ignores = (
            'mailer-daemon',
            'mailer',
            'daemon',
            'postmaster',
            'root'
           );

($opt_d)=(0);
### End Add by patricko@staff.singnet.com.sg 20060725

($message_file, $user_from) = @ARGV;

@pwent = getpwnam($user_from);
$Vaca_dir = $pwent[7];
$uname = $pwent[0];

$i18n=new I18n;

##### READ from STDIN and parse for variables, patricko

# set up variables for below
($sendto,$sender,$returnpath,$from,$replyto,$precedence,$for);
$crlf = qr/\x0a\x0d|\x0d\x0a|\x0a|\x0d/; # We are liberal in what we accept.
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
   # Next three lines commented out for Postlayer:
   #if ($sendto !~ /@/i)                          { exit;         }
   #elsif ($sendto =~ /[\w_\.\-]+[@%][\w_\.\-]+/) { $sendto = $&; }
   #else                                          { exit;         }

   # Prevent local mail loop
   ## Ignore local email users, prevent loop
   for (@ignores) {if ($sendto =~ /^$_/i)        { exit;         }}


##### END READ from STDIN and parse for variables, patricko


### START CCE Session, patricko

# gather info from cce
$cce = new CCE;
$cce->connectuds();

$username = $user_from;
$executioner = getpwuid( $< );

&debug_msg("Running as: $executioner\n");
@uOID = $cce->find('User', { 'name' => $user_from });
&debug_msg("User OID: $uOID[0]\n");
($ok, $user) = $cce->get($uOID[0]);
$UserData = $user;
&debug_msg("User $UserData->{'name'} - Site: $UserData->{'site'}\n");

&debug_msg("Locale $UserData->{'name'} = $UserData->{'localePreference'}\n");

if ( not $ok ) { 
    $cce->bye('FAIL', '[[base-email.cantGetUserInfo]]'); 
    exit(255);
}

($ok, $ObjUserEmail) = $cce->get($uOID[0], 'Email');

## Correct sender-address ($user_from) if need be:
$prefEmailAlias_file = $Vaca_dir . '/.prefEmailAlias';
$prefEmailDomain_file = $Vaca_dir . '/.prefEmailDomain';
$prefEmailAlias = '';
$prefEmailDomain = '';
if (-f $prefEmailAlias_file) {
    open($fh, '<:encoding(UTF-8)', $prefEmailAlias_file);
    while ($row = <$fh>) {
        chomp $row;
        if ($prefEmailAlias eq '') {
            $prefEmailAlias = $row;
        }
    }

    &debug_msg("1: prefEmailAlias is: $prefEmailAlias.\n");

    # Override $user_from:
    $user_from = $prefEmailAlias;
}
if (-f $prefEmailDomain_file) {
    open($fh, '<:encoding(UTF-8)', $prefEmailDomain_file);
    while ($row = <$fh>) {
        chomp $row;
        if ($prefEmailDomain eq '') {
            $prefEmailDomain = $row;
        }
    }
    &debug_msg("1: prefEmailDomain is: $prefEmailDomain.\n");
}

if ($for) {
    $user_from = $for;
    &debug_msg("Setting sender to: $user_from\n");
    if ($prefEmailAlias ne '') {
        @userAliases = $cce->scalar_to_array($ObjUserEmail->{'aliases'});
        push @userAliases, $user_from;
        if (in_array(\@userAliases, $prefEmailAlias)) {
            $user_from = $prefEmailAlias;
        }
        else {
            system("rm -f $prefEmailAlias_file");
        }
    }
}
else {
    $urgl = $UserData->{'site'};
    if ($UserData->{site} ne '') {
        @vOID = $cce->find('Vsite', { 'name' => $UserData->{'site'} });
        &debug_msg("Vsite OID: $vOID[0]\n");
        ($ok, $vsite) = $cce->get($vOID[0]);
        $fqdn = $vsite->{'fqdn'};

        if ($prefEmailDomain ne "") {
            $alias_check = `cat /etc/mail/virtusertable|grep ^\@$prefEmailDomain|grep $fqdn|wc -l`;
            chomp($alias_check);
            &debug_msg("Is configured prefEmailDomain valid: $alias_check\n");
            &debug_msg("2: prefEmailDomain is: $prefEmailDomain.\n");
        }
        else {
            $alias_check = '0';
        }

        if ($prefEmailAlias ne '') {
            &debug_msg("Sense check for alias $prefEmailAlias.\n");
            @userAliases = $cce->scalar_to_array($ObjUserEmail->{'aliases'});
            push @userAliases, $user_from;
            if (in_array(\@userAliases, $prefEmailAlias)) {
                $user_from = $prefEmailAlias;
                &debug_msg("Setting email alias $prefEmailAlias.\n");
            }
            else {
                system("rm -f $prefEmailAlias_file");
                &debug_msg("Invalid email alias found!\n");
            }
        }

        if ($prefEmailDomain eq '') {
            $user_from .= '@' . $fqdn;
            &debug_msg("Using standard domain alias of the Vsite.\n");
        }
        else {
            # Override domain in $user_from:
            &debug_msg("Vsite mailAliases: Using prefEmailDomain $prefEmailDomain\n");
            if ($alias_check eq "1") {
                $user_from .= '@' . $prefEmailDomain;
                &debug_msg("Setting domain alias $prefEmailDomain.\n");
            }
            else {
                # Domain alias no longer in use by Vsite. Revert to default:
                $user_from .= '@' . $fqdn;
                system("rm -f $prefEmailDomain_file");
                &debug_msg("Invalid domain alias found, using default fqdn!\n");
            }
        }
    }
}

# set locale for i18n
$locale = $UserData->{localePreference};
&debug_msg("Setting locale to $locale\n");
if (( not -d "/usr/share/locale/$locale" && not -d "/usr/local/share/locale/$locale" ) || ($locale eq '')) {
    $locale = I18n::i18n_getSystemLocale($cce);
    &debug_msg("Defaulting locale to $locale\n");
}

# Check start/stop date
$startDate = $ObjUserEmail->{vacationMsgStart};
$stopDate = $ObjUserEmail->{vacationMsgStop};

if ($startDate ne $stopDate) {
    if ( ! ($startDate < time() && $stopDate > time())) {
        # We will not use vacation!
        $cce->bye('SUCCESS');
        exit;
    }
}

$fullname = $UserData->{fullName};
&debug_msg("Fullname of $username: $fullname\n");

if ($fullname eq "") {
    $fullName = $user_from;
}
$cce->bye('SUCCESS');
$i18n->setLocale($locale);

### End CCE Session and related, patricko

#
# Snip and move up
#

%vacadb;

$vacadb = tie(%vacadb,'DB_File',"$Vaca_dir/.$username.db",O_RDWR|O_CREAT,0666) || die "Cannot open vacation database: $!\n";

$vacadb{$sendto} ||= 0;

if ($vacadb{$sendto} >= ($^T - 604800)) {
    # They've been given a reply recently
    &debug_msg("Not sending vacation message of $username to $sendto, as he received one already.\n");
    untie %vacadb;
    exit;
}
else {
    # lock the db just to be safe, this returns a filehandle that needs
    # to be closed after vacadb is untied
    $fh = &lock($vacadb);
    &debug_msg("Locking vacation database of $username.\n");
    $vacadb{$sendto} = $^T;
    &unlock($vacadb, $fh);  # this also undefines $vacadb
    untie %vacadb;
    &debug_msg("Unlocking vacation database of $username.\n");
    $fh->close();
}


## Start Quota checks: mstauber
$uid   = $pwent[2];
$gid   = $pwent[3];

# lookup dev
$dev_a = Quota::getqcarg($Vaca_dir);
$is_group = "1";

# do query for group's quota:
($group_used, $group_quota) = Quota::query($dev_a, $gid, $is_group);
&debug_msg("Quota: group_used: $group_used - group_quota: $group_quota\n");

# do query for user's quota:
$dev = Quota::getqcarg($Vaca_dir);
($used, $quota) = Quota::query($dev, $uid);

$diff = "20";

$is_overquota = "0";
if ((($group_used + $diff >= $group_quota) || ($used + $diff >= $quota)) && ($group_quota gt "0")) {
    $is_overquota = "1";

    &debug_msg("Not sending vacation message of user $username as his account or his site is over quota.\n");
    
    # User or group is over quota. Exit silently.
    $cce->bye('FAIL', "The recipient mailbox is full or the site he belongs to is over the allocated quota. Message delivery terminated.");
    exit(0);
}

## End Quota checks

if ($is_overquota eq "0") {

    $subject = $i18n->get("[[base-email.vacationSubject]]");
    $format = $i18n->getProperty("vacationSubject", "base-email");
    %data = (NAME => "$fullname", EMAIL => "<$user_from>", MSG => $subject);
    $format=~s/(NAME|EMAIL|MSG)/$data{$1}/g;

    # If the users locale preference is Japanese, then the Subject is now 
    # in EUC-JP, which we cannot mail with MIME::Lite. We need to convert
    # it into UTF-8 first:
    &debug_msg("Locale preference of user $username: $locale\n");
    if ($locale eq "ja_JP") {
        &debug_msg("Converting Japanese vacation message to UTF-8 for user $username.\n");
        $format = decode("euc-jp", $format)
    }

    open (INMESSAGE, "$message_file") || die "Can't open message file $!\n";
    $msg;
    {local $/=undef;$msg=<INMESSAGE>};
    close INMESSAGE;

    # Decode Subject:
    utf8::decode($format);
    # Turn Subject into MIME-B:
    $mail_subject = encode("MIME-B", $format);

    $tgt_sender_email = '"' . $fullname . '" <' . $user_from . '>';

    # Build the message using MIME::Lite instead:
    $send_msg = MIME::Lite->new(
        From     => $tgt_sender_email,
        To       => $sendto,
        Subject  => $mail_subject,
        Data     => $msg
    );

    # Set content type:
    $send_msg->attr("content-type"         => "text/plain");
    $send_msg->attr("content-type.charset" => "UTF-8");

    &debug_msg("Sending vacation message of user $username to $sendto.\n");

    # Out with the email:
    $send_msg->send;

}

# database locking sub-routine
# returns a filehandle that will need to be closed after unlock is called
sub lock {
    $db = shift;
    $fd = $db->fd;
    $fh = new FileHandle("+<&=$fd");
    $return_buffer = "";
    fcntl($fh, F_SETLKW, $return_buffer);
    return $fh;
}

# database unlocking sub-routine
sub unlock {
    $db = shift;
    $fh = shift;
    $db->sync;  # just in case
    # remove the lock on the filehandle
    $return_buffer = "";
    fcntl($fh, F_UNLCK, $return_buffer);
    undef $db;
}

sub in_array {
    ($arr,$search_for) = @_;
    %items = map {$_ => 1} @$arr; # create a hash out of the array values
    return (exists($items{$search_for}))?1:0;
}

sub debug_msg {
    if ($DEBUG) {
        $msg = shift;
        $user = $ENV{'USER'};
        setlogsock('unix');
        openlog($0,'','LOG_MAIL');
        syslog('info', "$ARGV[0]: $msg");
        closelog;
    }
}

# 
# Copyright (c) 2016 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2016 Team BlueOnyx, BLUEONYX.IT
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