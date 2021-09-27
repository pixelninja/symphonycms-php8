# PHP 8 Upgrade Instructions

These are the files I have used to upgrade existing Symphony CMS installs to PHP 8.0 compatibility.

As always, make sure your site is up to date with your master branch before making any of these changes.

The instructions below remove all extensions as submodules and adds them as directories, for obvious reasons.

This also contains a Theme plugin which I recommend as it makes Symphony look more modern.

**I will not be maintaining this repository.**


#### For sites using a version earlier than 2.7.10

The site needs to be upgraded to the 2.7.10 before moving on.

1. Copy each of the following folders and files from  `2.7.10 upgrade files` and paste into the root directory of the site:
   1. `index.php`
   2. `install`
   3. `symphony`
   4. `vendor`
2. In the browser, type `/install/` at the end of the project URL and follow the upgrade prompts. 
   *Do not delete this folder after upgrading. It will need to be run again on the new server before launch*
3. Check the frontend and backend of the site are still working correctly


#### Upgrading 2.7.10 to PHP 8.0 compatibility

Switch to PHP 8

I recommend creating a new branch for this (`git checkout -b php8`), however it's up to you. Use master if you wish.

- Replace `symphony` folder with updated directory in this repo
- Copy `extensions` folder from your site to your desktop or somewhere temporary
- Remove all submodules 
  `git submodule deinit -f .`
- Delete `extensions` folder
  `rm -rf extensions`
- Remove modules from git directory 
  `rm -rf .git/modules/*`
- We need to commit and push these changes before continuing. This is **important** otherwise submodules won't be properly removed.
  `git commit -am "update Symphony to PHP8.0 compatibility. Remove extensions as submodules"`
  `git push origin php8`
- Move the `extensions` folder you copied onto the desktop back into site folder. 
- Remove all `.git` files from extension folders 
  `rm -rf extensions/**/.git`
- And remove all `.gitignore` files from extension folders 
  `rm -rf extensions/**/.gitignore`
- Replace extension folders within `extensions` of the site folder with corresponding folder within this upgrade directory. Some extensions to ignore which are unique per site a lot of the time are:
  - `backend_add_script` - Only the `assets` folder will be custom
  - `richtext_tinymce` - Only the `assets` folder will be custom
- Again we need to commit now just to make sure submodules don't cause us a headache later
  `git add extensions install`
  `git commit -am "update extensions to PHP8.0 compatibility and add extensions as directories"`
  `git push origin php8`
- Log into the admin area
  - Go to the extensions page
    - Update any that require it
    - install `Theme: Modern` and `Are You Sure?` Extensions. 
    - Some other helpful extensions to consider which may not be currently installed are:
      - Anti Brute Force
      - Media Library
      - Tracker
    - All the extensions that are greyed out can be removed from the `extensions` directory. Update any that have prompts to do so. Commit and push once deleted.
      `git commit -am "remove unused extensions"`
      `git push origin php8`
  - Check every section is working as expected and fix any bugs that come up. Google is your friend here but some common ones are:
    - `Array and string offset access syntax with curly braces is no longer supported` - simply need to change curly braces to square brackets, e.g `$array{0}` to `$array[0]`
    - `Undefined array key` - means a script is trying to access an array key that hasn't been created. Most of the time simply adding in above it `$array[key] = $array[key] ?? null;` will fix the issue but be careful as it could sometimes be more complicated than this. Use your judgement or ask the community for help.

The site should now be working on PHP 8.

Commit all changes and push to the new branch.

`git commit -am "update Symphony and extensions to PHP8.0 compatibility"`

`git push origin php8`


#### Pushing to production

**For sites that needed to be upgraded to 2.7.10 first**

On your php 8.0 server, switch to the php8 branch and pull all changes in. The `install` script will need to be run again as database changes are made. In my updates, this was fairly simple. I could then log in and remove the installer, and update extensions again.

**For sites that were already at 2.7.10**

You can remove the installer, and when you push to the server simply install/update extensions again. Or if replace the database is an option then do that. Whatever works for your flow.
