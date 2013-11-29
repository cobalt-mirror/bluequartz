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

for my $oid (@oids) {
    my ($ok, $obj) = $cce->get($oid);
    if ($ok) {
        $transport .= $obj->{target} . "\tsmtp:[" . $obj->{server} . "]\n";
    }
}

# add end of CCE maintained /etc/postfix/transport section
$transport .= <<EOF;
#END of auto-generated code.  Customize beneath this line.
EOF

# update /etc/postfix/transport
my $fn = sub 
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

Sauce::Util::editfile('/etc/postfix/transport', $fn, $transport);
Sauce::Util::chmodfile(0644,'/etc/postfix/transport');
system("/usr/sbin/postmap hash:/etc/postfix/transport > /dev/null 2>&1");

Sauce::Service::service_toggle_init('postfix', 1);

$cce->bye('SUCCESS');
exit(0);

