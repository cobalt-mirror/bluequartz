#ifndef _I18N_PHP_H

#define _I18N_PHP_H
#define i18n_module_ptr &i18n_module_entry
#include "php.h"

extern zend_module_entry i18n_module_entry;
#define phpext_i18n_ptr &i18n_module_entry

#define I18N_TYPE 18

PHP_MINFO_FUNCTION(i18n);
PHP_MINIT_FUNCTION(i18n);
PHP_FUNCTION(i18n_new);
PHP_FUNCTION(i18n_get);
PHP_FUNCTION(i18n_get_js);
PHP_FUNCTION(i18n_get_html);
PHP_FUNCTION(i18n_get_property);
PHP_FUNCTION(i18n_get_file);
PHP_FUNCTION(i18n_interpolate);
PHP_FUNCTION(i18n_interpolate_js);
PHP_FUNCTION(i18n_interpolate_html);
PHP_FUNCTION(i18n_availlocales);
PHP_FUNCTION(i18n_locales);
PHP_FUNCTION(i18n_strftime);

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
