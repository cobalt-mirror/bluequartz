/* $Id: i18n_internals.h 201 2003-07-18 19:11:07Z will $
 *
 * common headers, data structures, fn declarations
 */

#ifndef __I18N_INTERNALS_H__
#define __I18N_INTERNALS_H__

#include <glib.h>
#include <string.h>
#include <dirent.h>
#include <libintl.h>
#include <stdio.h>
#include <stdlib.h>
#include <ctype.h>
#include <sys/stat.h>
#include <unistd.h>
#include <locale.h>
#include <time.h>
#include <libintl.h>
#include <errno.h>
#include <libdebug.h>
#include <i18n.h>

#ifdef DEBUG
#undef DPRINTF
#define DPRINTF(f, a...)      fprintf(stderr, "DEBUG: " f, ##a);
#undef DPERROR
#define DPERROR(f)            perror("DEBUG: " f);
#endif

/* i18n_handle_struct
 *
 * here is def for the struct that is typedefed in i18n.h: */
struct i18n_handle_struct {
	/* mapping of domain -> locale, computed
	 * as needed. This is used to determine which
	 * base locale to use for lookups on a domain
	 * by domain basis. 
	 */
	GHashTable *cached_locales;
	/* Cached hashes of encoding data */
	GHashTable *cached_encodings;
	GSList *preflist; 	/* user's locales, in order of preference */
	char *domain; 		/* the current default domain */
	int recursion_level;

	/* a list of strings that need to be freed */
	GSList *freelist;
};

/* escaping engine: */
GString *encode(GHashTable *encoding, char *str);
GString *encode_core(i18n_handle *i18n, char *str, char *key, 
	GHashTable *(*fn)(void));

/* encoding functions from encoding.c */
GHashTable *build_test_encoding();
GHashTable *build_html_encoding();
GHashTable *build_js_encoding();

/* internal functions that implement the new translation routines: */
char * internal_gettext (i18n_handle *i18n, 
        char *domain, char *tag, i18n_vars *vars);
char * internal_get_file(i18n_handle *i18n, char *file);
char *internal_interpolate(i18n_handle *i18n, char *magicstr, i18n_vars *vars);
char *internal_gettext(i18n_handle *i18n, 
	char *domain, char *tag, i18n_vars *vars);

/* helper functions */
void free_whole_g_slist(GSList *list);
gint slist_str_find(gconstpointer a, gconstpointer b);
gboolean hash_slist_rm(gpointer key, gpointer value, gpointer data);
gboolean hash_hash_rm(gpointer key, gpointer value, gpointer data);
gboolean hash_string_rm(gpointer key, gpointer value, gpointer data);
gboolean hash_int_rm(gpointer key, gpointer value, gpointer data);

#define gstring_2_string(g,s) { s = g->str; g_string_free(g, 0); }

/* language negotiation */
GSList* validLangs(const i18n_handle *i18n, char *domain);

/* locale finders */
GSList *AllAvailableLangs ();
GSList *AvailableLangs (char *domain);

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
