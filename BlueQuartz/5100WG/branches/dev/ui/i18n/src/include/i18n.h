/* 
 * $Id: i18n.h 201 2003-07-18 19:11:07Z will $
 */

#ifndef __I18N_H__
#define __I18N_H__ 1

#include <glib.h>
#include <time.h>

/* essential types */
typedef struct i18n_handle_struct i18n_handle;
typedef struct GHashTable i18n_vars;

/* create and destroy the i18n_handle object */
i18n_handle *i18n_new(char *domain, char *locales);
void i18n_destroy(i18n_handle *handle);

/*
 * Read various internationalized strings 
 * DO NOT FREE THE DATA THAT THESE RETURN...
 */
char *i18n_interpolate(i18n_handle *i, char *str, i18n_vars *vars);
char *i18n_interpolate_html(i18n_handle *i, char *str, i18n_vars *vars);
char *i18n_interpolate_js(i18n_handle *i, char *str, i18n_vars *vars);

char *i18n_get(i18n_handle *i, char *tag, char *domain, i18n_vars *vars);
char *i18n_get_html(i18n_handle *i, char *tag, char *domain, i18n_vars *vars);
char *i18n_get_js(i18n_handle *i, char *tag, char *domain, i18n_vars *vars);

char *i18n_strftime(i18n_handle *i, char *format, time_t time);
char *i18n_get_datetime(i18n_handle *i, time_t t);
char *i18n_get_date(i18n_handle *i, time_t t);
char *i18n_get_time(i18n_handle *i, time_t t);

char *i18n_get_property(i18n_handle *i, char *prop, char *domain, char *lang);
char *i18n_get_file(i18n_handle *i, char *file);
GSList *i18n_locales(i18n_handle *i, char *domain);

/* 
 * The following encode/escape strings for use in various ways
 * the pointer might change (be realloced). 
 */

char *i18n_encode_html(i18n_handle *i, char *s);
char *i18n_encode_js(i18n_handle *i, char *s);

GSList *i18n_availlocales (char * domain);

/*
 * The following provide the functions to mainuplate variables to
 * be passed to interpolations
 */

i18n_vars *i18n_vars_new(void);
void i18n_vars_add(i18n_vars *vars, char *key, char *value);
void i18n_vars_destroy(i18n_vars *vars);

#define C_MESSAGES_DIR        "/usr/share/locale"
#define MESSAGE_DIR           "LC_MESSAGES"
#define C_PROP_DIR            "/usr/share/locale"

#define MAX_PROP_LINE       256
#define MAX_LANG_LEN      16
#define MAX_FTIME_LEN     256

/* Default Defines */
#define DEFAULT_DEFAULT_LANGUAGE  "en"
#define DEFAULT_DEFAULT_DOMAIN  "cobalt"

#endif /* i18n.h */
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
