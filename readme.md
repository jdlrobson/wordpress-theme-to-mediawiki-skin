According to https://developer.wordpress.org/themes/getting-started/wordpress-licensing-the-gpl/ all Wordpress themes are GPL compliant.

"If you wish to submit your creation to the free theme repository on WordPress.org, it must be 100% GPL compliant, including CSS and image files. Because the freedoms spelled out in the GPL are at the heart of WordPress, we encourage developers to distribute their themes with a 100% GPL-compatible license."

Wordpress has thousands of usable themes available on
https://wordpress.org/themes/

This project aims to bridge the Wordpress theme development environment with the more simplistic MediaWiki skin development environment, allowing the MediaWiki community to benefit from the Wordpress theme environment.

# How it works

The `convert.php` script attempts to replicate the Wordpress environment but generates JavaScript, CSS and Mustache template files as output rather than an HTML page view.

The `init.mjs` script then converts these artifacts into skins using the `mw-skin-builder` npm library.

# How to use

Download the theme you want to transform and unzip it in the `themes` folder.

Then:
```
nvm use
npm install
npm start
```

Wordpress skins will be processed for assets that will be collected in the temporary
`output` folder (will eventually be hidden but useful for debugging).

This output folder is then put through the npm module `mediawiki-skins-cli` to generate skin folders inside the `WPMediaWikiSkins` folder.

TODO: 

Get Kandence generating by adding missing functions:
get_theme_file_path
