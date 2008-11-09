/*
** Copyright 2001 Sun Microsystems, Inc. All rights reserved.
** $Id: FileParse.c,v 1.1.2.1 2002/02/20 18:01:10 ge Exp $
*/

/*
 * This code is a hodgepodge to get email alerts to be sent out i18n
 * style.  The first part comes from the swatch+cce code and the second
 * part puts it all together.  I choose to use this client because its
 * i18n compliant and ours wasn't.  
 */
#include <stdlib.h>
#include <sys/stat.h>
#include <unistd.h>
#include <errno.h>
#include <string.h>
#include <ctype.h>

#include "CceClient.h"
#include "libphoenix.h"
#include "readconfig.h"

/***********************************************************************/

#define DEF_LOCALE      "en"

/* where to look for the sys locale */
#define SYS_LOCALE_FILE "/etc/cobalt/locale"

#define DOMAIN    "base-scandetection"

#define PSCONFIGFILE "/etc/scandetection/scandetection.conf"
/************************************************************************/

char file_adminemail[512] = "???";
int file_alertme;
int file_actionlevel;

static void do_email (const char *keyword, const char *value)
{
     strncpy(file_adminemail, value, sizeof(file_adminemail));
}

static void do_alertme (const char *keyword, unsigned long value)
{
     file_alertme = value;
}

static void do_actionlevel (const char *keyword, unsigned long value)
{
     file_actionlevel = value;
}

static void do_nothing (const char *keyword, unsigned long value)
{
	return;
}

static void do_nothing2 (const char *keyword, const char *value)
{
	return;
}

static keyword_t kwtable[] = {
	KW_NUMBER("actionlevel", do_actionlevel),
	KW_NUMBER("timeout", do_nothing),
	KW_NUMBER("numscans", do_nothing),
	KW_NUMBER("alertme", do_alertme),
	KW_STRING("alertemail", do_email),
	KW_STRING("alwaysblocked", do_nothing2),
	KW_STRING("neverblocked",do_nothing2)
};


/*
 *  get_admin_email - look in CCE and return admin email list, space separated
 *
 *  Args:    buffer and size
 *  Returns: true if successful, false otherwise
 */               
int get_admin_email(char *buffer, int size)
{

	FILE *config_file;
        char parse_error[128];
        if ((config_file = fopen(PSCONFIGFILE, "r")) == NULL) {
        	return 0;
	}

        if(ReadConfig(config_file, kwtable, sizeof(kwtable)/sizeof(kwtable[0]), parse_error, sizeof(parse_error)) == -1){
        fclose(config_file);
        return 0;
       };
     fclose(config_file);
LOG(LOG_BASE, "Email: %s", file_adminemail);	
     strncpy(buffer, file_adminemail, sizeof(file_adminemail));
     size = strlen(buffer);
     return size;

}

/*
 *  isPortScanOn
 * 
 * returns -1 when System.Scandetection isn't found - fwall not installed
 * returns 0 if System.Scandetection.paranoiaLevel == (0 || 1)
 * returns 1 if System.Scandetection.paranoiaLevel == 2
 */               
int isPortScanOn(void)
{
        FILE *config_file;
        char parse_error[128];
        if ((config_file = fopen(PSCONFIGFILE, "r")) == NULL) {
                return -1;
        }

        if(ReadConfig(config_file, kwtable, sizeof(kwtable)/sizeof(kwtable[0]), parse_error, sizeof(parse_error)) == -1)
        {
        fclose(config_file);
	return -1;
	}
        fclose(config_file);

LOG(LOG_BASE, "Logging: alertme %d, actionlevel %d", file_alertme, file_actionlevel);
        return (file_alertme && file_actionlevel);


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
