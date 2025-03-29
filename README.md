# DenpaBBS

## Required stack

DenpaBBS is tested on arch/openbsd.<br>
Web server: nginx/httpd<br>
DB: mariadb<br>
PHP: PHP8.2-PHP8.3<br>
<sub>to set up you would need like a lamp stack or similar like how mine is. the php version actually matters here as we are using fetures that are in 8.2+</sub>

## installation guide for OpenBSD

install the required packages :

```
pkg_add mariadb-server php php-mysqli php-gdb pecl83-pledge ffmpeg composer
```

php8.3 is what i am going with for this guide.
git clone it where ever you want. make sure you set the premisions so the www user can acsses all files and run things.
might need to run `chmod -R 770 denpaBBS` and `chown -R www:www ./denpaBBS`
since OpenBSD runs php in a chroot. you will need to copy ffmpeg and all of its dependecies into the chroot.
here is a script that can do that.

```
#!/bin/sh

# Define the paths
CHROOT_DIR="/var/www" #put in the correct path
FFMPEG_BIN=$(which ffmpeg)

# Function to copy files safely
safecopy() {
    src=$1
    dest=$2
    if [ -f "$src" ]; then
        doas mkdir -p "$(dirname $dest)"
        doas cp "$src" "$dest"
    else
        echo "File $src not found."
    fi
}

# Copy FFmpeg binary
safecopy "$FFMPEG_BIN" "$CHROOT_DIR$FFMPEG_BIN"

# Copy dependencies
for lib in $(ldd "$FFMPEG_BIN" | awk '{print $7}' | grep '^/'); do
    safecopy "$lib" "$CHROOT_DIR$lib"
done

# Copy /bin/sh if not present
safecopy "/bin/sh" "$CHROOT_DIR/bin/sh"

echo "All necessary files have been copied to $CHROOT_DIR."

```

now initalize and install the mysql server.
then start it up and run the secure instalation script.

```
mysql_install_db
rcctl start mysqld
mysql_secure_installation
```

log into mysql as root

```
mysql -u root -p
```

you will now need to create a database and a user account.
remeber the username and password as you will need that for the configs.

```mysql
CREATE DATABASE boarddb;
CREATE USER 'username'@'localhost' IDENTIFIED BY 'password';
GRANT ALL ON boarddb.* TO 'username'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

now with the database set we will set up the httpd server.<br>
edit `/etc/httpd.conf` and add the fallowing

```
server "example.com" {
	listen on * tls port 443

	root "/location/in/chroot/denpaBBS/"
	directory index index.php
	connection max request body 62914560

	# Serve static and threads content as-is
	location "/static/*" {
		root "/location/in/chroot/denpaBBS"
	}

	location "/threads/*" {
		root "/location/in/chroot/denpaBBS"
	}

	# Only allow route.php to be executed
	location "/route.php" {
		fastcgi {
			socket "/location/in/chroot/run/php-fpm.sock"
			strip 3 # this number denote where the php chroot starts if it is in subdirectorys in the web root
			#/www/php/run/php-fpm.sock would have to have 2 directorys trimmed off the front making it match php's chroot of /run/php.fpm
		}
	}

	# Everything else is rewritten to route.php
	location match ".*" {
		request rewrite "/route.php"
	}
}

```

what we need to do now is enable the php moduels we installed.<br>
edit the `/etc/php-8.3.ini` and find the extensions section and uncomment the fallowing<br>

```
extension=gd
extention=mysqli
```

while you are in this file, you should also change the max upload. by defualt php caps it to 2mb.

```
upload_max_filesize = 10M
post_max_size = 12M
```

now save that file. next you will want to link the pledge model to be useable.

`ln -s /etc/php-8.3.sample/pledge.ini /etc/php-8.3/pledge.ini`

now you need to allow data to come into the server
edit `/etc/pf.conf` and add this line

```
pass in on egress proto tcp from any to any port { 80 443 }
```

and run `pfctl -f /etc/pf.conf` to reload your firewall rules

```
pfctl -f /etc/pf.conf
```

now inside of denpaBBS. you are going to want to install the composter stuff. `composer install`

now you can enable and start all of the services<br>

```
rcctl enable php83_fpm mysqld httpd
rcctl start php83_fpm httpd
```

with everything set up. go to your website and go to install.php
fallow the instructions and then delete instal.php off your webserver and everything should be set!

# structure of the software

all acsses should go thu route.php. when you first install, all things will try to go into install.php.
once install is compleate, you will have .install_bypass createded. there will be a check preventing install.php from loading again, if the router is in place.

this route file is helpful as it will let us inject request paramiters and have a cleaner uri.

## common things

this software is not completed, but here is some common things that curently exist.
all of the routes should point to someplace in the /main/ directory.
this place will be where the main buissnes logic is located.

next we have /classes/ this place has the objects for common things board, post, filehandler, thread, and some other helper objects like hook and auth.
insiside of /classes/repos we have repo patteerns to get objects like post, threads, etc out of the data base.

/lib/ holds helper functions to do things, like admincontrols for functions for controling things like moving post deleting post, deleteing boards, etc

next we got /static/ this directory just hold static files, stuff here is gitignored so that if someone is running this software and wants to add there own images and stuff they can with out breaking compatabiulity.
infact there is a hand full of places where u can do quite a bit of customiazation.

thoese places will be named customization, and you will be able to put ur own software in them

apono installing, a file called customRoute.php will be made, that can be used if you as a sysadmin want to add custom pages to your software.
say you made a cool news feed script about newest gunpla anouncements. you can add routes there, and them make pages to it in /main/custom
and when you wish to gitpull, it wont break. or maybe your just have a seprate project into /main/custom you git clone there, and then you add custom routing so u can have a sub system of some sort <small>untested but i belive might work, try it!</small>

## unfinsihed things

now some there is things i just want and wish from this software i just have not made.

post editing, better ban system, public bans, fully using blade, emotes, and many other smaller things like that.
file handaler kinda sucks, a lot. ffmpeg is b0rked, image thumbnails and thumbnail pathing is also kinda broke.

things i really want to get done is, convert html that i have to use blade (that html class is a nightmare)
decouple more scripts i have for bbs.php and admin.php
thoese are turing into a mess.

# support

finaly, i dont really want much for you using my software, i got a bsd 3 clause. all i want is for you to leave a star and help me become a more recognized developer.
as of right now... highschool drop out with no ged gets me nothing.
当てはまるよ、友達。
