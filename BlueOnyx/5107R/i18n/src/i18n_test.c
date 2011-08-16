#ifdef DEBUG
/* Locally defined debugging functions */
static void GHashPrint (gpointer * key, gpointer * value, gpointer * user);
static void GListPrint (gpointer * value, gpointer * user);
#endif

#ifdef DEBUG

/*
 * Dumps the current state of to standard error
 *
 * @param i18n The i18n object to dump state of
 * @returns Nothing
 * @author Harris Vaegan-Lloyd <harris@cobalt.com>
 * @$ i18n_handle
 */
void
i18n_DumpState (i18n_handle * i18n)
{
  DPRINTF ("Beggining i18n debugging..\n");
  DPRINTF ("Cached Locales..\n");
  g_hash_table_foreach (i18n->cached_locales, (GHFunc) GHashPrint, NULL);
  DPRINTF ("User Preflist..\n");
  g_slist_foreach (i18n->preflist, (GFunc) GListPrint, NULL);
  g_mem_profile ();
  return;
}

static void
GHashPrint (gpointer * key, gpointer * value, gpointer * user)
{
  DPRINTF ("%s -> %s\n", (char *) key, (char *) value);
  return;
}

static void
GListPrint (gpointer * data, gpointer * user)
{
  DPRINTF ("%s\n", (char *) data);
  return;
}


int
i18n_test (void)
{
  GString *p;
  i18n_handle *i18n;
  FILE *fd;
  GHashTable *vars = g_hash_table_new (g_str_hash, g_str_equal);
  GString *str =
    g_string_new
    ("This is a long [[string]] to interpolate.  It includes:\n"
     "  [[var.nvars]] variables - var1:[[var.var1]], var2:[[var.2]]\n"
     "  a file reference - [[file./tmp/foo]]\n"
     "  a string reference - [[str.wont_be_found]]\n"
     "  2 literal tagstarts - [[]] [[]]\n" "  a bad tag - [[tag.cruft]]\n"
     "  a bad var - [[var.cruft]]\n" "  some [[var.var]][[var.trickery]]\n"
     "  a good 'loop' - [[var.foo]] [[var.bar]]\n"
     "  and a bad loop - [[var.loop]]");

  GString *expected =
    g_string_new
    ("This is a long [[string]] to interpolate.  It includes:\n"
     "  4 variables - var1:This is var1, var2:This is 2\n"
     "  a file reference - /tmp/foo.en_AU\n"
     "  a string reference - wont_be_foundUntranslated tag 'wont_be_found' encountered\n"
     "  2 literal tagstarts - [[ [[\n" "  a bad tag - [[tag.cruft]]\n"
     "  a bad var - [[var.cruft]]\n" "  some variable trickery\n"
     "  a good 'loop' - FOO FOOBAR\n"
     "  and a bad loop -  loop: loop2: loop: loop2: loop: loop2: loop: loop2: loop: loop2: loop: loop2: loop: loop2: loop: loop2: loop: loop2: loop: loop2: loop: loop2:[[EVAL LOOP:var.loop]]:loop2 :loop :loop2 :loop :loop2 :loop :loop2 :loop :loop2 :loop :loop2 :loop :loop2 :loop :loop2 :loop :loop2 :loop :loop2 :loop :loop2 :loop ");


  i18n = i18n_new ("cobalt", "en_AU, zh");

  g_hash_table_insert (vars, "nvars", "4");
  g_hash_table_insert (vars, "var1", "This is var1");
  g_hash_table_insert (vars, "2", "This is 2");
  g_hash_table_insert (vars, "loop", " loop:[[var.loop2]]:loop ");
  g_hash_table_insert (vars, "loop2", " loop2:[[var.loop]]:loop2 ");
  g_hash_table_insert (vars, "var", "[[var.var");
  g_hash_table_insert (vars, "trickery", "trickery]]");
  g_hash_table_insert (vars, "vartrickery", "variable trickery");
  g_hash_table_insert (vars, "foo", "FOO");
  g_hash_table_insert (vars, "bar", "[[var.foo]]BAR");

  unlink ("/tmp/foo.en_AU");
  fd = fopen ("/tmp/foo.en_AU", "w");
  fclose (fd);

  fflush (stdout);

  p = interpolate (i18n, str, vars, "cobalt");

  printf ("Internal test of comprehensive interpolate string\n");
  if (strcmp (p->str, expected->str) != 0) {
    printf ("Failed: We expected\n----\n%s\n----\n"
            "\tWe got\n----\n%s\n----\n", expected->str, p->str);
    return 0;
  }
  fflush (stdout);

  g_hash_table_destroy (vars);
  unlink ("/tmp/foo.en_AU");

  i18n_destroy (i18n);

  g_string_free (p, 1);
  g_string_free (str, 1);
  return 1;
}

char *
i18n_encode_test (i18n_handle * i18n, char *source)
{
  GString *result;
  GHashTable *encoding;
  char *p;

  encoding = g_hash_table_lookup (i18n->cached_encodings, "test");

  if (!encoding) {
    encoding = build_test_encoding ();
  }

  result = encode (encoding, source);

  DPRINTF ("%s test encodes to %s\n", source, result->str);

  p = strdup (result->str);
  remember_str (i18n, p);
  g_string_free (result, 1);

  return p;
}
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
