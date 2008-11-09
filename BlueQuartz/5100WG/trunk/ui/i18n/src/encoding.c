/*
 * Functions used for encoding strings into various formats
 *
 *
 */

#include <glib.h>
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <libdebug.h>
#include <i18n.h>

#ifdef DEBUG
#undef DPRINTF
#define DPRINTF(f, a...)      fprintf(stderr, "DEBUG: " f, ##a);
#undef DPERROR
#define DPERROR(f)            perror("DEBUG: " f);
#endif

#ifdef DEBUG
GHashTable *
build_test_encoding (void)
{
  GHashTable *encode;
  encode = g_hash_table_new (g_str_hash, g_str_equal);

  g_hash_table_freeze (encode);
  g_hash_table_insert (encode, "a", "\\a");
  g_hash_table_insert (encode, "b", "\\b");
  g_hash_table_insert (encode, "c", "\\c");
  g_hash_table_thaw (encode);

  return encode;
}
#endif

GHashTable *
build_html_encoding (void)
{
  GHashTable *encode;
  encode = g_hash_table_new (g_str_hash, g_str_equal);

  g_hash_table_freeze (encode);
  g_hash_table_insert (encode, "&", "&amp;");
  g_hash_table_insert (encode, "<", "&lt;");
  g_hash_table_insert (encode, ">", "&gt;");
  g_hash_table_insert (encode, "\"", "&quot;");
  g_hash_table_thaw (encode);

  return encode;
}

GHashTable *
build_js_encoding (void)
{
  GHashTable *encode;
  encode = g_hash_table_new (g_str_hash, g_str_equal);

  g_hash_table_freeze (encode);
  // g_hash_table_insert (encode, "\\", "\\\\");
  g_hash_table_insert (encode, "\n", "\\n");
  g_hash_table_insert (encode, "'", "\\047");
  g_hash_table_insert (encode, "\"", "\\042");
  g_hash_table_thaw (encode);

  return encode;
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
