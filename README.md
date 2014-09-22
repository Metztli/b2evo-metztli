b2evolution on OpenShift
=========================
b2evolution is a downloadable, open source (GPL licensed) powerful free blog/CMS engine you can install on Red Hat OpenShift online
platform as a service (PaaS).

b2evolution is an open source security-conscious -- full-featured -- alternative to WordPress.

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

Add this upstream b2evolution quickstart repo:

cd b2evo/.

rm -v index.php

git remote add upstream -m master git://github.com/Metztli/b2evo-metztli.git

git pull -s recursive -X theirs upstream master

Then push the repo upstream to OpenShift

git push

That's it, you can now instantiate your b2evolution application at:

http://b2evo-yourNameSpace.rhcloud.com

Please disregard if you get a message similar to:

"You cannot use the application before you finish configuration and installation.

MySQL error:
Table 'yourAppNameDB.evo_settings' doesn't exist(Errno=1146)"

It only means that the values where taken directly from your OpenShift environment.

Proceed to click either of the 'here' links to finish the b2evolution install.

Select your language from the upper left cascading menu.

If you are not familiar with blogging in b2evolution, allow the preselected defaults to install three(3) sample blogs.

Then press the 'GO!'-labeled button at your lower left. Wait a couple of seconds and you will be provided with your

default login username: admin

please make a note of your random password and use it for your initial login procedure --you can change it upon logging in.

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
