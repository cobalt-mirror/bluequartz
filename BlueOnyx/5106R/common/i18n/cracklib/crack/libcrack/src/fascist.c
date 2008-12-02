/*
 * This program is copyright Alec Muffett 1993. The author disclaims all
 * responsibility or liability with respect to it's usage or its effect
 * upon hardware or computer systems, and maintains copyright as set out
 * in the "LICENCE" document which accompanies distributions of Crack v4.0
 * and upwards.
 */

/*
 * Updated by Sascha Kettler <skettler@php.net> to not leak any
 * unwanted symbols and add extra functionality.
 * 
 * Also beautified code and optimized for the PHP extension.
 */
 
#include "cracklib.h"

#define MINDIFF 5
#define MINLEN 6
#define MAXSTEP 4

static char *cracklib_r_destructors[] = {
	":",                        /* noop - must do this to test raw word. */
	
	"[",                        /* trimming leading/trailing junk */
	"]",
	"[[",
	"]]",
	"[[[",
	"]]]",
	
	"/?p@?p",                   /* purging out punctuation/symbols/junk */
	"/?s@?s",
	"/?X@?X",
	
	/* attempt reverse engineering of password strings */
	
	"/$s$s",
	"/$s$s/0s0o",
	"/$s$s/0s0o/2s2a",
	"/$s$s/0s0o/2s2a/3s3e",
	"/$s$s/0s0o/2s2a/3s3e/5s5s",
	"/$s$s/0s0o/2s2a/3s3e/5s5s/1s1i",
	"/$s$s/0s0o/2s2a/3s3e/5s5s/1s1l",
	"/$s$s/0s0o/2s2a/3s3e/5s5s/1s1i/4s4a",
	"/$s$s/0s0o/2s2a/3s3e/5s5s/1s1i/4s4h",
	"/$s$s/0s0o/2s2a/3s3e/5s5s/1s1l/4s4a",
	"/$s$s/0s0o/2s2a/3s3e/5s5s/1s1l/4s4h",
	"/$s$s/0s0o/2s2a/3s3e/5s5s/4s4a",
	"/$s$s/0s0o/2s2a/3s3e/5s5s/4s4h",
	"/$s$s/0s0o/2s2a/3s3e/5s5s/4s4a",
	"/$s$s/0s0o/2s2a/3s3e/5s5s/4s4h",
	"/$s$s/0s0o/2s2a/3s3e/1s1i",
	"/$s$s/0s0o/2s2a/3s3e/1s1l",
	"/$s$s/0s0o/2s2a/3s3e/1s1i/4s4a",
	"/$s$s/0s0o/2s2a/3s3e/1s1i/4s4h",
	"/$s$s/0s0o/2s2a/3s3e/1s1l/4s4a",
	"/$s$s/0s0o/2s2a/3s3e/1s1l/4s4h",
	"/$s$s/0s0o/2s2a/3s3e/4s4a",
	"/$s$s/0s0o/2s2a/3s3e/4s4h",
	"/$s$s/0s0o/2s2a/3s3e/4s4a",
	"/$s$s/0s0o/2s2a/3s3e/4s4h",
	"/$s$s/0s0o/2s2a/5s5s",
	"/$s$s/0s0o/2s2a/5s5s/1s1i",
	"/$s$s/0s0o/2s2a/5s5s/1s1l",
	"/$s$s/0s0o/2s2a/5s5s/1s1i/4s4a",
	"/$s$s/0s0o/2s2a/5s5s/1s1i/4s4h",
	"/$s$s/0s0o/2s2a/5s5s/1s1l/4s4a",
	"/$s$s/0s0o/2s2a/5s5s/1s1l/4s4h",
	"/$s$s/0s0o/2s2a/5s5s/4s4a",
	"/$s$s/0s0o/2s2a/5s5s/4s4h",
	"/$s$s/0s0o/2s2a/5s5s/4s4a",
	"/$s$s/0s0o/2s2a/5s5s/4s4h",
	"/$s$s/0s0o/2s2a/1s1i",
	"/$s$s/0s0o/2s2a/1s1l",
	"/$s$s/0s0o/2s2a/1s1i/4s4a",
	"/$s$s/0s0o/2s2a/1s1i/4s4h",
	"/$s$s/0s0o/2s2a/1s1l/4s4a",
	"/$s$s/0s0o/2s2a/1s1l/4s4h",
	"/$s$s/0s0o/2s2a/4s4a",
	"/$s$s/0s0o/2s2a/4s4h",
	"/$s$s/0s0o/2s2a/4s4a",
	"/$s$s/0s0o/2s2a/4s4h",
	"/$s$s/0s0o/3s3e",
	"/$s$s/0s0o/3s3e/5s5s",
	"/$s$s/0s0o/3s3e/5s5s/1s1i",
	"/$s$s/0s0o/3s3e/5s5s/1s1l",
	"/$s$s/0s0o/3s3e/5s5s/1s1i/4s4a",
	"/$s$s/0s0o/3s3e/5s5s/1s1i/4s4h",
	"/$s$s/0s0o/3s3e/5s5s/1s1l/4s4a",
	"/$s$s/0s0o/3s3e/5s5s/1s1l/4s4h",
	"/$s$s/0s0o/3s3e/5s5s/4s4a",
	"/$s$s/0s0o/3s3e/5s5s/4s4h",
	"/$s$s/0s0o/3s3e/5s5s/4s4a",
	"/$s$s/0s0o/3s3e/5s5s/4s4h",
	"/$s$s/0s0o/3s3e/1s1i",
	"/$s$s/0s0o/3s3e/1s1l",
	"/$s$s/0s0o/3s3e/1s1i/4s4a",
	"/$s$s/0s0o/3s3e/1s1i/4s4h",
	"/$s$s/0s0o/3s3e/1s1l/4s4a",
	"/$s$s/0s0o/3s3e/1s1l/4s4h",
	"/$s$s/0s0o/3s3e/4s4a",
	"/$s$s/0s0o/3s3e/4s4h",
	"/$s$s/0s0o/3s3e/4s4a",
	"/$s$s/0s0o/3s3e/4s4h",
	"/$s$s/0s0o/5s5s",
	"/$s$s/0s0o/5s5s/1s1i",
	"/$s$s/0s0o/5s5s/1s1l",
	"/$s$s/0s0o/5s5s/1s1i/4s4a",
	"/$s$s/0s0o/5s5s/1s1i/4s4h",
	"/$s$s/0s0o/5s5s/1s1l/4s4a",
	"/$s$s/0s0o/5s5s/1s1l/4s4h",
	"/$s$s/0s0o/5s5s/4s4a",
	"/$s$s/0s0o/5s5s/4s4h",
	"/$s$s/0s0o/5s5s/4s4a",
	"/$s$s/0s0o/5s5s/4s4h",
	"/$s$s/0s0o/1s1i",
	"/$s$s/0s0o/1s1l",
	"/$s$s/0s0o/1s1i/4s4a",
	"/$s$s/0s0o/1s1i/4s4h",
	"/$s$s/0s0o/1s1l/4s4a",
	"/$s$s/0s0o/1s1l/4s4h",
	"/$s$s/0s0o/4s4a",
	"/$s$s/0s0o/4s4h",
	"/$s$s/0s0o/4s4a",
	"/$s$s/0s0o/4s4h",
	"/$s$s/2s2a",
	"/$s$s/2s2a/3s3e",
	"/$s$s/2s2a/3s3e/5s5s",
	"/$s$s/2s2a/3s3e/5s5s/1s1i",
	"/$s$s/2s2a/3s3e/5s5s/1s1l",
	"/$s$s/2s2a/3s3e/5s5s/1s1i/4s4a",
	"/$s$s/2s2a/3s3e/5s5s/1s1i/4s4h",
	"/$s$s/2s2a/3s3e/5s5s/1s1l/4s4a",
	"/$s$s/2s2a/3s3e/5s5s/1s1l/4s4h",
	"/$s$s/2s2a/3s3e/5s5s/4s4a",
	"/$s$s/2s2a/3s3e/5s5s/4s4h",
	"/$s$s/2s2a/3s3e/5s5s/4s4a",
	"/$s$s/2s2a/3s3e/5s5s/4s4h",
	"/$s$s/2s2a/3s3e/1s1i",
	"/$s$s/2s2a/3s3e/1s1l",
	"/$s$s/2s2a/3s3e/1s1i/4s4a",
	"/$s$s/2s2a/3s3e/1s1i/4s4h",
	"/$s$s/2s2a/3s3e/1s1l/4s4a",
	"/$s$s/2s2a/3s3e/1s1l/4s4h",
	"/$s$s/2s2a/3s3e/4s4a",
	"/$s$s/2s2a/3s3e/4s4h",
	"/$s$s/2s2a/3s3e/4s4a",
	"/$s$s/2s2a/3s3e/4s4h",
	"/$s$s/2s2a/5s5s",
	"/$s$s/2s2a/5s5s/1s1i",
	"/$s$s/2s2a/5s5s/1s1l",
	"/$s$s/2s2a/5s5s/1s1i/4s4a",
	"/$s$s/2s2a/5s5s/1s1i/4s4h",
	"/$s$s/2s2a/5s5s/1s1l/4s4a",
	"/$s$s/2s2a/5s5s/1s1l/4s4h",
	"/$s$s/2s2a/5s5s/4s4a",
	"/$s$s/2s2a/5s5s/4s4h",
	"/$s$s/2s2a/5s5s/4s4a",
	"/$s$s/2s2a/5s5s/4s4h",
	"/$s$s/2s2a/1s1i",
	"/$s$s/2s2a/1s1l",
	"/$s$s/2s2a/1s1i/4s4a",
	"/$s$s/2s2a/1s1i/4s4h",
	"/$s$s/2s2a/1s1l/4s4a",
	"/$s$s/2s2a/1s1l/4s4h",
	"/$s$s/2s2a/4s4a",
	"/$s$s/2s2a/4s4h",
	"/$s$s/2s2a/4s4a",
	"/$s$s/2s2a/4s4h",
	"/$s$s/3s3e",
	"/$s$s/3s3e/5s5s",
	"/$s$s/3s3e/5s5s/1s1i",
	"/$s$s/3s3e/5s5s/1s1l",
	"/$s$s/3s3e/5s5s/1s1i/4s4a",
	"/$s$s/3s3e/5s5s/1s1i/4s4h",
	"/$s$s/3s3e/5s5s/1s1l/4s4a",
	"/$s$s/3s3e/5s5s/1s1l/4s4h",
	"/$s$s/3s3e/5s5s/4s4a",
	"/$s$s/3s3e/5s5s/4s4h",
	"/$s$s/3s3e/5s5s/4s4a",
	"/$s$s/3s3e/5s5s/4s4h",
	"/$s$s/3s3e/1s1i",
	"/$s$s/3s3e/1s1l",
	"/$s$s/3s3e/1s1i/4s4a",
	"/$s$s/3s3e/1s1i/4s4h",
	"/$s$s/3s3e/1s1l/4s4a",
	"/$s$s/3s3e/1s1l/4s4h",
	"/$s$s/3s3e/4s4a",
	"/$s$s/3s3e/4s4h",
	"/$s$s/3s3e/4s4a",
	"/$s$s/3s3e/4s4h",
	"/$s$s/5s5s",
	"/$s$s/5s5s/1s1i",
	"/$s$s/5s5s/1s1l",
	"/$s$s/5s5s/1s1i/4s4a",
	"/$s$s/5s5s/1s1i/4s4h",
	"/$s$s/5s5s/1s1l/4s4a",
	"/$s$s/5s5s/1s1l/4s4h",
	"/$s$s/5s5s/4s4a",
	"/$s$s/5s5s/4s4h",
	"/$s$s/5s5s/4s4a",
	"/$s$s/5s5s/4s4h",
	"/$s$s/1s1i",
	"/$s$s/1s1l",
	"/$s$s/1s1i/4s4a",
	"/$s$s/1s1i/4s4h",
	"/$s$s/1s1l/4s4a",
	"/$s$s/1s1l/4s4h",
	"/$s$s/4s4a",
	"/$s$s/4s4h",
	"/$s$s/4s4a",
	"/$s$s/4s4h",
	"/0s0o",
	"/0s0o/2s2a",
	"/0s0o/2s2a/3s3e",
	"/0s0o/2s2a/3s3e/5s5s",
	"/0s0o/2s2a/3s3e/5s5s/1s1i",
	"/0s0o/2s2a/3s3e/5s5s/1s1l",
	"/0s0o/2s2a/3s3e/5s5s/1s1i/4s4a",
	"/0s0o/2s2a/3s3e/5s5s/1s1i/4s4h",
	"/0s0o/2s2a/3s3e/5s5s/1s1l/4s4a",
	"/0s0o/2s2a/3s3e/5s5s/1s1l/4s4h",
	"/0s0o/2s2a/3s3e/5s5s/4s4a",
	"/0s0o/2s2a/3s3e/5s5s/4s4h",
	"/0s0o/2s2a/3s3e/5s5s/4s4a",
	"/0s0o/2s2a/3s3e/5s5s/4s4h",
	"/0s0o/2s2a/3s3e/1s1i",
	"/0s0o/2s2a/3s3e/1s1l",
	"/0s0o/2s2a/3s3e/1s1i/4s4a",
	"/0s0o/2s2a/3s3e/1s1i/4s4h",
	"/0s0o/2s2a/3s3e/1s1l/4s4a",
	"/0s0o/2s2a/3s3e/1s1l/4s4h",
	"/0s0o/2s2a/3s3e/4s4a",
	"/0s0o/2s2a/3s3e/4s4h",
	"/0s0o/2s2a/3s3e/4s4a",
	"/0s0o/2s2a/3s3e/4s4h",
	"/0s0o/2s2a/5s5s",
	"/0s0o/2s2a/5s5s/1s1i",
	"/0s0o/2s2a/5s5s/1s1l",
	"/0s0o/2s2a/5s5s/1s1i/4s4a",
	"/0s0o/2s2a/5s5s/1s1i/4s4h",
	"/0s0o/2s2a/5s5s/1s1l/4s4a",
	"/0s0o/2s2a/5s5s/1s1l/4s4h",
	"/0s0o/2s2a/5s5s/4s4a",
	"/0s0o/2s2a/5s5s/4s4h",
	"/0s0o/2s2a/5s5s/4s4a",
	"/0s0o/2s2a/5s5s/4s4h",
	"/0s0o/2s2a/1s1i",
	"/0s0o/2s2a/1s1l",
	"/0s0o/2s2a/1s1i/4s4a",
	"/0s0o/2s2a/1s1i/4s4h",
	"/0s0o/2s2a/1s1l/4s4a",
	"/0s0o/2s2a/1s1l/4s4h",
	"/0s0o/2s2a/4s4a",
	"/0s0o/2s2a/4s4h",
	"/0s0o/2s2a/4s4a",
	"/0s0o/2s2a/4s4h",
	"/0s0o/3s3e",
	"/0s0o/3s3e/5s5s",
	"/0s0o/3s3e/5s5s/1s1i",
	"/0s0o/3s3e/5s5s/1s1l",
	"/0s0o/3s3e/5s5s/1s1i/4s4a",
	"/0s0o/3s3e/5s5s/1s1i/4s4h",
	"/0s0o/3s3e/5s5s/1s1l/4s4a",
	"/0s0o/3s3e/5s5s/1s1l/4s4h",
	"/0s0o/3s3e/5s5s/4s4a",
	"/0s0o/3s3e/5s5s/4s4h",
	"/0s0o/3s3e/5s5s/4s4a",
	"/0s0o/3s3e/5s5s/4s4h",
	"/0s0o/3s3e/1s1i",
	"/0s0o/3s3e/1s1l",
	"/0s0o/3s3e/1s1i/4s4a",
	"/0s0o/3s3e/1s1i/4s4h",
	"/0s0o/3s3e/1s1l/4s4a",
	"/0s0o/3s3e/1s1l/4s4h",
	"/0s0o/3s3e/4s4a",
	"/0s0o/3s3e/4s4h",
	"/0s0o/3s3e/4s4a",
	"/0s0o/3s3e/4s4h",
	"/0s0o/5s5s",
	"/0s0o/5s5s/1s1i",
	"/0s0o/5s5s/1s1l",
	"/0s0o/5s5s/1s1i/4s4a",
	"/0s0o/5s5s/1s1i/4s4h",
	"/0s0o/5s5s/1s1l/4s4a",
	"/0s0o/5s5s/1s1l/4s4h",
	"/0s0o/5s5s/4s4a",
	"/0s0o/5s5s/4s4h",
	"/0s0o/5s5s/4s4a",
	"/0s0o/5s5s/4s4h",
	"/0s0o/1s1i",
	"/0s0o/1s1l",
	"/0s0o/1s1i/4s4a",
	"/0s0o/1s1i/4s4h",
	"/0s0o/1s1l/4s4a",
	"/0s0o/1s1l/4s4h",
	"/0s0o/4s4a",
	"/0s0o/4s4h",
	"/0s0o/4s4a",
	"/0s0o/4s4h",
	"/2s2a",
	"/2s2a/3s3e",
	"/2s2a/3s3e/5s5s",
	"/2s2a/3s3e/5s5s/1s1i",
	"/2s2a/3s3e/5s5s/1s1l",
	"/2s2a/3s3e/5s5s/1s1i/4s4a",
	"/2s2a/3s3e/5s5s/1s1i/4s4h",
	"/2s2a/3s3e/5s5s/1s1l/4s4a",
	"/2s2a/3s3e/5s5s/1s1l/4s4h",
	"/2s2a/3s3e/5s5s/4s4a",
	"/2s2a/3s3e/5s5s/4s4h",
	"/2s2a/3s3e/5s5s/4s4a",
	"/2s2a/3s3e/5s5s/4s4h",
	"/2s2a/3s3e/1s1i",
	"/2s2a/3s3e/1s1l",
	"/2s2a/3s3e/1s1i/4s4a",
	"/2s2a/3s3e/1s1i/4s4h",
	"/2s2a/3s3e/1s1l/4s4a",
	"/2s2a/3s3e/1s1l/4s4h",
	"/2s2a/3s3e/4s4a",
	"/2s2a/3s3e/4s4h",
	"/2s2a/3s3e/4s4a",
	"/2s2a/3s3e/4s4h",
	"/2s2a/5s5s",
	"/2s2a/5s5s/1s1i",
	"/2s2a/5s5s/1s1l",
	"/2s2a/5s5s/1s1i/4s4a",
	"/2s2a/5s5s/1s1i/4s4h",
	"/2s2a/5s5s/1s1l/4s4a",
	"/2s2a/5s5s/1s1l/4s4h",
	"/2s2a/5s5s/4s4a",
	"/2s2a/5s5s/4s4h",
	"/2s2a/5s5s/4s4a",
	"/2s2a/5s5s/4s4h",
	"/2s2a/1s1i",
	"/2s2a/1s1l",
	"/2s2a/1s1i/4s4a",
	"/2s2a/1s1i/4s4h",
	"/2s2a/1s1l/4s4a",
	"/2s2a/1s1l/4s4h",
	"/2s2a/4s4a",
	"/2s2a/4s4h",
	"/2s2a/4s4a",
	"/2s2a/4s4h",
	"/3s3e",
	"/3s3e/5s5s",
	"/3s3e/5s5s/1s1i",
	"/3s3e/5s5s/1s1l",
	"/3s3e/5s5s/1s1i/4s4a",
	"/3s3e/5s5s/1s1i/4s4h",
	"/3s3e/5s5s/1s1l/4s4a",
	"/3s3e/5s5s/1s1l/4s4h",
	"/3s3e/5s5s/4s4a",
	"/3s3e/5s5s/4s4h",
	"/3s3e/5s5s/4s4a",
	"/3s3e/5s5s/4s4h",
	"/3s3e/1s1i",
	"/3s3e/1s1l",
	"/3s3e/1s1i/4s4a",
	"/3s3e/1s1i/4s4h",
	"/3s3e/1s1l/4s4a",
	"/3s3e/1s1l/4s4h",
	"/3s3e/4s4a",
	"/3s3e/4s4h",
	"/3s3e/4s4a",
	"/3s3e/4s4h",
	"/5s5s",
	"/5s5s/1s1i",
	"/5s5s/1s1l",
	"/5s5s/1s1i/4s4a",
	"/5s5s/1s1i/4s4h",
	"/5s5s/1s1l/4s4a",
	"/5s5s/1s1l/4s4h",
	"/5s5s/4s4a",
	"/5s5s/4s4h",
	"/5s5s/4s4a",
	"/5s5s/4s4h",
	"/1s1i",
	"/1s1l",
	"/1s1i/4s4a",
	"/1s1i/4s4h",
	"/1s1l/4s4a",
	"/1s1l/4s4h",
	"/4s4a",
	"/4s4h",
	"/4s4a",
	"/4s4h",
	
	/* done */
	(char *) 0
};

static char *cracklib_r_constructors[] = {
	":",
	"r",
	"d",
	"f",
	"dr",
	"fr",
	"rf",
	
	/* done */
	(char *) 0
};

static int
cracklib_gtry(char *rawtext, char *password)
{
	int i;
	int len;
	char *mp;
	
	/* use destructors to turn password into rawtext */
	/* note use of cracklib_reverse() to save duplicating all rules */
	
	len = strlen(password);
	
	for (i = 0; cracklib_r_destructors[i]; i++) {
		if (!(mp = cracklib_mangle(password, cracklib_r_destructors[i]))) {
			continue;
		}
		
		if (!strncmp(mp, rawtext, len)) {
			return 1;
		}
		
		if (!strncmp(cracklib_reverse(mp), rawtext, len)) {
			return 1;
		}
	}
	
	for (i=0; cracklib_r_constructors[i]; i++) {
		if (!(mp = cracklib_mangle(rawtext, cracklib_r_constructors[i])))
		{
			continue;
		}
		
		if (!strncmp(mp, password, len)) {
			return 1;
		}
	}
	
	return 0;
}

static char *
cracklib_fascist_gecos(char *password, int uid, char *username, char *gecos)
{
	int i;
	int j;
	int wc;
	char *ptr;
	int gwords;
	char gbuffer[STRINGSIZE];
	char tbuffer[STRINGSIZE];
	char *uwords[STRINGSIZE];
	char longbuffer[STRINGSIZE * 2];
	
#ifndef WIN32
	struct passwd *pwp;
	
	pwp = getpwuid(uid);
	if ((!username || !gecos) && !pwp) {
		return "you are not registered in the password file";
	}
#endif
	
	/* lets get really paranoid and assume a dangerously long gecos entry */
	
	if (username) {
		strncpy(tbuffer, username, STRINGSIZE);
	}
	else {
#ifndef WIN32
		strncpy(tbuffer, pwp->pw_name, STRINGSIZE);
#else
		strncpy(tbuffer, "", STRINGSIZE);
#endif
	}
	tbuffer[STRINGSIZE-1] = '\0';
	
	if (cracklib_gtry(tbuffer, password)) {
		return "it is based on your username";
	}
	
	/* it never used to be that you got passwd strings > 1024 chars, but now... */
	
	if (gecos) {
		strncpy(tbuffer, gecos, STRINGSIZE);
	}
	else {
#ifndef WIN32
		strncpy(tbuffer, pwp->pw_gecos, STRINGSIZE);
#else
		strncpy(tbuffer, "", STRINGSIZE);
#endif
	}
	tbuffer[STRINGSIZE-1] = '\0';
	strcpy(gbuffer, cracklib_lowercase(tbuffer));
	
	wc = 0;
	ptr = gbuffer;
	gwords = 0;
	uwords[0] = (char *) 0;
	
	while (*ptr) {
		while (*ptr && ISSKIP(*ptr)) {
			ptr++;
		}
		
		if (ptr != gbuffer) {
			ptr[-1] = '\0';
		}
		
		gwords++;
		uwords[wc++] = ptr;
	
		if (wc == STRINGSIZE) {
			uwords[--wc] = (char *) 0;
			break;
		} 
		else {
			uwords[wc] = (char *) 0;
		}
		
		while (*ptr && !ISSKIP(*ptr)) {
			ptr++;
		}
		
		if (*ptr) {
			*(ptr++) = '\0';
		}
	}
	
	for (i=0; uwords[i]; i++) {
		if (cracklib_gtry(uwords[i], password)) {
			return "it is based upon your password entry";
		}
	}
	
	/* since uwords are taken from gbuffer, no uword can be longer than gbuffer */
	
	for (j = 1; (j < gwords) && uwords[j]; j++) {
		for (i=0; i < j; i++) {
			strcpy(longbuffer, uwords[i]);
			strcat(longbuffer, uwords[j]);
			
			if (cracklib_gtry(longbuffer, password)) {
				return "it is derived from your password entry";
			}
			
			strcpy(longbuffer, uwords[j]);
			strcat(longbuffer, uwords[i]);
			
			if (cracklib_gtry(longbuffer, password)) {
				return "it's derived from your password entry";
			}
			
			longbuffer[0] = uwords[i][0];
			longbuffer[1] = '\0';
			strcat(longbuffer, uwords[j]);
			
			if (cracklib_gtry(longbuffer, password)) {
				return "it is derivable from your password entry";
			}
			
			longbuffer[0] = uwords[j][0];
			longbuffer[1] = '\0';
			strcat(longbuffer, uwords[i]);
			
			if (cracklib_gtry(longbuffer, password)) {
				return ("it's derivable from your password entry");
			}
		}
	}

	return (char *) 0;
}

char *
cracklib_fascist_look(CRACKLIB_PWDICT *pwp, char *instring)
{
	return cracklib_fascist_look_ex(pwp, instring, (char *) 0, (char *) 0);
}

char *
cracklib_fascist_look_ex(CRACKLIB_PWDICT *pwp, char *instring, char *username, char *gecos)
{
	int i;
	char *ptr;
	char *jptr;
	char junk[STRINGSIZE];
	char *password;
	char rpassword[STRINGSIZE];
	int32 notfound;
	
	notfound = PW_WORDS(pwp);
	/* already truncated if from FascistCheck() */
	/* but pretend it wasn't ... */
	strncpy(rpassword, instring, TRUNCSTRINGSIZE);
	rpassword[TRUNCSTRINGSIZE - 1] = '\0';
	password = rpassword;
	
	if (strlen(password) < 4) {
		return "it's WAY too short";
	}
	
	if (strlen(password) < MINLEN) {
		return "it is too short";
	}
	
	jptr = junk;
	*jptr = '\0';
	
	for (i=0; i < STRINGSIZE && password[i]; i++) {
		if (!strchr(junk, password[i])) {
			*(jptr++) = password[i];
			*jptr = '\0';
		}
	}
	
	if (strlen(junk) < MINDIFF) {
		return "it does not contain enough DIFFERENT characters";
	}
	
	strcpy(password, cracklib_lowercase(password));
	
	cracklib_trim(password);
	
	while (*password && isspace(*password)) {
		password++;
	}
	
	if (!*password) {
		return "it is all whitespace";
	}
	
	i = 0;
	ptr = password;
	while (ptr[0] && ptr[1]) {
		if ((ptr[1] == (ptr[0] + 1)) || (ptr[1] == (ptr[0] - 1))) {
			i++;
		}
		ptr++;
	}
	
	if (i > MAXSTEP) {
		return "it is too simplistic/systematic";
	}
	
	if (cracklib_pmatch("aadddddda", password)) {
		return "it looks like a National Insurance number.";
	}

#ifndef WIN32
	ptr = cracklib_fascist_gecos(password, getuid(), username, gecos);
	if (ptr) {
		return ptr;
	}
#endif
	
	/* it should be safe to use cracklib_mangle with its reliance on STRINGSIZE
	   since password cannot be longer than TRUNCSTRINGSIZE;
	   nonetheless this is not an elegant solution */
	
	for (i = 0; cracklib_r_destructors[i]; i++) {
		char *a;
		
		if (!(a = cracklib_mangle(password, cracklib_r_destructors[i]))) {
			continue;
		}
		
		if (cracklib_find_pw(pwp, a) != notfound) {
			return "it is based on a dictionary word";
		}
	}
	
	strcpy(password, cracklib_reverse(password));
	
	for (i = 0; cracklib_r_destructors[i]; i++) {
		char *a;
		
		if (!(a = cracklib_mangle(password, cracklib_r_destructors[i]))) {
			continue;
		}
		
		if (cracklib_find_pw(pwp, a) != notfound) {
			return "it is based on a (reversed) dictionary word";
		}
	}
	
	return (char *) 0;
}

char *
cracklib_fascist_check(char *password, char *path)
{
	return cracklib_fascist_check_ex(password, path, NULL, NULL);
}

char *
cracklib_fascist_check_ex(char *password, char *path, char *username, char *gecos)
{
	CRACKLIB_PWDICT *pwp;
	char *result;
	char pwtrunced[STRINGSIZE];
	
	/* security problem: assume we may have been given a really long
	   password (buffer attack) and so truncate it to a workable size;
	   try to define workable size as something from which we cannot
	   extend a buffer beyond its limits in the rest of the code */
	
	strncpy(pwtrunced, password, TRUNCSTRINGSIZE);
	pwtrunced[TRUNCSTRINGSIZE - 1] = '\0'; /* enforce */
	
	/* perhaps someone should put something here to check if password
	   is really long and syslog() a message denoting buffer attacks?  */
	
	if (!(pwp = cracklib_pw_open(path, "r"))) {
		perror("PWOpen");
		exit(-1);
	}
	
	result = cracklib_fascist_look_ex(pwp, pwtrunced, username, gecos);
	
	cracklib_pw_close(pwp);
	
	return result;
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 * vim600: sw=4 ts=4 fdm=marker
 * vim<600: sw=4 ts=4
 */
