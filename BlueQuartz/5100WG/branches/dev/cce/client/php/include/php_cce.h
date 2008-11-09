#ifndef _I18N_PHP_H
#define _I18N_PHP_H

#include <php.h>

extern zend_module_entry cce_module_entry;
#define phpext_cce_ptr &cce_module_entry

/* Meanaing of my life plus a bit */
#define CCE_TYPE 43

PHP_MINFO_FUNCTION(cce);
PHP_MINIT_FUNCTION(cce);
PHP_FUNCTION(cce_auth);
PHP_FUNCTION(cce_create);
PHP_FUNCTION(cce_destroy);
PHP_FUNCTION(cce_set);
PHP_FUNCTION(cce_get);
PHP_FUNCTION(cce_commit);
PHP_FUNCTION(cce_names);
PHP_FUNCTION(cce_find);
PHP_FUNCTION(cce_authkey);
PHP_FUNCTION(cce_whoami);
PHP_FUNCTION(cce_endkey);
PHP_FUNCTION(cce_bye);

/* Object creator */
PHP_FUNCTION(cce_new);

/* Meta Functions */
PHP_FUNCTION(cce_connect);
PHP_FUNCTION(cce_errors);

/* Handler functions */
PHP_FUNCTION(cce_handler_bye);
PHP_FUNCTION(cce_bad_data);
PHP_FUNCTION(cce_handler_get);

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
