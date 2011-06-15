#!/usr/bin/perl -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/vsite
# Copyright 2008-2011 Team BlueOnyx. All rights reserved.
# $Id: web_alias_redirects.pl,v 1.0.0.0 Wed 15 Jun 2011 07:23:09 AM CEST mstauber Exp $
#
# Two different behaviours are possible for 'Web Server Aliases':
#
# 1.) A web server alias will redirect to the sites primary domain name.
# 2.) Each web server alias will have it's own URL. Each of them shows the content of the website.
#
# This behaviour can be configured for each site through the switch "Web Alias Redirects".
#
# If the checkbox is ticked, behaviour #1 is used (redirects to main site).
# If the checkbox is not ticked, behaviour #2 is used and no redirects happen.
#
# With this script you can set ALL sites on this server to one of the two behaviours in one go.
#
# 	USAGE:
# 	======
#
#	/usr/sausalito/sbin/web_alias_redirects.pl --enabled
#	/usr/sausalito/sbin/web_alias_redirects.pl --disabled


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

# Handle switches:
$type_switch = $ARGV[0];
if ($type_switch eq "--enabled") {
        $switch = "1";
        $switch_desc = "Enabling";
}
elsif ($type_switch eq "--disabled") {
        $switch = "0";
        $switch_desc = "Disabling";
}
else {
    print "\n";
    print "Usage:\n";
    print "======\n";
    print "\n";
    print "/usr/sausalito/sbin/web_alias_redirects.pl --enabled\n";
    print "/usr/sausalito/sbin/web_alias_redirects.pl --disabled\n";
    print "\n";
    exit(0);
}



# Find all Vsites:
my @vhosts = ();
my (@vhosts) = $cce->findx('Vsite');

# Walk through all Vsites:
for my $vsite (@vhosts) {
    ($ok, my $my_vsite) = $cce->get($vsite);

    print "\n$switch_desc redirects for Site $my_vsite->{fqdn}\n";

    # Set 'VirtualHost':
    my ($VirtualHost) = $cce->find('VirtualHost', { 'name' => $my_vsite->{name} });
    ($ok) = $cce->set($VirtualHost, '', { 'webAliases' => '', 'webAliasRedirects' => $switch});
    ($ok) = $cce->set($VirtualHost, '', { 'webAliases' => $my_vsite->{webAliases}, 'webAliasRedirects' => $switch});

    # Set 'Vsite':
    ($ok) = $cce->set($vsite, '', { 'webAliases' => '', 'webAliasRedirects' => $switch}); 
    ($ok) = $cce->set($vsite, '', { 'webAliases' => $my_vsite->{webAliases}, 'webAliasRedirects' => $switch});

}

$cce->bye('SUCCESS');
exit(0);

