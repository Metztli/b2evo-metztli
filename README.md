b2evolution on OpenShift
=========================
b2evolution is a downloadable, open source (GPL licensed) powerful free CMS / content / blogs / photos galleries / community / forums / email marketing engine you can quickly install on Red Hat OpenShift online platform as a service (PaaS).

b2evolution is an open source security-conscious, full-featured alternative superseding WordPress.

More information can be found on the official b2evolution website at http://b2evolution.net

Running on OpenShift
--------------------

Create an account at https://www.openshift.com/

I assume that you have installed the rhc command line tools for your operating system, as guided in Getting Started With OpenShift:
https://developers.openshift.com/en/getting-started-overview.html

Create a PHP application. Evidently and to make your task ahead easier, create a working directory and change to that location.
All the rhc commands below are relative to your working directory location.

Below, please note that I will name the application b2evo, but you can select any (allowed) name desired.

rhc app create -a b2evo -t php-5.3

OR, alternatively,

rhc app create -a b2evo -t php-5.4

(since as of January 2014, OpenShift added PHP 5.4.)

A new directory named as your application (i.e. b2evo in this case) will be created at your current location. You may examine its content
for instance: ls -a b2evo/

Add mysql support to your application
    
rhc cartridge add -c mysql-5.1 -a b2evo

OR, alternatively,

rhc cartridge add -c mysql-5.5 -a b2evo

(since as of January 2014, OpenShift added MySQL 5.5.)

Make a note of the username, password, and host name (for reference purposes since your b2evolution instance will be provided with relevant values).

Change to your newly created directory -- which has the same name as your app:

cd b2evo/.

And remove the file:

rm -v index.php

Add this upstream b2evolution quickstart repo:

git remote add upstream -m master git://github.com/Metztli/b2evo-metztli.git

then try:

git pull -s recursive -X theirs upstream master

if it does not work, and output is similar to:

"fatal: refusing to merge unrelated histories"

it means your are using git 2.9+; then try:

git pull -s recursive --allow-unrelated-histories -X theirs upstream master

A subsequent dialog or editor may be activated prompting you to 'enter a commit message': you may simply close the dialog or editor.

Then push the repo upstream to OpenShift

git push

That's it, you can now instantiate your b2evolution application at:

http://b2evo-yourNameSpace.rhcloud.com

Please disregard if you get a message similar to:

..........................................................................................

The database is not installed yet!

The b2evolution files are present on your server, but it seems the database is not yet set up as expected.

[...]

"You cannot use the application before you finish configuration and installation.

MySQL error:
Table 'yourAppNameDB.evo_settings' doesn't exist(Errno=1146)"

Please use the installer to finish your configuration/installation now.

On most installations, the installer should be here (but I can't be sure since I have no config info available! :P)
..........................................................................................

It only means that the values were taken directly from your OpenShift environment.

Proceed to select (click) either the above message referenced 'here' link OR the 'Installer' link -- at the upper right options in the page -- to finish the b2evolution install.

Select your language from the upper left cascading menu.

Leave the default radio button selected: New Install

Select rectangle labeled 'Next' to proceed.

If you are not familiar with blogging in b2evolution, leave check mark on the 6 preselected sample collections/contents.

Then press the 'INSTALL!'-labeled button at your lower left. Wait a couple of seconds for installation to complete and you will be provided with your

default login username: admin

Please make a note of your random password and use it for your initial login procedure --you can change it upon logging in.

For security change your default login username and your email address.


-----------------------------


Huelmati [enjoy]!


Note: of course, if you used CNAME to point your (sub)domain name to your OpenShift application, then setup your b2evolution
application by accessing your (sub)domain URL.

Accordingly, if you have and want to point a domain name (or subdomain) to your b2evo application (recently created), then use form of command below:

rhc alias add b2evo b2evolution.yourDomainName.xyz

Don't forget that you must first modify the CNAME at your domain DNS service provider and it usually takes at least 12hours for it to propagate.

Thus, as an example to guide you in your CNAME settings:

b2evolution.yourDomainName.xyz		 should point to		b2evo-yourNameSpace.rhcloud.com
