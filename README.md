# Bart

*A collection of build and release tools.*

Bart is the library of PHP classes that we use as the base of our command line tools.

A quick look at some its features:

+ An intelligent PHP **Autoload** class that supports both namespaces and classic under-scored names (or a mix of the two)
+ An intuitive, lightweight **dependency injection** framework that has been tested and tried against thousands of lines of test code and situations
+ A PHP client to the **Jenkins** API
+ A PHP client to the **Gerrit** API
+ An easy to use SSH client wrapper
+ A generic and configurable git hook framework that may be shared by several git projects hosted on the same machine
+ A `Shell` class and mock shell class that allow the tester to mock out several of the PHP global system functions, including those with PHP reference parameter. E.g. `string exec ( string $command [, array &$output [, int &$return_var ]] );`
++ A `Command` class that completely replaces the need to use `exec() or shell_exec()` at all

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
$bartPath = "$projectRoot/vendor/box/bart/src/Bart/bart-common.php";
 
if (!file_exists($bartPath)) {
    echo <<<MSG
Cannot find required Bart in local path
Have you run `composer install`?

See https://github.com/box/bart

MSG;

    exit(1);
}

require $bartPath;
  
// Configure the PHP __autoload using Bart
Bart\Autoloader::register_autoload_path("$root/vendor/box/bart/src"); // Add Bart's namespace.
Bart\Autoloader::register_autoload_path("$root/src"); // Add this project's namespace
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

## Shell and Command

Replace your uses of `shell_exec` and `exec` with the much safer `Command` class. `Command` will shell escape all your command line arguments as well as your overall command. This makes building and running commands much safer. Failed commands will raise exceptions. Successful command output can be returned as arrays or a single string. See usage instructions at http://asheepapart.blogspot.com/2013/05/say-goodbye-to-php-shellexec-and-php.html

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


    // BART_DIR/etc/php/hooks.conf
    [gerrit]
    ; Show progress as hook runs
    verbose = yes
    enabled = yes
    
    // BART_DIR/etc/php/gerrit.conf
	[gerrit]
	; Required. The host running the service
	host = gerrit.example.com

	[www]
	; Valid options: https (default), http
	scheme = http
	; default (empty)
	port = 8080

	[ssh]
	; Defaults to 29418
	port = 29418
	; User and key file to use for ssh connections to the Gerrit server
	; Generally, this user and key file should be managed via a tool like Puppet
	user = gerrit
	key_file = "/home/gerrit/.ssh/id_rsa"





[![Build Status](https://secure.travis-ci.org/box/bart.png?branch=master)](http://travis-ci.org/box/bart)


