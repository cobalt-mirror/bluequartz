#
# Use this to send i18n email messages
#
# Usage:
#
#  SendEmail::sendEmail($to, $from, $subject, $body, $cc, $bcc);
#
# The $subject and $body args should be i18n strings.
# $cc and $bcc are optional.
#

package SendEmail;

use lib '/usr/sausalito/perl';
use CCE;
use I18n;
use Jcode;
use I18nMail;

my $mailBin = "/usr/sbin/sendmail -t -i";

sub getLocale
{
  my $whichUser = shift;
  my $locale;

  my $cce = new CCE;
  $cce->connectuds();
  my (@oids) = $cce->find('User', {'name' => $whichUser});
  if (@oids) {
	my ($ok, $user) = $cce->get($oids[0]);
	if ($ok) {
		$locale = $user->{'localePreference'} || "";
	}
  }

  if (!$locale || $locale eq 'browser') {
  	$locale = I18n::i18n_getSystemLocale($cce);
  }
  $cce->bye("SUCCESS");

  return $locale ? $locale : "en";
}

sub sendEmail
{
  my ($to, $from, $subject, $body, $cc, $bcc, $locale) = @_;

  if (! defined($locale)) {
      $locale = getLocale((split/,/,$to)[0]);
  }
  return 1 if (! defined ($locale));

  my $i18n = new I18n;
  $i18n->setLocale($locale);

  my $mail=new I18nMail($locale);

  if($subject) {
    $subject = $i18n->interpolate($subject);
  }
  
  if($body) {
    $body = $i18n->interpolate($body);
  }

  $mail->setLang($locale);
  $mail->setSubject($subject) || die "1";
  $mail->addTo(split/,/,$to) || die "2";
  ($mail->addCc(split/,/,$cc) || die "3") if defined $cc && $cc ne "";
  ($mail->addBcc(split/,/,$bcc) || die "4") if defined $bcc && $bcc ne "";
  $mail->setBody($body) || die "5";
  $mail->setFrom($from) || die "6";
  
#  print $mail->toText();

  if (open(MAIL, "| $mailBin")) {
      print MAIL $mail->toText();
      close(MAIL);
      return $?;
  }
  return -1;
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
