# pecl-crack
"Good Password" Checking Utility

[![PECL](https://img.shields.io/badge/PECL-0.4-blue.svg)](https://pecl.php.net/package/crack)

This package provides an interface to the cracklib (libcrack) libraries that come standard on most unix-like distributions. This allows you to check passwords against dictionaries of words to ensure some minimal level of password security.

## Documentation

Documentation is available on [php.net](http://docs.php.net/manual/en/book.crack.php).

## Installation

The easiest way to install the extension is to use PECL:

```
pecl install crack
```

If you're on Windows, you can download a compiled .dll on [PECL](https://pecl.php.net/package/crack).

## Enabling the extension

You'll need to add `extension=crack.so` to your primary *php.ini* file.

**Note**: Windows would use php_crack.dll instead.
