# Bart

*A collection of build and release tools.*

A brief overview of the tools included are:

+ A stackable PHP Autoload register
+ PHP dependency injection (Diesel)
+ A generic and configurable git hook framework that may be used singularly used by several git projects hosted on the same machine
+ A shell class and mock shell class that allow the tester to mock out PHP global system functions, including those with PHP parameter references. E.g. exec($command, &$output, &$status);

# Install
Bart can be installed with the [composer](http://getcomposer.org/) package manager.

Put this in your project dependencies:

```javascript
{
  "require": {
    "box/bart" : "dev-master"
  }
}
```

Use this PHP code in your project's bootstrap:

```php
$bartPath = "$root/vendor/box/bart/src/Bart/bart-common.php";
 
if (!file_exists($bartPath)) {
    echo 'Cannot find required Bart in local path.' . PHP_EOL;
    echo 'Have you run `composer install`?' . PHP_EOL;
    echo '' . PHP_EOL;
    echo 'See https://github.com/box/bart' . PHP_EOL;
    echo '' . PHP_EOL;
    exit(1);
}
 
 
require $bartPath;
  
// Setup autoloading using Bart
Bart\Autoloader::register_autoload_path("$root/src"); // Add this project's namespace
Bart\Autoloader::register_autoload_path("$root/vendor/box/bart/src"); // Add Bart's namespace.
```
## System Checkout

Alternatively, if you don't use composer. You can setup a system clone of Bart for use in other scripts, for example the pre-receive hook described below.

Use the following script to install a cron to periodically fetch and reset from git hub.

```
$bart_home/maint/install-cron.sh --help
```


# Some Features

## PHP Autoloader

The Bart Autoloader provides a stackable autoload register. This allows the developer to provide
several entry points into autoload directory trees. Read more at, 
http://developers.blog.box.com/2011/10/27/php-autoloader-building-your-own-register/

## Diesel

Diesel is a simple and useful dependency injection framework for PHP. It does not require major changes to your code base, nor does it require odd configurations in non-PHP.

Check out http://box.github.com/eng-services/blog/2012/05/03/introducting-diesel-php-dependency-injection/ for a brief description. Feel free to tweet at us @BoxEngServices with questions.

See ```./src/Bart/Diesel.php``` and ```./test/Bart/Diesel_Test.php```

## Pre-Receive

The pre-receive hook is designed to be symlinked from your upstream repo to a
local checkout of bart on disk. It gathers repository information from the path
and sends that to a pre-receive script which has been generalized to work for
all of your repositories. It then runs all configured hooks for the repo.

In order to configure behavior for the hook, configure 

```
$bart_home/conf/hooks.conf
```


### Setup

    cd /usr/local/lib/bart
    
    # Create the symlink to the pre-receive script
    # ...which will, in this case, verify received commits for repository "your proj"
    cd /git/projects/your-proj
    ln -s /etc/bart/hooks/pre-receive ./hooks/pre-receive


### Stop The Line

This hook checks the health of a jenkins job. See below for example configuration.


    [jenkins]
    class = Stop_The_Line
    host = jenkins.internal-ip.company.com
    
    ; By default, the job name is inferred from the name of the repo
    ; Otherwise, use this config to override
    job_name = 'the-build'
    
    ; Show progress as hook runs
    verbose = yes

    enabled = yes


Will check the current health of the the "the-build" on the jenkins server
at jenkins.internal-ip.company.com. If the job is currently unhealthy, the 
commit will be rejected unless the commit message contains the key "{buildfix}"

### Gerrit Approved?

This hook will verify the latest commit has been approved in Gerrit. See below
for example configuration.


    [gerrit]
    class = Gerrit_Approved
    host = gerrit.internal-ip.company.com
    port = 29418
    
    ; Show progress as hook runs
    verbose = yes

    enabled = yes


[![Build Status](https://secure.travis-ci.org/box/bart.png?branch=master)](http://travis-ci.org/box/bart)


