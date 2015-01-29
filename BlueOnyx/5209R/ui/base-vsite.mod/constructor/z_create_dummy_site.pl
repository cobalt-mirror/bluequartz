#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: z_create_dummy_site.pl
#
# This is a very dirty work around for fixing Vsite creation issue:
# We need to create a site this way first, otherwise future site 
# creation through the GUI will fail. So we create this dummy site,
# and delete it right afterwards. 
#
# This constructor only runs if the setup wizard hasn't been run yet
# and if there isn't already any site on the box.

use lib qw(/usr/sausalito/perl);
use CCE;

my $cce = new CCE;

$cce->connectuds();

# Get System object info:
($sys_oid) = $cce->find('System');
($ok, $system) = $cce->get($sys_oid);

# Get IP of venet0:0:
($sys_oid) = $cce->find('Network', {'device' => 'eth0'});
($ok, $network) = $cce->get($sys_oid);

# Make sure there is no Vsite yet, that the license isn't accepted yet and that we know the primary IP:
if ((not scalar($cce->find('Vsite'))) && ($network->{'ipaddr'} ne "") && ($system->{'isLicenseAccepted'} == "0")) {

        # Create the dummy Vsite:
        ($ok) = $cce->create('Vsite', {

                'webAliases' => '',
                'site_preview' => '0',
                'mailAliases' => '',
                'domain' => 'blueonyx.it',
                'ipaddr' => $network->{'ipaddr'},
                'maxusers' => '25',
                'prefix' => '',
                'emailDisabled' => '0',
                'volume' => '/home',
                'dns_auto' => '0',
                'fqdn' => 'dummy.blueonyx.it',
                'mailCatchAll' => '',
                'webAliasRedirects' => '1',
                'hostname' => 'dummy',
                'name' => 'site1',
                'basedir' => '/home/.sites/28/site1'
        });

        # Delete the dummy Vsite again:
        ($sys_oid) = $cce->find('Vsite', {'name' => 'site1', 'fqdn' => 'dummy.blueonyx.it'});
        if ($sys_oid) {
                ($ok, $sys) = $cce->get($sys_oid);
                ($ok) = $cce->destroy($sys_oid);
        }

}

$cce->bye('SUCCESS');
exit(0);
