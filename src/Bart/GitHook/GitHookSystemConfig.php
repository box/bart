<?php
namespace Bart\GitHook;

use Bart\Configuration\Configuration;
use PhpOption\Option;

/**
 * A configuration shared by a all repos on a host
 */
class GitHookSystemConfig extends Configuration
{
    /**
     * @return string Sample of how configuration is intended to be defined
     */
    public function README()
    {
        return <<<README
;;
; Configurations shared by all repositories on this host
;;
;; This section holds names for environment variables to be used by hooks
[env_vars]
; How to determine name of user pushing a change
push_user_name = GITOSIS_USER


;; This section holds configurations for freezing code
[frozen]
; Do not allow merges to the following repos
; Use 'all' to block everything; otherwise a CSV of repo names
repo_names = bart, example, my-organization/repo

; A CSV of users exempt from any code freeze
super_users = gitosis-admin, linus
README;
    }

    /**
     * @return Option [string] Name of environment variable capturing name of user
     * performing `git push`
     * @throws \Bart\Configuration\ConfigurationException
     */
    public function envVarNameForPushUser()
    {
        return Option::fromValue($this->getValue('env_vars', 'push_user_name', null, false));
    }

    /**
     * @return string[] Names of repositories configured to be frozen
     */
    public function frozenRepoNames()
    {
        return $this->getArray('frozen', 'repo_names', []);
    }

    /**
     * Super users are users to whom the frozen status does not apply
     * Typically, this has been used for automation & admins
     * @return string[] List of super users
     */
    public function superUserNames()
    {
        return $this->getArray('frozen', 'super_users', []);
    }
}
