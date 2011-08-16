/*
 * $Id: i18n.c 3 2003-07-17 15:19:15Z will $
 * 
 * i18n lib
 * 
 * Harris Vaegan-Lloyd, Tim Hockin, Jonathan Mayer, Kevin K.M. Chiu
 * Copyright (c) 1999,2000 Cobalt Networks
 *
 */

#include "i18n_internals.h"

/**@pkg i18n */

/* manipulations of the language list and helpers */
static GSList *insertSort (GSList * list, char *string);
static GString *getPropFromFile (char *lang, char *domain, char *prop);

/* Functons to work with available languages */
static GSList *breakUpLang (char *lang);
static GSList *insertListSort (GSList * target, GSList * source);
static GSList *preflistFromString (char *locales);

// #undef DPRINTF
// #define DPRINTF(a...) fprintf(stderr, ##a)

/* 
 * Below are the exported functions for libi18n.
 */

/* the following is a CcDoc style comment.
 * see: http://www.joelinoff.com/ccdoc/
 */
/**
 * Create a new i18n_handle
 *
 * Generates a new i18n handle with the specified default domain and
 * preference list of locales. Break up the locales, sort etc etc. Also 
 * init all caching structures
 *
 * @param domain Sets default domain -- grandfathered in.
 * @param locales The user's preference list of domains
 * @returns A new i18n_handle
 */
i18n_handle *
i18n_new (char *domain, char *locales)
{
  i18n_handle *i18n;

  i18n = (i18n_handle *) malloc (sizeof (struct i18n_handle_struct));
  if (!i18n) {
    return NULL;
  }

  if (locales) 
  {
    DPRINTF("i18n_new: Setting preferred languages to passed in %s.\n", locales);
    i18n->preflist = preflistFromString (locales);
  } 
  else if (getenv ("LANGUAGE") != NULL) 
  {
    DPRINTF("i18n_new: Setting preferred languages from LANGUAGE=%s\n", getenv("LANGUAGE"));
    i18n->preflist = preflistFromString (getenv("LANGUAGE"));
  } 
  else if (getenv ("LANG") != NULL) 
  {
    DPRINTF("i18n_new: Adding LANG=%s to language preflist.\n", getenv("LANG"));
    i18n->preflist = preflistFromString (getenv("LANG"));
  } 
  else 
  {
    DPRINTF("i18n_new: No locale info available.  i18n_new failed.\n");
    free (i18n);
    return NULL;
  }

  /* cached locales uses strings as it's index: */
  i18n->cached_locales = g_hash_table_new (g_str_hash, g_str_equal);

  /* cached encodings uses enctype as it's index: */
  i18n->cached_encodings = g_hash_table_new (g_str_hash, g_str_equal);

  /* Set our default domain */
  if (domain) {
    i18n->domain = strdup (domain);
  } else {
    i18n->domain = strdup (DEFAULT_DEFAULT_DOMAIN);
  }

  i18n->recursion_level = 0;

  // initialize free list
  i18n->freelist = NULL;

  return i18n;
}

/**
 *
 * Destroy an i18n_handle
 * 
 * Destroy the handle along with freeing all strings that have been allocated
 * internally and removing all caching hashes and status.
 *
 * @param i18n The i18n object to destroy
 * @returns void
 */
void
i18n_destroy (i18n_handle * i18n)
{
  /* free cached_locales data and cached_locales itself */
  g_hash_table_foreach_remove (i18n->cached_locales, hash_slist_rm, NULL);
  g_hash_table_destroy (i18n->cached_locales);

  /* free encoding cache */
  g_hash_table_foreach_remove (i18n->cached_encodings, hash_hash_rm, NULL);
  g_hash_table_destroy (i18n->cached_encodings);

  /* free each element of preflist */
  free_whole_g_slist (i18n->preflist);

  /* free the domain */
  free (i18n->domain);

  // free the free list
  free_whole_g_slist (i18n->freelist);

  /* free handle */
  free (i18n);
}

/**
 *
 * Get all available locales on the system or for a domain
 * 
 * @param domain The domain in question or NULL for all locales on the system
 * @returns A list of language code strings
 */
GSList *
i18n_availlocales (char * domain)
{
  if(domain == NULL || *domain == (char)NULL)
    return AllAvailableLangs ();
  else
    return AvailableLangs (domain);
}

/**
 *
 * Get negotiated locales for a domain
 * 
 * @param i18n The i18n object
 * @param domain The domain in question
 * @returns A list of language code strings
 */
GSList *
i18n_locales (i18n_handle * i18n, char * domain)
{
  char *realdomain;
  GSList *langs;

  if(domain == NULL || *domain == (char)NULL)
    realdomain = i18n->domain;
  else
    realdomain = domain;

  if (!(langs = g_hash_table_lookup (i18n->cached_locales, realdomain))) {
    langs = validLangs (i18n, realdomain);
    g_hash_table_insert (i18n->cached_locales, strdup (realdomain), langs);
  }

  return langs;
}

/**
 * 
 * Get the value of a property.
 *
 * Given a property name and optionally a language, will search through the
 * break down of the first specified language and find the first value of
 * the property it encounters, or return an empty string.
 *
 * @param i18n The handle to the current i18n object
 * @param property The name of the property to search for
 * @param lang The optional language to start the search with, NULL for the
 *             default language of the i18n object.
 * @returns A char pointer of the found value.
 */
char *
i18n_get_property (i18n_handle * i18n, char *property, char *domain, char *lang)
{
  GSList *alters;
  GSList *curr;
  GString *value;
  char *ret = NULL;

  if (!domain || *domain == (char) NULL) {
    domain = i18n->domain;
  }

  /* not empty language? */
  if (lang && *lang != (char) NULL) {
    alters = breakUpLang (lang);
  } else {
    /* identify languages to select from: */
    if (!(alters = g_hash_table_lookup (i18n->cached_locales, domain))) {
      alters = validLangs (i18n, domain);
      g_hash_table_insert (i18n->cached_locales, strdup (domain), alters);
    }
  }

  /* walk through the list */
  curr = alters;
  while (curr) {
    value = getPropFromFile ((char *) curr->data, domain, property);
    if (value) {
      ret = strdup (value->str);
      g_string_free (value, 1);
      break;
    }

    curr = g_slist_next (curr);
  }

  /* free return of breakUpLang */
  if (lang && *lang != (char) NULL) {
    free_whole_g_slist (alters);
  }

  if (!ret) {
    /* return an empty string by default */
    ret = strdup ("");
  }

  return ret;
}

/**
 *
 * Returns a fully expanded and translated string.
 *
 * The function that justifies this library. Takes a tag and a set of
 * variables and glues together the translation and interpolation functions
 *
 * @param i18n The i18n handle
 * @param tag The tag to translate and expand
 * @param vars The variables in an i18n_vars form to fill with, or NULL to
 *             disable variable interpolation
 *
 * @returns A pointer to the translated string
 */
char *
i18n_interpolate (i18n_handle * i18n, char *magicstr, i18n_vars * vars)
{
  char *ret;
  ret = internal_interpolate (i18n, magicstr, vars);

  // free the string later
  i18n->freelist = g_slist_prepend(i18n->freelist, ret);

  return ret;
}

char *
i18n_interpolate_html (i18n_handle * i18n, char *magicstr, i18n_vars * vars)
{
  char *ret;
  GString *result;

  ret = internal_interpolate (i18n, magicstr, vars);
  result = encode_core (i18n, ret, "html", build_html_encoding);
  free (ret);

  gstring_2_string(result, ret);

  // free the string later
  i18n->freelist = g_slist_prepend(i18n->freelist, ret);

  return ret;
}

char *
i18n_interpolate_js (i18n_handle * i18n, char *magicstr, i18n_vars * vars)
{
  char *ret;
  GString *result;

  ret = internal_interpolate (i18n, magicstr, vars);
  result = encode_core (i18n, ret, "js", build_js_encoding);
  free (ret);

  gstring_2_string(result, ret);

  // free the string later
  i18n->freelist = g_slist_prepend(i18n->freelist, ret);

  return ret;
}


/**
 *
 * Returns a fully expanded and translated string.
 *
 * The function that justifies this library. Takes a tag and a set of
 * variables and glues together the translation and interpolation functions
 *
 * @param i18n The i18n handle
 * @param tag The tag to translate and expand
 * @param vars The variables in an i18n_vars form to fill with, or NULL to
 *             disable variable interpolation
 *
 * @returns A pointer to the translated string
 */
char *
i18n_get (i18n_handle * i18n, char *tag, char *domain, i18n_vars * vars)
{
  char *ret;

  if (!domain) {
    domain = i18n->domain;
  }

  ret = internal_gettext (i18n, domain, tag, vars);

  // free the string later
  i18n->freelist = g_slist_prepend(i18n->freelist, ret);

  return ret;
}

/**
 *
 * The same as i18n_get except with javascript encoding.
 * @see i18n_get
 *
 */
char *
i18n_get_js (i18n_handle * i18n, char *tag, char *domain, i18n_vars * vars)
{
  char *ret;
  GString *result;

  if (!domain) {
    domain = i18n->domain;
  }

  ret = internal_gettext (i18n, domain, tag, vars);
  result = encode_core (i18n, ret, "js", build_js_encoding);
  free (ret);

  gstring_2_string(result, ret);

  // free the string later
  i18n->freelist = g_slist_prepend(i18n->freelist, ret);

  return ret;
}

/**
 *
 * The same as i18n_get except with html encoding.
 * @see i18n_get
 *
 */
char *
i18n_get_html (i18n_handle * i18n, char *tag, char *domain, i18n_vars * vars)
{
  char *ret;
  GString *result;

  if (!domain) {
    domain = i18n->domain;
  }

  ret = internal_gettext (i18n, domain, tag, vars);
  result = encode_core (i18n, ret, "html", build_html_encoding);
  free (ret);

  gstring_2_string(result, ret);

  // free the string later
  i18n->freelist = g_slist_prepend(i18n->freelist, ret);

  return ret;
}

/**
 *
 * Get the time in the correct format for the current locale.
 *
 * Given a format that is identical to the one for strftime will format
 * the epochal time as found in time_t to the current locale settings
 *
 * @param i18n The current i18n object.
 * @param format The format to print the string in. %x %X and %C are V.
 *               useful ones.
 * @param t The epochal time to format.
 *
 * @returns A pointer to a string formatted to the specified time
 */
char *
i18n_strftime (i18n_handle * i18n, char *format, time_t t)
{
  char *result;
  char buf[MAX_FTIME_LEN];
  struct tm *tm;

  setlocale (LC_TIME, i18n->preflist->data);

  tm = localtime (&t);

  strftime (buf, MAX_FTIME_LEN, format, tm);

  result = strdup (buf);

  return result;
}

/**
 *
 * Get the best existing filename, considering locale preferences.
 *
 * Given the list of locales from the user, this will find the best version
 * of a file (based on locale extension) for the user.
 *
 * @param i18n The current i18n object.
 * @param file The base filename (full path) for which to search
 *
 * @returns A pointer to a string formatted to the specified time
 */
char *
i18n_get_file (i18n_handle * i18n, char *file)
{
  char *result;

  result = internal_get_file (i18n, file);
  return result;
}


/**
 *
 * Encode a given string in HTML-compilant form.
 *
 * This is useful for reading translated tags, and using them in HTML
 * contexts.
 *
 * @param i18n The current i18n object.
 * @param source The string to encode.
 *
 * @returns A pointer to a string formatted to the specified time
 *
 * @see i18n_get_html
 */
char *
i18n_encode_html (i18n_handle * i18n, char *source)
{
  GString *result;
  char *p;

  result = encode_core (i18n, source, "html", build_html_encoding);

  p = strdup (result->str);
  g_string_free (result, 1);

  return p;
}

/**
 *
 * Encode a given string in javascript-compilant form.
 *
 * This is useful for reading translated tags, and using them in javascript
 * contexts.
 *
 * @param i18n The current i18n object.
 * @param source The string to encode.
 *
 * @returns A pointer to a string formatted to the specified time
 *
 * @see i18n_get_js
 */
char *
i18n_encode_js (i18n_handle * i18n, char *source)
{
  GString *result;
  char *p;

  result = encode_core (i18n, source, "js", build_js_encoding);

  p = strdup (result->str);
  g_string_free (result, 1);

  return p;
}

/**
 *
 * Retrieve the date/time in the format most applicable to the preferred 
 * locale
 *
 * @param i18n The i18n handle
 * @param t The time to format (0 for current time)
 * @returns A pointer to the formatted date/time string
 */
char *
i18n_get_datetime (i18n_handle * i18n, time_t t)
{
  return (i18n_strftime (i18n, "%c", t));
}

/**
 *
 * Retrieve the date in the format most applicable to the preferred locale
 *
 * @param i18n The i18n handle
 * @param t The time to format (0 for current time)
 * @returns A pointer to the formatted date string
 */
char *
i18n_get_date (i18n_handle * i18n, time_t t)
{
  return (i18n_strftime (i18n, "%x", t));
}

/**
 *
 * Retrieve the time in the format most applicable to the preferred locale
 *
 * @param i18n The i18n handle
 * @param t The time to format (0 for current time)
 * @returns A pointer to the formatted time string
 */
char *
i18n_get_time (i18n_handle * i18n, time_t t)
{
  return (i18n_strftime (i18n, "%X", t));
}


/*
 * Below this are internal functions - no more ccDoc comments.
 */


GString *
encode (GHashTable * encoding, char *str)
{
  char cur[2] = {0, 0};
  char *repl;
  GString *encoded = g_string_new("");

  while (*str != '\0') {
    /* We need to hold those single charactes in a
     * char * string for the ghash search */
    cur[0] = *str;
    repl = g_hash_table_lookup (encoding, (char *) cur);
    if (repl) {
      g_string_append (encoded, repl);
    } else {
      g_string_append_c (encoded, cur[0]);
    }
    str++;
  }

  return encoded;
}

GString *
encode_core (i18n_handle * i18n, char *str, char *key,
             GHashTable * (*fn) (void))
{
  GString *result;
  GHashTable *encoding;

  encoding = g_hash_table_lookup (i18n->cached_encodings, key);
  if (!encoding) {
    encoding = fn ();
    g_hash_table_insert (i18n->cached_encodings, strdup (key), encoding);
  }

  result = encode (encoding, str);

  DPRINTF ("encode_core: %s %s encodes to %s\n", str, key, result->str);

  return result;
}

static GString *
getPropFromFile (char *lang, char *domain, char *property)
{
  GString *propfile;
  FILE *fd;
  char *value;
  char buf[MAX_PROP_LINE];

  propfile = g_string_new (C_PROP_DIR);
  g_string_append_c (propfile, '/');
  g_string_append (propfile, lang);
  g_string_append_c (propfile, '/');
  g_string_append (propfile, domain);
  g_string_append (propfile, ".prop");
  DPRINTF ("Reading propfile %s\n", propfile->str);

  fd = fopen (propfile->str, "r");
  if (!fd) {
    DPRINTF ("Could not open file %s\n", propfile->str);
    g_string_free (propfile, 1);
    return NULL;
  }

  while (fgets (buf, MAX_PROP_LINE, fd)) {
    if ((value = index (buf, ':'))) {
      /* 
       * Turn the comma into a \0 so that buf will now read as
       * the property only 
       */
      buf[strlen (buf) - strlen (value)] = '\0';
      /* Skip the : in value */
      value++;
      /* Skip over whitespace between comma and value */
      while (isspace (*value)) {
        value++;
      }

      /* Remove trailing newline if any */
      if (value[strlen (value) - 1] == '\n') {
        value[strlen (value) - 1] = '\0';
      }

      DPRINTF ("Prop: '%s' Value: '%s'\n", buf, value);
      if (!strcmp (buf, property)) {
        g_string_free (propfile, 1);
	fclose(fd);
        return g_string_new (value);
      }
    }
  }

  fclose(fd);

  g_string_free (propfile, 1);
  return NULL;
}

static GSList *
breakUpLang (char *lang)
{
  GSList *alters = NULL;
  GString *alang;
  int index;
  char *p;

  /* make ourselves a copy - the caller may free */
  alang = g_string_new (lang);

  /* Without a doubt we want to get the full lang string in first */
  alters = g_slist_append (alters, strdup (alang->str));

  p = rindex (alang->str, '_');
  while (p) {
    index = alang->len - strlen (p);
    g_string_truncate (alang, index);
    /* only add if it is not in the list */
    if (!g_slist_find_custom (alters, alang->str, slist_str_find)) {
      alters = g_slist_append (alters, strdup (alang->str));
    }
    p = rindex (alang->str, '_');
  }

  g_string_free (alang, 1);

  return alters;
}

static GSList *
preflistFromString (char *locales)
{
  GString *lang;
  GSList *broken = NULL;
  GSList *langs = NULL;
  char *p = locales;
  char isLangSection = 1;

  lang = g_string_new ("");

  while (*p) 
  {
    // LANGUAGE env var uses : as seperator
    if (*p == ',' || *p == ':') {
      DPRINTF ("Adding lang %s to preflist\n", lang->str);
      broken = breakUpLang (lang->str);
      langs = insertListSort (langs, broken);
      /* Unused entries in broken are removed by insertListSort
       * but the structure remains */
      g_slist_free (broken);
      /* get ready for next loop */
      g_string_truncate (lang, 0);

      /* start over in the language section of locales */
      isLangSection = 1;
    } else if (*p == '-' || *p == '_') {
      /* IE can give something like "sq,ja;q=0.7,en-us;q=0.3",
	 so "-" needs to be translated into "_" */
      g_string_append_c (lang, '_');

      /* out of language section */
      isLangSection = 0;
    } else if (*p == ' ') {
      /* Just skip the spaces */
    } else if (*p == ';') {
      /* just skip over IE's cruft. */
      while (*p != '\0' && *p != ',') {
        p++;
      }
      continue;
    } else {
      if (isLangSection) {
	g_string_append_c (lang, *p);
      }
      /* country code and variant must be uppercase */
      else {
	g_string_append_c (lang, toupper(*p));
      }
    }
    p++;
  }

  /* If there's anything left in lang, dump that in.. */
  if (lang->len) {
    DPRINTF ("Adding last lang %s to preflist\n", lang->str);
    broken = breakUpLang (lang->str);
    langs = insertListSort (langs, broken);
    g_slist_free (broken);
  }

  g_string_free (lang, 1);

  return langs;
}


/* 
 * Ho, kay... Here's the drill, iterate over the list. If we reach a point 
 * where the current key is a substate of the new addition e.g. en to en_US 
 * then we add, if it's the same, we just return, if we reach the end, 
 * append as usual 
 */
static GSList *
insertSort (GSList * list, char *string)
{
  GSList *result = list;
  char *list_data;
  int position = 0;
  GSList *curr = list;

  DPRINTF ("SORT: Attempting to add %s\n", string);
  while (curr) {
    list_data = (char *) curr->data;
    if (!strcmp (list_data, string)) {
      DPRINTF ("SORT: %s already in list, dropping\n", string);
      free (string);
      return result;
    } else if (!strncmp (string, list_data, strlen (list_data))) {
      /* This is the list = en string = en_US case. Insert here */
      DPRINTF ("SORT: %s is substr of %s, insert %s at %d\n",
               list_data, string, string, position);
      result = g_slist_insert (result, string, position);
      return result;
    }
    curr = g_slist_next (curr);
    position++;
  }

  /* * When we hit EOList, we fall through to here.  Just append it.  */
  DPRINTF ("SORT: %s is a new entry\n", string);
  result = g_slist_append (result, string);
  return result;
}

static GSList *
insertListSort (GSList * target, GSList * source)
{
  while (source) {
    target = insertSort (target, (char *) source->data);
    source = g_slist_next (source);
  }
  return target;
}


/* eof */
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
