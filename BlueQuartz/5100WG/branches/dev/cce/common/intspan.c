/* $Id: intspan.c 229 2003-07-18 20:22:20Z will $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */
/*
 * * Overview: the intspan_t class
 *
 * Is used to manipulate a sparse set of guint integers.
 * Members can be added and removed from the set, the set
 * can be tested for membership, and the set can be serialized
 * and unserialized.  The serialization format is compatible with
 * the integer span format commonly used in files such as .newsrc.
 *
 * Contains a balanced binary tree, which contains a list of
 * non-overlapping, non-adjacent int spans.  These spans
 * are encoded such that the key of the tree entry is the lower
 * bound of an included span, the value of an entry is the upper
 * bound of an included span.
 *
 * FIXME: one of the tradeoffs of using GINT_TO_POINTER to store
 * my data in the tree efficiently is that storing the zero value
 * in the tree is indistinguishable from not having the entry in
 * the tree (ie. lookup returns NULL if an object is not found).
 * As a result, "0" is a reserved "error value", and is not a
 * permittable member of any span (ie. spans must start at 1).
 *
 * For many applications (message id lists, object id pools, etc.)
 * this is good enough.
 */

#include "intspan.h"
#include <glib.h>
#include <stdlib.h>

/* the glib macros are too verbose and distracting */
#define I2P(i)	((gpointer) (i))
#define P2I(p)  ((guint) (p))

/* intspan_t data structure */
struct intspan_t {
	GTree *tree;
};

/* GCF function to compare guints */
gint 
gcf_compare_ints(gconstpointer a, gconstpointer b)
{
	if (P2I(a) < P2I(b))
		return -1;
	else if (P2I(a) == P2I(b))
		return 0;
	else
		return 1;
}

/* constructor */
intspan_t *
intspan_new()
{
	intspan_t *set;

	set = g_new(intspan_t, 1);
	if (!set) {
		return NULL;
	}

	/* ... old habits die hard */
	set->tree = g_tree_new(gcf_compare_ints);	/* tree of guints */

	return set;
}

/* destructor */
void
intspan_destroy(intspan_t * set)
{
	g_tree_destroy(set->tree);
	g_free(set);
}

/** traverse_findbounds
  *
  * traversing function to find two keys that bound a value:
  * 	lower-key <= i < higher-key
  * If a lower bound and/or a higher bound don't exist, 0 is returned.
  */
gint 
traverse_findbounds(gpointer key, gpointer value, gpointer data)
{
	guint *iP = (guint *) data;

	if (P2I(key) <= iP[2]) {
		iP[0] = P2I(key);
		return FALSE;
	} else {
		iP[1] = P2I(key);
		return TRUE;
	}
}

/** intspan_findbounds
  *
  * For integer i, finds two adjacent keys in the set such that:
  *			*lowP <= i < *hiP
  *
  * A key of "0" means "outside span set."
  */
void
intspan_findbounds(intspan_t * set, guint i, guint * lowP, guint * hiP)
{
	guint low_hi_key[3];

	low_hi_key[0] = 0;
	low_hi_key[1] = 0;
	low_hi_key[2] = i;

	g_tree_traverse(set->tree, traverse_findbounds, G_IN_ORDER, low_hi_key);

	*lowP = low_hi_key[0];
	*hiP = low_hi_key[1];
}

/** intspan_set
  * 
  * Adds value "i" to the integer span set.
  */
void
intspan_set(intspan_t * set, guint i)
{
	guint lo, hi, low_val, hi_val;

	/* FIXME: what if any of these values approaches MAXVAL ? */

	if (i == 0)
		/* fail: 0 is a reserved value */
		return;

	intspan_findbounds(set, i, &lo, &hi);
	low_val = lo ? P2I(g_tree_lookup(set->tree, I2P(lo))) : 0;
	hi_val = hi ? P2I(g_tree_lookup(set->tree, I2P(hi))) : 0;

	/* remember: lo <= i < hi, unless hi == 0 */

	/* already in set: */
	if (i <= low_val)
		return;

	/* one greater than the top of the low set */
	if (low_val && (i == low_val + 1)) {
		if (i + 1 == hi) {
			/* fprintf(stderr, "join\n"); */
			/* joins the low set and the hi set */
			g_tree_remove(set->tree, I2P(lo));
			g_tree_remove(set->tree, I2P(hi));
			g_tree_insert(set->tree, I2P(lo), I2P(hi_val));
			return;
		} else {
			/* fprintf(stderr, "grow low\n"); */
			/* just grow lower set */
			g_tree_remove(set->tree, I2P(lo));
			g_tree_insert(set->tree, I2P(lo), I2P(i));
			return;
		}
	}
	/* one less than bottom of the high set */
	if (hi && (i + 1 == hi)) {
		/* fprintf(stderr, "grow high\n"); */
		/* grow higher set */
		g_tree_remove(set->tree, I2P(hi));
		g_tree_insert(set->tree, I2P(i), I2P(hi_val));
		return;
	}
	/* is not part of any set */
	/* fprintf(stderr, "insert\n"); */
	g_tree_insert(set->tree, I2P(i), I2P(i));
}

/** intspan_clear
  *
  * Removes value i from the integer span set.
  */
void
intspan_clear(intspan_t * set, guint i)
{
	guint lo, hi, low_val, hi_val;

	/* FIXME: what if any of these values approaches MAXVAL ? */

	if (i == 0) {
		return;
	}
	/* fail: 0 is a reserved value */
	intspan_findbounds(set, i, &lo, &hi);
	low_val = lo ? P2I(g_tree_lookup(set->tree, I2P(lo))) : 0;
	hi_val = hi ? P2I(g_tree_lookup(set->tree, I2P(hi))) : 0;

	if (i > low_val)
		return;	/* already clear */

	if (i == lo && i == low_val) {
		/* eliminate one-element set */
		g_tree_remove(set->tree, I2P(lo));
		return;
	}
	if (i == lo) {
		/* shrink set */
		g_tree_remove(set->tree, I2P(lo));
		g_tree_insert(set->tree, I2P(lo + 1), I2P(low_val));
		return;
	}
	if (i == low_val) {
		/* shrink set from top */
		g_tree_remove(set->tree, I2P(lo));
		g_tree_insert(set->tree, I2P(lo), I2P(low_val - 1));
		return;
	}
	/* must be somewhere in the middle! */
	g_tree_remove(set->tree, I2P(lo));
	g_tree_insert(set->tree, I2P(lo), I2P(i - 1));
	g_tree_insert(set->tree, I2P(i + 1), I2P(low_val));
}

/** intspan_test
  *
  * Tests to see if i is a member of the set.
  */
gint
intspan_test(intspan_t * set, guint i)
{
	guint lo, hi, low_val;

	/* FIXME: what if any of these values approaches MAXVAL ? */

	if (i == 0) {
		return 0;
	}
	/* fail: 0 is a reserved value */
	intspan_findbounds(set, i, &lo, &hi);
	low_val = lo ? P2I(g_tree_lookup(set->tree, I2P(lo))) : 0;

	if (i >= lo && i <= low_val) {
		return 1;
	} else {
		return 0;
	}
}

/** traverse_find_any
  *
  * Finds the first convenient sub-span in the integer span set.
  */
gint
traverse_find_any(gpointer key, gpointer value, gpointer data)
{
	/* take the first item that comes along */
	guint *iP = data;

	if (P2I(key) > 1)
		*iP = 1;
	else
		*iP = P2I(value) + 1;

	return TRUE;				/* never recurse */
}

/** intspan_find_any_avail
  *
  * Finds an integer that is not a member of the intspan_t set and returns
  * it.  Useful for doing things like grabbing a fresh, unique ID from
  * a list of used IDs.
  */
guint
intspan_find_any_avail(intspan_t * set)
{
	guint i = 1;

	g_tree_traverse(set->tree, traverse_find_any, G_IN_ORDER, &i);

	return i;
}

/** traverse_serialize
  *
  * iterator for serializing spans in the span set.
  */
gint
traverse_serialize(gpointer key, gpointer value, gpointer data)
{
	guint low = P2I(key);
	guint low_top = P2I(value);
	GString *buffer = data;

	if (*(buffer->str) != '\0') {
		g_string_append_c(buffer, ',');
	}
	if (low == low_top) {
		g_string_sprintfa(buffer, "%u", low);
	} else {
		g_string_sprintfa(buffer, "%u-%u", low, low_top);
	}

	return FALSE;				/* traverse whole structure */
}

/** intspan_serialize
  *
  * Appends a serialized version of the integer span set to 
  * the GString buffer.  Spans are serialized using the same
  * syntax as .newsrc (ie: "1-3000,3002,3005-3006")
  */
void
intspan_serialize(intspan_t * set, GString * buffer)
{
	g_string_assign(buffer, "");
	g_tree_traverse(set->tree, traverse_serialize, G_IN_ORDER,
		(gpointer) buffer);
}

/** intspan_unserialize
  *
  * Initializes the intspan_t object by unserializing intspan_t data
  * from a string.
  */
gint
intspan_unserialize(intspan_t * set, const gchar * buffer)
{
	/* i must cast away const because strtoul isn't declared const.
	 * bleah. */
	const gchar *cursor = buffer;
	guint bottom, top;

	/* empty the tree, the lazy way */
	g_tree_destroy(set->tree);
	set->tree = g_tree_new(gcf_compare_ints);

	/* populate the tree */
	while (*cursor >= '0' && *cursor <= '9') {
		char *next;
		bottom = strtoul(cursor, &next, 10);
		cursor = next;
		top = bottom;
		if (*cursor == '-') {
			/* a real span! */
			cursor++;
			top = strtoul(cursor, &next, 10);
			cursor = next;
		}
		if (bottom && top)
			g_tree_insert(set->tree, I2P(bottom), I2P(top));
		if (*cursor == ',')
			cursor++;
	}

	return 1;
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
