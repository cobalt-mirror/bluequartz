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

#include <stdio.h>
#include <stdlib.h>
#include <ctype.h>
#include <sys/types.h>
#include <string.h>
#ifndef WIN32
# include <unistd.h>
# include <pwd.h>
#endif

#ifdef HAVE_CONFIG_H
# include "config.h"
#endif

#if HAVE_CRACK_BUNDLED || defined(WIN32)
# include "php.h"
# include "php_ini.h"
# include "ext/standard/info.h"
# include "../../php_crack.h"
# define MALLOC(x)                          (emalloc(x))
# define FREE(x)                            (efree(x))
#elif defined(IN_CRACKLIB)
# define MALLOC(x)                          (malloc(x))
# define FREE(x)                            (free(x))
#else
# error not available yet
#endif

#define STRINGSIZE                          1024
#define TRUNCSTRINGSIZE                     (STRINGSIZE/4)

typedef unsigned char int8;
typedef unsigned short int int16;
typedef unsigned int int32;

#ifndef NUMWORDS
# define NUMWORDS                           16
#endif

#define MAXWORDLEN                          32
#define MAXBLOCKLEN                         (MAXWORDLEN * NUMWORDS)

struct cracklib_pi_header
{
	int32 pih_magic;
	int32 pih_numwords;
	int16 pih_blocklen;
	int16 pih_pad;
};

#define PFOR_WRITE                          0x0001
#define PFOR_FLUSH                          0x0002
#define PFOR_USEHWMS                        0x0004

typedef struct
{
	FILE *ifp;
	FILE *dfp;
	FILE *wfp;
	
	int32 flags;
	
	int32 hwms[256];
	
	struct cracklib_pi_header header;
	
	int count;
	char data[NUMWORDS][MAXWORDLEN];
	
	char prevdata[NUMWORDS][MAXWORDLEN];
	int32 prevblock;
	
	int needs_swap;
} CRACKLIB_PWDICT;

#define PW_WORDS(x)                         ((x)->header.pih_numwords)
#define PIH_MAGIC                           0x70775631

#define CRACK_TOLOWER(a)                    (isupper(a)?tolower(a):(a)) 
#define CRACK_TOUPPER(a)                    (islower(a)?toupper(a):(a)) 
#define ISSKIP(x)                           (isspace(x) || ispunct(x))

/* from rules.c */
char *cracklib_reverse(char *str);
char *cracklib_lowercase(char *str);
char *cracklib_mangle(char *input, char *control);
int cracklib_pmatch(char *control, char *string);
char *cracklib_trim(char *string);
char cracklib_chop(char *string);

/* from fascist.c */
char *cracklib_fascist_look(CRACKLIB_PWDICT *pwp, char *instring);
char *cracklib_fascist_look_ex(CRACKLIB_PWDICT *pwp, char *instring, char *username, char *gecos);
char *cracklib_fascist_check(char *password, char *path);
char *cracklib_fascist_check_ex(char *password, char *path, char *username, char *gecos);

/* from packlib.c */
CRACKLIB_PWDICT *cracklib_pw_open(char *prefix, char *mode);
int cracklib_pw_close(CRACKLIB_PWDICT *pwp);
int cracklib_put_pw(CRACKLIB_PWDICT *pwp, char *string);
char *cracklib_get_pw(CRACKLIB_PWDICT *pwp, int32 number);
int32 cracklib_find_pw(CRACKLIB_PWDICT *pwp, char *string);

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 * vim600: sw=4 ts=4 fdm=marker
 * vim<600: sw=4 ts=4
 */
