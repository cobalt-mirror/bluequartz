#!/usr/bin/perl -w -I/usr/sausalito/perl
# $Id: vacation.pl 259 2004-01-03 06:28:40Z shibuya $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.

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

my ($message_file,$user_from) = @ARGV;

my $Sendmail = Sauce::Config::bin_sendmail;

my @pwent = getpwnam($user_from);
my $Vaca_dir = $pwent[7];

my $i18n=new I18n;

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

if ($user->{site} ne '') {
	my ($v_oid) = $cce->find('Vsite', { 'name' => $user->{site} });
	my ($v_ok, $vsite) = $cce->get($v_oid);
	
	$user_from .= '@' . $vsite->{fqdn};
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

# set up variables for below
my ($sendto,$sender,$returnpath,$from,$replyto,$precedence);

while (<STDIN>)
{
    if    (/From:\s*(.+)/)        { $from = $1;       }
    elsif (/Reply-To:\s*(.+)/)    { $replyto = $1;    }
    elsif (/Sender:\s*(.+)/)      { $sender = $1;     }
    elsif (/Return-path:\s*(.+)/) { $returnpath = $1; }
    elsif (/Precedence:\s*(.+)/)  { $precedence = $1; }
}

exit if (defined $precedence && $precedence =~ /bulk|junk/oi);

if    ($replyto)     { $sendto = $replyto;     }
elsif ($from)        { $sendto = $from;        }
elsif ($sender)      { $sendto = $sender;      }
elsif ($returnpath)  { $sendto = $returnpath;  }
else                 { exit;                   }

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

$mail->setBody($msg);

open (OUT, "|$Sendmail -oi -t") || die "Can't open sendmail $!\n";
print OUT $mail->toText();
close OUT;


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
