#include "Profile_impl.h"

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <dlfcn.h>
#include <sys/stat.h>
#include <unistd.h>
#include <fcntl.h>
#include <time.h>

/*
 *  Note: The table member of create_profile.c has a dependency on the 
 *        interface of Profile.
 */

/*
 *  Test Driver - this should be defined first so as to not gain the benefit
 *                of static declarations.
 */

#ifdef PROFILE_TEST_MAIN

#ifdef WIN32
#define snprintf _snprintf
#endif

int main ()
{
    int fd;
    Profile * profile = getProfile ();
    printf ("Company name = %s\n", profileGetCompanyName (profile));
    profileSetCompanyName (profile, "Goofy.com");
    fd = open ("/home/brian/junk.dat", O_WRONLY | O_CREAT);
    
    if (fd == -1)
    {
	printf ("error opening file for writing\n");
	exit (-1);
    }
    
    profileStoreObject (profile, fd);
    close (fd);
    fd = open ("/home/brian/junk.dat", O_RDONLY);
    
    if (fd == -1)
    {
	printf ("error opening file for reading\n");
	exit (-1);
    }
    
    profileRestoreObject (profile, fd);
    close (fd);
    printf ("Company name = %s\n", profileGetCompanyName (profile));
    
#if 0
#define BUF_SIZE 256
    char buffer[BUF_SIZE];
    int count;
    Profile * profile = getProfile ();

    count = snprintf (buffer, sizeof(buffer),
        "<A HREF=\"mailto:%s\">%s</A>",
        profileGetSupportEmail (profile),
        profileGetCompanyName (profile));

    if (count < sizeof(buffer))
    {
        printf ("Result==> %s\n", buffer);
    }
    else
    {
        // SOL :(
    }
#endif
    return 0;
}

#endif

/*
 *  End of Test Driver
 */

#ifndef RELVERSION
#define RELVERSION "1.0.1"
#endif

#define PSI_APPLIANCE_FLAG_FILE "/etc/applflag"

static Profile * _profile = NULL;   // the Singleton

static void         assignString         (const char * str, char ** field);
static void         assignStringNoMalloc (const char * str, char ** field);
static Profile *    profileConstructor   ();
static void         profileDestructor    (Profile * obj);
static void         scramble             (unsigned char * buffer, int length);

/******************************************************************************
 *  Public Interface
 *****************************************************************************/

//  getProfile() implements the Singleton pattern.  It returns the one
//  instance of the Profile object.

Profile * getProfile ()
{
    if (_profile == NULL)
    {
	int fd;

        _profile = profileConstructor ();
	fd = open (PROFILE_DATA_FILE, O_RDONLY);
	
	if (fd != -1)
	{
	    profileRestoreObject (_profile, fd);
	    close (fd);
	}
    }

    return _profile;
}

/*
 *  Store a scrambled Profile object to a file
 */

int profileStoreObject (Profile * obj, int fd)
{
    const unsigned char *  buffer;
    int                    length;
    SerialObject *         serial;

    serial = so_createAlloc ();
    profileWriteObject (obj, serial);
    buffer = so_getBuffer (serial);
    length = so_getOffset (serial);
    scramble ((unsigned char *)buffer, length);
    write (fd, &length, sizeof(length));
    write (fd, buffer, length);
}

/*
 *  Restore a Profile object from a scrambled file
 */

int profileRestoreObject (Profile * obj, int fd)
{
    unsigned char *  buffer;
    size_t           len;
    int              profileLength;
    SerialObject *   serial;

    len = read (fd, &profileLength, sizeof(profileLength));
    
    if (len != 4 || profileLength < 1) return -1;

    buffer = (unsigned char *) malloc (profileLength);
    len = read (fd, buffer, profileLength);

    if (len != profileLength) return -1;

    scramble (buffer, profileLength);
    serial = so_create (buffer, profileLength);
    profileReadObject (obj, serial);

    return 0;
}

//  get methods

/*
 *  return the company's domain name eg. cobalt.com
 */

const char * profileGetCompanyDomainName (Profile * obj)
{
    return obj->companyDomainName;
}

/*
 *  return the company's name eg. Cobalt Networks, Inc.
 */

const char * profileGetCompanyName (Profile * obj)
{
    return obj->companyName;
}

/*
 *  return the first part of the LCD name eg. Cobalt
 */

const char * profileGetCompanyNameLcd1 (Profile * obj)
{
    return obj->companyNameLcd1;
}

/*
 *  return the second part of the LCD name eg. Networks
 */

const char * profileGetCompanyNameLcd2 (Profile * obj)
{
    return obj->companyNameLcd2;
}

/*
 *  return the default host name eg. firewall
 */

const char * profileGetHostDefault (Profile * obj)
{
    return obj->hostDefault;
}

/*
 *  return the product name eg. Phoenix Adaptive Firewall
 */

const char * profileGetProductName (Profile * obj)
{
    return obj->productName;
}

/*
 *  return the first part of the LCD product name eg. Phoenix Adaptive
 */

const char * profileGetProductNameLcd1 (Profile * obj)
{
    return obj->productNameLcd1;
}

/*
 *  return the second part of the LCD product name eg. Firewall
 */

const char * profileGetProductNameLcd2 (Profile * obj)
{
    return obj->productNameLcd2;
}

/*
 *  return the short version of the product name eg. Phoenix
 */

const char * profileGetProductNameShort (Profile * obj)
{
    return obj->productNameShort;
}

/*
 *  return the product license name eg. phoenix
 */

const char * profileGetProductLicenseName (Profile * obj)
{
    return obj->productLicenseName;
}

/*
 *  return the product version eg. 1.6.4
 */

const char * profileGetProductVersion (Profile * obj)
{
    return obj->productVersion;
}

/*
 *  return the support email address eg. support@cobalt.com
 */

const char * profileGetSupportEmail (Profile * obj)
{
    return obj->supportEmail;
}

/*
 *  return the support phone number eg. 1-800-555-1212
 */

const char * profileGetSupportPhone (Profile * obj)
{
    return obj->supportPhone;
}

/*
 *  return the website eg. www.cobalt.com
 */

const char * profileGetWebsite (Profile * obj)
{
    return obj->website;
}

/*
 *  returns true if the PSI_APPLIANCE_FLAG_FILE exists, and false
 *  otherwise.  Result is cached for CACHE_SECONDS seconds to avoid
 *  needlessly hitting the filesystem.
 */

int profileIsAppliance (Profile * obj) 
{
#define CACHE_SECONDS 60
    static int    isAppliance  = 0;
    static time_t lastReadTime = 0;

    time_t currentTime;
    struct stat statstuff;

    currentTime = time (NULL);

    if ((currentTime - lastReadTime) > CACHE_SECONDS)
    {
	lastReadTime = currentTime;

	if (stat(PSI_APPLIANCE_FLAG_FILE, &statstuff) == 0)
	{
	    isAppliance = 1;
	}
	else
	{
	    isAppliance = 0;
	}
    }	

    return isAppliance;
}       

//  set methods

void profileSetCompanyDomainName (Profile * obj, const char * str)
{
    assignString (str, &(obj->companyDomainName ));
}

void profileSetCompanyName (Profile * obj, const char * str)
{
    assignString (str, &(obj->companyName ));
}

void profileSetCompanyNameLcd1 (Profile * obj, const char * str)
{
    assignString (str, &(obj->companyNameLcd1 ));
}

void profileSetCompanyNameLcd2 (Profile * obj, const char * str)
{
    assignString (str, &(obj->companyNameLcd2 ));
}

void profileSetHostDefault (Profile * obj, const char * str)
{
    assignString (str, &(obj->hostDefault ));
}

void profileSetProductName (Profile * obj, const char * str)
{
    assignString (str, &(obj->productName ));
}

void profileSetProductNameLcd1 (Profile * obj, const char * str)
{
    assignString (str, &(obj->productNameLcd1 ));
}

void profileSetProductNameLcd2 (Profile * obj, const char * str)
{
    assignString (str, &(obj->productNameLcd2 ));
}

void profileSetProductNameShort (Profile * obj, const char * str)
{
    assignString (str, &(obj->productNameShort ));
}

void profileSetProductLicenseName (Profile * obj, const char * str)
{
    assignString (str, &(obj->productLicenseName ));
}

void profileSetProductVersion (Profile * obj, const char * str)
{
    assignString (str, &(obj->productVersion ));
}

void profileSetSupportEmail (Profile * obj, const char * str)
{
    assignString (str, &(obj->supportEmail ));
}

void profileSetSupportPhone (Profile * obj, const char * str)
{
    assignString (str, &(obj->supportPhone ));
}

void profileSetWebsite (Profile * obj, const char * str)
{
    assignString (str, &(obj->website ));
}

/*
 *  serialize the Profile object
 */

int profileReadObject (Profile * obj, SerialObject * serial)
{
    int version = so_getInt    (serial);

    if (version != PROFILE_VERSION)
    {
	return -1;
    }

    /*
     *  Since so_getString() malloc's memory on behalf of the caller,
     *  we'll use assignStringNoMalloc() to copy the pointer without
     *  obtaining additional memory.  The memory will be free'd in
     *  the destructor.
     */

    assignStringNoMalloc (so_getString (serial), &(obj->companyDomainName));
    assignStringNoMalloc (so_getString (serial), &(obj->companyName));
    assignStringNoMalloc (so_getString (serial), &(obj->companyNameLcd1));
    assignStringNoMalloc (so_getString (serial), &(obj->companyNameLcd2));
    assignStringNoMalloc (so_getString (serial), &(obj->hostDefault));

    so_getBoolean (serial);  // dummy read of isAppliance

    assignStringNoMalloc (so_getString (serial), &(obj->productName));
    assignStringNoMalloc (so_getString (serial), &(obj->productNameLcd1));
    assignStringNoMalloc (so_getString (serial), &(obj->productNameLcd2));
    assignStringNoMalloc (so_getString (serial), &(obj->productNameShort));
    assignStringNoMalloc (so_getString (serial), &(obj->productLicenseName));
    assignStringNoMalloc (so_getString (serial), &(obj->productVersion));
    assignStringNoMalloc (so_getString (serial), &(obj->supportEmail));
    assignStringNoMalloc (so_getString (serial), &(obj->supportPhone));
    assignStringNoMalloc (so_getString (serial), &(obj->website));

    return 0;
}

int profileWriteObject (Profile * obj, SerialObject * serial)
{
    so_setInt    (serial, PROFILE_VERSION);

    so_setString (serial, profileGetCompanyDomainName     (obj));
    so_setString (serial, profileGetCompanyName           (obj));
    so_setString (serial, profileGetCompanyNameLcd1       (obj));
    so_setString (serial, profileGetCompanyNameLcd2       (obj));
    so_setString (serial, profileGetHostDefault           (obj));

    so_setBoolean (serial, profileIsAppliance             (obj));

    so_setString (serial, profileGetProductName           (obj));
    so_setString (serial, profileGetProductNameLcd1       (obj));
    so_setString (serial, profileGetProductNameLcd2       (obj));
    so_setString (serial, profileGetProductNameShort      (obj));
    so_setString (serial, profileGetProductLicenseName    (obj));
    so_setString (serial, profileGetProductVersion        (obj));
    so_setString (serial, profileGetSupportEmail          (obj));
    so_setString (serial, profileGetSupportPhone          (obj));
    so_setString (serial, profileGetWebsite               (obj));

    return 0;
}

/******************************************************************************
 *  Private Interface
 *****************************************************************************/

static void assignString_base (const char * str, char ** field, int doMalloc)
{
    int len = (str != NULL) ? strlen (str) : 0;

    if (*field != NULL && strlen(*field) > 0)
    {
        free (*field);
    }

    if (len > 0)
    {
	if (doMalloc)
	{
	    *field = (char *) malloc (len + 1);
	    strcpy (*field, str);
	}
	else
	{
	    *field = (char*) str;
	}
    }
    else
    {
        *field = "";
    }
}

static void assignString (const char * str, char ** field)
{
    return assignString_base (str, field, 1);
}

static void assignStringNoMalloc (const char * str, char ** field)
{
    return assignString_base (str, field, 0);
}

Profile * profileConstructor ()
{
    Profile * obj = (Profile *) malloc (sizeof(*obj));

    if (obj != NULL)
    {
	obj->companyDomainName = NULL;
	obj->companyName       = NULL;
	obj->companyNameLcd1   = NULL;
	obj->companyNameLcd2   = NULL;
	obj->hostDefault       = NULL;

	obj->productName        = NULL;
	obj->productNameLcd1    = NULL;
	obj->productNameLcd2    = NULL;
	obj->productNameShort   = NULL;
	obj->productLicenseName = NULL;
	obj->productVersion     = NULL;
	obj->supportEmail       = NULL;
	obj->supportPhone       = NULL;
	obj->website            = NULL;

	assignString ("cobalt.com", &(obj->companyDomainName));
	assignString ("Cobalt Networks, Inc.", &(obj->companyName));
	assignString ("Cobalt", &(obj->companyNameLcd1));
	assignString ("Networks", &(obj->companyNameLcd2));
	assignString ("firewall", &(obj->hostDefault));
	assignString ("Phoenix Adaptive Firewall", &(obj->productName));
	assignString ("Phoenix Adaptive", &(obj->productNameLcd1));
	assignString ("Firewall", &(obj->productNameLcd2));
	assignString ("Phoenix", &(obj->productNameShort));
	assignString ("phoenix", &(obj->productLicenseName));
	assignString (RELVERSION, &(obj->productVersion));
	assignString ("support@cobalt.com", &(obj->supportEmail));
	assignString ("1-800-266-4378", &(obj->supportPhone));
	assignString ("www.cobalt.com", &(obj->website));
    }

    return obj;
}

void profileDestructor (Profile * obj)
{
    if (obj->companyDomainName != NULL &&
        *(obj->companyDomainName) != '\0')
    {
        free (obj->companyDomainName);
    }

    if (obj->companyName != NULL &&
        *(obj->companyName) != '\0')
    {
        free (obj->companyName);
    }

    if (obj->companyNameLcd1 != NULL &&
        *(obj->companyNameLcd1) != '\0')
    {
        free (obj->companyNameLcd1);
    }

    if (obj->companyNameLcd2 != NULL &&
        *(obj->companyNameLcd2) != '\0')
    {
        free (obj->companyNameLcd2);
    }

    if (obj->hostDefault != NULL &&
        *(obj->hostDefault) != '\0')
    {
        free (obj->hostDefault);
    }

    if (obj->productName != NULL &&
        *(obj->productName) != '\0')
    {
        free (obj->productName);
    }

    if (obj->productNameLcd1 != NULL &&
        *(obj->productNameLcd1) != '\0')
    {
        free (obj->productNameLcd1);
    }

    if (obj->productNameLcd2 != NULL &&
        *(obj->productNameLcd2) != '\0')
    {
        free (obj->productNameLcd2);
    }

    if (obj->productNameShort != NULL &&
        *(obj->productNameShort) != '\0')
    {
        free (obj->productNameShort);
    }

    if (obj->productLicenseName != NULL &&
        *(obj->productLicenseName) != '\0')
    {
        free (obj->productLicenseName);
    }

    if (obj->productVersion != NULL &&
        *(obj->productVersion) != '\0')
    {
        free (obj->productVersion);
    }

    if (obj->supportEmail != NULL &&
        *(obj->supportEmail) != '\0')
    {
        free (obj->supportEmail);
    }

    if (obj->supportPhone != NULL &&
        *(obj->supportPhone) != '\0')
    {
        free (obj->supportPhone);
    }

    if (obj->website != NULL &&
        *(obj->website) != '\0')
    {
        free (obj->website);
    }

    free (obj);
}

static void scramble (unsigned char * buffer, int length)
{
    int i;

    for (i = 0; i < length; i++)
    {
	buffer[i] ^= (i + 7);
    }
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
