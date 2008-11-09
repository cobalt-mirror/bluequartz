#include <stdio.h>
#include <string.h>
#include <unistd.h>
#include <ctype.h>
#include <fcntl.h>

#include "Profile.h"

#define ERROR_DUPLICATE_NAME  (-1)
#define ERROR_INVALID_NAME    (-2)
#define ERROR_MISSING_EQUAL   (-3)
#define ERROR_MISSING_VALUE   (-4)
#define ERROR_INVALID_ARGS    (-5)
#define ERROR_OPENING_INFILE  (-6)
#define ERROR_OPENING_OUTFILE (-7)
#define ERROR_MISSING_ENTRIES (-8)

#define MAX_LCD_LENGTH       16
#define MAX_STRING           255
#define MAX_VALUE            25

static int  assignValue   (Profile_ptr profile, const char * name, 
			  const char * value);
static int  createProfile (Profile_ptr profile, FILE * in);
static int  commentLine   (const char * buffer);
static int  parseLine     (Profile_ptr profile, char * buffer);
static void usage         ();
static int  validateLcd   (const char * value);
static int  validateWeb   (const char * value);

static FILE * errFile;
static FILE * inFile;

static struct Table
{
    char * name;
    void (*set)(Profile_ptr, const char *);
    int parsed;
    int (*validate)(const char *);
} table[] = { 
    "CompanyDomainName",  profileSetCompanyDomainName,  0, 0,
    "CompanyName",        profileSetCompanyName,        0, 0,
    "CompanyNameLcd1",    profileSetCompanyNameLcd1,    0, validateLcd,
    "CompanyNameLcd2",    profileSetCompanyNameLcd2,    0, validateLcd,
    "HostDefault",        profileSetHostDefault,        0, 0,
    "ProductName",        profileSetProductName,        0, 0,
    "ProductNameLcd1",    profileSetProductNameLcd1,    0, validateLcd,
    "ProductNameLcd2",    profileSetProductNameLcd2,    0, validateLcd,
    "ProductNameShort",   profileSetProductNameShort,   0, 0,
    "ProductLicenseName", profileSetProductLicenseName, 0, 0,
/*
 *  ProductVersion is set in Profile.c at compile time using
 *  an environment variable passed to it via make
 *    "ProductVersion",     profileSetProductVersion,     0, 0,
 */
    "SupportEmail",       profileSetSupportEmail,       0, 0,
    "SupportPhone",       profileSetSupportPhone,       0, 0,
    "Website",            profileSetWebsite,            0, validateWeb,
};

#define NUM_ENTRIES (sizeof(table) / sizeof(struct Table))

    
int main (int argc, char * argv[])
{
    int             fd;
    Profile_ptr     profile = getProfile ();
    int             rc;

    errFile = stderr;

    if (argc != 3)
    {
	usage ();
	return ERROR_INVALID_ARGS;
    }

    inFile = fopen (argv[1], "r");

    if (inFile == NULL)
    {
	fprintf (errFile, "error opening input file\n");
	return ERROR_OPENING_INFILE;
    }

    fd = open (argv[2], O_CREAT | O_WRONLY, 0444);

    if (fd == -1)
    {
	fprintf (errFile, "error opening output file\n");
	return ERROR_OPENING_OUTFILE;
    }
    
    rc = createProfile (profile, inFile);
    fclose (inFile);

    if (rc != 0)
    {
	fprintf (errFile, "Failed to create profile.\n");
	return rc;
    }

    profileStoreObject (profile, fd);
    close (fd);

    fprintf (errFile, "Profile created.\n");

    return 0;
}

static int lineNumber = 0;

static int createProfile (Profile_ptr profile, FILE * inFile)
{
    // Enough space for 2 strings and an equal sign
#define BUF_SIZE MAX_STRING * 2 + 1
    char buffer[BUF_SIZE + 1]; // + 1 for '\0'
    int haveErrors = 0;
    int i;
    int len;
    int rc;

    while (!feof(inFile))
    {
	buffer[len=0] = '\0';

	// Note: fgets reads at most one less than the 2nd parameter
	if (fgets (buffer, BUF_SIZE + 1, inFile) == NULL)
	{
	    break;
	}

	lineNumber++;

	if (commentLine(buffer))
	{
	    continue;
	}
	
	// Remove newline if it exists
	len = strlen (buffer);

	if (buffer[len-1] == '\n')
	{
	    buffer[--len] = '\0';
	}

	if (rc = parseLine (profile, buffer))
	{
	    fprintf (errFile, "Syntax error (%d) line # %d\n", rc, lineNumber);
	    haveErrors = 1;
	}
    }

    // Make sure we've read a value for each string

    rc = 0;

    for (i = 0; i < NUM_ENTRIES; i++)
    {
	if (table[i].parsed == 0)
	{
	    fprintf (errFile, "Missing value for %s\n", table[i].name);
	    haveErrors = 1;
	}
    }

    if (haveErrors)
	return -1;
    else
	return 0;
}

static int parseLine (Profile_ptr profile, char * buffer)
{
    char      c;
    char *    ptr     = buffer;
    char *    value   = NULL;
    
    while ((c = *ptr) != '\0')
    {
	if (c == '=')
	{
	    *ptr = '\0';
	    value = ++ptr;
	}
	else
	{
	    ++ptr;
	}
    }

    if (value == NULL)
    {
	return ERROR_MISSING_EQUAL;
    }

    return assignValue (profile, buffer, value);
}

static int assignValue (Profile_ptr profile, const char * name, 
			const char * value)
{
    int i;

    if (value != NULL && strlen(value) > MAX_VALUE)
    {
	fprintf (errFile, "Warning: line(%d): value exceeds %d characters.\n",
		 lineNumber, MAX_VALUE);
    }

    for (i = 0; i < NUM_ENTRIES; i++)
    {
	if (strcmp(table[i].name, name) == 0)
	{
	    if (table[i].parsed == 0)
	    {
		if (table[i].validate != NULL)
		{
		    table[i].validate (value);
		}

		table[i].set (profile, value);
		table[i].parsed = 1;
		return 0;
	    }
	    else
	    {
		return ERROR_DUPLICATE_NAME;
	    }
	}
    }

    return ERROR_INVALID_NAME;
}

static void usage ()
{
    fprintf (errFile, "usage: create_profile inFilename outFilename\n");
}

static int commentLine (const char * buffer)
{
    // Comment line?
    if (buffer[0] == '#')
	return 1;

    // Line containing non whitespace?
    while (*buffer)
    {
	if (!isspace(*buffer))
	{
	    return 0;
	}

	buffer++;
    }
    
    // The line contains only whitespace - treat it as a comment
    return 1;
}

static int validateWeb (const char * value)
{
    if (value == NULL || *value == '\0')
    {
	return 1;
    }

    if (strncasecmp("http", value, 4) == 0)
    {
	fprintf (errFile, "Line #%d, web site should not include http://\n",
		 lineNumber);
	return 0;
    }

    return 1;
}

static int validateLcd (const char * value)
{
    if (value == NULL || *value == '\0')
    {
	return 1;
    }

    if (strlen(value) > MAX_LCD_LENGTH)
    {
	fprintf (errFile, "Line #%d, length exceeds max for LCD\n", lineNumber);
	return 0;
    }

    return 1;
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
