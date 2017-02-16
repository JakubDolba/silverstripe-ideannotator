##Installation
Either run ```composer require axyr/silverstripe-ideannotator --dev```

Or add ```axyr/silverstripe-ideannotator: "*"``` to `require-dev` in your composer.json file

Or download and add it to your root directory.


##Config
This module is disabled by default and I recommend to only enable this module in your local development environment, since this module changes the file content of the Dataobject and DataExtension classes.

You can do this, by using something like this in your mysite/_config.php :

```php
if($_SERVER['HTTP_HOST'] == 'mysite.local.dev') {
    Config::inst()->update('IDEAnnotator\DataObjectAnnotator', 'enabled', true);
}
```

Even when the module is enabled, the generation will only work in a dev environment. Putting a live site into dev with ?isDev will not alter your files.

When enabled IdeAnnotator generates the docblocks on dev/build for mysite only.

You can add extra module folders with the following config setting :

```php
Config::inst()->update('IDEAnnotator\DataObjectAnnotator', 'enabled_modules', array('mysite', 'otherfolderinsiteroot'));
```
or
```
yml
---
Only:
    environment: 'dev'
---
IDEAnnotator\DataObjectAnnotator:
    enabled_modules:
      - mysite
      - otherfolderinsiteroot
````
