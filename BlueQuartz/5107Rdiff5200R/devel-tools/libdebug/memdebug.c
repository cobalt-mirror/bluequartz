/*
 * $Id: memdebug.c 3 2003-07-17 15:19:15Z will $
 * Quick hacks for debugging mem leaks
 */

#include <stdio.h>
#include <stdlib.h>
#include <unistd.h>
#include <string.h>
#include <ctype.h>
#include <glib.h>

#undef DEBUG_STR
#define DEBUG_STR "MEMDBG"
#include <libdebug.h>
#define MDPRINTF(f, a...)	if (memdbglvl > 1) DPRINTF(f, ##a)
#define WARN(f, a...)		DPRINTF("WARNING: " f, ##a)

#define DEBUG_BUFFER_LEN 64
#define FREED_PATTERN (unsigned char)0x93

int memdbglvl = 1;

static void print_leak(gpointer key, gpointer value, gpointer crap);
static void test_use(gpointer key, gpointer value, gpointer crap);
static struct alloc *new_alloc(int size, char *file, int line);
static int clean_str(char *buf);
static void memdebug_begin(void);
static void patternize(void *ptr, int size);

/* track currently outstanding allocations */
static GHashTable *alloc_hash = NULL;
/* track free()ed areas for reuse */
static GHashTable *dealloc_hash = NULL;

struct alloc {
	int size;
	char *file;
	int line;
	char *d_file;
	int d_line;
};

static int
clean_str(char *buf)
{
	int isprintable = 0;

	if (!buf) return 0;
	while (*buf != '\0') {
		if (!isprint(*buf))
			*buf = '.';
		else
			isprintable = 1;
		buf++;
	}

	return isprintable;
}

static void
memdebug_begin(void)
{
	if (!alloc_hash) {
		alloc_hash = g_hash_table_new(g_direct_hash, g_direct_equal);
		if (!alloc_hash) {
			DPERROR("g_hash_table_new(alloc_hash)");
		}
		dealloc_hash = g_hash_table_new(g_direct_hash, g_direct_equal);
		if (!alloc_hash) {
			DPERROR("g_hash_table_new(dealloc_hash)");
		}
	}
}

static void
patternize(void *ptr, int size)
{
	unsigned char *p = ptr;
	int n = size;

	while (n--) {
		*p = FREED_PATTERN;
		p++;
	}
}

void
memdebug_dump(void)
{
	DPRINTF("finding leaks:\n");
	if (alloc_hash) {
		g_hash_table_foreach(alloc_hash, print_leak, NULL);
		g_hash_table_destroy(alloc_hash);
	  alloc_hash = NULL;
	}
	alloc_hash = NULL;

	DPRINTF("finding uses of freed memory:\n");
	if (dealloc_hash) {
		g_hash_table_foreach(dealloc_hash, test_use, NULL);
		g_hash_table_destroy(dealloc_hash);
	}
	dealloc_hash = NULL;

	DPRINTF("done\n");
}

static void
print_leak(gpointer key, gpointer value, gpointer crap)
{
	char buf[DEBUG_BUFFER_LEN + 1];
	struct alloc *a = value;

	if (snprintf(buf, DEBUG_BUFFER_LEN, "%s", (char *)key) < 0) {
		free(value);
		return;
	}

	if (clean_str(buf)) {
		WARN("leak at %p [%s:%d] (%d bytes)(\"%s\")\n",
			key, a->file, a->line, a->size, buf);
	} else {
		WARN("leak at %p [%s:%d] (%d bytes)\n", 
			key, a->file, a->line, a->size);
	}

	free(value);
}

static void
test_use(gpointer key, gpointer value, gpointer crap)
{
	char buf[DEBUG_BUFFER_LEN + 1];
	struct alloc *a = value;
	unsigned char *p = key;
	int n = 0;
	int used = 0;

	n = a->size;
	while (n--) {
		if (*p != FREED_PATTERN) {
			used = 1;
			break;
		}
		p++;
	}

	if (used) {
		if (snprintf(buf, DEBUG_BUFFER_LEN, "%s", (char *)key) < 0) {
			free(key);
			free(value);
			return;
		}

		if (clean_str(buf)) {
			WARN("freed use at %p [%s:%d, %s:%d] "
				"(%d bytes)(\"%s\")\n", key, a->file, a->line, 
				a->d_file, a->d_line, a->size, buf);
		} else {
			WARN("freed use at %p [%s:%d, %s:%d] (%d bytes)\n", 
				key, a->file, a->line, a->d_file, a->d_line, a->size);
		}
	}

	free(key);
	free(value);
}


static struct alloc *
new_alloc(int size, char *file, int line)
{
	struct alloc *a;

	a = malloc(sizeof(struct alloc));
	a->size = size;
	a->file = file;
	a->line = line;
	a->d_file = "";
	a->d_line = 0;

	return a;
}

void *
_dbg_malloc(int size, char *f, int l)
{
	gpointer p;

	memdebug_begin();
	
	p = malloc(size);
	if (!p) {
		return NULL;
	}

	g_hash_table_insert(alloc_hash, p, new_alloc(size, f, l));

	MDPRINTF("dbg_malloc[%s:%d]: added %p (%d bytes)\n", f, l, p, size);

	return p;
}

void 
_dbg_free(void *ptr, char *f, int l)
{
	char buf[DEBUG_BUFFER_LEN + 1];
	gpointer k, v;
	int size = 0;

	memdebug_begin();

	if (!ptr) {
		WARN("dbg_free[%s:%d]: free(NULL) detected\n", f, l);
		return;
	}
	
	/* see if we allocated it */
	if (g_hash_table_lookup_extended(alloc_hash, ptr, &k, &v)) {
		struct alloc *ap = (struct alloc *)v;
		size = ap->size;
		g_hash_table_remove(alloc_hash, ptr);
		/* patternize, and move it to the deallocated list */
		patternize(ptr, size);
		g_hash_table_insert(dealloc_hash, ptr, v);
		ap->d_file = f;
		ap->d_line = l;
	} else {
		/* hmm, see if we already de-allocated it */
		if (g_hash_table_lookup(dealloc_hash, ptr)) {
			WARN("dbg_free[%s:%d]: %p already removed\n", f, l, ptr);
		} else {
			WARN("dbg_free[%s:%d]: %p not in hash\n", f, l, ptr);
		}
	}

	if (size) {
		snprintf(buf, DEBUG_BUFFER_LEN, "%s", (char *)ptr);
		if (clean_str(buf)) {
			MDPRINTF("dbg_free[%s:%d]: removed %p (%d bytes)(\"%s\")\n", 
				f, l, ptr, size, buf);
		} else {
			MDPRINTF("dbg_free[%s:%d]: removed %p (%d bytes)\n", 
				f, l, ptr, size);
		}
	}

#ifdef ACTUALLY_DEALLOC
	free(ptr);
#endif
}

char *
_dbg_strdup(char *str, char *f, int l)
{
	char *p;
	char buf[DEBUG_BUFFER_LEN + 1];

	memdebug_begin();

	if (!str) {
		WARN("dbg_strdup[%s:%d]: strdup(NULL) detected\n", f, l);
		return NULL;
	}

	p = strdup(str);
	if (!p) {
		return NULL;
	}

	g_hash_table_insert(alloc_hash, p, new_alloc(strlen(p)+1, f, l));

	snprintf(buf, DEBUG_BUFFER_LEN, "%s", p);
	if (clean_str(buf)) {
		MDPRINTF("dbg_strdup[%s:%d]: added %p (%d bytes)(\"%s\")\n", 
			f, l, p, strlen(p)+1, buf);
	} else {
		MDPRINTF("dbg_strdup[%s:%d]: added %p (%d bytes)\n", 
			f, l, p, strlen(p)+1);
	}

	return p;
}

void *
_dbg_realloc(void *ptr, int size, char *f, int l)
{
	char *p;
	gpointer k, v;
	int len = 0;

	memdebug_begin();
	
	/* see if it is in the allocated hash */
	if (g_hash_table_lookup_extended(alloc_hash, ptr, &k, &v)) {
		struct alloc *ap = (struct alloc *)v;
		len = ap->size;
		g_hash_table_remove(alloc_hash, ptr);
		/* move it to the deallocated list */
		g_hash_table_insert(dealloc_hash, ptr, v);
		MDPRINTF("dbg_realloc[%s:%d]: removed %p\n", f, l, ptr);
		ap->d_file = f;
		ap->d_line = l;
	} else if (g_hash_table_lookup(dealloc_hash, ptr)) {
		/* already deallocated it */
		WARN("dbg_realloc[%s:%d]: %p already removed\n", f, l, ptr);
	} else if (ptr) {
		/* don't have it */
		WARN("dbg_realloc[%s:%d]: %p not in hash\n", f, l, ptr);
	}

#ifdef ACTUALLY_DEALLOC
	p = realloc(ptr, size);
	if (!p) {
		return NULL;
	}
#else
	p = malloc(size);
	if (!p) {
		return NULL;
	}
	memcpy(p, ptr, len < size ? len : size);

	/* patternixe the old data */
	patternize(ptr, len);
#endif

	g_hash_table_insert(alloc_hash, p, new_alloc(size, f, l));

	MDPRINTF("dbg_realloc[%s:%d]: added %p (%d bytes)\n", f, l, p, size);

	return p;
}


GString *
_dbg_g_string_new(const gchar *init, char *f, int l)
{
	GString *p;
	char buf[DEBUG_BUFFER_LEN + 1];
	
	memdebug_begin();

	p = g_string_new(init);

	g_hash_table_insert(alloc_hash, p, new_alloc(sizeof(GString), f, l));
	g_hash_table_insert(alloc_hash, p->str, new_alloc(p->len+1, f, l));

	snprintf(buf, DEBUG_BUFFER_LEN, "%s", p->str);
	if (clean_str(buf)) {
		MDPRINTF("dbg_g_string_new[%s:%d]: added %p->%p "
			"(%d bytes)(\"%s\")\n", 
			f, l, p, p->str, (int)(p->len+1), buf);
	} else {
		MDPRINTF("dbg_g_string_new[%s:%d]: added %p->%p (%d bytes)\n",
			f, l, p, p->str, (int)(p->len+1));
	}

	return p;
}

void 
_dbg_g_string_free(GString *string, gint free_segment, char *f, int l)
{
	gpointer k, v;

	memdebug_begin();

	if (g_hash_table_lookup_extended(alloc_hash, string, &k, &v)) {
		free(v);
		g_hash_table_remove(alloc_hash, string);
	} else {
		WARN("dbg_g_string_free[%s:%d]: %p not in hash\n", f, l, string);
	}
		
	MDPRINTF("dbg_g_string_free[%s:%d]: removed %p\n", f, l, string);

	if (free_segment) {
		if (g_hash_table_lookup_extended(alloc_hash, string->str,&k,&v)) {
			free(v);
			g_hash_table_remove(alloc_hash, string->str);
		} else {
			WARN("dbg_g_string_free[%s:%d]: %p->%p not in hash\n", 
				f, l, string, string->str);
		}
		MDPRINTF("dbg_g_string_free[%s:%d]: removed %p->%p\n", 
			f, l, string, string->str);
	}

	g_string_free(string, free_segment);
}

GString *
_dbg_g_string_sized_new(const int sz, char *f, int l)
{
	GString *p;
	
	memdebug_begin();

	p = g_string_sized_new(sz);

	g_hash_table_insert(alloc_hash, p, new_alloc(sizeof(GString), f, l));
	g_hash_table_insert(alloc_hash, p->str, new_alloc(sz+1, f, l));
	MDPRINTF("dbg_g_string_sized_new[%s:%d]: added %p->%p (%d bytes)\n", 
		f, l, p, p->str, sz+1);

	return p;
}

GString *
_dbg_g_string_append(GString *s, char *new, char *f, int l)
{
	gpointer k, v;
	char *old = s->str;

	memdebug_begin();
	
	g_string_append(s, new);

	/* reflect the new length */
	if (g_hash_table_lookup_extended(alloc_hash, old, &k, &v)) {
		free(v);
	}
	g_hash_table_remove(alloc_hash, old);
	g_hash_table_insert(alloc_hash, s->str, new_alloc(s->len + 1, f, l));
	if (s->str != old) {
		MDPRINTF("dbg_g_string_append[%s:%d]: removed %p->%p (%d bytes)\n",
			f, l, s, old, s->len + 1);
		MDPRINTF("dbg_g_string_append[%s:%d]: added %p->%p (%d bytes)\n",
			f, l, s, s->str, s->len + 1); 
	} else {
		MDPRINTF("dbg_g_string_append[%s:%d]: resized %p->%p (%d bytes)\n", 
			f, l, s, s->str, s->len + 1); 
	}
		
	return s;
}

GString *
_dbg_g_string_append_c(GString *s, char c, char *f, int l)
{
	gpointer k, v;
	char *old = s->str;

	memdebug_begin();
	
	g_string_append_c(s, c);

	/* reflect the new length */
	if (g_hash_table_lookup_extended(alloc_hash, old, &k, &v)) {
		free(v);
	}
	g_hash_table_remove(alloc_hash, old);
	g_hash_table_insert(alloc_hash, s->str, new_alloc(s->len + 1, f, l));
	if (s->str != old) {
		MDPRINTF("dbg_g_string_append_c[%s:%d]: removed %p->%p (%d bytes)\n",
			f, l, s, old, s->len + 1);
		MDPRINTF("dbg_g_string_append_c[%s:%d]: added %p->%p (%d bytes)\n",
			f, l, s, s->str, s->len + 1); 
	} else {
		MDPRINTF("dbg_g_string_append_c[%s:%d]: resized %p->%p (%d bytes)\n",
			f, l, s, s->str, s->len + 1); 
	}
		
	return s;
}

GString *
_dbg_g_string_assign(GString *s, char *new, char *f, int l)
{
	gpointer k, v;
	char *old = s->str;
	int oldlen = s->len;

	memdebug_begin();
	
	g_string_assign(s, new);

	/* reflect the new length */
	if (g_hash_table_lookup_extended(alloc_hash, old, &k, &v)) {
		free(v);
	}
	g_hash_table_remove(alloc_hash, old);
	g_hash_table_insert(alloc_hash, s->str, new_alloc(s->len + 1, f, l));
	if (s->str != old) {
		MDPRINTF("dbg_g_string_assign[%s:%d]: removed %p->%p (%d bytes)\n",
			f, l, s, old, s->len + 1);
		MDPRINTF("dbg_g_string_assign[%s:%d]: added %p->%p (%d bytes)\n", 
			f, l, s, s->str, s->len + 1); 
	} else if (s->len != oldlen) {
		MDPRINTF("dbg_g_string_assign[%s:%d]: assigned %p->%p (%d bytes)\n", 
			f, l, s, s->str, s->len + 1); 
	}
		
	return s;
}

GString *
_dbg_g_string_sprintf(GString *s, char *format, char *f, int l, ... )
{
	gpointer k, v;
	va_list args;
	char *old = s->str;
	int oldlen = s->len;

	va_start(args, l);
	memdebug_begin();
	
	g_string_sprintf(s, format, args);

	/* reflect the new length */
	if (g_hash_table_lookup_extended(alloc_hash, old, &k, &v)) {
		free(v);
	}
	g_hash_table_remove(alloc_hash, old);
	g_hash_table_insert(alloc_hash, s->str, new_alloc(s->len + 1, f, l));
 	if (s->str != old) {
		MDPRINTF("dbg_g_string_assign[%s:%d]: removed %p->%p (%d bytes)\n",
			f, l, s, old, s->len);
		MDPRINTF("dbg_g_string_assign[%s:%d]: added %p->%p (%d bytes)\n", 
			f, l, s, s->str, s->len + 1); 
	} else if (s->len != oldlen) {
		MDPRINTF("dbg_g_string_assign[%s:%d]: assigned %p->%p (%d bytes)\n", 
			f, l, s, s->str, s->len + 1); 
	}
		
	return s;
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
