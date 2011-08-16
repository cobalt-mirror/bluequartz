/* excerpt from ghash.c of glib-1.2.7-C2 */
/* See Copyright in glib-1.2.7-C2.tar.gz distributed by Sun Microsystems */
#include <glib.h>
#include "glib-ghash.h"

/* a thread-safe iterator object -- make the iterator
 * local to your thread and you're safe.  This iterator
 * might be preferable to "foreach" if you want to avoid
 * pass a lot of data about. 
 */
GHashIter * g_hash_iter_new(GHashTable *t)
{
	GHashIter *it;
	gpointer key, val;

	if (!t) {
		return NULL;
	}

	it = g_new(GHashIter, 1);
	it->hash = t;
	it->node = NULL;
	g_hash_iter_first(it, &key, &val);
	return it;
}

void g_hash_iter_destroy(GHashIter *it)
{
	g_free(it);
}

gpointer g_hash_iter_first(GHashIter *it, gpointer *key, gpointer *val)
{
	*key = NULL;
	*val = NULL;

	it->i = 0;
	if (it->i < it->hash->size) 
		it->node = it->hash->nodes[0];
	while (it->i < it->hash->size) {
		if (it->node) {
			*key = it->node->key;
			*val = it->node->value;
			return *key;
		} else {
			(it->i)++;
			it->node = it->hash->nodes[it->i];
		}
	}
	return NULL;
}

gpointer g_hash_iter_next(GHashIter *it, gpointer *key, gpointer *val)
{
	*key = NULL;
	*val = NULL;
	it->node = it->node->next;
	while (1) {
		if (it->node) {
			*key = it->node->key;
			*val = it->node->value;
			return *key;
		} else {
			it->i++;
			if (it->i < it->hash->size) {
				it->node = it->hash->nodes[it->i];
			} else {
				break;
			}
		}
	}
	return NULL;
}
