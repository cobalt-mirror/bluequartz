IMPORTANT:

Since the addition of the /cracklib source tree this module requires (at least on CentOS)
the presence of the following RPMs in order to build correctly:

libtool
libtool-libs
gcc-c++

They were previously not necessary, so your build will fail unless you install these.

For usage information of crack.so from within PHP see: http://de2.php.net/manual/en/ref.crack.php

crack.so returns one of the following messages upon password checks:

- strong password
- it is based on a dictionary word
- it is based on a (reversed) dictionary word
- it's WAY too short
- it is too short
- it does not contain enough DIFFERENT characters
- it is all whitespace
- it is too simplistic/systematic
- it looks like a National Insurance number

At the moment several pages in base-user.mod use this library for strong password enforcements.

-- mstauber



