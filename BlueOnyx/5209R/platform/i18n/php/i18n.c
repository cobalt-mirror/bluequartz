#include <php.h>
#include "ext/standard/info.h"

#include <stdlib.h>
#include <ctype.h>
#include <stdio.h>
#include <string.h>

#include "php_i18n.h"
#include <cce/i18n.h>

zend_function_entry i18n_functions[] = {
	PHP_FE(i18n_new,	NULL)
	PHP_FE(i18n_get,	NULL)
	PHP_FE(i18n_get_js,	NULL)
	PHP_FE(i18n_get_html,	NULL)
	PHP_FE(i18n_get_property,	NULL)
	PHP_FE(i18n_get_file,	NULL)
	PHP_FE(i18n_availlocales,	NULL)
	PHP_FE(i18n_locales,	NULL)
	PHP_FE(i18n_strftime,	NULL)
	PHP_FE(i18n_interpolate, NULL)
	PHP_FE(i18n_interpolate_js, NULL)
	PHP_FE(i18n_interpolate_html, NULL)
	{NULL, NULL, NULL}
};

zend_module_entry i18n_module_entry = {
	/* Added for PHP >= 4.1 */
#if ZEND_MODULE_API_NO >= 20010901
	STANDARD_MODULE_HEADER, /* Standard Recommended Header */
#endif
	"i18n",
       	i18n_functions,
       	PHP_MINIT(i18n),
       	NULL,
       	NULL,
       	NULL,
       	PHP_MINFO(i18n),
#if ZEND_MODULE_API_NO >= 20010901
	NO_VERSION_YET, /* Version String.  None yet. */
#endif
       	STANDARD_MODULE_PROPERTIES
};

static int list_i18n;

/* Internally used functions */
void              php_i18n_close        ( i18n_handle *i18n );
static i18n_vars* php_i18n_hash_to_vars ( HashTable *ht );

DLEXPORT zend_module_entry *get_module(void) { return &i18n_module_entry; }

PHP_MINIT_FUNCTION(i18n)
{
	list_i18n = register_list_destructors(php_i18n_close,NULL);
	return SUCCESS;
}

void
php_i18n_close(i18n_handle *i18n)
{
	i18n_destroy(i18n);
}

PHP_FUNCTION(i18n_new)
{
	zval *locale, *domain;
	int argc;
	i18n_handle *i18n;
	int index;
	char *loc_str;
	char *dom_str;

	argc = ARG_COUNT(ht);
	if ( argc !=2 || getParameters(ht, argc, &domain, &locale) == FAILURE ) {
		WRONG_PARAM_COUNT;
	}

	convert_to_string(locale);
	convert_to_string(domain);

	if( strlen( locale->value.str.val ) == 0 ) {
		loc_str = NULL;
	} else {
		loc_str = locale->value.str.val;
	}

	if( strlen( domain->value.str.val ) == 0 ) {
		dom_str = NULL;
	} else {
		dom_str = domain->value.str.val;
	}

	i18n = i18n_new(dom_str, loc_str);

	if (! i18n ) {
		php_error(E_WARNING,"i18n_new did not return a handle");
		/* FIXME: Right thing to do on failure ? */
		RETURN_LONG( 0 );
	}

	index = zend_list_insert(i18n, list_i18n);
	RETURN_LONG(index);
}

PHP_FUNCTION( i18n_availlocales )
{
	zval *domain;
	int argc;
	int type;
	GSList *result;

	argc = ARG_COUNT(ht);
	if ( argc > 1 || getParameters(ht, argc, &domain) == FAILURE )
	{
		WRONG_PARAM_COUNT;
	}

	convert_to_string(domain);

	if( array_init(return_value) == FAILURE ) {
		php_error(E_ERROR,"Could not initialize array");
		RETURN_FALSE;
	}

	result = i18n_availlocales(domain->value.str.val);

	while( result ) {
		add_next_index_string(return_value, result->data, 1);
		result = g_slist_next(result);
	}
}

PHP_FUNCTION( i18n_locales )
{
	zval *i18n_index, *domain;
	int argc;
	i18n_handle *i18n;
	int type;
	GSList *result;

	argc = ARG_COUNT(ht);
	if ( argc < 1 || argc > 2 || getParameters(ht, argc, &i18n_index,
			&domain) == FAILURE )
	{
		WRONG_PARAM_COUNT;
	}

	convert_to_long(i18n_index);
	convert_to_string(domain);

	i18n = (i18n_handle *) zend_list_find(i18n_index->value.lval, &type);
	if(! i18n ) {
		php_error(E_WARNING, "%d is not a valid i18n object index!", i18n_index->value.lval);
		RETURN_FALSE;
	}

	if( array_init(return_value) == FAILURE ) {
		php_error(E_ERROR,"Could not initialize array");
		RETURN_FALSE;
	}

	result = i18n_locales(i18n, domain->value.str.val);

	while( result ) {
		add_next_index_string(return_value, result->data, 1);
		result = g_slist_next(result);
	}
}

PHP_FUNCTION( i18n_get_property )
{
	zval *i18n_index, *property, *domain, *language;
	int argc;
	/* Don't know why we need type yet. */
	int type;
	char *result;
	i18n_handle *i18n;

	argc = ARG_COUNT(ht);
	if ( argc < 3 || argc > 4 || getParameters(ht, argc, &i18n_index,
			&property, &domain, &language) == FAILURE )
	{
		WRONG_PARAM_COUNT;
	}

	convert_to_long(i18n_index);
	convert_to_string(property);
	convert_to_string(domain);

	if ( argc == 4 ) {
		convert_to_string(language);
	}

	i18n = (i18n_handle *) zend_list_find(i18n_index->value.lval, &type);

	if(! i18n ) {
		php_error(E_WARNING, "%d is not a valid i18n object index!", i18n_index->value.lval);
		RETURN_FALSE;
	}

	if (argc == 3) {
		result = i18n_get_property(i18n, property->value.str.val, domain->value.str.val, NULL);
	} else {
		result = i18n_get_property(i18n, property->value.str.val, domain->value.str.val, language->value.str.val);
	}
	/* I think the 0 is to free the string, don't.. i18n destroy does that */
	RETURN_STRING(result, 1);
}

PHP_FUNCTION(i18n_get_file)
{
	zval *i18n_index, *file;
	int argc;
	/* Type is filled with the type number of a i18n_handle */
	int type;
	char *result;
	i18n_handle *i18n;

	argc = ARG_COUNT(ht);
	if ( argc != 2 || getParameters(ht, argc, &i18n_index,
			&file) == FAILURE )
	{
		WRONG_PARAM_COUNT;
	}

	convert_to_long(i18n_index);
	convert_to_string(file);

	i18n = (i18n_handle *) zend_list_find(i18n_index->value.lval, &type);
	if(! i18n ) {
		php_error(E_WARNING, "%d is not a valid i18n object index!", i18n_index->value.lval);
		RETURN_FALSE;
	}

	result = i18n_get_file(i18n, file->value.str.val);
	/* I think the 0 is to free the string, don't.. i18n destroy does that */
	RETURN_STRING(result, 1);
}

PHP_FUNCTION(i18n_interpolate)
{
	zval *i18n_index, *magicstr, *vars;
	i18n_handle *i18n;
	i18n_vars *i18n_vars;
	int argc;
	int type;
	char *result;
	
	argc = ARG_COUNT(ht);
  if (argc == 2) {
  	if (getParameters(ht, 2, &i18n_index, &magicstr) == FAILURE) {
    	WRONG_PARAM_COUNT;
    }
    i18n_vars = i18n_vars_new(); /* empty vars hash */
  } else if (argc == 3) {
  	if (getParameters(ht, 3, &i18n_index, &magicstr, &vars) == FAILURE) {
    	WRONG_PARAM_COUNT;
    } else {
    	if (vars->type != IS_ARRAY) {
      	php_error(E_WARNING, "i18n_interpolate: 3d arg must be array");
        RETURN_FALSE;
      }
    }
		i18n_vars = php_i18n_hash_to_vars(vars->value.ht);
  } else {
  	WRONG_PARAM_COUNT;
  }
	convert_to_long(i18n_index);
	convert_to_string(magicstr);

	i18n = (i18n_handle *) zend_list_find(i18n_index->value.lval, &type);
	if(! i18n ) {
		php_error(E_WARNING, "%d is not a valid i18n object index!", i18n_index->value.lval);
		RETURN_FALSE;
	}

  result = i18n_interpolate(i18n, magicstr->value.str.val, i18n_vars);
  i18n_vars_destroy(i18n_vars);

	RETURN_STRING(result, 1);
}

PHP_FUNCTION(i18n_interpolate_js)
{
	zval *i18n_index, *magicstr, *vars;
	i18n_handle *i18n;
	i18n_vars *i18n_vars;
	int argc;
	int type;
	char *result;
	
	argc = ARG_COUNT(ht);
  if (argc == 2) {
  	if (getParameters(ht, 2, &i18n_index, &magicstr) == FAILURE) {
    	WRONG_PARAM_COUNT;
    }
    i18n_vars = i18n_vars_new(); /* empty vars hash */
  } else if (argc == 3) {
  	if (getParameters(ht, 3, &i18n_index, &magicstr, &vars) == FAILURE) {
    	WRONG_PARAM_COUNT;
    } else {
    	if (vars->type != IS_ARRAY) {
      	php_error(E_WARNING, "i18n_interpolate: 3d arg must be array");
        RETURN_FALSE;
      }
    }
		i18n_vars = php_i18n_hash_to_vars(vars->value.ht);
  } else {
  	WRONG_PARAM_COUNT;
  }
	convert_to_long(i18n_index);
	convert_to_string(magicstr);

	i18n = (i18n_handle *) zend_list_find(i18n_index->value.lval, &type);
	if(! i18n ) {
		php_error(E_WARNING, "%d is not a valid i18n object index!", i18n_index->value.lval);
		RETURN_FALSE;
	}

  result = i18n_interpolate_js(i18n, magicstr->value.str.val, i18n_vars);
  i18n_vars_destroy(i18n_vars);

	RETURN_STRING(result, 1);
}

PHP_FUNCTION(i18n_interpolate_html)
{
	zval *i18n_index, *magicstr, *vars;
	i18n_handle *i18n;
	i18n_vars *i18n_vars;
	int argc;
	int type;
	char *result;
	
	argc = ARG_COUNT(ht);
  if (argc == 2) {
  	if (getParameters(ht, 2, &i18n_index, &magicstr) == FAILURE) {
    	WRONG_PARAM_COUNT;
    }
    i18n_vars = i18n_vars_new(); /* empty vars hash */
  } else if (argc == 3) {
  	if (getParameters(ht, 3, &i18n_index, &magicstr, &vars) == FAILURE) {
    	WRONG_PARAM_COUNT;
    } else {
    	if (vars->type != IS_ARRAY) {
      	php_error(E_WARNING, "i18n_interpolate: 3d arg must be array");
        RETURN_FALSE;
      }
    }
		i18n_vars = php_i18n_hash_to_vars(vars->value.ht);
  } else {
  	WRONG_PARAM_COUNT;
  }
	convert_to_long(i18n_index);
	convert_to_string(magicstr);

	i18n = (i18n_handle *) zend_list_find(i18n_index->value.lval, &type);
	if(! i18n ) {
		php_error(E_WARNING, "%d is not a valid i18n object index!", i18n_index->value.lval);
		RETURN_FALSE;
	}

  result = i18n_interpolate_html(i18n, magicstr->value.str.val, i18n_vars);
  i18n_vars_destroy(i18n_vars);

	RETURN_STRING(result, 1);
}

PHP_FUNCTION(i18n_get)
{
	zval *i18n_index, *tag, *domain, *vars;
	i18n_handle *i18n;
	i18n_vars *i18n_vars;
	int argc;
	int type;
	int num_elements;
	char *result;
	

	argc = ARG_COUNT(ht);
	if (argc < 2 || argc > 4 || getParameters(ht, argc, &i18n_index,
			&tag, &domain, &vars) == FAILURE ) {
		WRONG_PARAM_COUNT;
	}

	convert_to_long(i18n_index);
	convert_to_string(tag);

	if( argc >= 3 ) {
		convert_to_string(domain);
	}

	if( argc == 4 && vars->type  != IS_ARRAY ) {
		php_error(E_WARNING, "Fourth arg must be an array!");
		RETURN_FALSE;
	}
	
	i18n = (i18n_handle *) zend_list_find(i18n_index->value.lval, &type);
	if(! i18n ) {
		php_error(E_WARNING, "%d is not a valid i18n object index!", i18n_index->value.lval);
		RETURN_FALSE;
	}

	if ( argc == 2 ) {
		result = i18n_get(i18n, tag->value.str.val, NULL, NULL);
	} else if ( argc == 3) {
		result = i18n_get(i18n,tag->value.str.val,domain->value.str.val,NULL);
	} else if ( argc == 4 ) {
		i18n_vars = php_i18n_hash_to_vars(vars->value.ht);
		if( strlen( domain->value.str.val ) ) {
			result = i18n_get(i18n,
				tag->value.str.val,
				domain->value.str.val,
				i18n_vars);
		} else {
			result = i18n_get(i18n,
				tag->value.str.val,
				NULL,
				i18n_vars);
		}
    i18n_vars_destroy(i18n_vars);
	}

	RETURN_STRING(result, 1);
}

PHP_FUNCTION(i18n_get_js)
{
	zval *i18n_index, *tag, *domain, *vars;
	i18n_handle *i18n;
	i18n_vars *i18n_vars;
	int argc;
	int type;
	int num_elements;
	char *result;
	

	argc = ARG_COUNT(ht);
	if (argc < 2 || argc > 4 || getParameters(ht, argc, &i18n_index,
			&tag, &domain, &vars) == FAILURE ) {
		WRONG_PARAM_COUNT;
	}

	convert_to_long(i18n_index);
	convert_to_string(tag);

	if( argc >= 3 ) {
		convert_to_string(domain);
	}

	if( argc == 4 && vars->type  != IS_ARRAY ) {
		php_error(E_WARNING, "Fourth arg must be an array!");
		RETURN_FALSE;
	}
	
	i18n = (i18n_handle *) zend_list_find(i18n_index->value.lval, &type);
	if(! i18n ) {
		php_error(E_WARNING, "%d is not a valid i18n object index!", i18n_index->value.lval);
		RETURN_FALSE;
	}

	if ( argc == 2 ) {
		result = i18n_get_js(i18n, tag->value.str.val, NULL, NULL);
	} else if ( argc == 3) {
		result = i18n_get_js(i18n,tag->value.str.val,domain->value.str.val,NULL);
	} else if ( argc == 4 ) {
		i18n_vars = php_i18n_hash_to_vars(vars->value.ht);
		if( strlen( domain->value.str.val ) ) {
			result = i18n_get_js(i18n,
				tag->value.str.val,
				domain->value.str.val,
				i18n_vars);
		} else {
			result = i18n_get_js(i18n,
				tag->value.str.val,
				NULL,
				i18n_vars);
		}
    i18n_vars_destroy(i18n_vars);
	}

	RETURN_STRING(result, 1);
}

PHP_FUNCTION(i18n_get_html)
{
	zval *i18n_index, *tag, *domain, *vars;
	i18n_handle *i18n;
	i18n_vars *i18n_vars;
	int argc;
	int type;
	int num_elements;
	char *result;
	

	argc = ARG_COUNT(ht);
	if (argc < 2 || argc > 4 || getParameters(ht, argc, &i18n_index,
			&tag, &domain, &vars) == FAILURE ) {
		WRONG_PARAM_COUNT;
	}

	convert_to_long(i18n_index);
	convert_to_string(tag);

	if( argc >= 3 ) {
		convert_to_string(domain);
	}

	if( argc == 4 && vars->type  != IS_ARRAY ) {
		php_error(E_WARNING, "Fourth arg must be an array!");
		RETURN_FALSE;
	}
	
	i18n = (i18n_handle *) zend_list_find(i18n_index->value.lval, &type);
	if(! i18n ) {
		php_error(E_WARNING, "%d is not a valid i18n object index!", i18n_index->value.lval);
		RETURN_FALSE;
	}

	if ( argc == 2 ) {
		result = i18n_get_html(i18n, tag->value.str.val, NULL, NULL);
	} else if ( argc == 3) {
		result = i18n_get_html(i18n,tag->value.str.val,domain->value.str.val,NULL);
	} else if ( argc == 4 ) {
		i18n_vars = php_i18n_hash_to_vars(vars->value.ht);
		if( strlen( domain->value.str.val ) ) {
			result = i18n_get_html(i18n,
				tag->value.str.val,
				domain->value.str.val,
				i18n_vars);
		} else {
			result = i18n_get_html(i18n,
				tag->value.str.val,
				NULL,
				i18n_vars);
		}
    i18n_vars_destroy(i18n_vars);
	}

	RETURN_STRING(result, 1);
}


static i18n_vars*
php_i18n_hash_to_vars(HashTable *ht) {
	int num_fields;
	int i;
	int keytype;
	ulong keylength;
	char *keyname;
	i18n_vars *vars;
	zval *keydata, **keydataptr;

	vars = i18n_vars_new();

	if( ht == NULL ) {
		return vars;
	}

	if(! (num_fields = zend_hash_num_elements(ht) ) ) {
		return vars;
	}

	zend_hash_internal_pointer_reset(ht);
	for (i=0 ; i<num_fields ; i++) {
		/* NOTE: the prototype for zend_hash_get_current_key changed in
         * php 4.0.5.  It now requires an additional zend_bool argument
         * as the fourth argument.  Of course there is no docmentation
         * of this API easily found so I'm assuming false means no
         * duplicates. -- pbaltz 6/24/2001
         */

	/* NOTE: zend_hash_get_current_key with 4 arguments makes PHP 4.0.4
	 *	unhappy.  the API version number below comes from php-4.0.4C1q3
	 *	which, as I type this, is the version shipping on Qubes.
	 *	-- pmartin 6/26/2001
	 */

#if PHP_API_VERSION == 19990421
	keytype = zend_hash_get_current_key(ht, &keyname, &keylength);
#else
        keytype = zend_hash_get_current_key(ht, &keyname, &keylength, 0);
#endif
		zend_hash_get_current_data(ht, (void**) &keydataptr);
		keydata = *keydataptr;
		convert_to_string(keydata);
		i18n_vars_add(vars, keyname, keydata->value.str.val);
		zend_hash_move_forward(ht);
	}
	return vars;
}

PHP_FUNCTION(i18n_strftime)
{
	i18n_handle *i18n;
	char *result;
	zval *i18n_index, *format, *time;
	int argc, type;
	char *format_str;

	argc = ARG_COUNT(ht);
	if( argc != 3 || getParameters(ht,argc,&i18n_index,&format,&time)==FAILURE){
		WRONG_PARAM_COUNT;
	}

	convert_to_long(i18n_index);
	convert_to_string(format);
	convert_to_long(time);

	if ( strlen( format->value.str.val ) == 0 ) {
		format_str = NULL;
	} else {
		format_str = format->value.str.val;
	}

	i18n = (i18n_handle *) zend_list_find(i18n_index->value.lval, &type);
	if(! i18n ) {
		php_error(E_WARNING, "%d is not a valid i18n object index!", i18n_index->value.lval);
		RETURN_FALSE;
	}

	result = i18n_strftime(i18n, format_str, time->value.lval);
	RETURN_STRING(result,1);
}

PHP_MINFO_FUNCTION(i18n)
{
	php_info_print_table_start();
	php_info_print_table_row(2, "Cobalt I18n Support", "enabled");
	php_info_print_table_end();
}
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
