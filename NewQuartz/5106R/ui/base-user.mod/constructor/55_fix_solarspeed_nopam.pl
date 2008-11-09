#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: 55_fix_solarspeed_nopam.pl, v1.0.0.0 Wed 23 Jan 2008 03:24:29 PM CET mstauber Exp $
#
# This constructor is a hotfix for people that were using the Solarspeed base-user*-NOPAM
# RPMs to switch their servers from PAM to Shadow. Now that we have a different
# implementation, this script checks for the presence of the modification and brings their
# installs back in line with the base BlueQuartz distribution.
#
# This constructor will only do something meaningful if:
#
# /etc/sysconfig/bq_use_shadow is present (means server was converted from PAM to Shadow by 
# Solarspeed.net).

use CCE;
my $cce = new CCE;
my $conf = '/var/lib/cobalt';

$cce->connectuds();

# Check if Solarspeed NOPAM is in effect:
$solhack = 0;
if (-e "/etc/sysconfig/bq_use_shadow") {
        $solhack = "1";

        # Check if we have already been converted:
        @oids = $cce->find('System', '');
        if ($#oids < 0) {
            print "Object 'System' not found in CCE.\n";
        }
        # Manually set convert2passwd to "1" in CODB:
        elsif ($solhack == "1") {
            ($sys_oid) = $cce->find('System', '');
            ($ok) = $cce->set($sys_oid, 'convert2passwd', {
                    'convert' => '1'
                  });

                # Delete /etc/sysconfig/bq_use_shadow as it is no longer needed:
                system("/bin/rm -f /etc/sysconfig/bq_use_shadow");
        }
}

$cce->bye();

# failed?
if(!$success)
{
  exit 1;
}

exit 0;

