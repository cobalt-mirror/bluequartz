/* $Id: cce_scalar.c 3 2003-07-17 15:19:15Z will $
 */
#ifdef DEBUG_LIBSCALAR
#	define CCE_ENABLE_DEBUG
#else
#	undef CCE_ENABLE_DEBUG
#endif /* ifdef DEBUG */
#include <cce_debug.h>

#include <cce_common.h>
#include <cce_scalar.h>
#include <stresc.h>

#include <string.h>
#include <stdio.h>
#include <sys/stat.h>
#include <unistd.h>
#include <stdlib.h>
#include <fcntl.h>
#include <unistd.h>
#include <glib.h>

static void *cce_scalar_alloc_data(int len);

/*
 * cce_scalar_alloc_data
 *
 * internal: allocate the data region of a scalar
 * returns:
 *	NULL on error
 *	void * to data region on success
 */
static void * 
cce_scalar_alloc_data(int len)
{
	char *newdata;
	
	/* tack on an extra NULL for strings */
	newdata = (void *)malloc(len+1);
	if (!newdata) {
		return NULL;
	}
	memset(newdata, 0, len+1);

	return newdata;
}


/*
 * cce_scalar_new_undef
 * 
 * allocate a new undefined scalar
 * returns:
 *	NULL on failure
 *	a new, undefined cce_scalar * on success
 */
cce_scalar *
cce_scalar_new_undef(void)
{
	cce_scalar *s;
	
	/* get the space for the scalar */	
	s = (cce_scalar *)malloc(sizeof(cce_scalar));
	if (!s) {
		return NULL;
	}

	/* set undefined vals */
	s->data = NULL;
	s->length = 0;

	return s;
}
	

/*
 * cce_scalar_new
 * 
 * allocate a new scalar of a given size
 * returns:
 *	NULL on failure
 *	a new cce_scalar * on success
 */
cce_scalar *
cce_scalar_new(int size)
{
	cce_scalar *s;
	
	/* first get the space for the scalar itself */	
	s = cce_scalar_new_undef();
	if (!s) {
		return NULL;
	}

	/* allocate the data */
	s->data = (void *)cce_scalar_alloc_data(size);
	if (!s->data) {
		/* crap! */
		free(s);
		return NULL;
	}
	s->length = size;

	return s;
}


/*
 * cce_scalar_resize
 * 
 * change the size of an existing scalar
 * returns:
 *	NULL on failure (s is unchanged)
 *	the modified cce_scalar * on success
 */
cce_scalar * 
cce_scalar_resize(cce_scalar *s, int size)
{
	void *new_data;

	/* JIC */
	if (!s) { 
		return NULL; 
	}
	
	/* resize it +1 for NULL - this may or may not change from orig data */
	new_data = (void *)realloc(s->data, size+1);
	if (!new_data) {
		return NULL;
	}
	if (size >= s->length) {
		memset(new_data + s->length, '\0', size - s->length + 1);
	} else {
		/* if we shrink, we still need to null-terminate. */
		memset(new_data + size, '\0', 1);
	}
	
	/* only change if it suceeded */
	s->length = size;
	s->data = new_data;
		
	return s;
}

void
cce_scalar_reset(cce_scalar *s)
{
	memset(s->data, '\0', s->length+1);
}


/*
 * cce_scalar_undefine
 * 
 * free data for an existing scalar
 * returns:
 *	nothing - always succeeds
 */
void 
cce_scalar_undefine(cce_scalar *s)
{
	/* JIC */
	if (!s) { 
		return;
	}

	if (s->data) {
		free(s->data);
	}
	s->data = NULL;
	s->length = 0;
}


/*
 * cce_scalar_destroy
 * 
 * free data and the scalar structure
 * returns:
 *	nothing - always succeeds
 */
void 
cce_scalar_destroy(cce_scalar *s)
{
	/* JIC */
	if (!s) { 
		return; 
	}
	
	cce_scalar_undefine(s);
	free(s);
}
	

/*
 * cce_scalar_new_from_str
 *
 * allocate a new scalar from a string
 * returns:
 *	NULL on error
 *	new cce_scalar * on success
 */
cce_scalar *
cce_scalar_new_from_str(char *str)
{
	cce_scalar *s;

	if (!str) {
		return cce_scalar_new_undef();
	}

	s = cce_scalar_new(strlen(str));
	if (s) {
		strncpy(s->data, str, s->length);
	}

	return s;
}


/*
 * cce_scalar_new_from_qstr
 *
 * allocate a new scalar from a quoted string
 * this may include \n, \t etc., between '"' signs
 * returns:
 *	NULL on error
 *	new cce_scalar * on success
 */
cce_scalar *
cce_scalar_new_from_qstr(char *str)
{
	char *newstr;
	cce_scalar *s;

	/* must be a quoted string */
	if (!str || str[0] != '\"') {
		return NULL;
	}

	/* decode the quoted string (skip the first '"') */
	newstr = strunesc(str+1);

	/* wipe out the last '"' with '\0', but only if the last character is a quote */
	if (newstr[strlen(newstr)-1] == '\"')
		*(newstr + strlen(newstr) - 1) = '\0';

	s = cce_scalar_new_from_str(newstr);
	free(newstr);

	return s;
}


/*
 * cce_scalar_new_from_binstr
 *
 * allocate a new scalar from a base64 encoded binary string
 * format #n+#base64_string
 *
 * see: http://www.marlant.halifax.dnd.ca/Connected/RFC/1521/7.html
 * for more info on Base64 encoding.
 * 
 * returns:
 *	NULL on error
 *	new cce_scalar * on success
 */
cce_scalar *
cce_scalar_new_from_binstr(char *str)
{
	cce_scalar *s;
	unsigned long size = 0;
	char *cP;
	char *writeP;
	unsigned long int i;
	unsigned long int accumulator; /* must be at least 3 bytes */
	int counter;
	
	if (!str) {
		return NULL;
	}

	/* find size */
	size = strtoul(str+1, NULL, 0);
	
	/* allocate */
	s = cce_scalar_new(size);
	if (!s) {
		return NULL;
	}

	/* base64 decode and copy */
	size = 0;
	cP = str+1;
	while(*cP!='\0' && *cP!='#'){cP++;};cP++; /* ff past '#' */
	writeP = s->data;
	accumulator = 0;
	counter = 0;
	while (size < s->length) {
		/* translate character -> 6 bit value */
		i = 0;
		if (*cP) {
			if ((*cP >= 'A') && (*cP <= 'Z')) i = *cP - 'A';
			else if ((*cP >= 'a') && (*cP <= 'z')) i = 26 + *cP - 'a';
			else if ((*cP >= '0') && (*cP <= '9')) i = 52 + *cP - '0';
			else if (*cP == '+') i = 62;
			else if (*cP == '/') i = 63;
			DPRINTF(DBG_SCALAR, "%c -> %02x\n", *cP, (unsigned int)i);
			cP++;
		} /* else use 0 */
		
		/* accumulate 6-bit value onto 3-byte accumulator */
		accumulator <<= 6;
		accumulator += i;
		counter++;
		
		/* when the accumulator is full, flush it */
		if (counter == 4) {
			for (counter = 0; counter < 3; counter++)
			{
				DPRINTF(DBG_SCALAR, "acc = %08lx\n", accumulator);
				*writeP++ = 0xFF & (accumulator >> 16);
				size++;
				if (size >= s->length) break;
				accumulator <<= 8;
			}
			counter = 0;
			accumulator = 0;
		}
	} 
	 
	return s;
}
	

/*
 * cce_scalar_new_from_bin
 *
 * allocate a new scalar from binary data 
 * returns:
 *	NULL on error
 *	new cce_scalar * on success
 */
cce_scalar *
cce_scalar_new_from_bin(char *bindata, int len)
{
	cce_scalar *new_sc;

	if (!bindata) {
		return NULL;
	}

	new_sc = cce_scalar_new(len);
	if (!new_sc) {
		return NULL;
	}

	/* copy the data */
	memcpy(new_sc->data, bindata, new_sc->length);

	return new_sc;
}

cce_scalar *
cce_scalar_new_from_any(char *str)
{
  /* if (*str == '#') {
    return cce_scalar_new_from_binstr(str);
  } */
  if (*str == '\"') { 
    return cce_scalar_new_from_qstr(str);
	
  }

  return cce_scalar_new_from_str(str);
}


/*
 * cce_scalar_dup
 *
 * allocate a new scalar from an existing scalar 
 * returns:
 *	NULL on error
 *	new cce_scalar * on success
 */
cce_scalar *
cce_scalar_dup(cce_scalar *orig)
{
	cce_scalar *new_sc = NULL;

	if (cce_scalar_isdefined(orig)) {
		new_sc = cce_scalar_new(orig->length);
		if (!new_sc) {
			return NULL;
		}

		/* copy the data */
		memcpy(new_sc->data, orig->data, new_sc->length);
	} else if (orig) {
		new_sc = cce_scalar_new_undef();
	}

	return new_sc;
}

void 
cce_scalar_assign(cce_scalar *l, cce_scalar *r)
{
	cce_scalar_resize(l, r->length);
	memcpy(l->data, r->data, r->length);
}


/* cce_scalar_to_str
 *
 * converts the scalar data to an appropriate string representation
 * (ie. binary, quoted, etc.)
 * returns null on error
 */
char *
cce_scalar_to_str(cce_scalar *s)
{
  int quoted_chars = 0;
  int binflag = 0;
  char *cp;
  int i;
 
  static const char * alltext = 
    "ABCDEFGHIJKLMNOPQRSTUVWXYZ"
    "abcdefghijklmnopqrstuvwxyz"
    "0123456789 ,.<>/?;:'[{]}\\|=+-_!@#$%^&*()`~"
    " \t\n\r\"";
  static const char *specials = "\t\r\n\"";
  
  /* make sure s is actually there */
  if (!s)
	return NULL;

  /* scan string to see what's in it: */
  cp = s->data;
  for (i = 0; i < s->length; i++,cp++) {
    if (!strchr(alltext, *cp))  {
      binflag = 1;
    }
    if (strchr(specials,*cp)) {
      quoted_chars++;
    }
  }

  if (binflag) {
    /* return a binstr encoded string */
    return cce_scalar_to_binstr(s);
  } else {
    /* return a quoted string encoded string */
    char *buf;
    char *p;
    
    p = stresc(s->data);
    buf = malloc(strlen(p) + 2 + 1);
    sprintf(buf, "\"%s\"", p);
    free(p);

    return buf;
  }
}

/* 
 * cce_scalar_to_binstr
 *
 * convert the scalar data to a scalar readable binstr
 * returns:
 *	NULL on error
 *	a char * to the new binstr (NULL terminated)
 */
char *
cce_scalar_to_binstr(cce_scalar *s)
{
	int outsize;
	int extra;
	int nprinted = 0;
	int charcount = 0;
	char buf[17];
	char *newdata;
	char *ptr;
	char *src;
	unsigned long acc = 0;
	unsigned char alpha[64] =
	"ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/";

	/* figure how long the result will be */
	outsize = ((s->length / 3) + ((s->length % 3) ? 1 : 0)) * 4 ;
	
	/* add the two '#' signs, add the size length */
	snprintf(buf, 16, "%u", s->length);
	extra = 2 + strlen(buf);

	/* make space for the encoded data */
	newdata = (char *)malloc(outsize+extra+1);
	if (!newdata) {
		return NULL;
	}
	
	/* clear it, JIC */
	memset(newdata, 0, outsize+extra+1);

	/* starting points */
	ptr = newdata;
	src = (char *)s->data;

	/* start the output */
	ptr += snprintf(ptr, 19, "#%d#", s->length);
	
	/* outsize is rounded to a multiple of 4 */
	while (nprinted < outsize) {
		/* make room for a new byte */
		acc <<= 8;
		acc += (*src) & 0xff;
		
		charcount++;
		if (charcount == 3) {
			/* 3*8bytes => 4*6bytes (ASCII) */
			ptr += snprintf(ptr, 5, "%c%c%c%c", alpha[acc >> 18], 
				alpha[(acc >> 12) & 0x3f], alpha[(acc >> 6) & 0x3f],
				alpha[acc & 0x3f]);
			charcount = 0;
			acc = 0;
			nprinted+=4;
		}

		/* if we get to the end of the src, stay there, at NULL */
		if ((src - (char *)s->data) < s->length) {
			src++; 
		}
	}
	
	return newdata;
}


/*
 * cce_scalar_new_from_file
 *
 * read binary data from a file into a newly created scalar object
 * returns:
 *	NULL if OOM or can't open file
 *	a new cce_scalar *on success
 */
cce_scalar *
cce_scalar_new_from_file(char *filename)
{
	cce_scalar *scalar;
	
	/* alloc */
	scalar = cce_scalar_new_undef();
	if (!scalar) {
		/* out of memory */
		return NULL; 
	}
	
	if (cce_scalar_from_file(scalar, filename)) {
		/* something bad happened */
		cce_scalar_destroy(scalar);
		return NULL;
	}
	
	return scalar;
}


/*
 * cce_scalar_from_file
 *
 * read binary data from a file into a newly resized scalar object
 * returns:
 *	-1 if an error occurs
 *	0 on success
 */
int
cce_scalar_from_file(cce_scalar *scalar, char *filename)
{
	struct stat statbuf;
	int fd;

	if (stat(filename, &statbuf)) {
		/* file does not exist */
		cce_scalar_undefine(scalar);
		return 0;
	}

	/* realloc */	
	if (!cce_scalar_resize(scalar, statbuf.st_size)) {
		return -1;
	}
        cce_scalar_reset(scalar); /* otherwise not null-terminated. */

	/* open */
	fd = open(filename, O_RDONLY);
	if (!fd) {
		/* file could not be read */
		return -1; 
	}
	
	/* load */
	read(fd, scalar->data, scalar->length);
	close(fd);
	
	return 0;
}


/*
 * cce_scalar_to_file
 *
 * write binary data from scalar into a file, or unlink the file
 * if the scalar is undefined.  This corresponds to the three (or two,
 * depending on POV) states of a scalar: 
 * - defined: data exists
 * - blank: data is 0 length
 * - undefined: data is NULL ptr
 * returns
 *	-1 on error (check errno)
 *	0 on success
 */
int 
cce_scalar_to_file(cce_scalar *scalar, char *filename)
{
	struct stat statbuf;
	int fd;
	
	/* check scalar */
	if (!scalar) {
		return -1;
	}
	
	if (cce_scalar_isdefined(scalar)) {
		/* open it */
		fd = open(filename, O_TRUNC|O_WRONLY|O_CREAT, S_IRUSR|S_IWUSR);
		if (fd < 0) {
			/* could not open file for writing */
			return -1; 
		}

		/* write it */
		if ((write(fd, scalar->data, scalar->length)) < 0) {
			close(fd);
			return -1;
		}
		close(fd);
	} else {
		/* scalar is undefined */
		if (!stat(filename, &statbuf)) {
			if ((unlink(filename)) < 0) { 
				return -1; 
			}
		}
	}

	/* done */	
	return 0;
}

////////////////////////////////////////////////////////////////////////
// memory leak debugging code
////////////////////////////////////////////////////////////////////////
static GHashTable *cce_scalar_alloc = NULL;

struct alloc {
	int size;
	char *file;
	int line;
};

static struct alloc *
new_alloc(int size, char *file, int line)
{
	struct alloc *a;

	a = malloc(sizeof(struct alloc));
	a->size = size;
	a->file = file;
	a->line = line;

	return a;
}

static void
dbg_cce_scalar_init(void)
{
	if (!cce_scalar_alloc) {
		cce_scalar_alloc = g_hash_table_new(g_direct_hash, g_direct_equal);
		if (!cce_scalar_alloc) {
			DPERROR(DBG_SCALAR, "g_hash_table_new");
		}
	}
}

static void
print_leak(gpointer key, gpointer value, gpointer crap)
{
	char buf[16];
	struct alloc *a = value;
	FILE *file = (FILE*)crap;

	fprintf(file, "cce_scalar: %s(%d): leak at %p, %d bytes (\"%.15s\")\n",
  		a->file, a->line, key, a->size, buf );
}

static void
free_alloc(gpointer key, gpointer value, gpointer crap)
{
	free(value);
}

void
dbg_cce_scalar_dump(FILE *file)
{
	if (!file) { file = stderr; }
	fprintf(file, "\ncce_scalar leaks:\n");

	if (cce_scalar_alloc) {
		g_hash_table_foreach(cce_scalar_alloc, print_leak, file);
		g_hash_table_foreach(cce_scalar_alloc, free_alloc, NULL);
		g_hash_table_destroy(cce_scalar_alloc);
		cce_scalar_alloc = NULL;
	}
	fprintf(file, "end of cce_scalar leaks.\n");
}

cce_scalar *
dbg_cce_scalar_alloc (cce_scalar *sc, char *file, int line)
{
	dbg_cce_scalar_init();
	fprintf(stderr,"cce_scalar: %s(%d): alloced 0x%lx\n",
		file, line, (unsigned long)sc);
	g_hash_table_insert(cce_scalar_alloc, sc, new_alloc(0, file, line ));
  
	return sc;
}  

void
dbg_cce_scalar_free(cce_scalar *sc, char *file, int line)
{
	gpointer k, v;
	dbg_cce_scalar_init();

	fprintf(stderr,"cce_scalar: %s(%d): freed 0x%lx\n",
		file, line, (unsigned long)sc);	

	if (g_hash_table_lookup_extended(cce_scalar_alloc, sc, &k, &v)) {
		free(v);
		g_hash_table_remove(cce_scalar_alloc, sc);
	} else {
		DPRINTF(DBG_SCALAR, 
			"%s(%d): Deallocated non-existant cce_scalar 0x%lx\n",
		file, line, (long unsigned int)sc);
	}
}

int
cce_scalar_compare(cce_scalar *s1, cce_scalar *s2)
{
	int minlen;
	int cmp = 0;

	minlen = (s1->length < s2->length) ? s1->length : s2->length;
	
	if (minlen) {
		cmp = memcmp(s1->data, s2->data, minlen);
	}

	if (cmp == 0) {
		if (s1->length < s2->length) { 
			cmp = -1; 
		} else if (s1->length > s2->length) { 
			cmp = 1; 
		}
	}

	if ((cmp == 0) 
	 && (!cce_scalar_isdefined(s1) ^ !cce_scalar_isdefined(s2))) {
		if (!cce_scalar_isdefined(s1)) {
			cmp = -1;
		} else {
			cmp = 1;
		}
	}

	return cmp;
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
