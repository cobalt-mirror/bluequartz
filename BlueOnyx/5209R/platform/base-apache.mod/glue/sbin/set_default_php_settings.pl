#!/usr/bin/perl -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/vsite
#
# $Id: set_default_php_settings.pl, v 1.0.0.1 Oct 15 2009 05:32:01 AM EDT mstauber Exp $
# Copyright 2006-2009 Team BlueOnyx. All rights reserved.
#
# This script walks through all sites and pushes the default PHP security settings into
# the /etc/httpd/conf/vhosts/site* files of all sites.
#
# Please note: With 'default' it means: Default as defined by BlueOnyx. Not what you
# set as server wide default in the GUI interface.
#
# Usage:
#
# Simply run this script once. Running it multiple times will do no harm, though.

use CCE;
my $cce = new CCE;
$cce->connectuds();

# Root check:
my $id = `id -u`;
chomp($id);
if ($id ne "0") {
    print "$0 must be run by user 'root'!\n";

    $cce->bye('FAIL');
    exit(1);
}

# Find all Vsites:
my @vhosts = ();
my (@vhosts) = $cce->findx('Vsite');

print "Going through all sites to reset the PHP security settings to defaults: \n";

# Walk through all Vsites:
for my $vsite (@vhosts) {
    ($ok, my $my_vsite) = $cce->get($vsite);

    print "Processing Site: $my_vsite->{fqdn} \n";

    ($ok) = $cce->set($vsite, 'PHPVsite',{
        'open_basedir' => '/tmp/:/var/lib/php/session/',
        'max_execution_time' => '30',
        'safe_mode_exec_dir' => '.',
        'upload_max_filesize' => '2M',
        'max_input_time' => '60',
        'safe_mode_gid' => 'Off',
        'safe_mode_protected_env_vars' => 'LD_LIBRARY_PATH',
        'allow_url_fopen' => 'Off',
        'memory_limit' => '16M',
        'safe_mode_include_dir' => '.',
        'safe_mode_allowed_env_vars' => 'PHP_',
        'allow_url_include' => 'Off',
        'register_globals' => 'Off',
        'safe_mode' => 'On',
        'post_max_size' => '8M',
        'force_update' => time()
       });

}

# tell cce everything is okay
$cce->bye('SUCCESS');
exit(0);

