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
/* $Id$ */

#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php.h"
#include "php_ini.h"
#include "php_globals.h"
#include "ext/standard/info.h"
#include "ext/standard/php_string.h"

#if HAVE_CRACK

#include "php_crack.h"
#include "libcrack/src/cracklib.h"

/* True global resources - no need for thread safety here */
static int le_crack;

ZEND_BEGIN_ARG_INFO_EX(crack_opendict_args, 0, ZEND_RETURN_VALUE, 1)
	ZEND_ARG_INFO(0, dictionary)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(crack_closedict_args, 0, ZEND_RETURN_VALUE, 0)
	ZEND_ARG_INFO(0, dictionary)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(crack_check_args, 0, ZEND_RETURN_VALUE, 1)
	ZEND_ARG_INFO(0, password)
	ZEND_ARG_INFO(0, username)
	ZEND_ARG_INFO(0, gecos)
	ZEND_ARG_INFO(0, dictionary)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(crack_getlastmessage_args, 0, ZEND_RETURN_VALUE, 0)
ZEND_END_ARG_INFO()

/* {{{ crack_functions[]
 */
zend_function_entry crack_functions[] = {
	ZEND_FE(crack_opendict,			crack_opendict_args)
	ZEND_FE(crack_closedict,			crack_closedict_args)
	ZEND_FE(crack_check,				crack_check_args)
	ZEND_FE(crack_getlastmessage,	crack_getlastmessage_args)
	{NULL, NULL, NULL}
};
/* }}} */

/* {{{ crack_module_entry
 */
zend_module_entry crack_module_entry = {
#if ZEND_MODULE_API_NO >= 20010901
    STANDARD_MODULE_HEADER,
#endif
	"crack",
	crack_functions,
	PHP_MINIT(crack),
	PHP_MSHUTDOWN(crack),
	PHP_RINIT(crack),
	PHP_RSHUTDOWN(crack),
	PHP_MINFO(crack),
	PHP_CRACK_VERSION,
	STANDARD_MODULE_PROPERTIES,
};
/* }}} */

ZEND_DECLARE_MODULE_GLOBALS(crack)

#ifdef COMPILE_DL_CRACK
ZEND_GET_MODULE(crack)
#endif

/* {{{ PHP_INI */
PHP_INI_BEGIN()
	STD_PHP_INI_ENTRY("crack.default_dictionary", NULL, PHP_INI_PERDIR|PHP_INI_SYSTEM, OnUpdateString, default_dictionary, zend_crack_globals, crack_globals)
PHP_INI_END()
/* }}} */

/* {{{ php_crack_init_globals
 */
static void php_crack_init_globals(zend_crack_globals *crack_globals)
{
	crack_globals->last_message = NULL;
#if PHP_VERSION_ID >= 70000
	crack_globals->default_dict = NULL;
#else
	crack_globals->default_dict = -1;
#endif
}
/* }}} */

/* {{{ php_crack_checkpath
 */
static int php_crack_checkpath(char* path TSRMLS_DC)
{
	char *filename;
	int filename_len;
	int result = SUCCESS;

#if PHP_VERSION_ID < 50400
	if (PG(safe_mode)) {
		filename_len = strlen(path) + 10;
		filename = (char *) emalloc(filename_len);
		if (NULL == filename) {
			return FAILURE;
		}

		memset(filename, '\0', filename_len);
		strcpy(filename, path);
		strcat(filename, ".pwd");
		if (!php_checkuid(filename, "r", CHECKUID_CHECK_FILE_AND_DIR)) {
			efree(filename);
			return FAILURE;
		}

		memset(filename, '\0', filename_len);
		strcpy(filename, path);
		strcat(filename, ".pwi");
		if (!php_checkuid(filename, "r", CHECKUID_CHECK_FILE_AND_DIR)) {
			efree(filename);
			return FAILURE;
		}

		memset(filename, '\0', filename_len);
		strcpy(filename, path);
		strcat(filename, ".hwm");
		if (!php_checkuid(filename, "r", CHECKUID_CHECK_FILE_AND_DIR)) {
			efree(filename);
			return FAILURE;
		}
	}
#endif

	if (php_check_open_basedir(path TSRMLS_CC)) {
		return FAILURE;
	}

	return SUCCESS;
}
/* }}} */

/* {{{ php_crack_set_default_dict
 */
#if PHP_VERSION_ID >= 70000
static void php_crack_set_default_dict(zend_resource *id)
{
	if (CRACKG(default_dict) != NULL) {
		zend_list_close(CRACKG(default_dict));
	}

	CRACKG(default_dict) = id;
	id->gc.refcount++;
}
#else
static void php_crack_set_default_dict(int id TSRMLS_DC)
{
	if (CRACKG(default_dict) != -1) {
		zend_list_delete(CRACKG(default_dict));
	}

	CRACKG(default_dict) = id;
	zend_list_addref(id);
}
#endif
/* }}} */

/* {{{ php_crack_get_default_dict
 */
#if PHP_VERSION_ID >= 70000
static zend_resource * php_crack_get_default_dict(INTERNAL_FUNCTION_PARAMETERS)
{
	if ((NULL == CRACKG(default_dict)) && (NULL != CRACKG(default_dictionary))) {
		CRACKLIB_PWDICT *pwdict;
		pwdict = cracklib_pw_open(CRACKG(default_dictionary), "r");
		if (NULL != pwdict) {
			ZVAL_RES(return_value, zend_register_resource(pwdict, le_crack));
			php_crack_set_default_dict(Z_RES_P(return_value));
#else
static int php_crack_get_default_dict(INTERNAL_FUNCTION_PARAMETERS)
{
	if ((-1 == CRACKG(default_dict)) && (NULL != CRACKG(default_dictionary))) {
		CRACKLIB_PWDICT *pwdict;
		pwdict = cracklib_pw_open(CRACKG(default_dictionary), "r");
		if (NULL != pwdict) {
			ZEND_REGISTER_RESOURCE(return_value, pwdict, le_crack);
			php_crack_set_default_dict(Z_LVAL_P(return_value) TSRMLS_CC);
#endif
		}
	}

	return CRACKG(default_dict);
}
/* }}} */

/* {{{ php_crack_module_dtor
 */
#if PHP_VERSION_ID >= 70000
static void php_crack_module_dtor(zend_resource *rsrc)
#else
static void php_crack_module_dtor(zend_rsrc_list_entry *rsrc TSRMLS_DC)
#endif
{
	CRACKLIB_PWDICT *pwdict = (CRACKLIB_PWDICT *) rsrc->ptr;
	
	if (pwdict != NULL) {
		cracklib_pw_close(pwdict);
	}
}
/* }}} */

/* {{{ PHP_MINIT_FUNCTION
 */
PHP_MINIT_FUNCTION(crack)
{
#ifdef ZTS
	ZEND_INIT_MODULE_GLOBALS(crack, php_crack_init_globals, NULL);
#endif
	
	REGISTER_INI_ENTRIES();
	le_crack = zend_register_list_destructors_ex(php_crack_module_dtor, NULL, "crack dictionary", module_number);
#if PHP_VERSION_ID < 70000
	Z_TYPE(crack_module_entry) = type;
#endif
	
	return SUCCESS;
}

/* {{{ PHP_MSHUTDOWN_FUNCTION
 */
PHP_MSHUTDOWN_FUNCTION(crack)
{
	UNREGISTER_INI_ENTRIES();
	return SUCCESS;
}
/* }}} */

/* {{{ PHP_RINIT_FUNCTION
 */
PHP_RINIT_FUNCTION(crack)
{
	CRACKG(last_message) = NULL;
#if PHP_VERSION_ID >= 70000
	CRACKG(default_dict) = NULL;
#else
	CRACKG(default_dict) = -1;
#endif
	
	return SUCCESS;
}
/* }}} */

/* {{{ PHP_RSHUTDOWN_FUNCTION
 */
ZEND_MODULE_DEACTIVATE_D(crack)
{
	if (NULL != CRACKG(last_message)) {
		efree(CRACKG(last_message));
	}
	
	return SUCCESS;
}
/* }}} */

/* {{{ PHP_MINFO_FUNCTION
 */
PHP_MINFO_FUNCTION(crack)
{
	php_info_print_table_start();
	php_info_print_table_header(2, "crack support", "enabled");
	php_info_print_table_row(2, "extension version", PHP_CRACK_VERSION);
	php_info_print_table_end();
	
	DISPLAY_INI_ENTRIES();
}
/* }}} */

/* {{{ proto resource crack_opendict(string dictionary)
   Opens a new cracklib dictionary */
PHP_FUNCTION(crack_opendict)
{
	char *path;
	size_t path_len;
	CRACKLIB_PWDICT *pwdict;
	
	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s", &path, &path_len) == FAILURE) {
		RETURN_FALSE;
	}
	
	if (php_crack_checkpath(path TSRMLS_CC) == FAILURE) {
		RETURN_FALSE;
	}
	
	pwdict = cracklib_pw_open(path, "r");
	if (NULL == pwdict) {
#if ZEND_MODULE_API_NO >= 20021010
		php_error_docref(NULL TSRMLS_CC, E_WARNING, "Could not open crack dictionary: %s", path);
#else
		php_error(E_WARNING, "Could not open crack dictionary: %s", path);
#endif
		RETURN_FALSE;
	}

#if PHP_VERSION_ID >= 70000
	RETURN_RES(zend_register_resource(pwdict, le_crack));
	php_crack_set_default_dict(Z_RES_P(return_value));
#else
	ZEND_REGISTER_RESOURCE(return_value, pwdict, le_crack);
	php_crack_set_default_dict(Z_LVAL_P(return_value) TSRMLS_CC);
#endif
}
/* }}} */

/* {{{ proto bool crack_closedict([resource dictionary])
   Closes an open cracklib dictionary */
PHP_FUNCTION(crack_closedict)
{
	zval *dictionary = NULL;
#if PHP_VERSION_ID >= 70000
	zend_resource *id;
#else
	int id = -1;
#endif
	CRACKLIB_PWDICT *pwdict;
	
	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "|r", &dictionary)) {
		RETURN_FALSE;
	}
	
	if (NULL == dictionary) {
		id = php_crack_get_default_dict(INTERNAL_FUNCTION_PARAM_PASSTHRU);
#if PHP_VERSION_ID >= 70000
		if (id == NULL) {
#else
		if (id == -1) {
#endif
#if ZEND_MODULE_API_NO >= 20021010
			php_error_docref(NULL TSRMLS_CC, E_WARNING, "Could not open default crack dicionary"); 
#else
			php_error(E_WARNING, "Could not open default crack dicionary"); 
#endif
			RETURN_FALSE;
		}
	}
#if PHP_VERSION_ID >= 70000
	if((pwdict = (CRACKLIB_PWDICT *)zend_fetch_resource(Z_RES_P(dictionary), "crack dictionary", le_crack)) == NULL)
	{
		RETURN_FALSE;
	}
	if (NULL == dictionary) {
		zend_list_close(CRACKG(default_dict));
		CRACKG(default_dict) = NULL;
	}
	else {
		zend_list_close(Z_RES_P(dictionary));
	}
#else
	ZEND_FETCH_RESOURCE(pwdict, CRACKLIB_PWDICT *, &dictionary, id, "crack dictionary", le_crack);

	if (NULL == dictionary) {
		zend_list_delete(CRACKG(default_dict));
		CRACKG(default_dict) = -1;
	}
	else {
		zend_list_delete(Z_RESVAL_P(dictionary));
	}
#endif
	RETURN_TRUE;
}
/* }}} */

/* {{{ proto bool crack_check(string password [, string username [, string gecos [, resource dictionary]]])
   Performs an obscure check with the given password */
PHP_FUNCTION(crack_check)
{
	zval *dictionary = NULL;
	char *password = NULL;
	size_t password_len;
	char *username = NULL;
	size_t username_len;
	char *gecos = NULL;
	size_t gecos_len;
	char *message;
	CRACKLIB_PWDICT *pwdict;
#if PHP_VERSION_ID >= 70000
	zend_resource *crack_res;
#else
	int id = -1;
#endif
	
	if (NULL != CRACKG(last_message)) {
		efree(CRACKG(last_message));
		CRACKG(last_message) = NULL;
	}
	
	if (zend_parse_parameters_ex(ZEND_PARSE_PARAMS_QUIET, ZEND_NUM_ARGS() TSRMLS_CC, "rs", &dictionary, &password, &password_len) == FAILURE) {
		if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s|ssr", &password, &password_len, &username, &username_len, &gecos, &gecos_len, &dictionary) == FAILURE) {
			RETURN_FALSE;
		}
	}
	
	if (NULL == dictionary) {
#if PHP_VERSION_ID >= 70000
		crack_res = php_crack_get_default_dict(INTERNAL_FUNCTION_PARAM_PASSTHRU);
		if (crack_res == NULL || crack_res->ptr == NULL) {
			php_error(E_WARNING, "Could not open default crack dicionary");
			RETURN_FALSE;
		}

	}
	else {
		if((pwdict = (CRACKLIB_PWDICT *)zend_fetch_resource(Z_RES_P(dictionary), "crack dictionary", le_crack)) == NULL) {
			php_error(E_WARNING, "Could not open crack dicionary resource");
			RETURN_FALSE;
		}
	}
#else
		id = php_crack_get_default_dict(INTERNAL_FUNCTION_PARAM_PASSTHRU);
		if (id == -1) {
#if ZEND_MODULE_API_NO >= 20021010
			php_error_docref(NULL TSRMLS_CC, E_WARNING, "Could not open default crack dicionary"); 
#else
			php_error(E_WARNING, "Could not open default crack dicionary"); 
#endif
			RETURN_FALSE;
		}
	}
	ZEND_FETCH_RESOURCE(pwdict, CRACKLIB_PWDICT *, &dictionary, id, "crack dictionary", le_crack);
#endif
	
	message = cracklib_fascist_look_ex(pwdict, password, username, gecos);
	
	if (NULL == message) {
		CRACKG(last_message) = estrdup("strong password");
		RETURN_TRUE;
	}
	else {
		CRACKG(last_message) = estrdup(message);
		RETURN_FALSE;
	}
}
/* }}} */

/* {{{ proto string crack_getlastmessage(void)
   Returns the message from the last obscure check */
PHP_FUNCTION(crack_getlastmessage)
{
	if (ZEND_NUM_ARGS() != 0) {
		WRONG_PARAM_COUNT;
	}
	
	if (NULL == CRACKG(last_message)) {
#if ZEND_MODULE_API_NO >= 20021010
		php_error_docref(NULL TSRMLS_CC, E_WARNING, "No obscure checks in this session");
#else
		php_error(E_WARNING, "No obscure checks in this session");
#endif
		RETURN_FALSE;
	}
	
#if PHP_VERSION_ID >= 70000
	RETURN_STRING(CRACKG(last_message));
#else
	RETURN_STRING(CRACKG(last_message), 1);
#endif
}
/* }}} */

#endif /* HAVE_CRACK */
