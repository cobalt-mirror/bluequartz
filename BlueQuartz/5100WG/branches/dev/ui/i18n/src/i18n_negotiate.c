/* $Id: i18n_negotiate.c 201 2003-07-18 19:11:07Z will $
 *
 * language negotiation functions.
 */

#include "i18n_internals.h"

static gint isLangStr (char *name);
static int isdir (char *path);
static char *defaultLang (char *domain);
static gint gCharPointCmp (gpointer * data1, gpointer * data2);

// #undef DPRINTF
// #define DPRINTF(a...) fprintf(stderr, ##a )


GSList *
validLangs (const i18n_handle * i18n, char *domain)
{
  GSList *available;
  GSList *valids = NULL;
  GSList *prefs = i18n->preflist;

  DPRINTF("validLangs\n");

  available = AvailableLangs (domain);

  /* for each language in our prefered language list */
  while (prefs) {
	DPRINTF("%s in preferences\n", prefs->data);
    /* If we can find the language in our prefrences */
    if (g_slist_find_custom (available, prefs->data,
                             (GCompareFunc) gCharPointCmp)) {
      DPRINTF ("%s is a possibile lang for translation in domain %s\n",
               (char *) prefs->data, domain);
      /* Have to strdup as we will free the whole shebang later */
      valids = g_slist_append (valids, strdup (prefs->data));
    }
    prefs = g_slist_next (prefs);
  }

  /* Out of general principle we now insert our default to the end of
   * valids */
  if (valids == NULL) {
    char *def = defaultLang(domain);
    if (g_slist_find_custom(available, def, (GCompareFunc) gCharPointCmp)) {
      valids = g_slist_append (valids, def);
      DPRINTF("No preferred languages.  Adding default, %s.\n", def);
    } else {
      free(def);
    }
  }
  
  /* if all else fails, choose a random valid locale */
  if (valids == NULL && available != NULL) {
    DPRINTF("No valids.  Using available language, %s.\n", (char *)available->data);
    valids = g_slist_append(valids, strdup((char*)available->data));
  }
  
  /* Free the available GSList */
  free_whole_g_slist (available);

  /* now we're really screwed.  Just give the default locale. */
  if (valids == NULL) {
    DPRINTF("Can't find any locales!  Adding default locale, %s.\n", defaultLang(domain));
    valids = g_slist_append(valids, defaultLang(domain));
  }

  return valids;
}

/*
 * Returns the locales available for a passed domain
 *
 * This method searches the uncompiled locale files for all .po
 * files for the passed domain and returns a list of available locales
 * for that domain
 *
 * @author Harris Vaegan-Lloyd <harris@cobalt.com>
 * @param domain The domain to search for the locale in
 * @returns A singly linked list
 */
GSList *
AvailableLangs (char *domain)
{
  GSList *available = NULL;
  GSList *all;
  GString *mofile;
  struct stat buf;

  DPRINTF ("AvailableLangs\n");

  all = AllAvailableLangs ();
  
  while (all) {
    GSList *p;

    mofile = g_string_new (C_MESSAGES_DIR);
    g_string_append_c (mofile, '/');
    g_string_append (mofile, (char *) all->data);
    g_string_append_c (mofile, '/');
    g_string_append (mofile, MESSAGE_DIR);
    g_string_append_c (mofile, '/');
    g_string_append (mofile, domain);
    g_string_append (mofile, ".mo");

    if (!stat (mofile->str, &buf)) {
      DPRINTF ("Adding %s to available language list for %s\n",
               ((char *) all->data), domain);
      available = g_slist_append (available, strdup (all->data));
    }

    /* cleanup */
    g_string_free (mofile, 1);

    p = all;
    all = g_slist_next (all);

    /* data was allocated in AllAvailableLangs */
    free (p->data);
    g_slist_free_1 (p);
  }

  return available;
}

GSList *
AllAvailableLangs (void)
{
  GSList *available = NULL;
  DIR *dfd;
  struct dirent *dir;

  dfd = opendir (C_MESSAGES_DIR);
  if (!dfd) {
    DPRINTF ("AllAvailableLangs: opendir failed : %s\n", strerror (errno));
    return NULL;
  }

  while ((dir = readdir (dfd))) {
    if (isLangStr (dir->d_name) && isdir (dir->d_name)) {
      available = g_slist_append (available, strdup (dir->d_name));
    }
  }

  closedir (dfd);

  return available;
}

static gint
isLangStr (char *lang_dir)
{
  int i;

  /* Certain lengths of string can never be lang dirs. */
  switch (strlen (lang_dir)) {
  case 0:
  case 1:
  case 3:
  case 6:
    return FALSE;

    /* 
     * At this point the string will either be 2 (en)
     * 5 (en_US) or 7+(en_US_EUC characters long 
     */

  default:
    if (!(lang_dir[5] == '_' && lang_dir[5] == '.')) {
      return FALSE;
    }
    for (i = 6; i <= strlen (lang_dir); i++) {
      if (!isalpha(lang_dir[i])) {
        return FALSE;
      }
    }

  case 5:
    if (!(isalpha (lang_dir[0]) && isalpha (lang_dir[1])
          && lang_dir[2] == '_'
	  && isalpha (lang_dir[3]) && isalpha (lang_dir[4]))) {
      return FALSE;
    }

  case 2:
    if (!(isalpha (lang_dir[0]) && isalpha (lang_dir[1]))) {
      return FALSE;
    }
    break;
  }

  return TRUE;
}

/*
 *
 * Checks that the path is an actual directory.
 *
 * Stats and confirms that the passed string in an actual honest to god dir.
 *
 * @author Tim Hockin <thockin@cobalt.com>
 * @author Harris Vaegan-Lloyd <harris@cobalt.com>
 * @param path The path to check.
 * @returns int
 *
 */
static int
isdir (char *path)
{
  char *dirname;
  struct stat s;

  dirname = g_strdup_printf ("%s/%s", C_MESSAGES_DIR, path);

  if ((!stat (dirname, &s)) && (S_ISDIR (s.st_mode))) {
    g_free (dirname);
    return 1;
  }
  g_free (dirname);
  return 0;
}

static char *
defaultLang (char *domain)
{
  GString *filename;
  char lang[MAX_LANG_LEN];
  FILE *fd;

  filename = g_string_new (C_PROP_DIR);
  g_string_append_c (filename, '/');
  g_string_append (filename, domain);
  g_string_append (filename, ".prop");

  fd = fopen (filename->str, "r");
  if (!fd) {
    DPRINTF ("File %s does not exist, using default default lang\n",
             filename->str);
    g_string_free (filename, 1);
    return strdup (DEFAULT_DEFAULT_LANGUAGE);
  }
  g_string_free (filename, 1);

  if (!fgets (lang, MAX_LANG_LEN, fd)) {
    DPRINTF ("File %s is empty\n", filename->str);
    return strdup (DEFAULT_DEFAULT_LANGUAGE);
  }

  if (lang[strlen (lang) - 1] == '\n') {
    lang[strlen (lang) - 1] = '\0';
  }

  if (!isLangStr (lang)) {
    DPRINTF ("Lang %s is not valid\n", lang);
    return strdup (DEFAULT_DEFAULT_LANGUAGE);
  }

  return strdup (lang);
}

static gint
gCharPointCmp (gpointer * data1, gpointer * data2)
{
  if (!data1) { return 1; };
  if (!data2) { return -1; };
  return strcmp ((char *) data1, (char *) data2);
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
