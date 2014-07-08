#!/usr/bin/perl -w
# $Id: $

use CCE;
use Base::Httpd qw(httpd_get_vhost_conf_file);

my $cce = new CCE;
$cce->connectfd();

my $vsite = $cce->event_object();

my ($void) = $cce->find('VirtualHost', {'name' => $vsite->{name}});
my ($ok) = $cce->set($void, '',
                     { 'sslDirty' => time() });

$cce->bye('SUCCESS');
exit(0);

