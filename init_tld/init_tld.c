/*
	Initialise TLD
	By Martin COLEMAN (C) 2012-2014. All rights reserved.
	Redistribution and use in source and binary forms, with or without
	modification, are permitted provided that the following conditions are met: 

	1. Redistributions of source code must retain the above copyright notice, this
	   list of conditions and the following disclaimer. 
	2. Redistributions in binary form must reproduce the above copyright notice,
	   this list of conditions and the following disclaimer in the documentation
	   and/or other materials provided with the distribution. 

	THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
	ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
	WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
	DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
	ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
	(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
	LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
	ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
	(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
	SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

    TO BUILD:
	Compile via $CC -o init_tld init_tld.c -lsqlite3
    Where $CC can be gcc, clang or tcc.
	
    VERSION HISTORY:
	v0.1
	- Extract domain info from OZ_tld.sq3
	- Prepare outline of zone file
	- Prints information intended for zone file
	
	v0.2
	- Improved handling for output re-direction.
	
	v0.3
	- Improved initial opennic.TLD and register.TLD setup
	
	v0.4 - 2012-06-10
	- Full custom nameserver support is now implemented.
	
	v0.4a - 2013-04-06
	- Released under BSD 2 clause license.
    
    v0.5 - 2014-02-23
    - Removed redundant wording about old license.
    - Improved TLD templating.
    - Clarified compilation note above.
*/
#include <stdio.h>
#include <time.h>
#include <string.h>
#include <sqlite3.h>
/* #include "sqlite3.c" */
#define VERSION "0.5"
/* TLD CHANGE - Change these to suit */
#define TLD ".oz"
#define TLD_DB "oz_tld.sq3"
/* #define VERBOSE 1 */

sqlite3 *db;
sqlite3_stmt *res;
char *zErrMsg=0;
int rc;

int main(int argc, char *argv[])
{
	char sql_str[1024];
	int result=0;
	char *tld;
	char blank[10];
	blank[0]='\0';
	char *subdomain;
	char *subdomain_cp;
	char ns1[54];
	char ns2[54];
	char ns1_ip[16];
	char ns2_ip[16];

	#ifdef VERBOSE
	fprintf(stderr, "TLD Initialiser v%s (C) 2012 Martin COLEMAN.\n", VERSION);
	#endif
	if(argc<6)
	{
		#ifndef VERBOSE
		fprintf(stderr, "TLD Initialiser v%s (C) 2012 Martin COLEMAN.\n", VERSION);
		#endif
		fprintf(stderr, "Run: init_tld [TLD] [host] [email] [IP1] [IP2]\n");
		return 0;
	}
	
	/* we're running? ok, let's get some data happening. */
	time_t t = time(NULL);
	struct tm * now = localtime(&t);
	tld=argv[1];
	#ifdef VERBOSE
	fprintf(stderr, "Processing .%s ", tld);
	fprintf(stderr, "using serial: %d%d%d%d%d...", (now->tm_year+1900), (now->tm_mon+1), now->tm_mday, now->tm_hour, now->tm_min);
	#endif
	rc = sqlite3_open(TLD_DB, &db);
	if(rc)
	{
		fprintf(stderr, "Can't open domain database.\n");
		sqlite3_close(db);
		return 0;
	}
	
	/* start the template. ok, this is brute forcing it, but it works */
	printf("$ORIGIN .\n");
	printf("$TTL 86400\t; 1 day\n");
	printf("%-8s\t\tIN SOA %s. %s. (\n", tld, argv[2], argv[3]);
	/* printf("\t\t\t\t%d%02d%02d%02d%02d  ; serial\n", (now->tm_year+1900), (now->tm_mon+1), now->tm_mday, now->tm_hour, now->tm_min); */
	printf("\t\t\t\t%d%02d%02d%02d  ; serial\n", (now->tm_year+1900), (now->tm_mon+1), now->tm_mday, now->tm_hour);
	printf("\t\t\t\t10800\t; refresh\n");
	printf("\t\t\t\t3600\t; retry\n");
	printf("\t\t\t\t1209600\t; expire\n");
	printf("\t\t\t\t7200\t; minimum\n");
	printf("\t\t\t\t)\n");
	printf("\t\t\tNS\tns2.opennic.glue.\n");
	printf("\t\t\tNS\tns4.opennic.glue.\n");
	printf("\t\t\tNS\tns5.opennic.glue.\n");
	printf("\t\t\tNS\tns6.opennic.glue.\n");
	printf("\t\t\tNS\tns7.opennic.glue.\n");
	printf("\t\t\tNS\tns8.opennic.glue.\n");
	printf("\t\t\tNS\tns21.opennic.glue.\n");
	printf("$ORIGIN %s.\n", tld);

	/* do our preliminary setup */
	/* opennic */
	printf("opennic\t\t\tNS\tns1.opennic\n");
	printf("\t\t\tNS\tns2.opennic\n");
	printf("$ORIGIN opennic.%s.\n", tld);
	printf("ns1\t\t\tA\t%s\n", argv[4]);
	printf("ns2\t\t\tA\t%s\n", argv[5]);

	/* register */
	printf("$ORIGIN %s.\n", tld);
	printf("%-8s\t\tNS\tns1.opennic\n", "register");
	printf("\t\t\tNS\tns2.opennic\n");
	/* end of basic setup */

	sprintf(sql_str, "SELECT domain, ns1, ns2, ns1_ip, ns2_ip FROM domains ORDER BY domain ASC");
	rc = sqlite3_prepare_v2(db, sql_str, 1024, &res, 0);
	if(rc != SQLITE_OK)
	{
		printf("The package database file is corrupt!\n");
		sqlite3_free(zErrMsg);
		sqlite3_close(db);
		return 0;
	}
	while(1)
	{
		result=sqlite3_step(res);
		if(result==SQLITE_ROW)
		{
			if( (strcmp(sqlite3_column_text(res, 0), "register")) && (strcmp(sqlite3_column_text(res, 0), "opennic")))
			{
				if( (strlen(sqlite3_column_text(res, 1))>7) && (strlen(sqlite3_column_text(res, 2))>7))
				{
				printf("%-16s\tNS\t%s.\n", sqlite3_column_text(res, 0), sqlite3_column_text(res, 1));
				printf("\t\t\tNS\t%s.\n", sqlite3_column_text(res, 2));
				if(sqlite3_column_text(res, 3))
				{
					sprintf(ns1, "%s", sqlite3_column_text(res, 1));
					sprintf(ns2, "%s", sqlite3_column_text(res, 2));
					sprintf(ns1_ip, "%s", sqlite3_column_text(res, 3));
					sprintf(ns2_ip, "%s", sqlite3_column_text(res, 4));
					/* mega hack following */
					/* printf("NS1: [%s]\nNS2: [%s]\n", ns1_ip, ns2_ip);*/ /* FOR DEBUGGING ONLY */
					if(strstr(ns1, TLD))
					{
						printf("$ORIGIN %s.%s.\n", sqlite3_column_text(res, 0), tld);
						subdomain_cp=strdup(ns1);
						subdomain=strtok(subdomain_cp, ".");
						/* subdomain=strtok(NULL, subdomain); */
						printf("%-16s\tA\t%s\n", subdomain, ns1_ip);
						subdomain_cp[0]='\0';
						subdomain[0]='\0';

						subdomain_cp=strdup(ns2);
						subdomain=strtok(subdomain_cp, ".");
						/* subdomain=strtok(NULL, subdomain); */
						printf("%-16s\tA\t%s\n", subdomain, ns2_ip);
						subdomain_cp[0]='\0';
						subdomain[0]='\0';
					printf("$ORIGIN %s.\n", tld);
					}
					ns1_ip[0]='\0';
					ns2_ip[0]='\0';
				}
				}
			}
		} else {
			break;
		}
	}
	printf("\n");
	sqlite3_finalize(res);
	sqlite3_close(db);
	#ifdef VERBOSE
	fprintf(stderr, "Done.\n");
	#endif
	return 0;
}
