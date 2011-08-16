#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: preview_site.pl 711 2006-03-11 15:21:10Z shibuya $
# Copyright Project BlueOnyx, All rights reserved.
#
# handle preview site configuration for a virtual site

use CCE;

my $DEBUG = 0;

my $cce = new CCE("Domain" => "base-vsite");
$cce->connectfd();

my $vsite = $cce->event_object();

my ($vhost) = $cce->findx('VirtualHost', { 'name' => $vsite->{name} });
if ($vhost) {
    my ($ok) = $cce->set($vhost, '',
        { 'site_preview' => $vsite->{site_preview} ? 1 : 0 });

    if (not $ok) {
        $cce->bye('FAIL', '[[base-vsite.cantChangeSitePreview]]');
        exit(1);
    }
}

$cce->bye('SUCCESS');
exit(0);
