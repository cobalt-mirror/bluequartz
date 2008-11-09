/* $Id: g_hashwrap.h 229 2003-07-18 20:22:20Z will $ */
/* Copyright (c) 2001-2002 Sun Microsystems, Inc.  All rights reserved. */
#ifndef G_HASHWRAP_H__
#define G_HASHWRAP_H__
#include <glib.h>

/*
 * Implementations of both of these functions should check their inputs for
 * NULL.  The calling routines may clone/free one or both of the parameters
 */
typedef void (*GHCloneFunc)(gpointer key, gpointer value, 
    gpointer *newkey, gpointer *newvalue);
typedef void (*GHFreeFunc)(gpointer key, gpointer value);

/*
 * A wrapper for GHashTable with a few enhancements:
 * - The ability to iterate - g_hashwrap_index()
 * - The ability to let the functions do memory management for you
 */
typedef struct GHashWrap {
	GHashTable *hash;
	GHCloneFunc clone_func;
	GHFreeFunc free_func;
	gpointer *keys; /* for iterating */
	gint keys_len; /* length of the keys array */
} GHashWrap;

GHashWrap *g_hashwrap_new(GHashFunc hash_func, GCompareFunc compare_func,
    GHCloneFunc clone_func, GHFreeFunc free_func);
void g_hashwrap_insert(GHashWrap *hashwrap, gpointer key, gpointer value);
#define g_hashwrap_size(h) g_hash_table_size((h)->hash)
#define g_hashwrap_lookup(h, k) g_hash_table_lookup((h)->hash, (k))
#define g_hashwrap_lookup_extended(h, k, ok, ov) \
	g_hash_table_lookup_extended((h)->hash, (k), (ok), (ov))
#define g_hashwrap_foreach(h, f, u) g_hash_table_foreach((h)->hash, (f), (u))
void g_hashwrap_remove(GHashWrap *hashwrap, gpointer key);
#define g_hashwrap_foreach_remove(h, f, u) \
	g_hash_table_foreach_remove((h)->hash, (f), (u))
#define g_hashwrap_freeze(h) g_hash_table_freeze((h)->hash)
#define g_hashwrap_thaw(h) g_hash_table_thaw((h)->hash)
void g_hashwrap_destroy(GHashWrap *hashwrap);
gint g_hashwrap_index(GHashWrap *hashwrap, gint idx, gpointer *key, 
    gpointer *value);

/* functions for use as GHCloneFunc or GHFreeFunc */
void g_str_clone(gpointer key, gpointer value, gpointer *newkey, 
    gpointer *newvalue);
void g_str_free(gpointer key, gpointer value);

#endif /* G_HASHWRAP_H__ */
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
