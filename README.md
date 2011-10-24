Bart
====

*A collection of build and release tools.*

Stop The Line
-------------

```./hooks/fail-if-job-unhealthy.php --domain jenkins.com --job the-build "A short commit message"
```

Will check the current health of the the "the-build" on the jenkins server
at jenkins.com. If the job is currently unhealthy, the commit will be rejected
unless the commit message contains the key "{buildfix}"



Pre-Receive
-----------

The pre-receive git hook is an example of where to plug in the stop the line
script. Hook it up to the git pre-receive hook of your upstream repository via
a symlink and then define your jenkins host in your environment file. The name
of the jenkins job, by default, is extracted via the name of your git upstream
project.

```
cd /etc/bart
# Configure your personal environment
echo "declare JENKINS='jenkins.internal-ip.company.com'" >> rc/env

# Create the symlink to the pre-receive script
# ...which will, in this case, verify the health of job "your proj"
cd /git/projects/your-proj
ln -s /etc/bart/hooks/pre-receive ./hooks/pre-receive
```

