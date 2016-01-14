/* excerpt from glib.h of glib-1.2.7-C2 */
/* See Copyright in glib-1.2.7-C2.tar.gz distributed by Sun Microsystems */

typedef struct _GHashNode GHashNode;
typedef struct _GHashIter GHashIter;

struct _GHashNode
{
  gpointer key;
  gpointer value;
  GHashNode *next;
};

struct _GHashIter
{
        GHashTable *hash;
        gint i;
        GHashNode *node;
};

struct _GHashTable
{
  gint size;
  gint nnodes;
  guint frozen;
  GHashNode **nodes;
  GHashFunc hash_func;
  GCompareFunc key_compare_func;
};

/* GHashIter - alternate iterator for GHashTable objects
 */
GHashIter* g_hash_iter_new (GHashTable *hash_table);
gpointer g_hash_iter_first (GHashIter *, gpointer *key, gpointer *val);
gpointer g_hash_iter_next  (GHashIter *, gpointer *key, gpointer *val);
void g_hash_iter_destroy (GHashIter *);
