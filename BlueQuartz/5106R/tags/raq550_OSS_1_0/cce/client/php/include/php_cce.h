/* $Id: php_cce.h 259 2004-01-03 06:28:40Z shibuya $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */

#ifndef _I18N_PHP_H
#define _I18N_PHP_H

#include <php.h>

extern zend_module_entry ccephp_module_entry;
#define phpext_ccephp_ptr &ccephp_module_entry

/* Meanaing of my life plus a bit */
#define CCE_TYPE 43

PHP_MINFO_FUNCTION(ccephp);
PHP_MINIT_FUNCTION(ccephp);
PHP_FUNCTION(ccephp_auth);
PHP_FUNCTION(ccephp_suspend);
PHP_FUNCTION(ccephp_resume);
PHP_FUNCTION(ccephp_create);
PHP_FUNCTION(ccephp_destroy);
PHP_FUNCTION(ccephp_set);
PHP_FUNCTION(ccephp_get);
PHP_FUNCTION(ccephp_begin);
PHP_FUNCTION(ccephp_commit);
PHP_FUNCTION(ccephp_names);
PHP_FUNCTION(ccephp_find);
PHP_FUNCTION(ccephp_findx);
PHP_FUNCTION(ccephp_authkey);
PHP_FUNCTION(ccephp_whoami);
PHP_FUNCTION(ccephp_endkey);
PHP_FUNCTION(ccephp_bye);
PHP_FUNCTION(ccephp_is_rollback);

/* Object creator */
PHP_FUNCTION(ccephp_new);

/* Meta Functions */
PHP_FUNCTION(ccephp_connect);
PHP_FUNCTION(ccephp_suspended);
PHP_FUNCTION(ccephp_errors);

/* Handler functions */
PHP_FUNCTION(ccephp_handler_bye);
PHP_FUNCTION(ccephp_bad_data);
PHP_FUNCTION(ccephp_handler_get);

#endif
/* Copyright (c) 2003 Sun Microsystems, Inc. All  Rights Reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met: 
 * 
 * -Redistribution of source code must retain the above copyright notice, this
 * list of conditions and the following disclaimer.
 * 
 * -Redistribution in binary form must reproduce the above copyright notice,
 * this list of conditions and the following disclaimer in the documentation
 * and/or other materials provided with the distribution. 
 *
 * Neither the name of Sun Microsystems, Inc. or the names of contributors may
 * be used to endorse or promote products derived from this software without 
 * specific prior written permission.

 * This software is provided "AS IS," without a warranty of any kind. ALL EXPRESS OR IMPLIED CONDITIONS, REPRESENTATIONS AND WARRANTIES, INCLUDING ANY IMPLIED WARRANTY OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE OR NON-INFRINGEMENT, ARE HEREBY EXCLUDED. SUN MICROSYSTEMS, INC. ("SUN") AND ITS LICENSORS SHALL NOT BE LIABLE FOR ANY DAMAGES SUFFERED BY LICENSEE AS A RESULT OF USING, MODIFYING OR DISTRIBUTING THIS SOFTWARE OR ITS DERIVATIVES. IN NO EVENT WILL SUN OR ITS LICENSORS BE LIABLE FOR ANY LOST REVENUE, PROFIT OR DATA, OR FOR DIRECT, INDIRECT, SPECIAL, CONSEQUENTIAL, INCIDENTAL OR PUNITIVE DAMAGES, HOWEVER CAUSED AND REGARDLESS OF THE THEORY OF LIABILITY, ARISING OUT OF THE USE OF OR INABILITY TO USE THIS SOFTWARE, EVEN IF SUN HAS BEEN ADVISED OF THE POSSIBILITY OF SUCH DAMAGES.
 * 
 * You acknowledge that  this software is not designed or intended for use in the design, construction, operation or maintenance of any nuclear facility.
 */
