<?php
namespace Bart\GitHook;

use Bart\Configuration\ProjectConfiguration;

/**
 * Configurations for Git Hooks
 */
class GitHookConfig extends ProjectConfiguration
{
	/**
	 * @return string Sample of how configuration is intended to be defined
	 */
	public function README()
	{
		return <<<README
[general]
; Used to determine which refs to run git hooks on. Full ref must be specified.
valid_refs = 'refs/heads/master'

[pre_receive]
hook_actions = '\Bart\GitHook\StopTheLineTravis'

[post_receive]
hook_actions = '\Bart\GitHook\JiraComment', '\Bart\GitHook\GerritAbandon'

[jira]
; Used by JiraComment Hook Action
; %s will be replaced with commit revision hash
comment_template = 'Commit %s pushed to JIRA. See online at https://git.example.com/?h=%s'

[jenkins]
; Used by StopTheLineJenkins Hook Action to allow
; commits that have this directive to go through,
; when the build is broken. Defaults to {buildfix}.
build_fix_directive = '{buildfix}'

[notifications]
; Optional email address to notify when emergencies are pushed
emergency_notification_email = emergencies@example.com
subject = 'Emergency push notification'
body = 'An emergency change has been pushed out.'

README;
	}

	/**
	 * @return \string[] List of FQCN's of each GitHookAction to be run on pre-receive
	 */
	public function getPreReceiveHookActions()
	{
		return $this->getArray('pre_receive', 'hook_actions', [], false);
	}

	/**
	 * @return \string[] List of FQCN's of each GitHookAction to be run on post-receive
	 */
	public function getPostReceiveHookActions()
	{
		return $this->getArray('post_receive', 'hook_actions', [], false);
	}

	/**
	 * @return string Template string to be sent to sprintf
	 */
	public function jiraCommentTemplate()
	{
		return $this->getValue('jira', 'comment_template');
	}

	/**
	 * @return string Build fix directive used to allow commits that fix builds to go through
	 */
	public function jenkinsBuildFixDirective()
	{
		return $this->getValue('jenkins', 'build_fix_directive', '{buildFix}', false);
	}

    /**
     * @return \string[] List of refs to run git hooks on
     */
    public function getValidRefs()
    {
        return $this->getArray('general', 'valid_refs');
    }

    /**
     * @return string email address to send notifications to
     */
	public function getEmergencyNotificationEmailAddress()
	{
		return $this->getValue('notifications', 'emergency_notification_email', '', false);
	}

    /**
     * @return string subject to send notification email with
     */
    public function getEmergencyNotificationSubject()
    {
        return $this->getValue('notifications', 'subject', '', false);
    }

    /**
     * @return string body to send in notification email
     */
    public function getEmergencyNotificationBody()
    {
        return $this->getValue('notifications', 'body', '', false);
    }


}