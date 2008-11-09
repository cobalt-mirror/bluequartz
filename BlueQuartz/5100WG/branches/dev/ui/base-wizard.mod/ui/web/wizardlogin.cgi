#!/usr/bin/perl

my $proto = 'http'; # would like to default to https
# $proto = 'http' if ( macintosh/ie test FIXME );

my $port = 444;
$port = 81 if ($proto =~ /s$/);

# print "Content-type: text/html\n\n<BODY><PRE>";
# foreach my $x (keys %ENV) {
# 	print "$x\t".$ENV{$x}."\n";
# }
# exit 0;

my $date = time();
print "Location: $proto://".$ENV{'SERVER_ADDR'}.":$port/loginHandler.php?timeStamp=$date&reuseWindow=1&target=/base/wizard/index.php&fallback=/login.php\?target=/base/wizard/index.php&newLoginName=admin&newLoginPassword=admin\n\n";

exit 0;
