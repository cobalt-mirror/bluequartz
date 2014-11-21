/*
   +----------------------------------------------------------------------+
   | PHP Version 4                                                        |
   +----------------------------------------------------------------------+
   | Copyright (c) 1997-2005 The PHP Group                                |
   +----------------------------------------------------------------------+
   | This source file is subject to version 3.0 of the PHP license,       |
   | that is bundled with this package in the file LICENSE, and is        |
   | available through the world-wide-web at the following url:           |
   | http://www.php.net/license/3_0.txt.                                  |
   | If you did not receive a copy of the PHP license and are unable to   |
   | obtain it through the world-wide-web, please send a note to          |
   | license@php.net so we can mail you a copy immediately.               |
   +----------------------------------------------------------------------+
   | Authors: Alexander Feldman                                           |
   |          Sascha Kettler                                              |
   +----------------------------------------------------------------------+
 */

/* $Id: php_crack.h 257091 2008-04-10 15:51:36Z sfox $ */

#ifndef PHP_CRACK_H
#define PHP_CRACK_H

#ifdef PHP_WIN32
#define PHP_CRACK_API __declspec(dllexport)
#else
#define PHP_CRACK_API
#endif

#if HAVE_CRACK

#ifdef ZTS
#include "TSRM.h"
#endif

extern zend_module_entry crack_module_entry;

#define crack_module_ptr &crack_module_entry

#define PHP_CRACK_VERSION "0.5.0-dev"

PHP_MINIT_FUNCTION(crack);
PHP_MSHUTDOWN_FUNCTION(crack);
PHP_RINIT_FUNCTION(crack);
PHP_RSHUTDOWN_FUNCTION(crack);
PHP_MINFO_FUNCTION(crack);

PHP_FUNCTION(crack_opendict);
PHP_FUNCTION(crack_closedict);
PHP_FUNCTION(crack_check);
PHP_FUNCTION(crack_getlastmessage);

ZEND_BEGIN_MODULE_GLOBALS(crack)
    char *default_dictionary;
	char *last_message;
	int default_dict;
ZEND_END_MODULE_GLOBALS(crack)

#ifdef ZTS
# define CRACKG(v) TSRMG(crack_globals_id, zend_crack_globals *, v)
#else
# define CRACKG(v) (crack_globals.v)
#endif

#else

#define crack_module_ptr NULL

#endif

#define phpext_crack_ptr crack_module_ptr

#endif	/* PHP_CRACK_H */
