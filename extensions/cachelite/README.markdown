# CacheLite

## Installation 

1. Upload the 'cachelite' folder in this archive to your Symphony 'extensions' folder.
2. Enable it by selecting the "CacheLite", choose Enable from the with-selected menu, then click Apply.
3. Go to System > Preferences and enter the cache period (in seconds).
4. The output of your site will now be cached.


## Usage

### Excluding pages

By default all pages are cached. You can exclude URLs from the cache by adding them to the list of excluded pages in System > Preferences. Each URL must sit on a separate line and wildcards (`*`) may be used at the end of URLs to match *everything* below that URL.

Excluded pages are assumed to originate from the root. All the following examples will resolve to the same page (providing there are none below it in the hierarchy):

	/about-us/get-in-touch/*
	http://root.com/about-us/get-in-touch/
	about-us/get-in-touch*
	/about-us/get*

Note that caching is *not* done for logged in users. This lets you add administrative tools to the frontend of your site without them being cached for normal users.

### Flushing the cache

Caching is done on a per-URL basis. To manually flush the cache for an individual page simply append `?flush` to the end of its URL. To flush the cache for the entire site you just need to append `?flush=site` to any URL in the site. You *must* be logged in to flush the cache.

You can also remove cache files using your FTP client: navigate to `/manifest/cache` and remove the files named as `cache_{hash}`.

The cache of each page will automatically flush itself after the timeout period is reached. The timeout period can be modified on the System > Preferences page.

### Flushing the cache when content changes

You can selectively flush the cache when new entries are created or an entry is updated. Updates through both the backend and frontend Events are supported. To flush the cache when entries are modified in the Symphony backend navigate to System > Preferences and tick the "Expire cache when entries are created/updated through the backend?" checkbox. When an entry is modified, one of two outcomes are achieved:

a) **When a brand new entry is created**, the cache will be flushed for any pages that show entries from *the entry's parent section*.  
For example if you have an Articles section which is used to display a list of recent article titles on the Home page; a list of articles on an Articles Index page; and another page to read an Article; the cache of all three pages will be flushed when a new Article entry is created.

b) **When an existing entry is edited**, the cache will be flushed for any pages that display *this entry*.  
In the above example, if the article being edited is very old and no longer features on the Home page or Articles Index page, only the specific instance of the Aricle view page for this entry will be flushed. Other Article view pages remain cached.

The same conditions are provided for frontend Events through the use of Event Filters. To add this functionality to your event, select one or all of the CacheLite event filters when configuring your event and trigger them using values in your HTML form:

a) **"CacheLite: expire cache for pages showing this entry"**  
When editing existing entries (one or many, supports the Allow Multiple option) any pages showing this entry will be flushed. Send the following in your form to trigger this filter:

	<input type="hidden" name="cachelite[flush-entry]" value="yes"/>

b) **CacheLite: expire cache for pages showing content from this section**  
This will flush the cache of pages using any entries from this event's *section*. Since you may want to only run it when creating new entries, this will only run if you pass a specific field in your HTML:

	<input type="hidden" name="cachelite[flush-section]" value="yes"/>

c) **CacheLite: expire cache for the passed URL**
  
This allows you to selectively flush the cache during Event execution, which is useful if you want to expire the cache as new entries are added but don't want to flush the whole *section*. This filter will only run if you pass a specific field in your HTML:
  
    <input type="hidden" name="cachelite[flush-url]" value="/article/123/"/>

If you pass this field with no value, it will default to the *current* URL. That is, from a page at <http://domain.tld/article/123/>, submitting the following:

	<input type="hidden" name="cachelite[flush-url]"/>

Would have the same result as the previous example.

#### Large websites

Deleting lots of cache entries may make the backend slow.
If you feel like you are waiting too long for entries to save, it may be because deleting files on disk is slow.
You can change the cache invalidation strategy and use a cron job to purge the cache.
You first need to manually edit your config.php file, to set the 'clean-strategy' value to 'cron'

	###### CACHELITE ######
	'cachelite' => array(
		...
		'clean-strategy' => 'cron',
	),
	########

Then, your need to configure the cron job that will purge the cache in the background, at every 5 minutes

	*/5 * * * * php /path/to/website/extensions/cachelite/cron/delete_invalid.php

### Bypassing the cache

Extensions can tell cachelite to bypass the cache on particular requests. Extensions must implement the `CacheliteBypass` delegate.

The delegate is as follows:

```php
/**
 * Allows extensions to make this request
 * bypass the cache.
 *
 * @delegate CacheliteBypass
 * @since 2.0.0
 * @param string $context
 *  '/frontend/'
 * @param bool $bypass
 *  A flag to tell if the user is logged in and cache must be disabled
 */
Symphony::ExtensionManager()->notifyMembers('CacheliteBypass', '/frontend/', array(
    'bypass' => &$cachebypass,
));
```

By changing the value of `$context['bypass']` to `true` the request will not use the cache.
