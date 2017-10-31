/* $Id: interpolate.c 3 2003-07-17 15:19:15Z will $
 * 
 * Jonathan's new interpolation and gettext routines.
 */

/* 
 * Grammar for the i18n string is as follows:
 *
 * magicstr := ( text | tag | esctag )*
 * tag := "[[" domain "." tagname vars* "]]"
 * vars := "," key "=" value
 * text, domain, tagname, key, value are ALWAYS escaped text strings, with
 * the possible exception of value, which may be a quoted literal.  The
 * truth is, escaping in the others is pretty inane (except text..duh) - I
 * can't see any good reason to escape within domain, tagname, or key.
 *
 * There are two special reserved domains have special meaning: FILE and VAR
 * "[[FILE." tagname vars* "]]" -> tagname is used as filename, vars ignored.
 * "[[VAR." tagname vars* "]]" -> tagname used as var id, vars ignored.
 *
 * escaping:
 *   "\n" -> newline  "\t" -> tab "\[" -> "["  "\\" -> "\"
 *   "\," -> ","  "\=" -> "=".  other than those chars, everything
 *   else is left as-is.
 *
 */

#include "i18n_internals.h"

// maximum levels of recursion allowable
#define MAX_RECURSION		(64)

/* get_unescaped_token1
 *
 * the first simple lexer: gets all characters up to the beginning of a tag,
 * unescaping the string as it goes.
 */
char *
get_unescaped_token1 (char *src, GString * dest)
{
  char *ptr = src;
  char c;

  while (*ptr != '\0' && !(ptr[0] == '[' && ptr[1] == '[')) {
    c = *ptr;                   /* get a character for analysis */

    /* all escaping is done at gettext() time, except \[ */
    if (c == '\\') {
	ptr++;
	switch (*ptr) {
		case '[':
			c = '[';
			break;
		default:
			g_string_append_c(dest, '\\');
			c = *ptr;
	}
    }
    g_string_append_c(dest, c);  /* push char onto dest string */
    ptr++; /* advance to next character */
  }
  return ptr;
}

/* get_unescaped_token2
 *
 * the second simple lexer: gets all characters upt the first occurence of
 * one of the delimiter characters, unescaping as it goes.
 */
char *
get_unescaped_token2 (char *src, char *delims, GString * dest)
{
  char *ptr = src;
  char c;

  while (*ptr != '\0' && !strchr (delims, *ptr)) {
    c = *ptr;                   /* get a character for analysis */

    /* all escaping is done at gettext() time, except \[ */
    if (c == '\\') {
	ptr++;
	switch (*ptr) {
		case '[':
			c = '[';
			break;
		default:
			g_string_append_c(dest, '\\');
			c = *ptr;
	}
    }
    g_string_append_c(dest, c);  /* push char onto dest string */
    ptr++;                      /* advance to next character */
  }
  return ptr;
}

/* get_quoted_token
 *
 * get a quoted literal string - no interpreting escapes, except \"
 */
char *
get_quoted_token(char *src, GString *dest)
{
	char *p;
	char *tmp;
	int len;

	/* copy so we can change it */
	tmp = strdup(src);

	p = strchr(tmp, '"');
	while (p && *(p-1) == '\\') {
		p = strchr(p+1, '"');
	}

	if (p) {
		*p = '\0';
		g_string_append(dest, tmp);
		len = strlen(tmp) + 1; /* +1 for the last quote */
	} else {
		g_string_append(dest, src);
		len = strlen(src);
	}
	free(tmp);

	return (src + len);
}

/*
 * provide basic escaping for freshly dgettext()ed strings
 * returns 1 if the string needs to be interpolated (with [[), 0 otherwise
 */
int
base_escape(char *s, GString *result)
{
  char *p;
  char c;
  int isInterpolate = 0;

  g_string_assign(result, "");

  p = s;
  /* walk through the string */
  while (*p) {
    // check for 
    if(!isInterpolate && *p == '[' && *(p+1) == '[')
      isInterpolate = 1;

    c = *p;

    if (c == '\\') {
	p++;
	switch (*p) {
		/* we don't unescape \[ here - those are at the end */
		/* case made redundant by change in default case */
/*		case '[':
    			g_string_append_c(result, '\\');
			c = *p;
			break; */
		case ',':
			c = ',';
			break;
		case '=':
			c = '=';
			break;
	    	case '\\':
			c = '\\';
			break;
		case '"':
			c = '"';
			break;
		case 'a':
			c = '\a';
			break;
		case 'b':
			c = '\b';
			break;
		case 'f':
			c = '\f';
			break;
		case 'n':
			c = '\n';
			break;
		case 'r':
			c = '\r';
			break;
		case 't':
			c = '\t';
			break;
		case 'v':
			c = '\v';
			break;
		default:
			g_string_append_c(result,'\\');
			c = *p;
	}
    }
    g_string_append_c(result, c);
    p++;
  }

  return isInterpolate;
}

/* internal_interpolate
 *
 * parses a magic string, unescaping and performing all tag expansions
 * as they are encountered.
 *
 * calls internal_gettext to expand tags.
 *
 * caller to this function must free up returned string after use
 */
char *
internal_interpolate (i18n_handle * i18n, char *magicstr, i18n_vars * vars)
{
  char *str;
  GString *outbuf, *buffer;
  char *domain, *tag, *key, *value;
  char *inptr;
  enum
  { NORMAL, TAG, KEY, VALUE, ENDTAG }
  state;

  // don't let i18n go too deep:
  if (i18n->recursion_level > MAX_RECURSION) {
	char *buf;
	buf = strdup("MAX-RECURSION-REACHED");
	return(buf);
  }
  i18n->recursion_level++;

  /* initialize buffers and such */
  outbuf = g_string_new ("");
  buffer = g_string_new ("");
  inptr = magicstr;
  domain = tag = key = value = 0;
  state = NORMAL;

  while (*inptr != '\0') {
    g_string_assign (buffer, "");
    switch (state) {
    case NORMAL:
      inptr = get_unescaped_token1 (inptr, buffer);
      /* if we're at the beginning of a tag ... */
      if (inptr[0] == '[' && inptr[1] == '[') {
        inptr += 2;
        state = TAG;
      }
      g_string_append (outbuf, buffer->str);
      break;
    case TAG:
      /* support a dummy tag ... */
      if (inptr[0] == ']' && inptr[1] == ']') {
        g_string_append (outbuf, "[[");
        inptr += 2;
        state = NORMAL;
        break;
      }
      inptr = get_unescaped_token2 (inptr, ".,]", buffer);
      if (*inptr == '.') {
        if (domain) {
          free (domain);
        }
        domain = strdup (buffer->str);
        inptr++;
        state = TAG;
        break;
      }
      if (*inptr == ',') {
        if (tag) {
          free (tag);
        }
        tag = strdup (buffer->str);
        inptr++;
        state = KEY;
        break;
      }
      if (*inptr == ']') {
        if (tag) {
          free (tag);
        }
        tag = strdup (buffer->str);
        inptr++;
        state = ENDTAG;
        break;
      }
      break;
    case KEY:
      inptr = get_unescaped_token2 (inptr, "=", buffer);
      if (key) {
        free (key);
      }
      key = strdup (buffer->str);
      if (*inptr == '=') {
        inptr++;
        state = VALUE;
      }
      break;
    case VALUE:
	if (inptr[0] == '"') {
		inptr = get_quoted_token(inptr+1, buffer);
	} else {
      	inptr = get_unescaped_token2 (inptr, ",]", buffer);
	}
      if (value) {
        free (value);
      }
      value = strdup (buffer->str);
      i18n_vars_add (vars, key, value); /* add to hash */
      if (*inptr == ',') {
        inptr++;
        state = KEY;
      } else if (*inptr == ']') {
        inptr++;
        state = ENDTAG;
      }
      break;
    case ENDTAG:
      if (*inptr == ']') {
        inptr++;
      }
      str = internal_gettext (i18n, domain, tag, vars);
      g_string_append (outbuf, str);
      free (str);
      state = NORMAL;
      break;
    }                          /* end of switch */
  }                            /* end of while loop */
  if (state != NORMAL) {
    DPRINTF("internal_interpolate(): exiting with state = %d\n", state);
    /* return the passed-in string */
    g_string_assign(outbuf, magicstr);
  }

  /* return the internationalized string */
  str = outbuf->str;
  g_string_free (outbuf, 0);
  /* free stuff */
  g_string_free (buffer, 1);
  if (domain) free(domain);
  if (tag) free(tag);
  if (key) free(key);
  if (value) free(value);

  i18n->recursion_level--;
  return str;
}

/* internal_gettext
 *
 * expands and interpolates a tag specified by domain and tag, rather than
 * by magic string.
 *
 * calls internal_interpolate to expand magic strings.
 *
 * caller of this function must free the returned string after use.
 */
char *
internal_gettext(i18n_handle *i18n,
                  char *domain, char *tag, i18n_vars *vars)
{
  char *buf;

  if (!domain) {
    domain = i18n->domain;
  }

  /* check for special VAR domain */
  if (!strcmp(domain, "VAR")) {
    char *val;

    /* fetch a variable value, interpolate it, or leave it as a literal */
    val = g_hash_table_lookup((GHashTable *)vars, tag);
    if (val) {
    	buf = internal_interpolate(i18n, val, vars);
    } else {
      buf = malloc(6 + strlen (tag) + 2 + 1);
      strcpy(buf, "[[VAR.");
      strcat(buf, tag);
      strcat(buf, "]]");
    }
    return buf;
  }

  /* check for special FILE domain */
  if (!strcmp (domain, "FILE")) {
    /* fetch a file thingy or leave it as a literal */
    buf = internal_interpolate(i18n, internal_get_file(i18n, tag), vars);
    if (!buf) {
      buf = malloc(7 + strlen (tag) + 2 + 1);
      strcpy(buf, "[[FILE.");
      strcat(buf, tag);
      strcat(buf, "]]");
    }
    return buf;
  }

  /* else we've got a genuine string to interpolate */
  {
    GSList *langs;
    char *tagstring = NULL;
    int isInterpolate = 0;

    /* identify languages to select from: */
    if (!(langs = g_hash_table_lookup (i18n->cached_locales, domain))) {
      langs = validLangs (i18n, domain);
      g_hash_table_insert (i18n->cached_locales, strdup (domain), langs);
    }

    /* attempt to find language string by searching all langs */
    while (langs) {
      // XXX lookup using gettext
      // it appears that in glibc 2.2.X gettext doesn't look for the LANG
      // environment variable itself, so it is now necessary to call 
      // setlocale here.  the gettext functions will however look at the
      // LANGUAGE env var for a colon-delimited list of preferred locales,
      // which can mess things up if you have it set so that an available
      // language is listed earlier in the LANGUAGE list then the language
      // that you actually wanted.  Shouldn't normally be a problem though.
      // setenv("LANG", (char *)langs->data, 1);
     
      // rather than bothering to set an env variable, just give setlocale
      // the locale directly, to avoid any funky collisions if the LC_* env
      // vars are already set
      setlocale(LC_ALL, (char *)langs->data);

      // Set the LANG env also just so that we don't break glibc 2.1 stuff
      setenv("LANGUAGE", (char *)langs->data, 1);


      tagstring = dgettext(domain, tag);
      
      DPRINTF("internal_gettext: language = %s\n", (char *)langs->data);
      DPRINTF("internal_gettext: domain = %s, tag = %s, string = %s\n", 
		domain, tag, tagstring);

      // FIXME: dgettext does NOT return null for failure.  It returns a
      // copy of the "tag" string.  This means that it is _impossible to
      // reliably detect failure of gettext_, since sometimes the _correct_
      // translation of a string is the string itself (ie. "save"->"save").
      // This is going to REMAIN BROKEN until we replace gettext with
      // a solution that doesn't suck so hard.
      // always assume it succeed here
      if (1) 
      {
	    // escape the string
	    GString *escaped = g_string_sized_new(strlen(tagstring));
	    isInterpolate = base_escape(tagstring, escaped);
	    gstring_2_string(escaped, tagstring);

        break;
      }

      // next preferred
      langs = g_slist_next(langs);
    }

    // need to interpolate?
    if (isInterpolate) {
      buf = internal_interpolate(i18n, tagstring, vars);
      // free tagstring
      free(tagstring);

      return buf;
    }
    else
      return tagstring;
  }
}

/* internal_get_file
 *
 * I don't think this function is going to solve the problem that it's
 * designer's meant it to solve.  But, hey, I'll let them figure that on
 * on their own.  mod_negotiate in Apache does the right thing, better.
 */
char *
internal_get_file (i18n_handle * i18n, char *file)
{
  struct stat buf;
  char *result;
  GString *filename = g_string_new ("");
  GSList *prefs;

  prefs = i18n->preflist;

  g_string_assign (filename, file);
  while (prefs) {
    g_string_append_c (filename, '.');
    g_string_append (filename, (char *) prefs->data);

    DPRINTF ("Searching for file %s\n", filename->str);

    if (stat (filename->str, &buf) == 0) {
      break;
    }

    prefs = g_slist_next (prefs);
    g_string_assign (filename, file);
  }

  result = filename->str;
  g_string_free (filename, 0);
  return result;
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
