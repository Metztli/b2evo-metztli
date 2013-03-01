b2evolution on OpenShift
=========================
b2evolution is a downloadable, open source (GPL licensed) powerful free blog/CMS engine you can install on Red Hat OpenShift online
platform as a service (PaaS).

b2evolution is an open source security-conscious alternative to WordPress.

More information can be found on the official b2evolution website at http://b2evolution.net

Running on OpenShift
--------------------

Create an account at http://openshift.redhat.com/

I assume that you have installed the rhc command line tools for your operating system, as guided in Getting Started With OpenShift:
https://openshift.redhat.com/community/get-started

Create a PHP application. Evidently and to make your task ahead easier, create a working directory and change to that location.
All the rhc commands below are relative to your working directory location.

Below, please note that I will name the application b2evo, but you can select any (allowed) name desired.

rhc app create -a b2evo -t php-5.3

Add mysql support to your application
    
rhc cartridge add -c mysql-5.1 -a b2evo

Make a note of the username, password, and host name as you will need to use these to complete the b2evolution installation on OpenShift.

Add this upstream b2evolution quickstart repo:

cd b2evo/php

rm -rf *

git remote add upstream -m master git://github.com/Metztli/b2evo-metztli.git

git pull -s recursive -X theirs upstream master

Then push the repo upstream to OpenShift

git push

That's it, you can now checkout your application at:

http://b2evo-yourNameSpace.rhcloud.com

Note: of course, if you used CNAME to point your (sub)domain name to your OpenShift application then setup your b2evolution
application by accessing your (sub)domain URL.

Accordingly, if you have and want to point a domain name (or subdomain) to your b2evo application (recently created), then use form of command below:

rhc alias add b2evo b2evolution.yourDomainName.xyz

Don't forget that you must first modify the CNAME at your domain DNS service provider and it usually takes at least 12hours for it to propagate.

Thus, as an example to guide you in your CNAME settings:

b2evolution.yourDomainName.xyz	  should point to  	b2evo-yourNameSpace.rhcloud.com
-----------------------------


Huelmati [enjoy]!

[1] Note: for mysql database in b2evolution, take special note of string similar to Connection URL: mysql://abc.opq.stu.xyz:3306/ provided

to you by Openshift. You will need to input similar as abc.opq.stu.xyz:3306 (not localhost) into install b2evolution MySQL Host/Server: field.
 

