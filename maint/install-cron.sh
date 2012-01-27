#!/usr/bin/env bash
#
# Installs a cron as current user to keep Bart up to date
##

function showHelp() {
	echo "
    Usage: $0 [-v]
           $0 --help

	Installs cron to hard reset the current Bart checkout.
	...Sorry for now, you'll have to manually remove it (crontab -e)

    -v      Verbose output
    --help  Show this help
";

  exit $1;
}

function parseArgs() {
	while [ $# -gt 0 ]
	do
	  case "$1" in
		  -v)     verbose=1 ;;

		  --help) showHelp ;;

		  -*)
			  echo "Invalid option: \"$1\"" >&2;
			  showHelp 1
			  ;;

		  *)
			  if [ $# -gt 0 ]; then
				echo "$0 takes no arguments" >&2;
				showHelp 1
			  fi
			  ;;
	  esac

	  shift
	done
}

##
# Set global variables
##
function configureVars() {
	# Ensure we're not in a root directory so that git-dir will
	# ...be a full path
	cd $(dirname $0)
	GIT_BART="$(git rev-parse --git-dir)"
}

function assertPermissions() {
	if ! touch $GIT_BART; then
		echo >&2 "User executing script must have write permissions to Bart checkout"
		echo >&2 "Install failed"
		exit 1
	fi
}

function installCron() {
	declare git="git --git-dir=$GIT_BART"
	declare resetGit="$git fetch origin && $git reset --hard origin/master"
	declare cronCmd="
# Fetch and reset bart against upstream
0,15,30,45 * * * * $resetGit"

	[[ $verbose -eq 1 ]] && {
		echo "DEBUG Adding new cron: $cronCmd"
	}

	# Append this command to existing list of cron commands
	if echo "$(crontab -l) $cronCmd" | crontab - ; then
		echo ""
		echo "Successfully registered cron for $USER"
		echo "View existing crons using crontab -l"
	else
		echo "Failed to register cron"
		exit 1
	fi
}

parseArgs "$@"

configureVars
assertPermissions

installCron

