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
/* $Id: crack.c,v 1.26 2005/09/21 08:59:57 skettler Exp $ */

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

/* {{{ crack_functions[]
 */
zend_function_entry crack_functions[] = {
	PHP_FE(crack_opendict,			NULL)
	PHP_FE(crack_closedict,			NULL)
	PHP_FE(crack_check,				NULL)
	PHP_FE(crack_getlastmessage,	NULL)
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
#if ZEND_MODULE_API_NO >= 20010901
	"0.3",
#endif
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
	crack_globals->default_dict = -1;
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
static void php_crack_set_default_dict(int id TSRMLS_DC)
{
	if (CRACKG(default_dict) != -1) {
		zend_list_delete(CRACKG(default_dict));
	}
	
	CRACKG(default_dict) = id;
	zend_list_addref(id);
}
/* }}} */

/* {{{ php_crack_get_default_dict
 */
static int php_crack_get_default_dict(INTERNAL_FUNCTION_PARAMETERS)
{
	if ((-1 == CRACKG(default_dict)) && (NULL != CRACKG(default_dictionary))) {
		CRACKLIB_PWDICT *pwdict;
		printf("trying to open: %s\n", CRACKG(default_dictionary));
		pwdict = cracklib_pw_open(CRACKG(default_dictionary), "r");
		if (NULL != pwdict) {
			ZEND_REGISTER_RESOURCE(return_value, pwdict, le_crack);
			php_crack_set_default_dict(Z_LVAL_P(return_value) TSRMLS_CC);
		}
	}
	
	return CRACKG(default_dict);
}
/* }}} */

/* {{{ php_crack_module_dtor
 */
static void php_crack_module_dtor(zend_rsrc_list_entry *rsrc TSRMLS_DC)
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
	Z_TYPE(crack_module_entry) = type;
	
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
	CRACKG(default_dict) = -1;
	
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
	php_info_print_table_end();
	
	DISPLAY_INI_ENTRIES();
}
/* }}} */

/* {{{ proto resource crack_opendict(string dictionary)
   Opens a new cracklib dictionary */
PHP_FUNCTION(crack_opendict)
{
	char *path;
	int path_len;
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
	
	ZEND_REGISTER_RESOURCE(return_value, pwdict, le_crack);
	php_crack_set_default_dict(Z_LVAL_P(return_value) TSRMLS_CC);
}
/* }}} */

/* {{{ proto bool crack_closedict([resource dictionary])
   Closes an open cracklib dictionary */
PHP_FUNCTION(crack_closedict)
{
	zval *dictionary = NULL;
	int id = -1;
	CRACKLIB_PWDICT *pwdict;
	
	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "|r", &dictionary)) {
		RETURN_FALSE;
	}
	
	if (NULL == dictionary) {
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
	
	if (NULL == dictionary) {
		zend_list_delete(CRACKG(default_dict));
		CRACKG(default_dict) = -1;
	}
	else {
		zend_list_delete(Z_RESVAL_P(dictionary));
	}
	
	RETURN_TRUE;
}
/* }}} */

/* {{{ proto bool crack_check(string password [, string username [, string gecos [, resource dictionary]]])
   Performs an obscure check with the given password */
PHP_FUNCTION(crack_check)
{
	zval *dictionary = NULL;
	char *password = NULL;
	int password_len;
	char *username = NULL;
	int username_len;
	char *gecos = NULL;
	int gecos_len;
	char *message;
	CRACKLIB_PWDICT *pwdict;
	int id = -1;
	
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
	
	RETURN_STRING(CRACKG(last_message), 1);
}
/* }}} */

#endif /* HAVE_CRACK */
