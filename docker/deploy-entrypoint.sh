#!/bin/sh
set -eu
# Listing tasks, showing their tree and checking an artifact need no SSH keys.
case "${1:-list}" in
    list|--help|--version|help|tree|artifact:check) ;;
    *) php /usr/local/bin/prepare-ssh.php ;;
esac
exec /opt/deployer/vendor/bin/dep -f /workspace/deploy.php "$@"
