=== wp2blosxom ===
Contributors: fliptoad
Tags: blosxom, export, files, zip, backup
Requires at least: 2.1
Tested up to: 2.7
Stable tag: trunk

Exports all your posts to a zip file containing a blosxom style directory
hierarchy of posts.

== Description ==

This plugin will allow you to export all your posts to a zip file where each
post is a txt file located under a directory named for its category, just as 
[blosxom][] is set up to be able to use.

  [blosxom]: http://blosxom.ookee.com/blog/

This plugin will make it easy to switch to blosxom or to get your posts into a
good format for archiving, but unlike the xml and database dumps you can 
export to by default in wordpress, your entries can be easily read and edited
on your own computer.

This plugin appears as a top level menu page on your wordpress admin backend.

It will get the content and title without any formatting so things like 
markdown work. It does not back up your images so you may have to work a 
little to obtain a fully working blosxom blog set up with these posts. it 
probably would involve placing the images from the `/wp-content/uploads` 
directory someplace on your server. 

The modification time of the entry files are changed to whenever the entries 
were published. I've also included the option to export a meta-creation_date 
in each entry. To use this in blosxom you will need to install the 
[entriescache][] plugin.

  [entriescache]: http://blosxom.ookee.com/blosxom/plugins/v2/entriescache-v0i92

== Installation ==

before installing make sure you meet the requirements. they are:

-   the user php runs under must have write access to `/wp-content` directory.
    (my plugin writes all zips to /wp-content/wp2blosxom)

-   the zip program must be in your path and php must be able to use the 
    `system()` function. If you're on windows you can get the command line zip
    program [here][zip].

  [zip]: http://gnuwin32.sourceforge.net/packages/zip.htm

-   I've only tried this on wp v2.6 and v2.7 so i dont know if this will work
    on other versions.

* * * * *    

1.  copy `wp2blosxom` directory to `/wp-content/plugins`

2.  activate the plugin in wordpress's `plugins` menu

3.  you should notice a new menu item on your admin area called `wp2blosxom`. 
    click this to be shown a page where you can kick off creation of zips and
    to delete zips you've already created.

== Frequently Asked Questions ==

= what is blosxom? =

blosxom is blogging software written in perl with lots of features and addons,
that has a really simple way of adding entries -- as text files filed under 
directories named for the category which they belong. These text files can be 
filtered with plugins such as [markdown][], just as they can on wordpress.

  [markdown]: http://daringfireball.net/projects/markdown/

For more info, see the the [official site][] or the more up-to-date 
[blosxom user group][].

  [official site]: http://blosxom.sourceforge.net/
  [blosxom user group]: http://blosxom.ookee.com/blog/

== Screenshots ==

1.  the menu page to create, delete, and download blosxom zips.

2.  file structure of resulting zip file.
