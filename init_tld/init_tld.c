/*
	Initialise TLD
	By Martin COLEMAN (C) 2012. All rights reserved.
	Compile via gcc -o init_tld main.c -lsqlite3
	Tiny C Compiler should work well too.
	
	v0.1
	- Extract domain info from OZ_tld.sq3
	- Prepare outline of zone file
	- Prints information intended for zone file
	
	v0.2
	- Improved handling for output re-direction.
	
	v0.3
	- Improved initial opennic.TLD and register.TLD setup
*/
#include <stdio.h>
#include <sqlite3.h>
#include <time.h>

#define VERSION "0.3"
// #define VERBOSE 1

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
	rc = sqlite3_open("OZ_tld.sq3", &db);
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
	printf("\t\t\t\t%d%d%d%d%d  ; serial\n", (now->tm_year+1900), (now->tm_mon+1), now->tm_mday, now->tm_hour, now->tm_min);
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
	printf("opennic\t\t\tNS\tns1.opennic.%s\n", tld);
	printf("\t\t\tNS\tns2.opennic.%s\n", tld);
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
				printf("%-16s\tNS\t%s.\n", sqlite3_column_text(res, 0), sqlite3_column_text(res, 1));
				printf("\t\t\tNS\t%s.\n", sqlite3_column_text(res, 2));
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
