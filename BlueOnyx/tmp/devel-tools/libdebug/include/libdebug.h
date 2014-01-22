/*
 * $Id: libdebug.h 3 2003-07-17 15:19:15Z will $
 *
 * This is the current list of debugged functions: (keep this current!)
 * --------------------------------------------------------------------
 * malloc
 * free
 * realloc
 * strdup
 * g_string_new
 * g_string_free
 * g_string_sized_new
 * g_string_append
 * g_string_append_c
 * g_string_assign
 * g_string_sprintf
 */
#ifndef __LIBDEBUG_H__
#define __LIBDEBUG_H__

#define LIBDEBUG_VER	"0.5"

#if USE_LIBDEBUG == 1 
/* **************************************************** 
 * use general debugging  (macros, etc)
 * ****************************************************/

#ifndef LIBDEBUG_INTERNAL
#warning USE_LIBDEBUG is ON
#endif

#include <stdio.h>
#include <unistd.h>
#include <errno.h>
#include <glib.h>
#include <string.h>
#include <stdlib.h>

/* commonly used macros */
#ifndef DEBUG_STR
#define DEBUG_STR			"DBG"
#endif

#ifndef DPRINTF

#ifdef LIBDEBUG_INTERNAL
#define DPRINTF(fmt, a...) \
	do { \
		if (debug_fp) { \
			fprintf(debug_fp, "%s[%li]: " fmt, \
				DEBUG_STR , (long)getpid() , ##a); \
		} else { \
			fprintf(stderr, "%s[%li]: " fmt, \
				DEBUG_STR , (long)getpid() , ##a); \
		} \
	} while (0)
#else
#define DPRINTF(f, a...) \
	do { \
		if (debug_fp) { \
			fprintf(debug_fp, "%s[%li] (%s:%d): " f, \
				DEBUG_STR , (long)getpid() , \
				__FILE__ , __LINE__ , ##a); \
		  } else { \
			fprintf(stderr, "%s[%li] (%s:%d): " f, \
				DEBUG_STR , (long)getpid() , \
				__FILE__ , __LINE__ , ##a); \
		  } \
	} while (0) 
#endif  /* ifdef LIBDEBUG_INTERNAL */
#endif  /* ifndef DPRINTF */

#ifndef DPERROR
/* aren't we clever - varargs to perror() */
#define DPERROR(f, a...)	DPRINTF(f ": %s\n" , ##a , strerror(errno))
#endif

/* init */
extern int debug_fd;
extern FILE *debug_fp;
void libdebug_init(int fd);
void libdebug_set_fd(int fd);
void libdebug_open(char *filename);
void libdebug_close();

#else
/* **************************************************** 
 * no debugging 
 * ****************************************************/

#ifndef DPRINTF
#define DPRINTF(f, a...)
#endif

#ifndef DPERROR
#define DPERROR(f)
#endif

/* init */
#define libdebug_init(a)
#define libdebug_set_fd(a)
#define libdebug_open(a)
#define libdebug_close()

#endif


#if (USE_LIBDEBUG == 1) && (USE_MEMDEBUG)
/* **************************************************** 
 * use memory debugging functions
 *
 * FIXME: make this work
 * #define USE_MEMDEBUG value determines the debugging level:
 *	* 1 = just leak/trample
 *	* 2 = full verbose info
 * ****************************************************/

#ifndef LIBDEBUG_INTERNAL
#warning USE_MEMDEBUG is ON
#endif

extern int memdbglvl;
void memdebug_dump(void);

/* standard */
#define dbg_malloc(a)			_dbg_malloc(a, __FILE__, __LINE__)
#define dbg_free(a)			_dbg_free(a, __FILE__, __LINE__)
#define dbg_strdup(a)			_dbg_strdup(a, __FILE__, __LINE__)
#define dbg_realloc(a,b)		_dbg_realloc(a, b, __FILE__, __LINE__)
void *_dbg_malloc(int size, char *f, int l);
void _dbg_free(void *ptr, char *f, int l);
char *_dbg_strdup(char *str, char *f, int l);
void *_dbg_realloc(void *ptr, int size, char *f, int l);

/* g_string */
#define dbg_g_string_new(a)		_dbg_g_string_new(a, __FILE__, __LINE__)
#define dbg_g_string_free(a,b)	_dbg_g_string_free(a,b, __FILE__, __LINE__)
#define dbg_g_string_sized_new(a)	_dbg_g_string_sized_new(a, __FILE__, __LINE__)
#define dbg_g_string_append(a,b)	_dbg_g_string_append(a,b, __FILE__, __LINE__)
#define dbg_g_string_append_c(a,b)	_dbg_g_string_append_c(a,b, __FILE__, __LINE__)
#define dbg_g_string_assign(a,b)	_dbg_g_string_assign(a,b, __FILE__, __LINE__)
#define dbg_g_string_sprintf(a,b,v...)	_dbg_g_string_sprintf(a,b, __FILE__, __LINE__, ##v )

GString *_dbg_g_string_new(const gchar *init, char *f, int l);
void _dbg_g_string_free(GString *string, gint free_segment, char *f, int l);
GString *_dbg_g_string_sized_new(const int sz, char *f, int l);
GString *_dbg_g_string_append(GString *s, char *new, char *f, int l);
GString *_dbg_g_string_append_c(GString *s, char c, char *f, int l);
GString *_dbg_g_string_assign(GString *s, char *new, char *f, int l);
GString *_dbg_g_string_sprintf(GString *s, char *format, char *f, int l, ...);

#ifndef LIBDEBUG_INTERNAL
/* override the default lib versions (outside source only) */
#define malloc(a)				dbg_malloc(a)
#define free(a)				dbg_free(a)
#undef strdup	/* already a macro */
#define strdup(a)				dbg_strdup(a)
#define realloc(a,b)			dbg_realloc(a,b)

#define g_string_new(a)			dbg_g_string_new(a)
#define g_string_free(a,b)		dbg_g_string_free(a,b)
#define g_string_sized_new(a)		dbg_g_string_sized_new(a)
#define g_string_append(a,b)		dbg_g_string_append(a,b)
#define g_string_append_c(a,b)	dbg_g_string_append_c(a,b)
#define g_string_assign(a,b)		dbg_g_string_assign(a,b)
#define g_string_sprintf(a,b,v...)	dbg_dbg_g_string_sprintf(a,b,##v)

#endif /* #ifdef LIBDEBUG_INTERNAL */

#else 
/* **************************************************** 
 * no debugging 
 * ****************************************************/

/*
 * memdebug
 */
#define memdebug_dump()
#define dbg_malloc			malloc
#define dbg_free				free
#define dbg_strdup			strdup
#define dbg_realloc			realloc

#define dbg_g_string_new		g_string_new
#define dbg_g_string_sized_new	g_string_sized_new
#define dbg_g_string_free		g_string_free
#define dbg_g_string_append		g_string_append
#define dbg_g_string_append_c		g_string_append_c
#define dbg_g_string_assign		g_string_assign
#define dbg_g_string_sprintf		g_string_sprintf

#endif /* #if USE_MEMDEBUG == 1 */


#endif /* #ifndef __LIBDEBUG_H__ */
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
