#!/usr/bin/perl -w -I/usr/sausalito/perl/ -I/usr/sausalito/handlers/base/email/
# $Id: system.pl 1290 2009-10-12 10:31:10Z shibuya $
# Copyright 2009 Project BlueQuartz, All rights reserved.

use CCE;

use Email;

use Sauce::Util;
use Sauce::Service;

my $Postfix_cf = Email::PostfixMainCF;

my $cce = new CCE;
$cce->connectfd();

my @oids = $cce->find('EmailRoute');

# create the /etc/postfix/transport file
my $transport = <<EOF;
# /etc/postfix/transport
# Auto-generated file.  Keep your customizations at the bottom of this file.
EOF

my $relay = '';

for my $oid (@oids) {
    my ($ok, $obj) = $cce->get($oid);
    if ($ok) {
        $transport .= $obj->{target} . "\tsmtp:[" . $obj->{server} . "]\n";
        $relay .= ' ' . $obj->{target};
    }
}

# add end of CCE maintained /etc/postfix/transport section
$transport .= <<EOF;
#END of auto-generated code.  Customize beneath this line.
EOF

Sauce::Util::editfile('/etc/postfix/transport', *make_transport, $transport);
Sauce::Util::chmodfile(0644,'/etc/postfix/transport');
system("/usr/sbin/postmap hash:/etc/postfix/transport > /dev/null 2>&1");

Sauce::Util::editfile($Postfix_cf, *make_main_cf, $relay);

Sauce::Service::service_toggle_init('postfix', 1);

$cce->bye('SUCCESS');
exit(0);


sub make_main_cf
{
    my $in  = shift;
    my $out = shift;

    my $text = shift;

    my $found = 0;
    my $r_found = 0;

    select $out;
    while (<$in>) {
        if (/^# Add configuration for BlueQuartz by init script./o) {
            $found = 1;
        } elsif (!$found) {
            print $_;
        }

        if ($found) {
            if ($text eq '') {
                if ( /^relay_domains =/o ) {
                    print "#relay_domains =\n";
                    $r_found = 1;
                } else {
                    print $_;
                }
            } else {
                if ( /^relay_domains =/o || /^#relay_domains =/o ) {
                    print "relay_domains =$text\n";
                    $r_found = 1;
                } else {
                    print $_;
                }
            }
       }
    }

    if (!$r_found) {
        if ($text eq '') {
            print "#relay_domains =\n";
        } else {
            print "relay_domains =$text\n";
        }
    }
    return 1;
}


# update /etc/postfix/transport
sub make_transport
{
    my ($fin, $fout) = (shift,shift);
    my ($text) = (shift);

    # print out the CCE maintained section
    print $fout $text;

    # mark whether we found the end yet
    my $flag = 0;
    while (<$fin>) 
    {
        # print out customizations after the END mark
        if ($flag) 
        {
            # need explicit $_ here, or perl gets confused with the file handle
            print $fout $_; 
        }
        else 
        {
            # remember that the END has come
            if (m/^#END/) { $flag = 1; }
        }
    }
    return 1;
};

