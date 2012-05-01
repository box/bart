# Bart

*A collection of build and release tools.*

A brief overview of the tools included are:

+ A stackable PHP Autoload register
+ PHP dependency injection (Diesel)
+ A shell class and mock shell class that allow the tester to mock out PHP global system functions, including those with PHP parameter references. E.g. exec($command, &$output, &$status);
+ A generic and configurable git hook framework that may be used singularly used by several git projects hosted on the same machine

## PHP Autoloader

The Bart Autoloader provides a stackable autoload register. This allows the developer to provide
several entry points into autoload directory trees. Read more at, 
http://developers.blog.box.com/2011/10/27/php-autoloader-building-your-own-register/

## System Checkout

It can be effective to have a system clone of Bart for use in other scripts, for example the pre-receive hook described below.

Since system checkouts are not really owned or kept up to date by anyone, you'll need a cron to periodically fetch and reset from git hub. For convenience, we've included a script to install a cron for you.

```
$bart_home/maint/install-cron.sh --help
```

### Composer

Bart supports the composer dependency format, https://github.com/composer/composer, which can serve as an appealing alternative to crons.

## Diesel

See ```./lib/Diesel.php``` and ```./test/lib/Diesel_Test.php```

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





