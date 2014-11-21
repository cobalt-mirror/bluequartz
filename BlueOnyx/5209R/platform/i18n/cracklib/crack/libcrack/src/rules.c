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

#define RULE_NOOP       ':'
#define RULE_PREPEND    '^'
#define RULE_APPEND     '$'
#define RULE_REVERSE    'r'
#define RULE_UPPERCASE  'u'
#define RULE_LOWERCASE  'l'
#define RULE_PLURALISE  'p'
#define RULE_CAPITALISE 'c'
#define RULE_DUPLICATE  'd'
#define RULE_REFLECT    'f'
#define RULE_SUBSTITUTE 's'
#define RULE_MATCH      '/'
#define RULE_NOT        '!'
#define RULE_LT         '<'
#define RULE_GT         '>'
#define RULE_EXTRACT    'x'
#define RULE_OVERSTRIKE 'o'
#define RULE_INSERT     'i'
#define RULE_EQUALS     '='
#define RULE_PURGE      '@'
#define RULE_CLASS      '?'

#define RULE_DFIRST     '['
#define RULE_DLAST      ']'
#define RULE_MFIRST     '('
#define RULE_MLAST      ')'

static int
cracklib_suffix(char *myword, char *suffix)
{
	int i;
	int j;
	
	i = strlen(myword);
	j = strlen(suffix);
	
	if (i > j) {
		return strcmp((myword + i - j), suffix);
	}
	else {
		return -1;
	}
}

char *
cracklib_reverse(char *str)
{
	int i;
	int j;
	static char area[STRINGSIZE];
	
	j = i = strlen(str);
	while (*str) {
		area[--i] = *str++;
	}
	area[j] = '\0';
	
	return area;
}

static char *
cracklib_uppercase(char *str)
{
	char *ptr;
	static char area[STRINGSIZE];
	
	ptr = area;
	while (*str) {
		*(ptr++) = CRACK_TOUPPER(*str);
		str++;
	}
	*ptr = '\0';
	
	return area;
}

char *
cracklib_lowercase(char *str)
{
	char *ptr;
	static char area[STRINGSIZE];
	
	ptr = area;
	while (*str) {
		*(ptr++) = CRACK_TOLOWER(*str);
		str++;
	}
	*ptr = '\0';
	
	return area;
}

static char *
cracklib_capitalise(char *str)
{
	char *ptr;
	static char area[STRINGSIZE];
	
	ptr = area;
	
	while (*str) {
		*(ptr++) = CRACK_TOLOWER(*str);
		str++;
	}
	
	*ptr = '\0';
	area[0] = CRACK_TOUPPER(area[0]);
	
	return area;
}

static char *
cracklib_pluralise(char *string)
{
	int length;
	static char area[STRINGSIZE];
	
	length = strlen(string);
	strcpy(area, string);
	
	if (!cracklib_suffix(string, "ch")
		|| !cracklib_suffix(string, "ex")
		|| !cracklib_suffix(string, "ix")
		|| !cracklib_suffix(string, "sh")
		|| !cracklib_suffix(string, "ss")) {
		/* bench -> benches */
		strcat(area, "es");
	}
	else if ((length > 2) && (string[length - 1] == 'y')) {
		if (strchr("aeiou", string[length - 2])) {
			/* alloy -> alloys */
			strcat(area, "s");
		}
		else {
			/* gully -> gullies */
			strcpy(area + length - 1, "ies");
		}
	}
	else if (string[length - 1] == 's') {
		/* bias -> biases */
		strcat(area, "es");
	}
	else {
		/* catchall */
		strcat(area, "s");
	}
	
	return area;
}

static char *
cracklib_substitute(char *string, char oldChar, char newChar)
{
	char *ptr;
	static char area[STRINGSIZE];
	
	ptr = area;
	while (*string) {
		*(ptr++) = (*string == oldChar) ? (newChar) : (*string);
		string++;
	}
	*ptr = '\0';
	
	return area;
}

static char *
cracklib_purge(char *string, char target)
{
	char *ptr;
	static char area[STRINGSIZE];
	
	ptr = area;
	while (*string) {
		if (*string != target) {
			*(ptr++) = *string;
		}
		string++;
	}
	*ptr = '\0';
	
	return area;
}

/*
 * this function takes two inputs, a class identifier and a character, and
 * returns non-null if the given character is a member of the class, based
 * upon restrictions set out below
 */

static int
cracklib_match_class(char charClass, char input)
{
	char c;
	int retval;
	
	retval = 0;
	switch (charClass) {
		/* ESCAPE */
		case '?':                       /* ?? -> ? */
			if (input == '?') {
				retval = 1;
			}
			break;
			
		/* ILLOGICAL GROUPINGS (ie: not in ctype.h) */
		case 'V':
		case 'v':                       /* vowels */
			c = CRACK_TOLOWER(input);
			if (strchr("aeiou", c)) {
				retval = 1;
			}
			break;
			
		case 'C':
		case 'c':                       /* consonants */
			c = CRACK_TOLOWER(input);
			if (strchr("bcdfghjklmnpqrstvwxyz", c)) {
				retval = 1;
			}
			break;
			
		case 'W':
		case 'w':                       /* whitespace */
			if (strchr("\t ", input)) {
				retval = 1;
			}
			break;
			
		case 'P':
		case 'p':                       /* punctuation */
			if (strchr(".`,:;'!?\"", input)) {
				retval = 1;
			}
			break;
			
		case 'S':
		case 's':                       /* symbols */
			if (strchr("$%%^&*()-_+=|\\[]{}#@/~", input)) {
				retval = 1;
			}
			break;
			
		/* LOGICAL GROUPINGS */
		case 'L':
		case 'l':                       /* lowercase */
			if (islower(input)) {
				retval = 1;
			}
			break;
			
		case 'U':
		case 'u':                       /* uppercase */
			if (isupper(input)) {
				retval = 1;
			}
			break;
			
		case 'A':
		case 'a':                       /* alphabetic */
			if (isalpha(input)) {
				retval = 1;
			}
			break;
			
		case 'X':
		case 'x':                       /* alphanumeric */
			if (isalnum(input)) {
				retval = 1;
			}
			break;
			
		case 'D':
		case 'd':                       /* digits */
			if (isdigit(input)) {
				retval = 1;
			}
			break;
			
		default:
			/* unknown class */
			return 0;
			break;
		}
	
	if (isupper(charClass)) {
		return !retval;
	}
	
	return retval;
}

static char *
cracklib_poly_strchr(char *string, char charClass)
{
	while (*string) {
		if (cracklib_match_class(charClass, *string)){
			return (string);
		}
		string++;
	}
	
	return (char *) 0;
}

static char *
cracklib_poly_subst(char *string, char charClass, char newChar)
{
	char *ptr;
	static char area[STRINGSIZE];
	
	ptr = area;
	while (*string) {
		*(ptr++) = (cracklib_match_class(charClass, *string)) ? (newChar) : (*string);
		string++;
	}
	*ptr = '\0';
	
	return area;
}

static char *
cracklib_poly_purge(char *string, char charClass)
{
	char *ptr;
	static char area[STRINGSIZE];
	
	ptr = area;
	while (*string) {
		if (!cracklib_match_class(charClass, *string)) {
			*(ptr++) = *string;
		}
		string++;
	}
	*ptr = '\0';
	
	return area;
}

static int
cracklib_char2int(char character)
{
	if (isdigit(character)) {
		return (character - '0');
	}
	else if (islower(character)) {
		return (character - 'a' + 10);
	}
	else if (isupper(character)) {
		return (character - 'A' + 10);
	}
	
	return -1;
}

char *
cracklib_mangle(char *input, char *control)
{
	int limit;
	char *ptr;
	static char area[STRINGSIZE];
	char area2[STRINGSIZE];
	char *string;
	int i;
	int start;
	int length;
	char *p1;
	char *p2;
	
	area[0] = '\0';
	strcpy(area, input);
	
	for (ptr = control; *ptr; ptr++) {
		switch (*ptr) {
			case RULE_NOOP:
				break;
				
			case RULE_REVERSE:
				strcpy(area, cracklib_reverse(area));
				break;
				
			case RULE_UPPERCASE:
				strcpy(area, cracklib_uppercase(area));
				break;
				
			case RULE_LOWERCASE:
				strcpy(area, cracklib_lowercase(area));
				break;
				
			case RULE_CAPITALISE:
				strcpy(area, cracklib_capitalise(area));
				break;
				
			case RULE_PLURALISE:
				strcpy(area, cracklib_pluralise(area));
				break;
				
			case RULE_REFLECT:
				strcat(area, cracklib_reverse(area));
				break;
				
			case RULE_DUPLICATE:
				strcpy(area2, area);
				strcat(area, area2);
				break;
				
			case RULE_GT:
				if (!ptr[1]) {
					/* '>' missing argument */ 
					return (char *) 0;
				} 
				
				limit = cracklib_char2int(*(++ptr));
				if (limit < 0) {
					/* '>' weird argument */ 
					return (char *) 0;
				}
				
				if ((int) strlen(area) <= limit) {
					return (char *) 0;
				}
				break;
				
			case RULE_LT:
				if (!ptr[1]) {
					/* '<' missing argument */
					return (char *) 0;
				}
				
				limit = cracklib_char2int(*(++ptr));
				if (limit < 0) {
					/* '<' weird argument */
					return (char *) 0;
				}
				
				if ((int) strlen(area) >= limit) {
					return (char *) 0;
				}
				break;
				
			case RULE_PREPEND:
				if (!ptr[1]) {
					/* prepend missing argument */
					return (char *) 0;
				}
				
				area2[0] = *(++ptr);
				strcpy(area2 + 1, area);
				strcpy(area, area2);
				break;
				
			case RULE_APPEND:
				if (!ptr[1]) {
					/* append missing argument */
					return (char *) 0;
				}
				
				string = area;
				while (*(string++)) {
				}
				
				string[-1] = *(++ptr);
				*string = '\0';
				break;
				
			case RULE_EXTRACT:
				if (!ptr[1] || !ptr[2]) {
					/* extract missing argument */
					return (char *) 0;
				}
				
				start = cracklib_char2int(*(++ptr));
				length = cracklib_char2int(*(++ptr));
				if ((start < 0) || (length < 0)) {
					/* extract: weird argument */
					return (char *) 0;
				}
				
				strcpy(area2, area);
				for (i=0; length-- && area2[start + i]; i++) {
					area[i] = area2[start + i];
				}
				
				/* cant use strncpy() - no trailing NUL */
				area[i] = '\0';
				break;
				
			case RULE_OVERSTRIKE:
				if (!ptr[1] || !ptr[2]) {
					/* overstrike missing argument */
					return (char *) 0;
				}
				
				i = cracklib_char2int(*(++ptr));
				if (i < 0) {
					/* overstrike weird argument */
					return (char *) 0;
				}
				
				++ptr;
				if (area[i]) {
					area[i] = *ptr;
				}
				break;
				
			case RULE_INSERT:
				if (!ptr[1] || !ptr[2]) {
					/* insert missing argument */
					return (char *) 0;
				}
				
				i = cracklib_char2int(*(++ptr));
				if (i < 0) {
					/* insert weird argument */
					return (char *) 0;
				}
				
				p1 = area;
				p2 = area2;
				while (i && *p1) {
					i--;
					*(p2++) = *(p1++);
				}
				*(p2++) = *(++ptr);
				strcpy(p2, p1);
				strcpy(area, area2);
				break;
		
			case RULE_PURGE:
				if (!ptr[1] || ((ptr[1] == RULE_CLASS) && !ptr[2])) {
					/* delete missing arguments */
					return (char *) 0;
				}
	
				if (ptr[1] != RULE_CLASS) {
					strcpy(area, cracklib_purge(area, *(++ptr)));
				}
				else {
					strcpy(area, cracklib_poly_purge(area, ptr[2]));
					ptr += 2;
				}
				break;
				
			case RULE_SUBSTITUTE:
				if (!ptr[1] || !ptr[2] || ((ptr[1] == RULE_CLASS) && !ptr[3])) {
					/* subst missing argument */
					return (char *) 0;
				}
				
				if (ptr[1] != RULE_CLASS) {
					strcpy(area, cracklib_substitute(area, ptr[1], ptr[2]));
					ptr += 2;
				}
				else {
					strcpy(area, cracklib_poly_subst(area, ptr[2], ptr[3]));
					ptr += 3;
				}
				break;
				
			case RULE_MATCH:
				if (!ptr[1] || ((ptr[1] == RULE_CLASS) && !ptr[2])) {
					/* '/' missing argument */
					return (char *) 0;
				}
				
				if (ptr[1] != RULE_CLASS) {
					if (!strchr(area, *(++ptr))) {
						return (char *) 0;
					}
				} 
				else {
					if (!cracklib_poly_strchr(area, ptr[2])) {
						return (char *) 0;
					}
					ptr += 2;
				}
				break;
				
			case RULE_NOT:
				if (!ptr[1] || ((ptr[1] == RULE_CLASS) && !ptr[2])) {
					/* '!' missing argument */
					return (char *) 0;
				}
				
				if (ptr[1] != RULE_CLASS) {
					if (strchr(area, *(++ptr))) {
						return (char *) 0;
					}
				}
				else {
					if (cracklib_poly_strchr(area, ptr[2])) {
						return (char *) 0;
					}
					ptr += 2;
				}
				break;
				
			case RULE_EQUALS:
				if (!ptr[1] || !ptr[2] || ((ptr[2] == RULE_CLASS) && !ptr[3])) {
					/* '=' missing argument */
					return (char *) 0;
				}
				
				if ((i = cracklib_char2int(ptr[1])) < 0) {
					/* '=' weird argument */
					return (char *) 0;
				}
				
				if (ptr[2] != RULE_CLASS) {
					ptr += 2;
					if (area[i] != *ptr) {
						return (char *) 0;
					}
				}
				else {
					ptr += 3;
					if (!cracklib_match_class(*ptr, area[i])) {
						return (char *) 0;
					}
				}
				break;
				
			case RULE_DFIRST:
				if (area[0]) {
					for (i = 1; area[i]; i++) {
						area[i - 1] = area[i];
					}
					area[i - 1] = '\0';
				}
				break;
			
			case RULE_DLAST:
				if (area[0]) {
					for (i = 1; area[i]; i++) {
					}
					area[i - 1] = '\0';
				}
				break;
				
			case RULE_MFIRST:
				if (!ptr[1] || ((ptr[1] == RULE_CLASS) && !ptr[2])) {
					/* '(' missing argument */
					return (char *) 0;
				}
				
				if (ptr[1] != RULE_CLASS) {
					ptr++;
					if (area[0] != *ptr) {
						return (char *) 0;
					}
				}
				else {
					ptr += 2;
					if (!cracklib_match_class(*ptr, area[0])) {
						return (char *) 0;
					}
				}
				break;
				
			case RULE_MLAST:
				if (!ptr[1] || ((ptr[1] == RULE_CLASS) && !ptr[2])) {
					/* ')' missing argument */
					return (char *) 0;
				}
				
				for (i=0; area[i]; i++) {
				}
				
				if (i > 0) {
					i--;
				} 
				else {
					return (char *) 0;
				}
				
				if (ptr[1] != RULE_CLASS) {
					ptr++;
					if (area[i] != *ptr) {
						return (char *) 0;
					}
				}
				else {
					ptr += 2;
					if (!cracklib_match_class(*ptr, area[i])) {
						return (char *) 0;
					}
				}
				break;
				
			default:
				/* unknown command */
				return (char *) 0;
			}
	}
	
	if (!area[0]) {
		/* have we deweted de poor widdle fing away? */
		return (char *) 0;
	}
	
	return area;
}

int
cracklib_pmatch(char *control, char *string)
{
	while (*string && *control) {
		if (!cracklib_match_class(*control, *string)) {
			return 0;
		}
		
		string++;
		control++;
	}
	
	if (*string || *control) {
		return 0;
	}
	
	return 1;
}

char *
cracklib_trim(char *string)
{
	char *ptr;
	
	for (ptr = string; *ptr; ptr++) {
	}
	
	while ((--ptr >= string) && isspace(*ptr)) {
	}
	
	*(++ptr) = '\0';
	
	return ptr;
}

char
cracklib_chop(char *string)
{
	char c;
	char *ptr;
	
	c = '\0';
	
	for (ptr = string; *ptr; ptr++) {
	}
	
	if (ptr != string) {
		c = *(--ptr);
		*ptr = '\0';
	}
	
	return c;
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 * vim600: sw=4 ts=4 fdm=marker
 * vim<600: sw=4 ts=4
 */
