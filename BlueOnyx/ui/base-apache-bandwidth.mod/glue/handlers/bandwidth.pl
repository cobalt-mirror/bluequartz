#!/usr/bin/perl -I/usr/sausalito/perl

use CCE;
use Base::Httpd qw(httpd_get_vhost_conf_file);

my $cce = new CCE('Domain' => 'base-apache-bandwidth');
$cce->connectfd();

my $vsite = $cce->event_object();

my ($ok, $band) = $cce->get($cce->event_oid(), 'ApacheBandwidth');

if(!Sauce::Util::editfile(httpd_get_vhost_conf_file($vsite->{name}), 
                            *edit_vhost, $band))
{
    $cce->bye('FAIL', '[[base-apache.cantEditVhost]]');
    exit(1);
}

$cce->bye('SUCCESS');
exit(0);

sub edit_vhost
{
    my ($in, $out, $band) = @_;

    my $band_conf = '';

    my $begin = '# BEGIN Bandwidth SECTION.  DO NOT EDIT MARKS OR IN BETWEEN.';
    my $end = '# END Bandwidth SECTION.  DO NOT EDIT MARKS OR IN BETWEEN.';

    if ($band->{enabled}) {
        $band_conf .= "CBandSpeed " . $band->{speed} . " 10 30\n";
    }

    my $last;
    while(<$in>)
    {
        if(/^<\/VirtualHost>/i) { $last = $_; last; }

        if(/^$begin$/)
        {
            while(<$in>)
            {
                if(/^$end$/) { last; }
            }
        }
        else
        {
            print $out $_;
        }
    }

    print $out $begin, "\n";
    print $out $band_conf;
    print $out $end, "\n";
    print $out $last;
    while(<$in>)
    {
        print $out $_;
    }

    return 1;
}

