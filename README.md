# Bart

*A collection of build and release tools.*

## PHP Autoloader

The Bart Autoloader provides a stackable autoload register. This allows the developer to provide
several entry points into autoload directory trees. Read more at, 
http://developers.blog.box.net/2011/10/27/php-autoloader-building-your-own-register/


## Pre-Receive

The pre-receive hook is designed to be symlinked from your upstream repo to a
local checkout of bart on disk. It gathers repository information from the path
and sends that to a pre-receive script which has been generalized to work for
all of your repositories. It then runs all configured hooks for the repo.

In order to configure behavior for the hook, configure 

```$bart_home/conf/hooks.conf```


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
    
    enabled = yes




