
#include <sys/types.h>
#include <sys/file.h>
#include <sys/wait.h>
#include <unistd.h>
#include <stdlib.h>
#include <stdio.h>
#include <string.h>
#include <signal.h>

#include "lcdutils.h"
#include "messages.h"
#include "lcd.h"

#define         nil             (NULL)
#define         SIGNORE(s)      ((void)signal((s), SIG_IGN))
#define         NOZERO(ip)      ((char*)do_inaddr_remove_zero((ip)))
#define         VALIDIP(ip)     ((int)do_inaddr_verify((ip)))
#define         INVALIDIP(ip)   (!((int)do_inaddr_verify((ip))))

/*
 * functions
 */
void            get_net_numbers(int*,int*,int*);
int             check_netmask(char*);
int             formatter(char*,char*,int);
void            system_setup(void*);
char*           get_console(char*,char*);
void            get_ip_console(void);
void            get_netmask_console(void);
void            get_gateway_console(void);
void            get_save_console(void);
void            get_ip_lcd(void*);
void            get_netmask_lcd(void*);
void            get_gateway_lcd(void*);
void            get_save_lcd(void*);
int             get_finish_lcd(void*);

/*
 * variables
 */
int             button,bingo,net_class,last_button,validaddr,i;
int		first_boot=1,netlink=1,ctr=0,finished=0;
int             cancel_position = CURSOR_CANCEL_POS;
unsigned char   checkhoststr[40];
unsigned char   iporig[40];
unsigned char   nmorig[40];
unsigned char   gworig[40];
unsigned char   nsorig[40];
unsigned char   configstr[40];
unsigned char   formatted[16];
unsigned char   formattedip[16];
unsigned char   formattednm[16];
unsigned char   formattedgw[16];
struct          lcd_display display;

static int do_inaddr_verify (char *in)
{
	int a,b,c,d;
	sscanf(in, "%d.%d.%d.%d", &a, &b, &c, &d);
	if (a > 255 || a < 0) return 0;
	if (b > 255 || b < 0) return 0;
	if (c > 255 || c < 0) return 0;
	if (d > 255 || d < 0) return 0;
	return 1;
}

static char* do_inaddr_remove_zero (char *in)
{
	int one, two, three, four;
	static char out[16];
	memset(&out, 0, 16);

	sscanf(in, "%d.%d.%d.%d", &one, &two, &three, &four);
	if (one > 255) one = 0;
	if (two > 255) two = 0;
	if (three > 255) three = 0;
	if (four > 255) four = 0;
	sprintf(out, "%d.%d.%d.%d", one, two, three, four);
	out[15] = 0;

	return &out[0];
}

static void display_input (void)
{
	printf("\n\r");
	printf("---------------------------------\n\r");
	printf("%s  %s\n\r", _(ipstr), NOZERO(ipaddr));
	printf("%s  %s\n\r", _(nmstr), NOZERO(defmask));
	printf("%s  %s\n\r", _(gwstr), NOZERO(gwaddr));
	printf("---------------------------------\n\r");
	fflush(stdout);
}

static void reaper(int sig)
{
	pid_t id;
	int st;

	switch (sig) {
	case SIGCHLD:
		while ((id = waitpid(-1, &st, WNOHANG)) > 0)
			sleep(1);
		break;
	case SIGUSR1:
		sleep(2);
		exit(0);
		break;
	default:
		return;
	}
}

int main(int argc, char **argv)
{
	pid_t pid;
	struct sigaction sig;

#ifdef SIGTTOU
	SIGNORE(SIGTTOU);
#endif
#ifdef SIGTTIN
	SIGNORE(SIGTTIN);
#endif
#ifdef SIGTSTP
	SIGNORE(SIGTSTP);
#endif
#ifdef SIGHUP
	SIGNORE(SIGHUP);
#endif
	SIGNORE(SIGINT);
	SIGNORE(SIGQUIT);
	SIGNORE(SIGABRT);
	SIGNORE(SIGTERM);
	SIGNORE(SIGUSR2);

	lcd_setlocale();
	
	if ((pid = fork()) < 0) {

                /* FORK ERROR */

		printf(_("\n\rERROR: cannot fork!\n\r\n\r"));
		fflush(stdout);

		exit(1);

	}
	else if (pid == 0) {

                /* CHILD */
		/* HANDLE CONSOLE INPUT */

		SIGNORE(SIGCHLD);
		SIGNORE(SIGUSR1);

       /*		
	        while (512 != system("/etc/lcd.d/10main.m/10SETUP_NETWORK.m/10SETUP_NET1.m/10CONFIGURE.s/10ipnmgw -c"))
		{
		}
		
		lcd_unlock();  
		system("/etc/rc.d/init.d/lcd-showip"); 

		(void) kill(-getppid(), SIGUSR1);
	*/
		_exit(0);
	}

	/* PARENT */
	/* HANDLE LCD INPUT */

	/* enable handler for child deaths */
	sig.sa_handler = reaper;
	sigemptyset(&sig.sa_mask);
	sig.sa_flags = 0;
	sigaction(SIGUSR1, &sig, nil);
	sig.sa_flags = SA_NOCLDSTOP;
	sigaction(SIGCHLD, &sig, nil);

	while (512 != system("/etc/lcd.d/10main.m/10SETUP_NETWORK.m/10SETUP_NET1.m/10CONFIGURE.s/10ipnmgw"))
	{
	}

	system("/etc/rc.d/init.d/lcd-showip"); /* showip */
	
	/* Kill all processes in this group */
	/*
	(void) kill(0, SIGKILL);
	*/
	exit(0);
}

/*
 * get IPv4 address from console
 */
char* get_console (char *str, char *orig)
{
	static char buf[20];
	char *line = nil;

	validaddr = 0;

	while (!validaddr)
	{
		memset(&buf,0,20);
		line = &buf[0];

		printf("\n\r%s\n\r[%s]> ", _(str), NOZERO(orig));

		if ((line = fgets(line, 19, stdin)) == nil) {
			printf("\n\r%s\n\r", _("invalid input!"));
			continue;
		}

		if (buf[0] == '\n')
			line = orig;
 
		if (INVALIDIP(line))
		        printf("\n\r%s  %s", _("INVALID ADDRESS:"), line);
		else
			validaddr = 1;
	}

	lcd_rev_format(line);
	return(line);
}

/*
 * get IP address from console
 */
void get_ip_console (void)
{
	char *line;

	validaddr = 0;

	do {
		line = get_console(ipstr, iporig);
		if (!line) continue;

		validaddr = formatter(line, formatted, kIPADDR);
		if (!validaddr)
			printf("\n\r%s  %s\n\r", _(xipstr), NOZERO(line));

	} while (!validaddr);

	memset(ipaddr,0,16);
	memcpy(ipaddr,line,15);
	ipaddr[15] = 0;
}

/*
 * get netmask from console
 */
void get_netmask_console (void)
{
	char *line, *mask;

	validaddr = 0;

	do {
		net_class = formatter(ipaddr, formatted, kIPADDR);

		if (strcmp(defmask,noip))
			mask = defmask;
		else if (net_class == kClassA)
			mask = defmaskA;
		else if (net_class == kClassB)
			mask = defmaskB;
		else if (net_class == kClassC)
			mask = defmaskC;
		else
			mask = defmaskC;

		do {
			line = get_console(nmstr, mask);
			if (!line) continue;

			validaddr = formatter(line, formattednm, kNETMASK);
			if (!validaddr)
				printf("\n%s  %s\n", _(xnmstr), NOZERO(line));

		} while (!validaddr);

		memset(fdefmask,0,16);
		memcpy(fdefmask,line,15);
		fdefmask[15] = 0;

	} while (!check_netmask(fdefmask));
}

/*
 * get gateway from the console
 */
void get_gateway_console (void)
{
	char *line;

	validaddr = 0;

        do {
		line = get_console(gwstr, gworig);
		if (!line) continue;
		
		if (!strncmp(line, noip, strlen(noip)))
			validaddr = 1;
		else
			validaddr = formatter(line, formattedgw, kGATEWAY);

		if (!validaddr)
			printf("\n\r%s  %s\n\r", _(xgwstr), NOZERO(line));

	} while (!validaddr);

	memset(gwaddr,0,16);
	memcpy(gwaddr,line,15);
	gwaddr[15] = 0;
}

/*
 * ask user to save or cancel
 */
void get_save_console (void)
{
	static char buf[8];

	for (;;)
	{
		(void) display_input();

		printf("\n\r%s  > ", _(exstr));
		if (fgets(buf, 7, stdin) == nil) {
			printf("\n\r%s\n\r", _("invalid input!"));
			continue;
		}

		if (buf[0] == 'c' || buf[0] == 'C') {
			finished = 0;
			validaddr = 1;
			break;
		}
		else if (buf[0] == 's' || buf[0] == 'S') {
			finished = 1;
			return;
		}
	}

	memcpy(iporig, ipaddr, 15);
	iporig[15]=0;

	memcpy(nmorig, defmask, 15);
	nmorig[15]=0;

	memcpy(gworig, gwaddr, 15);
	gworig[15]=0;
}

/*
 * Get IP Address from the LCD
 */
void get_ip_lcd (void * lcd)
{
	char *line;

	lcd_reset(lcd);
	lcd_write(lcd, _(ipstr), iporig); 

	validaddr = 0;
	while (!validaddr)
	{
		lcd_setcursorpos(lcd, 0x40);
		lcd_wait_no_button(lcd);
		button = lcd_netbutton(lcd);
		lcd_getdisplay(lcd, nil, &line, nil);
		strcpy(ipaddr, line);

		validaddr = formatter(ipaddr, formatted, kIPADDR);
		if (!validaddr) {
			lcd_write(lcd, _(xipstr), ipaddr);
			lcd_wait_no_button(lcd);
			lcd_blink_invalid(lcd);
		}
	}
	ipaddr[15]=0;
}


/*
 *  Get Netmask from LCD
 */
void get_netmask_lcd (void *lcd)
{
	char *mask, *line;
	
        net_class = formatter (ipaddr, formatted, kIPADDR);

	if (strcmp(defmask,noip))
		mask = defmask;
	else if (net_class == kClassA)
		mask = defmaskA;
	else if (net_class == kClassB)
		mask = defmaskB;
	else if (net_class == kClassC)
		mask = defmaskC;
	else
		mask = defmaskB;

	lcd_reset(lcd);
	lcd_write(lcd, _(nmstr), mask);

	validaddr = 0;
	while (!validaddr) {
		lcd_setcursorpos(lcd, 0x40);
		lcd_wait_no_button(lcd);
		button = lcd_netbutton(lcd);
		lcd_getdisplay(lcd, nil, &line, nil);
		strcpy(defmask, line);

		validaddr = formatter(defmask, formatted, kNETMASK);
		if (!validaddr || !(check_netmask(defmask))) {
			lcd_write(lcd, _(xnmstr), defmask);
			lcd_wait_no_button(lcd);
			lcd_blink_invalid(lcd);
		} 
	}
	defmask[15]=0;
}

/*
 * get gateway from the lcd panel
 */
void get_gateway_lcd (void *lcd)
{
	char *line;
	int button;

	lcd_reset(lcd);
	lcd_write(lcd, _(gwstr), gworig);

	validaddr = 0;
	while (!validaddr)
	{
		lcd_setcursorpos(lcd, 0x40);
		lcd_wait_no_button(lcd);
		button = lcd_netbutton(lcd);
		lcd_getdisplay(lcd, nil, &line, nil);
		strcpy(gwaddr, line);

		if (!strncmp(gwaddr, noip, strlen(noip)))
			validaddr = 1;
		else
			validaddr = formatter(gwaddr, formattedgw, kGATEWAY);

		if (!validaddr) {
			lcd_write(lcd, _(xgwstr), gwaddr);
			lcd_wait_no_button(lcd);
			lcd_blink_invalid(lcd);
		}
	}
	gwaddr[15]=0;
}

/*
 * ask user to save/cancel changes via LCD
 */
void get_save_lcd (void *lcd)
{
	char *line;
	int i,pos,button;

	line = strdup(_(exstr));
	lcd_reset(lcd);
	lcd_write(lcd, line, "");

	for (i=0; i<14; i++)
	{
		if (line[i] == '[' && line[i+2] == ']')
			cancel_position = i + kDD_R00 + 1;
	}

	lcd_setcursorpos(lcd, cancel_position);
	lcd_set(lcd, LCD_Cursor_On);
	lcd_wait_no_button(lcd);
	button = get_finish_lcd(lcd);

	pos = lcd_getcursorpos(lcd);
	finished = (pos == cancel_position) ? 0 : 1;

	if (!finished) {
		memcpy(iporig, ipaddr, 15);
		iporig[15]=0;

		memcpy(nmorig, defmask, 15);
		nmorig[15]=0;

		memcpy(gworig, gwaddr, 15);
		gworig[15]=0;
	}
}

/*
 * consolidate random system hacks & other crap
 */
void system_setup (void *lcd)
{
	FILE *fp;

	/* We now have the IP, NM and GW, setup eth0 and check GW */
	printf("\n\rVerifying and Saving... ");
	fflush(stdout);
	lcd_reset(lcd);
	system("/sbin/lcdstop");
	system(_("/sbin/lcd-swrite \"Verifying and\" Saving &>/dev/null &"));

	/*  User has chosen [S]ave and this is a fresh qube,
	 *  or a failed DHCP, so clean network house
	 */
	system("cp /etc/resolv.conf.master /etc/resolv.conf");
	system("cp /etc/hosts.master /etc/hosts");
	system("cp /etc/sysconfig/network.master /etc/sysconfig/network");
	system("cp /etc/sysconfig/network-scripts/ifcfg-eth0.master /etc/sysconfig/network-scripts/ifcfg-eth0");
	system("/bin/hostname localhost");
	system("echo localhost > /etc/HOSTNAME");

	/* Save IP Addr and Netmask in config files */
	validaddr = formatter(ipaddr, formattedip, kIPADDR);
	validaddr = formatter(defmask, formattednm, kNETMASK);
	sprintf(configstr, "/usr/local/sbin/setup-network -a %s -m %s", formattedip, formattednm);
	system(configstr);

	validaddr = formatter(gwaddr, formattedgw, kGATEWAY);
	bingo = (netlink==0) ? 1 : 0;
	
	if (strncmp(gwaddr, noip, strlen(noip))) {
		if (validaddr && netlink)
			bingo = 1;
		
		/* If bad GW Addr report INAVALID and re-enter */
		if ((netlink && !bingo) || !validaddr) {
			printf("\n\r%s  %s\n\r", _(xgwstr), gwaddr);
			system("/sbin/lcdstop");
			lcd_reset(lcd);
			lcd_write(lcd, _(xgwstr), gwaddr);
			lcd_wait_no_button(lcd);
			lcd_blink_invalid(lcd);
		}
		if (validaddr && bingo) {
			validaddr = formatter(gwaddr, formattedgw, kGATEWAY);
			sprintf(configstr, "/usr/local/sbin/setup-network -g %s", formattedgw);
			system(configstr);
			finished = 1;
			system("/sbin/lcdstop");
			fp = fopen("/etc/NET-CONFIG","w");
			fprintf(fp,"BOOTPROTO=lcd\n");
			fclose (fp);
		}
	}
	else {
		bingo = 1;
		validaddr = 1;
		finished = 1;

		system("/usr/local/sbin/setup-network -g none");
		system("/sbin/lcdstop");
		fp = fopen("/etc/NET-CONFIG","w");
		fprintf(fp,"BOOTPROTO=lcd\n");
		fclose (fp);
	}
	system("/sbin/lcdstop");
}

int get_finish_lcd (void * lcd)
{
	int button, pos;

	ctr = 0;
	for(;;) {
		button = lcd_readbutton(lcd) & 0xFF;
		if (button != BUTTON_NONE &&
		    button != BUTTON_NONE_B) {

			if (ctr++ == BUTTON_DEBOUNCE) {

				switch (button) {
                                case BUTTON_Up:
                                case BUTTON_Up_B:
                                case BUTTON_Down:
                                case BUTTON_Down_B:
				case BUTTON_Next:
				case BUTTON_Next_B:
					break;

                                case BUTTON_Left:
                                case BUTTON_Left_B:
                                case BUTTON_Right:
                                case BUTTON_Right_B:
					pos = lcd_getcursorpos(lcd);
					lcd_setcursorpos(lcd, (pos == cancel_position) ?
							 0x01 : cancel_position);
					break;
                        
                                case BUTTON_Enter:
                                case BUTTON_Enter_B:
                                        return button;
				default:
					break;
				}
                        }
                        if (ctr == BUTTON_SENSE) {
				ctr = 0;
			}
		}
		else {
			ctr = 0;
		}
	}

	return(0);
}

/*
 * verify a netmask address
 */
int check_netmask( char *in ) 
{

	int ctr, valid, tag;
	char temp[4];
	unsigned int mask = 0x00;
	unsigned char num;

	temp[3] = 0;

	tag = 0;
	valid = 1;

	for (ctr=0;ctr<4;ctr++) {
		strncpy(temp,in,3);
		num = atoi(temp);
		mask = num | mask;
		if (ctr<3)
			mask = mask << 8;
   		in+=4;
	}

	/* 0 for bad netmask */
        mask = ~mask;

        if (mask & (mask + 1))
                return 0;

        return 1;
}

/*
 * format and validate an ipv4 address
 */
int formatter( char *in, char *out, int addr_code)
{
	int ctr,num,valid;
	char temp[5];
 
	out[0] = 0;
	temp[3] = 0;

	valid = 1;

	for (ctr=0;ctr<4;ctr++)	{
		strncpy(temp,in,3);
		num = atoi(temp);
		if ( (ctr == 0) && (addr_code != kNETMASK) ) {
			if ( (num>0) && (num<127) ) {
				valid = kClassA;
			}
			else if ( (num>127) && (num<192) ) {
				valid = kClassB;
			}
			else if ( (num>191) && (num<224) ) {
				valid = kClassC;
			}
			else 
				valid = 0; 
		}
		else if ( (ctr == 3) && (addr_code != kNETMASK) ) {
			if ( (num==0) ) {
				valid = 0;
			}
		}
		if ( num > 255 ) 
			valid = 0;
		if (ctr!=3)
			sprintf(temp,"%d.",num);
		else
			sprintf(temp,"%d",num);
		strcat(out,temp);
		in+=4;
	}
	return valid;
}

/*
 * retrieve network info from the system
 */
void get_net_numbers (int *set_ipaddr, int *set_netmask, int *set_gateway)
{

	FILE *fp;
	char *network_file = "/etc/sysconfig/network";
	char *ifcfg_eth0_file = "/etc/sysconfig/network-scripts/ifcfg-eth0";
	static char buf[40];

	fp = fopen(network_file, "r");
	if (fp) {
		while (!feof(fp))
		{
			fgets(buf, sizeof(buf), fp);
			if (! strncmp(buf, "GATEWAY=", 8)) {
				sscanf (buf, "GATEWAY=%s", fgwaddr);
				*set_gateway = 1;
				break;
			}
		}
		fclose (fp);
	}

	fp = fopen(ifcfg_eth0_file, "r");
	if (fp) {
		while (!feof(fp))
		{
			if (*set_ipaddr==1 && *set_netmask==1) break;
			fgets(buf, sizeof(buf), fp);

			if (! strncmp(buf, "IPADDR=", 7)) {
				sscanf(buf, "IPADDR=%s", fipaddr);
				*set_ipaddr = 1;
				continue;
			}
			if (! strncmp(buf, "NETMASK=", 8)) {
				sscanf(buf, "NETMASK=%s", fdefmask);
				*set_netmask = 1;
				continue;
			}
		}
		fclose(fp);
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
