/*
 * This program is copyright Alec Muffett 1993. The author disclaims all 
 * responsibility or liability with respect to it's usage or its effect 
 * upon hardware or computer systems, and maintains copyright as set out 
 * in the "LICENCE" document which accompanies distributions of Crack v4.0 
 * and upwards.
 */

/*
 * Updated by Sascha Kettler <skettler@php.net> to not leak any
 * unwanted symbols and add extra functionality.
 * 
 * Also beautified code and optimized for the PHP extension.
 */

#ifndef _CRACK_H
#define _CRACK_H

#define FascistCheck(x,y) cracklib_fascist_check(x,y)

static const char CRACKLIB_DICTPATH[] = "/var/cache/cracklib/cracklib_dict";

extern const char *cracklib_fascist_check(const char *password, const char *dictpath);

#endif

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 * vim600: sw=4 ts=4 fdm=marker
 * vim<600: sw=4 ts=4
 */
