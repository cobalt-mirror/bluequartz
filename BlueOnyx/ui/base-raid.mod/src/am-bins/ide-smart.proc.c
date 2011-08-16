/*
 * ide-smart.c
 *
 * usage: ide-smart /dev/hdx
 */
#include <stdio.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <fcntl.h>
#include <string.h>
#include <linux/hdreg.h>
#include <linux/types.h>

#define NR_ATTRIBUTES	30

typedef struct threshold_s {
	__u8		id;
	__u8		threshold;
	__u8		reserved[10];
} __attribute__ ((packed)) threshold_t;
	
typedef struct thresholds_s {
	__u16		revision;
	threshold_t	thresholds[NR_ATTRIBUTES];
	__u8		reserved[18];
	__u8		vendor[131];
	__u8		checksum;
} __attribute__ ((packed)) thresholds_t;

typedef struct value_s {
	__u8		id;
	__u16		status;
	__u8		value;
	__u8		vendor[8];
} __attribute__ ((packed)) value_t;

typedef struct values_s {
	__u16		revision;
	value_t		values[NR_ATTRIBUTES];
	__u8		offline_status;
	__u8		vendor1;
	__u16		offline_timeout;
	__u8		vendor2;
	__u8		offline_capability;
	__u16		smart_capability;
	__u8		reserved[16];
	__u8		vendor[125];
	__u8		checksum;
} __attribute__ ((packed)) values_t;

#define NR_OFFLINE_TEXTS	5
struct {
	__u8		value;
	char		*text;
} offline_status_text[NR_OFFLINE_TEXTS] = {
	{ 0x00, "NeverStarted" },
	{ 0x02, "Completed" },
	{ 0x04, "Suspended" },
	{ 0x05, "Aborted" },
	{ 0x06, "Failed" }
};

values_t values;
thresholds_t thresholds;
char basename[80] = "/proc/ide/";

static char *get_offline_text(int status)
{
	int i;

	for (i = 0; i < NR_OFFLINE_TEXTS; i++)
		if (offline_status_text[i].value == status)
			return offline_status_text[i].text;
	return "unknown";
}

static int read_ascii(char *name, __u16 *buffer)
{
	FILE *fp;
	__u16 c, i, tmp;

	if ((fp = fopen(name, "r")) == NULL) {
		fprintf(stderr, "failed to open %s\n", name);
		return 1;
	}
	for (i = 0; i < 256; i++)
		if (fscanf(fp, "%04hx%c", buffer + i, &c) != 2) {
			fprintf(stderr, "failed to read from %s\n", name);
			fclose(fp);
			return 1;
		}
	fclose(fp);
	return 0;
}

static int smart_read_values(void)
{
	char name[80];
	return read_ascii(strcat(strcpy(name, basename), "/smart_values"), (__u16 *) &values);
}

static void print_value(value_t *p, threshold_t *t)
{
	if (!p->id || !t->id || p->id != t->id)
		return;
	printf("Id=%3d, Status=%2d {%s , %s}, Value=%3d, Threshold=%3d, %s\n", p->id, p->status,
		p->status & 1 ? "PreFailture" : "Advisory   ",
		p->status & 2 ? "OnLine " : "OffLine",
		p->value, t->threshold,
		p->value > t->threshold ? "Passed" : "Failed");
}

static void print_values(values_t *p, thresholds_t *t)
{
	value_t *value = p->values;
	threshold_t *threshold = t->thresholds;
	int i;

	printf("\n");
	for (i = 0; i < NR_ATTRIBUTES; i++)
		print_value(value++, threshold++);
	printf("OffLineStatus=%d {%s}, AutoOffLine=%s, OffLineTimeout=%d minutes\n",
		p->offline_status, get_offline_text(p->offline_status & 0x7f),
		p->offline_status & 0x80 ? "Yes" : "No",
		p->offline_timeout / 60);
	printf("OffLineCapability=%d {%s %s %s}\n",  p->offline_capability,
		p->offline_capability & 1 ? "Immediate" : "",
		p->offline_capability & 2 ? "Auto" : "",
		p->offline_capability & 4 ? "AbortOnCmd" : "SuspendOnCmd");
	printf("SmartRevision=%d, CheckSum=%d, SmartCapability=%d {%s %s}\n",
		p->revision, p->checksum, p->smart_capability,
		p->smart_capability & 1 ? "SaveOnStandBy" : "",
		p->smart_capability & 2 ? "AutoSave" : "");
	printf("\n");
}

static void print_threshold(threshold_t *p)
{
	if (!p->id)
		return;
	printf("Id=%3d, Threshold=%3d\n", p->id, p->threshold);
}

static void print_thresholds(thresholds_t *p)
{
	threshold_t *threshold = p->thresholds;
	int i;

	printf("\n");
	printf("SmartRevision=%d\n", p->revision);
	for (i = 0; i < NR_ATTRIBUTES; i++)
		print_threshold(threshold++);
	printf("CheckSum=%d\n", p->checksum);
	printf("\n");
}

static int smart_read_thresholds(void)
{
	char name[80];
	return read_ascii(strcat(strcpy(name, basename), "/smart_thresholds"), (__u16 *) &thresholds);
}

int main(int argc, char *argv[])
{
	if (argc != 2 || strstr(argv[1], "hd") == NULL) {
		printf("usage: ide-smart /dev/hdx\n");
		return 0;
	}
	strncat(basename, strstr(argv[1], "hd"), 3);
	if (smart_read_values())
		return 0;
	if (smart_read_thresholds())
		return 0;
	print_values(&values, &thresholds);
	return 0;
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
