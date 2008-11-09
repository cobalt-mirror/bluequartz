#!/usr/bin/perl
package ldapExport;
@EXPORT = qw(makeEntry ldapAttr restartService);
use lib '/usr/sausalito/perl';
use Jcode;
use MIME::Base64;
use I18n;
use Text::Iconv;
use strict;

my $locale = I18n::i18n_getSystemLocale();
my $converter = {};
$converter->{gb2312} = Text::Iconv->new("gb2312", "utf8");
$converter->{big5} = Text::Iconv->new("big5", "utf8");
$converter->{'iso-8859-1'} = Text::Iconv->new("iso-8859-1", "utf8");

sub makeEntry {
	my ($cce, $item, $vals) = @_;

	my $oid = $item->{"OID"};
	
	my $baseDn = $vals->{baseDn};
	my $emailSuffix = $vals->{emailSuffix};

	my $ok;
	my $newData;

	if ($item->{CLASS} eq "Workgroup") {
		my $disk; ($ok, $disk) = $cce->get($oid, "Disk");
		my @members = $cce->scalar_to_array($item->{members});
		my @gr = getgrnam $item->{name};
		
		$newData .= ldapAttr("dn", "group=" . $item->{name} . ", $baseDn");
		$newData .= ldapAttr("objectclass", "top");
		$newData .= ldapAttr("objectclass", "person");
		$newData .= ldapAttr("objectclass", "posixGroup");
		$newData .= ldapAttr("objectclass", "cobaltGroup");
		$newData .= ldapAttr("cn", $item->{name});
		$newData .= ldapAttr("enabled", ($item->{enabled} ? "true" : "false"));
		$newData .= ldapAttr("gidnumber", $gr[2]);
		for my $member (@members) {
			$newData .= ldapAttr("memberuid", $member);
		}
		$newData .= ldapAttr("mail", $item->{name} . "@" . $emailSuffix);
		$newData .= ldapAttr("diskquota", $disk->{quota});
	
	} elsif ($item->{CLASS} eq "User") {
	
		my $email; ($ok, $email) = $cce->get($oid, "Email");
		my $addressbookEntry; ($ok, $addressbookEntry) = $cce->get($oid, "AddressbookEntry");
		my @aliases = $cce->scalar_to_array( $email->{aliases});
		my $disk; ($ok, $disk) = $cce->get($oid, "Disk"); 
		my @pw = getpwnam $item->{name};
		my @gr = getgrgid $pw[3];
	
		$newData .= ldapAttr("dn", "cn=" . $item->{name} . ", $baseDn");
		$newData .= ldapAttr("objectclass", "top");
		$newData .= ldapAttr("objectclass", "person");
		$newData .= ldapAttr("objectclass", "organizationalPerson");
		$newData .= ldapAttr("objectclass", "posixAccount");
		$newData .= ldapAttr("objectclass", "cobaltAccount");
		$newData .= ldapAttr("uid", $pw[0]); 
		$newData .= ldapAttr("gid", $gr[0]);
		$newData .= ldapAttr("userpassword", "{CRYPT}" . $item->{crypt_password});
		$newData .= ldapAttr("userpassword", "{MD5}" . $item->{md5_password});
		$newData .= ldapAttr("cn", $item->{fullName});
		$newData .= ldapAttr("systemadministrator", ($item->{systemAdministrator} ? "true" : "false"));
		$newData .= ldapAttr("mail", $item->{name} . "@" . $emailSuffix);
		for my $alias (@aliases) {
			$newData .= ldapAttr("mail", $alias . "@" . $emailSuffix);
		}
		$newData .= ldapAttr("quota", $disk->{quota});
		$newData .= ldapAttr("uidnumber", $pw[2]);
		$newData .= ldapAttr("gidnumber", $pw[3]);
		$newData .= ldapAttr("gecos", $item->{fullName});
		$newData .= ldapAttr("homedirectory", $pw[7]);
		$newData .= ldapAttr("loginshell", $pw[8]);
		
		# new objectClass = organizationalPerson object stuff...
		$newData .= ldapAttr("seeAlso", $addressbookEntry->{homeUrl});
		$newData .= ldapAttr("telephoneNumber", $addressbookEntry->{phone});
		$newData .= ldapAttr("facsimileTelephoneNumber", $addressbookEntry->{fax});
		$newData .= ldapAttr("postalAddress", $addressbookEntry->{address});
		$newData .= ldapAttr("description", $item->{description});
		
		
	}
	$newData .= "\n";
	return $newData;
}	

sub ldapAttr {
	my ($key, $val) = @_;
	my $newval;

	if($locale =~ /^ja/){
                $newval = encode_base64(Jcode->new($val)->utf8);
        }elsif($locale eq "zh" || $locale =~ /zh[-_]CN/i){
		$newval = $converter->{gb2312}->convert($val);
		$newval=encode_base64($newval);
        }elsif($locale =~ /zh[-_]TW/i){
		$newval = $converter->{big5}->convert($val);
                $newval=encode_base64($newval);
        }else{
		$newval = $converter->{'iso-8859-1'}->convert($val);
                $newval=encode_base64($newval);
        }
	if (!($newval =~ m/\n/)) {
		$newval .= "\n";
	}
	return "$key:" . ": $newval";
	#($val) && (return "$key: " . $val . "\n");
}

sub restartService {
	my $cce = shift;
	my $enabled = shift;
	
	my $LDAPScript = "/etc/rc.d/init.d/ldap";
	my $ChkConfig_bin = "/sbin/chkconfig";

	system("$LDAPScript stop > /dev/null ");
	system("/usr/sbin/ldif2ldbm -i /var/lib/ldap/ldif > /dev/null");

	# don't horse around with symlinks
	if ( $enabled ) {
		if (system("$ChkConfig_bin ldap on")) {
			$cce->warn("cannotLinkLdap");
			$cce->bye("FAIL");
			exit 1;
		}
		system("$LDAPScript start > /dev/null &");
	} else { 		# disabled
		if (system("$ChkConfig_bin ldap off")) {
			$cce->warn("cannotRemoveLink");
		}
	}
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
