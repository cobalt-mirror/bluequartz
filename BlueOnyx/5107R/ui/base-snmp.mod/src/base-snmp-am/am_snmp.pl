#!/usr/bin/perl -I/usr/sausalito/perl
# test snmp state

#use strict;
use warnings;
use AM::Util;
use CCE;

my $cce = new CCE;
$cce->connectuds();

my %am_states = am_get_statecodes();

my ($sysoid) = $cce->findx('System');
my ($ok, $object) = $cce->get($sysoid, 'Snmp');
if (!$ok) {
    $cce->bye('FAIL');
    exit $am_states{AM_STATE_NOINFO};

}

# return value of snmpwalk indicates if it successfully found the sysName
# property
my $fail = system("/usr/bin/snmpwalk -v 2c -c $object->{readCommunity} localhost system.sysName >/dev/null 2>&1");
if ($fail) {
    # can't directly call "system" since snmpd doesn't close stdin
    # and as a result, swatch would keep waiting forever
    # my_system handles the closing of stdin/stdout and so it all works
    AM::Util::my_system('/etc/init.d/snmpd restart >/dev/null 2>&1');
    sleep 3;
    $fail = system("/usr/bin/snmpwalk -v 2c -c $object->{readCommunity} localhost system.sysName >/dev/null 2>&1");
    if ($fail) {
	print $ENV{redMsg};
	exit $am_states{AM_STATE_RED};
    }
}

print $ENV{greenMsg};
exit $am_states{AM_STATE_GREEN};
