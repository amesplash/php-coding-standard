# amésplash Coding Standard

The amésplash coding standard definition for [PHP_CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer) used at amésplash. Our coding standard is heavily based on [Slevomat Coding Standard](https://github.com/slevomat/coding-standard) and [Doctrine Coding Standard](https://github.com/doctrine/coding-standard).


``` bash
$ php composer require --dev amesplash/php-coding-standard
```

You now use it to sniff your files like below:

``` bash
$ ./vendor/bin/phpcs --standard=Amesplash /path/to/file/or/folder/to/sniff.php
```

Or to do automatic fixes using phpcbf like below:

```
$ ./vendor/bin/phpcbf --standard=Amesplash /path/to/file/or/folder/to/fix.php
```

# Per project ruleset
To enable the amésplash coding standard for your project, create a phpcs.xml.dist file with the following content:

```xml
<?xml version="1.0"?>
<ruleset>
    <arg name="basepath" value="."/>
    <arg name="extensions" value="php"/>
    <arg name="parallel" value="80"/>
    <arg name="cache" value=".phpcs-cache"/>
    <arg name="colors"/>

    <!-- Ignore warnings, show progress of the run and show sniff names -->
    <arg value="nps"/>

    <!-- Directories to be checked -->
    <file>src</file>

    <!-- Reference the amésplash coding standard -->
    <rule ref="Amesplash"/>
</ruleset>
```
