#!/bin/sh
# OpenNIC MUD4TLD operations script.
# By Martin COLEMAN (C) 2012. mud4tld@mchomenet.info.
# Can be freely used by the OpenNIC community.
# To install change the following to appropriate settings for your TLD and configuration,
# especially the OpenNIC suite, and run. A cron job could be handy too.

# change these to suit. they should be self-explanatory.
TLD_DB=/var/www/opennic.oz/OZ_tld.sq3
ZONE_DIR=/etc/bind/zones/
OPENNIC_SUITE=/home/USER/opennic_tools/
PRIMARY_IP=96.44.164.100
SECONDARY_IP=96.44.164.101
MY_EMAIL=hostmaster.opennic.oz
MY_HOST=ns1.opennic.oz
MY_TLD=oz
# end of modifications

cd $OPENNIC_SUITE

echo -n "Copying TLD database..."
cp $TLD_DB $OPENNIC_SUITE
echo "Done"

echo -n "Generating TLD zone file..."
cd $OPENNIC_SUITE
./init_tld $MY_TLD $MY_HOST $MY_EMAIL $PRIMARY_IP $SECONDARY_IP > $MY_TLD.zone
echo "Done"

echo -n "Installing new zone file..."
cp $MY_TLD.zone $ZONE_DIR
echo "Done"

echo -n "Restarting BIND..."
/etc/init.d/bind reload
echo "Done"
