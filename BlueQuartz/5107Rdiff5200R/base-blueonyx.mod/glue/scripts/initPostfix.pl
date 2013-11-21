#!/usr/bin/perl

use File::Copy;

$conf = "/etc/postfix/main.cf";
$bak = "/etc/postfix/main.cf.save";

copy $conf, $bak or die $!;

my $MyHost = 0;
my $MyDest = 0;

my $hostname;

open IN, "< /etc/sysconfig/network";
while(<IN>) {
  if (/^HOSTNAME=(.*)/) {
    $hostname=$1;
  }
}
close(IN);

open INPUT, "< $bak";
open OUTPUT, "> $conf";

while(<INPUT>) {
  select OUTPUT;
  if (/^# Add configuration for BlueQuartz by init script./o) {
    last;
  } elsif (/^myhostname = /o) {
    print "#" . $_;
  } elsif (/^mydestination = /o) {
    print "#" . $_;
  } elsif (/^smtpd_sender_restrictions = /o) {
    print "#" . $_;
  } elsif (/^inet_interfaces = /o) {
    print "#" . $_;
  } elsif (/^alias_maps = /o) {
    print "#" . $_;
  } elsif (/^alias_database = /o) {
    print "#" . $_;
  } elsif (/^virtual_alias_maps = /o) {
    print "#" . $_;
  } elsif (/^message_size_limit = /o) {
    print "#" . $_;
  } elsif (/^relayhost = /o) {
    print "#" . $_;
  } elsif (/^fallback_relay = /o) {
    print "#" . $_;
  } elsif (/^transport_maps = /o) {
    print "#" . $_;
  } elsif (/^sender_canonical_maps = /o) {
    print "#" . $_;
  } elsif (/^local_header_rewrite_clients = /o) {
    print "#" . $_;
  } elsif (/^mailbox_size_limit = /o) {
    print "#" . $_;
  } elsif (/^mailbox_command = /o) {
    print "#" . $_;
  } else {
    print $_;
  }
}

print <<END;

# Add configuration for BlueQuartz by init script.
mailbox_command = /usr/bin/procmail
mailbox_size_limit = 0
inet_interfaces = all
alias_maps = hash:/etc/aliases, hash:/etc/aliases.majordomo
alias_database = hash:/etc/aliases, hash:/etc/aliases.majordomo
virtual_alias_maps = hash:/etc/postfix/virtual
mydestination = \$myhostname, localhost.\$mydomain, localhost, /etc/postfix/local-host-names
smtpd_sender_restrictions = hash:/etc/postfix/access
smtpd_recipient_restrictions = check_recipient_access hash:/etc/postfix/access, permit_mynetworks, reject_unauth_destination
transport_maps = hash:/etc/postfix/transport

myhostname = $hostname

#smtpd_tls_security_level = may
#smtpd_tls_cert_file = /etc/pki/tls/certs/postfix.pem
#smtpd_tls_key_file = /etc/pki/tls/certs/postfix.pem
#smtpd_tls_session_cache_database = btree:/etc/postfix/smtpd_scache

#broken_sasl_auth_clients = yes

message_size_limit = 0
#relayhost = 
#fallback_relay =
#sender_canonical_maps =
#local_header_rewrite_clients = static:all
#relay_domains =
END

close INPUT;
close OUTPUT;

