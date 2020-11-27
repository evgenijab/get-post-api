=== Get Posts from API ===
Tags: api, json, dinamicly, insert post, insert attachment, insert category, insert user, posts, get posts
Requires at least: 4.6
Tested up to: 5.5
License: GPLv2 or later

Custom plugin for inserting posts from REST API via json file. Easy and Simple.

== Description ==

Gets the latest 5 posts from a blog via the REST API. Inserting new posts, users, categories, attachments.
Automatically inserting posts with all of their meta as well as users, categories and attachment.
Associating the users, categories and attachments with the post.

Major features include:

* Automatically insert post from a json file.
* Inserting and associating the post with the coresponding featured image, category(s), user(author) from the json file(API)
* Easy to customize

== Installation ==

Upload the plugin to your blog install it and activate it.

You're done!
Let the plugin take care of the rest!

== IMPORTANT INFO ==
If featured images are not displaied and $image_data ($image_data = file_get_contents($image_url)) on line 102 returns false:
- check if allow_url_fopen = On in your php.ini file
- try with putting php_value allow_url_fopen On in your .htaccess file
- use curl
