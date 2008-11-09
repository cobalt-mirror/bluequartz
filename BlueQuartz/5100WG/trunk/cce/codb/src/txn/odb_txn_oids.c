/* $Id: odb_txn_oids.c 3 2003-07-17 15:19:15Z will $
 */

#include "odb_txn_internal.h"

/* hardcoded paths FIXME */
#define OBJECT_ID_FILE  CCEDBDIR "/codb.oids"

static guint really_release_an_oid (guint oid);
static guint really_grab_an_oid (void);

odb_oid 
odb_txn_oid_grab( odb_txn txn )
{
  odb_oid oid;
  oid.oid = really_grab_an_oid();
  odb_txn_oid_mark (txn, oid);
  return oid;
}

// mark an oid as in use:
void
odb_txn_oid_mark(odb_txn txn, odb_oid oid)
{
  odb_oid *copy;
  copy = malloc(sizeof(odb_oid));
  copy->oid = oid.oid;
  txn->allocated_oids = g_slist_append(txn->allocated_oids, copy);
}

// mark an oid for death:
void
odb_txn_oid_release(odb_txn txn, odb_oid oid)
{
  odb_oid *copy;
  copy = malloc(sizeof(odb_oid));
  copy->oid = oid.oid;
  txn->released_oids = g_slist_append(txn->released_oids, copy);
}

// pass info about marked oids down to the next txn layer, or
// actually adjust the pool if there is no subtxn
void
odb_txn_oid_commit( odb_txn txn )
{
  GSList *ptr;
  if (txn->txn) {
  
    // allocated oids are commited, so pass them on to subtxn

    ptr = txn->allocated_oids;
    while (ptr) {
      odb_txn_oid_mark(txn->txn, *((odb_oid*)(ptr->data)));
      free(ptr->data);
      ptr = g_slist_next(ptr);
    }
    g_slist_free(txn->allocated_oids);
    txn->allocated_oids = NULL;
    
    // released oids are committed, so pass them on to subtxn

    ptr = txn->released_oids;
    while (ptr) {
      odb_txn_oid_release(txn->txn, *((odb_oid*)(ptr->data)));
      free(ptr->data);
      ptr = g_slist_next(ptr);
    }
    g_slist_free(txn->released_oids);
    txn->released_oids = NULL;

  } else {

    // allocated oids are commited, so forget about them.

    ptr = txn->allocated_oids;
    while (ptr) {
      free(ptr->data);
      ptr = g_slist_next(ptr);
    }
    g_slist_free(txn->allocated_oids);
    txn->allocated_oids = NULL;
    
    // released oids are commited, so release them back into the pool.

    ptr = txn->released_oids;
    while (ptr) {
      odb_oid o = *((odb_oid*)(ptr->data));
      really_release_an_oid(o.oid);
      free(ptr->data);
      ptr = g_slist_next(ptr);
    }
    g_slist_free(txn->released_oids);
    txn->released_oids = NULL;

  }
}

// this transaction is being discarded, so adjust the oid pool
// appropriately:
void
odb_txn_oid_flush( odb_txn txn )
{
  GSList *ptr;
  // allocated oids are flushed, so release them back into the pool.

    ptr = txn->allocated_oids;
    while (ptr) {
      odb_oid o = *((odb_oid*)(ptr->data));
      really_release_an_oid(o.oid);
      free(ptr->data);
      ptr = g_slist_next(ptr);
    }
    g_slist_free(txn->allocated_oids);
    txn->allocated_oids = NULL;

  // released oids are flushed, so forget about them.

    ptr = txn->released_oids;
    while (ptr) {
      free(ptr->data);
      ptr = g_slist_next(ptr);
    }
    g_slist_free(txn->released_oids);
    txn->released_oids = NULL;

}

#include <sys/stat.h>
#include <unistd.h>
#include <sys/file.h>

void
flock_wait (int fd)
{
  int i;
  for (i = 0; i < 10; i++) {
    if (flock (fd, LOCK_EX | LOCK_NB) == 0) {
      /* file locked successfully */
      return;
    }
    usleep (1E6 / 10);          /* a tenth of a second */
  }
  return;                       /* break the lock */
}

static guint 
really_grab_an_oid (void)
{
  int fd;
  GIntSpan *used_oids;
  guint new_oid;

  {                             /* read intspan from disk */
    gchar *buf;
    struct stat statbuf;

    fd = open (OBJECT_ID_FILE, O_RDWR | O_CREAT | O_SYNC, S_IRUSR | S_IWUSR);
    if (!fd) {
      CCE_SYSLOG("Could not open %s: %m", OBJECT_ID_FILE);
      return 0;                 /* failure */
    }

    flock_wait (fd);

    fstat (fd, &statbuf);
    buf = malloc (statbuf.st_size + 1);
    if (!buf) {
      CCE_SYSLOG("Out of memory.");
      return 0;
    }

    memset (buf, 0, statbuf.st_size + 1);
    read (fd, buf, statbuf.st_size);
    // DPRINTF(DBG_CODB, "read: %s\n", buf);

    used_oids = g_intspan_new ();
    g_intspan_unserialize (used_oids, buf);
    free (buf);
  };

  new_oid = g_intspan_find_any_avail (used_oids);
  if (new_oid) {
    g_intspan_set (used_oids, new_oid);
  };

  {                             /* write back out to disk */
    GString *buf;
    size_t count;
    buf = g_string_new ("");
    g_intspan_serialize (used_oids, buf);
    lseek (fd, SEEK_SET, 0);
    count = strlen (buf->str);
    write (fd, buf->str, count);
    // DPRINTF(DBG_CODB, "grb ==> usedoids = %s\n", buf->str);
    ftruncate (fd, count);
    fsync (fd);
    flock (fd, LOCK_UN);
    close (fd);
    g_string_free (buf, 1);
  };

  g_intspan_destroy (used_oids);
  
  DPRINTF(DBG_CODB, "Allocated OID: %d\n", new_oid);

  return new_oid;
}

static guint 
really_release_an_oid (guint oid)
{
  int fd;
  GIntSpan *used_oids;

  {                             /* read intspan from disk */
    gchar *buf;
    struct stat statbuf;

    fd = open(OBJECT_ID_FILE, O_RDWR | O_CREAT | O_SYNC, S_IRUSR | S_IWUSR);
    if (!fd) {
      // h->lasterr = ERR_INTERNAL;
      return 0;                 /* failure */
    }

    flock_wait (fd);

    fstat (fd, &statbuf);
    buf = malloc (statbuf.st_size + 1);
    if (!buf) {
      // h->lasterr = ERR_INTERNAL;
      return 0;
    }

    memset (buf, 0, statbuf.st_size + 1);
    read (fd, buf, statbuf.st_size);

    used_oids = g_intspan_new ();
    g_intspan_unserialize (used_oids, buf);
    free (buf);
  }

  g_intspan_clear (used_oids, oid);

  {                             /* write back out to disk */
    GString *buf;
    size_t count;
    buf = g_string_new ("");
    g_intspan_serialize (used_oids, buf);
    lseek (fd, SEEK_SET, 0);
    count = strlen (buf->str);
    write (fd, buf->str, count);
    // DPRINTF(DBG_CODB, "rel ==> usedoids = %s\n", buf->str);
    ftruncate (fd, count);
    fsync (fd);
    flock (fd, LOCK_UN);
    close (fd);
    g_string_free (buf, 1);
  }

  g_intspan_destroy (used_oids);

  DPRINTF(DBG_CODB, "Released OID: %d\n", oid);

  return oid;
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
