/*

$Header: /home/cvs/base-scandetection.mod/src/commonlib/libphoenix.c,v 1.1 2001/09/18 16:59:37 jthrowe Exp $

                   Site:  Progressive Systems, Inc.

                Module :  $Source: /home/cvs/base-scandetection.mod/src/commonlib/libphoenix.c,v $
              Revision :  $Revision: 1.1 $

               Package :  Phoenix Adaptive Firewall for Unix

         Creation Date :  Sat Aug 22 13:22:28 EDT 1998
    Originating Author :  Ge' Weijers, MJ Hullhorst

      Last Modified by :  $Author: jthrowe $ 
    Date Last Modified :  $Date: 2001/09/18 16:59:37 $

   **********************************************************************

   Copyright (c) 1997-1998 Progressive Systems Inc.
   All rights reserved.

   This code is confidential property of Progressive Systems Inc.  The
   algorithms, methods and software used herein may not be duplicated or
   disclosed to any party without the express written consent from
   Progressive Systems Inc.

   Progressive Systems Inc. makes no representations concerning either
   the merchantability of this software or the suitability of this
   software for any particular purpose.

   These notices must be retained in any copies of any part of this
   documentation and/or software.

   ********************************************************************** */

# include "libphoenix.h"
#include <dirent.h>
#include <time.h>
#include <stdlib.h>
#include <string.h>
#include <sys/socket.h>
#include <netinet/in.h>
#include <arpa/inet.h>

#ifdef __cplusplus
extern "C" {
#endif

extern int errno ;
extern int debug ;

/* ********************************************************************** */

/*
 *  Create a list of directory entries for the given path.  The caller
 *  is responsible for destroying the list and its contents.
 */

ArrayList * getDirectoryList (char * path)
{
    struct dirent *   de;
    DIR *             dir;
    int               len;
    ArrayList *       list;
    char *            str;

    dir = opendir (path);

    if (dir == NULL)
    {
        return NULL;
    }

    list = arrayListCreate (256, 256);

    while((de = readdir(dir)) != NULL)
    {
        len = strlen(de->d_name);
        str = (char *) malloc (len + 1);
        memcpy (str, de->d_name, len + 1);
        arrayListAppend (list, str);
    }

    closedir(dir);

    return list;
}

/* ********************************************************************** */

int
checkfilename( char *filename )
{

   unsigned inc;

   for( inc=0; inc < strlen( filename )-1 ; inc++ )
      if ( filename[ inc ] == '.' )
         if ( filename[ inc + 1 ] == '.' ) 
            return -1 ;

   return 0 ;
}

/* ********************************************************************** */

/*
 * Compress an absolute path (to remove all . and .. directory entries)
 */
int 
CompressPath (const char *p, char *q, unsigned qlen)
{
     unsigned pathstack[128];	/* stack of path fragments */
     unsigned ps = 1;		/* pathstack pointer */
     unsigned ip = 0, iq = 1;	/* index in p, q */
     char c;
     pathstack[0] = 1;		/* init path stack */
     q[0] = '/';		/* init q */

     /* drop all initial slashes on p */
     while(p[ip] == '/')	
	  ip++;

     while((c = p[ip++]) != '\0'){
	  switch(c){
	  case '/':
	       /* drop multiple slashes */
	       while(p[ip] == '/')
		    ip++;

	       /* add a slash to q */
	       if(iq >= qlen)
		    return 0;
	       q[iq++] = '/';
	       break;

	  case '.':

	       /* Case 1: a single . */
	       if(p[ip] == '/' || p[ip] == '\0'){
		    /* drop trailing slashes */
		    while(p[ip] == '/')
			 ip++;
		    break;
	       };

	       /* Case 2: .. */
	       if(p[ip] == '.' && (p[ip+1] == '/' || p[ip+1] == '\0')){
		    /* pop entry of path stack */
		    if(ps > 0)
			 ps--;
		    iq = pathstack[ps];

		    ip++;

		    /* drop trailing slashes */
		    while(p[ip] == '/')ip++;
		    break;
	       };
	  default:
	       /* push the next entry on the path stack */
	       if(ps >= 128)
		    return 0;
	       pathstack[ps++] = iq;

	       /* add current character to q */
	       if(iq >= qlen)
		    return 0;
	       q[iq++] = c;

	       /* copy the rest to q */
	       while((c = p[ip]) != '/' && c != '\0'){
		    if(iq >= qlen)
			 return 0;
		    q[iq++] = c;
		    ip++;
	       }
	  }
     };

     /* add trailing '\0' */
     if(iq >= qlen)
	  return 0;
     q[iq] = '\0';

     return 1;
}

/* ****************************** */

/*
 * Check validity of path
 */
int 
ValidPath(const char *path, const char *prefix)
{
     char buffer[1024];
     unsigned i;
     if(!CompressPath(path, buffer, 1024))
	  return 0;
     for(i = 0; prefix[i] != '\0'; i++)
	  if(buffer[i] != prefix[i])
	       return 0;
     return 1;
}

/* ********************************************************************** */

/*
 * Test driver 
 */

#if 0
char prefix[] = "/etc/phoenix/html/";

int main (int argc, char *argv[])
{
     int i;
     char buffer[1000];
     for(i = 1; i < argc; i++){
	  if(CompressPath(argv[i], buffer, 1000)){
	       printf("%s -> %s%s\n", argv[i], buffer,
		      (ValidPath(argv[i], prefix) ? "" : " (invalid)"));
	  }else{
	       printf("Bad path: %s\n", argv[i]);
	  }
     };
     return 0;
}
#endif

/* ********************************************************************** */

int
compairandparse( char* linein, char* compair, char* result, int resultsize )
{
    int copylen ;

    if ( strncmp( linein, compair, strlen( compair ) ) == 0 )
    {
        copylen = ( strlen( linein ) - strlen( compair ) ) ;
        if ( copylen > resultsize - 1 ) copylen = resultsize - 1 ;
        strncpy( result, &linein[ strlen( compair ) ], copylen ) ;
        result[ copylen ] = 0 ;
        return -1 ;
    }
    return 0 ;
}

/* ********************************************************************** */
//  Reads a line in from a file and returns it without the line termination
int
fgetline( char* line, int linemax, FILE *infile )
{
    char c ;
    int linesize = 0 ;

    while( -1 )
    {
        c = fgetc( infile ) ;
        if ( c == EOF || c == '\n' )
        {
            line[ linesize++ ] = 0 ;
            if ( c == EOF || linesize == 0 )
                return EOF ;
            return linesize ;
        }
        line[ linesize++ ] = c ;
        if ( linesize + 1 >= linemax )
        {
            line[ linesize++ ] = 0 ;
            return linemax ;
        }
    }
}

/* ********************************************************************** */
/* ********************************************************************** */

int
element( char *returnarg, int returnsize, char *inputarg, char *delimiter, int elementnbr )
{

   int cnt;
   int startptr=0;
   int endptr=0;
   int size ;

   int slen;

   slen = strlen( inputarg );
   if ( (slen == 0 ) || elementnbr == 0 )
   {
      strcpy( returnarg, "" );
      return -1;
   }

   /* Setup to the delimiter */
   for (cnt=2; cnt <= elementnbr; cnt++ )
      while ( inputarg[++startptr] != delimiter[0] )
         if ( startptr > slen ) break;

   if ( startptr > slen )
   {
      strcpy( returnarg, "" );
      return -1;
   }

   if ( startptr > 0 )
      startptr++;

   /* Locate the ending delimiter */
   endptr = startptr;

   while ( inputarg[endptr] != delimiter[0] )
   {
      if ( endptr > slen )
      {
         endptr = slen;
         break;
      }
      endptr++;
   }

   /* return the info */
   size = endptr - startptr ;
   if ( size > returnsize ) size=returnsize ;
   strncpy( returnarg, &inputarg[startptr], size ) ;
   returnarg[ size ] = 0 ;

   return 0;
}

/* ********************************************************************** */

void
compressIP( char *arg, int size )
{
   char tpl1str[ 4 ] ;
   char tpl2str[ 4 ] ;
   char tpl3str[ 4 ] ;
   char tpl4str[ 4 ] ;

   int tpl1, tpl2, tpl3, tpl4 = 0 ;
   char ip[16] ;

   element( tpl1str, 3, arg, ".", 1 ) ;
   element( tpl2str, 3, arg, ".", 2 ) ;
   element( tpl3str, 3, arg, ".", 3 ) ;
   element( tpl4str, 3, arg, ".", 4 ) ;

   tpl1 = atoi( tpl1str ) ;
   if ( tpl1 > 255 ) tpl1 = 0 ;

   tpl2 = atoi( tpl2str ) ;
   if ( tpl2 > 255 ) tpl2 = 0 ;

   tpl3 = atoi( tpl3str ) ;
   if ( tpl3 > 255 ) tpl3 = 0 ;

   tpl4 = atoi( tpl4str ) ;
   if ( tpl4 > 255 ) tpl4 = 0 ;

   snprintf( ip, 16, "%d.%d.%d.%d", tpl1, tpl2, tpl3, tpl4 ) ;

   strncpy( arg, ip, size ) ;
   arg[size-1] = 0 ;
}


/* ********************************************************************** */

int
findmaskbits( char* maskarg )
{
   unsigned long int fullmask = 0xffffffff ;
   unsigned long int mask, maskn ;
   int bits ;

   if ( strlen( maskarg ) == 0 ) return 0 ;

   // Compress the IP (eliminate the leading zeros) so as not to confuse aton
   compressIP( maskarg, 16 ) ;

   inet_aton( maskarg, (struct in_addr *) &maskn ) ;
   mask = ntohl( maskn ) ;

   for( bits = 1; bits <= 30 ; bits++ )
      if ( ( ( fullmask << (32-bits) ) & fullmask ) == mask )
         break ;

   LOG( LOG_BUTTON, "Mask %s %x %d\n", maskarg, mask, bits ) ;

   return bits ;
}

/* ********************************************************************** */

# define WORDCNT 1469
# define WORDLEN 5

char*
getrandomword( char* word )
{

    static char wordlist[WORDCNT][WORDLEN] =
    {
      "abel",
      "able",
      "ache",
      "acid",
      "acme",
      "acre",
      "acta",
      "acts",
      "adam",
      "adds",
      "aden",
      "afar",
      "afro",
      "agee",
      "ahem",
      "ahoy",
      "aida",
      "aide",
      "airy",
      "ajar",
      "akin",
      "alan",
      "alec",
      "alga",
      "alia",
      "ally",
      "alma",
      "aloe",
      "also",
      "alum",
      "amen",
      "ames",
      "amid",
      "ammo",
      "amok",
      "amos",
      "amra",
      "andy",
      "anew",
      "anna",
      "anne",
      "ante",
      "anti",
      "aqua",
      "arab",
      "arch",
      "area",
      "argo",
      "arid",
      "army",
      "arts",
      "arty",
      "asia",
      "asks",
      "atom",
      "aunt",
      "aura",
      "auto",
      "aver",
      "avid",
      "avis",
      "avon",
      "avow",
      "away",
      "awry",
      "babe",
      "baby",
      "bach",
      "back",
      "bail",
      "bait",
      "bake",
      "bald",
      "bale",
      "bali",
      "balk",
      "ball",
      "band",
      "bane",
      "bang",
      "bank",
      "barb",
      "bard",
      "bare",
      "bark",
      "barn",
      "barr",
      "base",
      "bash",
      "bask",
      "bass",
      "bate",
      "bath",
      "bawd",
      "bawl",
      "bead",
      "beak",
      "beam",
      "bean",
      "bear",
      "beat",
      "beau",
      "beck",
      "beef",
      "been",
      "beer",
      "beet",
      "bela",
      "bell",
      "belt",
      "bend",
      "bent",
      "berg",
      "bern",
      "bert",
      "bess",
      "best",
      "beta",
      "beth",
      "bhoy",
      "bias",
      "bide",
      "bien",
      "bile",
      "bilk",
      "bill",
      "bind",
      "bing",
      "bird",
      "bite",
      "bits",
      "blab",
      "blat",
      "bled",
      "blew",
      "blob",
      "bloc",
      "blot",
      "blow",
      "blue",
      "blum",
      "blur",
      "boar",
      "boat",
      "boca",
      "bock",
      "bode",
      "body",
      "bogy",
      "bohr",
      "boil",
      "bold",
      "bolo",
      "bolt",
      "bomb",
      "bona",
      "bond",
      "bone",
      "bong",
      "bonn",
      "bony",
      "book",
      "boom",
      "boon",
      "boot",
      "bore",
      "borg",
      "born",
      "bose",
      "boss",
      "both",
      "bout",
      "bowl",
      "boyd",
      "brad",
      "brae",
      "brag",
      "bran",
      "bray",
      "bred",
      "brew",
      "brig",
      "brim",
      "brow",
      "buck",
      "budd",
      "buff",
      "bulb",
      "bulk",
      "bull",
      "bunk",
      "bunt",
      "buoy",
      "burg",
      "burl",
      "burn",
      "burr",
      "burt",
      "bury",
      "bush",
      "buss",
      "bust",
      "busy",
      "byte",
      "cady",
      "cafe",
      "cage",
      "cain",
      "cake",
      "calf",
      "call",
      "calm",
      "came",
      "cane",
      "cant",
      "card",
      "care",
      "carl",
      "carr",
      "cart",
      "case",
      "cash",
      "cask",
      "cast",
      "cave",
      "ceil",
      "cell",
      "cent",
      "cern",
      "chad",
      "char",
      "chat",
      "chaw",
      "chef",
      "chen",
      "chew",
      "chic",
      "chin",
      "chou",
      "chow",
      "chub",
      "chug",
      "chum",
      "cite",
      "city",
      "clad",
      "clam",
      "clan",
      "claw",
      "clay",
      "clod",
      "clog",
      "clot",
      "club",
      "clue",
      "coal",
      "coat",
      "coca",
      "cock",
      "coco",
      "coda",
      "code",
      "cody",
      "coed",
      "coil",
      "coin",
      "coke",
      "cola",
      "cold",
      "colt",
      "coma",
      "comb",
      "come",
      "cook",
      "cool",
      "coon",
      "coot",
      "cord",
      "core",
      "cork",
      "corn",
      "cost",
      "cove",
      "cowl",
      "crab",
      "crag",
      "cram",
      "cray",
      "crew",
      "crib",
      "crow",
      "crud",
      "cuba",
      "cube",
      "cuff",
      "cull",
      "cult",
      "cuny",
      "curb",
      "curd",
      "cure",
      "curl",
      "curt",
      "cuts",
      "dade",
      "dale",
      "dame",
      "dana",
      "dane",
      "dang",
      "dank",
      "dare",
      "dark",
      "darn",
      "dart",
      "dash",
      "data",
      "date",
      "dave",
      "davy",
      "dawn",
      "days",
      "dead",
      "deaf",
      "deal",
      "dean",
      "dear",
      "debt",
      "deck",
      "deed",
      "deem",
      "deer",
      "deft",
      "defy",
      "dell",
      "dent",
      "deny",
      "desk",
      "dial",
      "dice",
      "died",
      "diet",
      "dime",
      "dine",
      "ding",
      "dint",
      "dire",
      "dirt",
      "disc",
      "dish",
      "disk",
      "dive",
      "dock",
      "does",
      "dole",
      "doll",
      "dolt",
      "dome",
      "done",
      "doom",
      "door",
      "dora",
      "dose",
      "dote",
      "doug",
      "dour",
      "dove",
      "down",
      "drab",
      "drag",
      "dram",
      "draw",
      "drew",
      "drub",
      "drug",
      "drum",
      "dual",
      "duck",
      "duct",
      "duel",
      "duet",
      "duke",
      "dull",
      "dumb",
      "dune",
      "dunk",
      "dusk",
      "dust",
      "duty",
      "each",
      "earl",
      "earn",
      "ease",
      "east",
      "easy",
      "eben",
      "echo",
      "eddy",
      "eden",
      "edge",
      "edgy",
      "edit",
      "edna",
      "egan",
      "elan",
      "elba",
      "ella",
      "else",
      "emil",
      "emit",
      "emma",
      "ends",
      "eric",
      "eros",
      "even",
      "ever",
      "evil",
      "eyed",
      "face",
      "fact",
      "fade",
      "fail",
      "fain",
      "fair",
      "fake",
      "fall",
      "fame",
      "fang",
      "farm",
      "fast",
      "fate",
      "fawn",
      "fear",
      "feat",
      "feed",
      "feel",
      "feet",
      "fell",
      "felt",
      "fend",
      "fern",
      "fest",
      "feud",
      "fief",
      "figs",
      "file",
      "fill",
      "film",
      "find",
      "fine",
      "fink",
      "fire",
      "firm",
      "fish",
      "fisk",
      "fist",
      "fits",
      "five",
      "flag",
      "flak",
      "flam",
      "flat",
      "flaw",
      "flea",
      "fled",
      "flew",
      "flit",
      "floc",
      "flog",
      "flow",
      "flub",
      "flue",
      "foal",
      "foam",
      "fogy",
      "foil",
      "fold",
      "folk",
      "fond",
      "font",
      "food",
      "fool",
      "foot",
      "ford",
      "fore",
      "fork",
      "form",
      "fort",
      "foss",
      "foul",
      "four",
      "fowl",
      "frau",
      "fray",
      "fred",
      "free",
      "fret",
      "frey",
      "frog",
      "from",
      "fuel",
      "full",
      "fume",
      "fund",
      "funk",
      "fury",
      "fuse",
      "fuss",
      "gaff",
      "gage",
      "gail",
      "gain",
      "gait",
      "gala",
      "gale",
      "gall",
      "galt",
      "game",
      "gang",
      "garb",
      "gary",
      "gash",
      "gate",
      "gaul",
      "gaur",
      "gave",
      "gawk",
      "gear",
      "geld",
      "gene",
      "gent",
      "germ",
      "gets",
      "gibe",
      "gift",
      "gild",
      "gill",
      "gilt",
      "gina",
      "gird",
      "girl",
      "gist",
      "give",
      "glad",
      "glee",
      "glen",
      "glib",
      "glob",
      "glom",
      "glow",
      "glue",
      "glum",
      "glut",
      "goad",
      "goal",
      "goat",
      "goer",
      "goes",
      "gold",
      "golf",
      "gone",
      "gong",
      "good",
      "goof",
      "gore",
      "gory",
      "gosh",
      "gout",
      "gown",
      "grab",
      "grad",
      "gray",
      "greg",
      "grew",
      "grey",
      "grid",
      "grim",
      "grin",
      "grit",
      "grow",
      "grub",
      "gulf",
      "gull",
      "gunk",
      "guru",
      "gush",
      "gust",
      "gwen",
      "gwyn",
      "haag",
      "haas",
      "hack",
      "hail",
      "hair",
      "hale",
      "half",
      "hall",
      "halo",
      "halt",
      "hand",
      "hang",
      "hank",
      "hans",
      "hard",
      "hark",
      "harm",
      "hart",
      "hash",
      "hast",
      "hate",
      "hath",
      "haul",
      "have",
      "hawk",
      "hays",
      "head",
      "heal",
      "hear",
      "heat",
      "hebe",
      "heck",
      "heed",
      "heel",
      "heft",
      "held",
      "hell",
      "helm",
      "herb",
      "herd",
      "here",
      "hero",
      "hers",
      "hess",
      "hewn",
      "hick",
      "hide",
      "high",
      "hike",
      "hill",
      "hilt",
      "hind",
      "hint",
      "hire",
      "hiss",
      "hive",
      "hobo",
      "hock",
      "hoff",
      "hold",
      "hole",
      "holm",
      "holt",
      "home",
      "hone",
      "honk",
      "hood",
      "hoof",
      "hook",
      "hoot",
      "horn",
      "hose",
      "host",
      "hour",
      "hove",
      "howe",
      "howl",
      "hoyt",
      "huck",
      "hued",
      "huff",
      "huge",
      "hugh",
      "hugo",
      "hulk",
      "hull",
      "hunk",
      "hunt",
      "hurd",
      "hurl",
      "hurt",
      "hush",
      "hyde",
      "hymn",
      "ibis",
      "icon",
      "idea",
      "idle",
      "iffy",
      "inca",
      "inch",
      "into",
      "ions",
      "iota",
      "iowa",
      "iris",
      "irma",
      "iron",
      "isle",
      "itch",
      "item",
      "ivan",
      "jack",
      "jade",
      "jail",
      "jake",
      "jane",
      "java",
      "jean",
      "jeff",
      "jerk",
      "jess",
      "jest",
      "jibe",
      "jill",
      "jilt",
      "jive",
      "joan",
      "jobs",
      "jock",
      "joel",
      "joey",
      "john",
      "join",
      "joke",
      "jolt",
      "jove",
      "judd",
      "jude",
      "judo",
      "judy",
      "juju",
      "juke",
      "july",
      "june",
      "junk",
      "juno",
      "jury",
      "just",
      "jute",
      "kahn",
      "kale",
      "kane",
      "kant",
      "karl",
      "kate",
      "keel",
      "keen",
      "keno",
      "kent",
      "kern",
      "kerr",
      "keys",
      "kick",
      "kill",
      "kind",
      "king",
      "kirk",
      "kiss",
      "kite",
      "klan",
      "knee",
      "knew",
      "knit",
      "knob",
      "knot",
      "know",
      "koch",
      "kong",
      "kudo",
      "kurd",
      "kurt",
      "kyle",
      "lace",
      "lack",
      "lacy",
      "lady",
      "laid",
      "lain",
      "lair",
      "lake",
      "lamb",
      "lame",
      "land",
      "lane",
      "lang",
      "lard",
      "lark",
      "lass",
      "last",
      "late",
      "laud",
      "lava",
      "lawn",
      "laws",
      "lays",
      "lead",
      "leaf",
      "leak",
      "lean",
      "lear",
      "leek",
      "leer",
      "left",
      "lend",
      "lens",
      "lent",
      "leon",
      "lesk",
      "less",
      "lest",
      "lets",
      "liar",
      "lice",
      "lick",
      "lied",
      "lien",
      "lies",
      "lieu",
      "life",
      "lift",
      "like",
      "lila",
      "lilt",
      "lily",
      "lima",
      "limb",
      "lime",
      "lind",
      "line",
      "link",
      "lint",
      "lion",
      "lisa",
      "list",
      "live",
      "load",
      "loaf",
      "loam",
      "loan",
      "lock",
      "loft",
      "loge",
      "lois",
      "lola",
      "lone",
      "long",
      "look",
      "loon",
      "loot",
      "lord",
      "lore",
      "lose",
      "loss",
      "lost",
      "loud",
      "love",
      "lowe",
      "luck",
      "lucy",
      "luge",
      "luke",
      "lulu",
      "lund",
      "lung",
      "lura",
      "lure",
      "lurk",
      "lush",
      "lust",
      "lyle",
      "lynn",
      "lyon",
      "lyra",
      "mace",
      "made",
      "magi",
      "maid",
      "mail",
      "main",
      "make",
      "male",
      "mali",
      "mall",
      "malt",
      "mana",
      "mann",
      "many",
      "marc",
      "mare",
      "mark",
      "mars",
      "mart",
      "mary",
      "mash",
      "mask",
      "mass",
      "mast",
      "mate",
      "math",
      "maul",
      "mayo",
      "mead",
      "meal",
      "mean",
      "meat",
      "meek",
      "meet",
      "meld",
      "melt",
      "memo",
      "mend",
      "menu",
      "mert",
      "mesh",
      "mess",
      "mice",
      "mike",
      "mild",
      "mile",
      "milk",
      "mill",
      "milt",
      "mimi",
      "mind",
      "mine",
      "mini",
      "mink",
      "mint",
      "mire",
      "miss",
      "mist",
      "mite",
      "mitt",
      "moan",
      "moat",
      "mock",
      "mode",
      "mold",
      "mole",
      "moll",
      "molt",
      "mona",
      "monk",
      "mont",
      "mood",
      "moon",
      "moor",
      "moot",
      "more",
      "morn",
      "mort",
      "moss",
      "most",
      "moth",
      "move",
      "much",
      "muck",
      "mudd",
      "muff",
      "mule",
      "mull",
      "murk",
      "mush",
      "must",
      "mute",
      "mutt",
      "myra",
      "myth",
      "nagy",
      "nail",
      "nair",
      "name",
      "nary",
      "nash",
      "nave",
      "navy",
      "neal",
      "near",
      "neat",
      "neck",
      "need",
      "neil",
      "nell",
      "neon",
      "nero",
      "ness",
      "nest",
      "news",
      "newt",
      "nibs",
      "nice",
      "nick",
      "nile",
      "nina",
      "nine",
      "noah",
      "node",
      "noel",
      "noll",
      "none",
      "nook",
      "noon",
      "norm",
      "nose",
      "note",
      "noun",
      "nova",
      "nude",
      "null",
      "numb",
      "oath",
      "obey",
      "oboe",
      "odin",
      "ohio",
      "oily",
      "oint",
      "okay",
      "olaf",
      "oldy",
      "olga",
      "olin",
      "oman",
      "omen",
      "omit",
      "once",
      "ones",
      "only",
      "onto",
      "onus",
      "oral",
      "orgy",
      "oslo",
      "otis",
      "otto",
      "ouch",
      "oust",
      "outs",
      "oval",
      "oven",
      "over",
      "owly",
      "owns",
      "quad",
      "quit",
      "quod",
      "race",
      "rack",
      "racy",
      "raft",
      "rage",
      "raid",
      "rail",
      "rain",
      "rake",
      "rank",
      "rant",
      "rare",
      "rash",
      "rate",
      "rave",
      "rays",
      "read",
      "real",
      "ream",
      "rear",
      "reck",
      "reed",
      "reef",
      "reek",
      "reel",
      "reid",
      "rein",
      "rena",
      "rend",
      "rent",
      "rest",
      "rice",
      "rich",
      "rick",
      "ride",
      "rift",
      "rill",
      "rime",
      "ring",
      "rink",
      "rise",
      "risk",
      "rite",
      "road",
      "roam",
      "roar",
      "robe",
      "rock",
      "rode",
      "roil",
      "roll",
      "rome",
      "rood",
      "roof",
      "rook",
      "room",
      "root",
      "rosa",
      "rose",
      "ross",
      "rosy",
      "roth",
      "rout",
      "rove",
      "rowe",
      "rows",
      "rube",
      "ruby",
      "rude",
      "rudy",
      "ruin",
      "rule",
      "rung",
      "runs",
      "runt",
      "ruse",
      "rush",
      "rusk",
      "russ",
      "rust",
      "ruth",
      "sack",
      "safe",
      "sage",
      "said",
      "sail",
      "sale",
      "salk",
      "salt",
      "same",
      "sand",
      "sane",
      "sang",
      "sank",
      "sara",
      "saul",
      "save",
      "says",
      "scan",
      "scar",
      "scat",
      "scot",
      "seal",
      "seam",
      "sear",
      "seat",
      "seed",
      "seek",
      "seem",
      "seen",
      "sees",
      "self",
      "sell",
      "send",
      "sent",
      "sets",
      "sewn",
      "shag",
      "sham",
      "shaw",
      "shay",
      "shed",
      "shim",
      "shin",
      "shod",
      "shoe",
      "shot",
      "show",
      "shun",
      "shut",
      "sick",
      "side",
      "sift",
      "sigh",
      "sign",
      "silk",
      "sill",
      "silo",
      "silt",
      "sine",
      "sing",
      "sink",
      "sire",
      "site",
      "sits",
      "situ",
      "skat",
      "skew",
      "skid",
      "skim",
      "skin",
      "skit",
      "slab",
      "slam",
      "slat",
      "slay",
      "sled",
      "slew",
      "slid",
      "slim",
      "slit",
      "slob",
      "slog",
      "slot",
      "slow",
      "slug",
      "slum",
      "slur",
      "smog",
      "smug",
      "snag",
      "snob",
      "snow",
      "snub",
      "snug",
      "soak",
      "soar",
      "sock",
      "soda",
      "sofa",
      "soft",
      "soil",
      "sold",
      "some",
      "song",
      "soon",
      "soot",
      "sore",
      "sort",
      "soul",
      "sour",
      "sown",
      "stab",
      "stag",
      "stan",
      "star",
      "stay",
      "stem",
      "stew",
      "stir",
      "stow",
      "stub",
      "stun",
      "such",
      "suds",
      "suit",
      "sulk",
      "sums",
      "sung",
      "sunk",
      "sure",
      "surf",
      "swab",
      "swag",
      "swam",
      "swan",
      "swat",
      "sway",
      "swim",
      "swum",
      "tack",
      "tact",
      "tail",
      "take",
      "tale",
      "talk",
      "tall",
      "tank",
      "task",
      "tate",
      "taut",
      "teal",
      "team",
      "tear",
      "tech",
      "teem",
      "teen",
      "teet",
      "tell",
      "tend",
      "tent",
      "term",
      "tern",
      "tess",
      "test",
      "than",
      "that",
      "thee",
      "them",
      "then",
      "they",
      "thin",
      "this",
      "thud",
      "thug",
      "tick",
      "tide",
      "tidy",
      "tied",
      "tier",
      "tile",
      "till",
      "tilt",
      "time",
      "tina",
      "tine",
      "tint",
      "tiny",
      "tire",
      "toad",
      "togo",
      "toil",
      "told",
      "toll",
      "tone",
      "tong",
      "tony",
      "took",
      "tool",
      "toot",
      "tore",
      "torn",
      "tote",
      "tour",
      "tout",
      "town",
      "trag",
      "tram",
      "tray",
      "tree",
      "trek",
      "trig",
      "trim",
      "trio",
      "trod",
      "trot",
      "troy",
      "true",
      "tuba",
      "tube",
      "tuck",
      "tuft",
      "tuna",
      "tune",
      "tung",
      "turf",
      "turn",
      "tusk",
      "twig",
      "twin",
      "twit",
      "ulan",
      "unit",
      "urge",
      "used",
      "user",
      "uses",
      "utah",
      "vail",
      "vain",
      "vale",
      "vary",
      "vase",
      "vast",
      "veal",
      "veda",
      "veil",
      "vein",
      "vend",
      "vent",
      "verb",
      "very",
      "veto",
      "vice",
      "view",
      "vine",
      "vise",
      "void",
      "volt",
      "vote",
      "wack",
      "wade",
      "wage",
      "wail",
      "wait",
      "wake",
      "wale",
      "walk",
      "wall",
      "walt",
      "wand",
      "wane",
      "wang",
      "want",
      "ward",
      "warm",
      "warn",
      "wart",
      "wash",
      "wast",
      "wats",
      "watt",
      "wave",
      "wavy",
      "ways",
      "weak",
      "weal",
      "wean",
      "wear",
      "weed",
      "week",
      "weir",
      "weld",
      "well",
      "welt",
      "went",
      "were",
      "wert",
      "west",
      "wham",
      "what",
      "whee",
      "when",
      "whet",
      "whoa",
      "whom",
      "wick",
      "wife",
      "wild",
      "will",
      "wind",
      "wine",
      "wing",
      "wink",
      "wino",
      "wire",
      "wise",
      "wish",
      "with",
      "wolf",
      "wont",
      "wood",
      "wool",
      "word",
      "wore",
      "work",
      "worm",
      "worn",
      "wove",
      "writ",
      "wynn",
      "yale",
      "yang",
      "yank",
      "yard",
      "yarn",
      "yawl",
      "yawn",
      "yeah",
      "year",
      "yell",
      "yoga",
      "yoke"
    };

    int randnum  ;
    int wordptr = -1 ;
    static FILE *rf = NULL;
    
    if ( ( rf = fopen("/dev/urandom", "rb") ) == NULL )
    {
       perror("/dev/urandom");
       strncpy( word, wordlist[0], WORDLEN ) ;
       return( word ) ;
    }
    if ( fread( &randnum, sizeof(randnum), 1, rf ) != 1 )
    {
       strncpy( word, wordlist[0], WORDLEN ) ;
       return( word ) ;
    }
    wordptr = abs( randnum ) % WORDCNT ;

    strncpy( word, wordlist[ wordptr ], WORDLEN ) ;
    return( word ) ;
}

/* ********************************************************************** */

void
stringDump( char *desc, BYTE *string, int stringlen )
{
   int inc ;

   // debug here

   //fprintf( stderr, desc ) ;
   for ( inc=0; inc < (int)strlen( desc ); inc++ )
       fprintf( stderr, " " ) ;

   for ( inc=0; inc < stringlen ; inc++ )
      if ( string[ inc ] > 31 && string[ inc ] < 127 )
         fprintf( stderr, "%c   ", string[ inc ] ) ;
      else
         fprintf( stderr, "    " ) ;
   fprintf( stderr, "\n" ) ;

   fprintf( stderr, desc ) ;
   for ( inc=0; inc < stringlen ; inc++ )
      fprintf( stderr, "%4d", string[ inc ] ) ;
   fprintf( stderr, "\n" ) ;
}

// **********************************************************************

void
bufferDump( char *desc, BYTE *string, int strlen )
{
   int inc ;

   // debug here
   fprintf( stderr, desc ) ;
   for ( inc=0; inc < strlen ; inc++ )
      fprintf( stderr, "%03d ", string[ inc ] ) ;
   fprintf( stderr, "\n" ) ;
}

// **********************************************************************

extern int debug ;


void
Sys_Logger_v( int debuglvl, const char *fmt, va_list ap )
{
   char target[ 1024 ] ;

   if ( debug < debuglvl ) return ;

   (void) vsprintf( target, fmt, ap ) ;
   if (debuglvl != LOG_STDERR)
       syslog( LOG_INFO, target ) ;
   else
   {
       /* Print out string plus newline if not there */
       fprintf( stderr, target ) ;
       if (target[strlen(target)-1] != '\n')
	   fprintf( stderr, "\n" );
   }
}

void 
Sys_Logger( int debuglvl, const char *fmt, ... )
{
   va_list ap;

   if ( debug < debuglvl ) return ;

   va_start(ap, fmt);
   Sys_Logger_v(debuglvl, fmt, ap);
   va_end(ap);
}

/* ********************************************************************** */

/* NOTE
   NOTE
   NOTE  These routines are __very__ kernel version specific !
   NOTE
   NOTE */

#include <unistd.h>
#include <linux/unistd.h>
#include <linux/sysctl.h>

_syscall1(int, _sysctl, struct __sysctl_args *, args);

int sysctl (int *name, int nlen, void *oldval, size_t *oldlenp,
	    void *newval, size_t newlen)
{
     struct __sysctl_args args = {name, nlen, oldval, oldlenp, newval, newlen};
     return _sysctl(&args);
}

//* *********************************

#define ALEN(A) (sizeof(A)/sizeof((A)[0]))

int GetOSRelease (int release[3])
{
     static int name[] = {CTL_KERN, KERN_OSRELEASE};
     char buffer[50];
     size_t buflen = sizeof(buffer)-1;
     buffer[buflen] = '\0';
     if(sysctl(name, ALEN(name), buffer, &buflen, NULL, 0) == -1)
	  return -1;
     if(sscanf(buffer, "%d.%d.%d", &release[0], &release[1], &release[2]) == 3)
	  return 0;
     return -1;
}

//* *********************************

static int ipforward_name[] = {CTL_NET, NET_IPV4, NET_IPV4_FORWARD};

int GetIPForwardingFlag (int *pforward)
{
     size_t lforward = sizeof(int);

// TODO TODO TODO

// IMPORTANT - this is a short term hack
//             this needs to be kernel specific not architecture specific

#if __mips__ 
// for linux-2.0.x kernels
     if(sysctl(ipforward_name, ALEN(ipforward_name), 
	       pforward, &lforward, NULL, 0) == -1)
	  return -1;
#else
// for linux-2.2.x kernels
     size_t tforward = sizeof(int);
     int    tpforward ;

     if(sysctl(ipforward_name, ALEN(ipforward_name),
               pforward, &lforward, &tpforward, sizeof(int) ) == -1)
          return -1;
#endif
     return 0;
}

//* *********************************

int SetIPForwardingFlag (int forward)
{
     if(sysctl(ipforward_name, ALEN(ipforward_name),
	       NULL, NULL, &forward, sizeof(int)) == -1)
	  return -1;
     return 0;
}

/* ********************************************************************** */


#ifdef __cplusplus
}
#endif

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
