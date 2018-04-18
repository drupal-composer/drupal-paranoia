#!/bin/bash

SITE_WEB="$SITE_ROOT/web"
SITE_APP="$SITE_ROOT/app"

# Colors.
RED=$'\E[1;31m'
GREEN=$'\E[1;32m'
BLUE=$'\E[1;34m'

# Message status.
COLOR_RESET=$'\E[0m'
MSG_ERROR="${RED}[ERROR]${COLOR_RESET}"
MSG_OK="${GREEN}[OK]${COLOR_RESET}"
MSG_INFO="${BLUE}[INFO]${COLOR_RESET}"

##
# Check if the "app" folder exists.
#
if [ ! -d "$SITE_APP" ]; then
  echo "${MSG_ERROR} 'app' folder does not exist"
  exit 1
else
  echo "${MSG_OK} 'app' folder exists"
fi

##
# Check if the "web" folder exists.
#
if [ ! -d "$SITE_WEB" ]; then
  echo "${MSG_ERROR} 'web' folder does not exist"
  exit 1
else
  echo "${MSG_OK} 'web' folder exists"
fi

##
# Check if the stub files exist.
# See \DrupalComposer\DrupalParanoia $frontControllers
#
STUB_FILES=(
"web/index.php"
"web/core/install.php"
"web/core/rebuild.php"
"web/core/modules/statistics/statistics.php"
)

for STUB_FILE_PATH in ${STUB_FILES[*]}; do
  if [ ! -f "$STUB_FILE_PATH" ]; then
    echo "${MSG_ERROR} stub file '$STUB_FILE_PATH' does not exist"
    exit 1
  else
    echo "${MSG_OK} stub file '$STUB_FILE_PATH' exists"
  fi

  # Check if the stub file has the correct code.
  STUB_FILE=$( basename "$STUB_FILE_PATH" )
  if [ $( grep -c "require './$STUB_FILE';" "$STUB_FILE_PATH" ) -eq 0 ]; then
    echo "${MSG_ERROR} stub file '$STUB_FILE_PATH' does not contain the correct content"
    exit 1
  else
    echo "${MSG_OK} stub file '$STUB_FILE_PATH' contains the correct content"
  fi
done

##
# Check if there are no PHP files in the "web" folder.
# It is ignoring the stub files.
#
if [ $( grep -Rl "<?php" "$SITE_WEB" | awk '{print $0" "}' | tr -d '\n' | grep -vc "$SITE_WEB/index.php $SITE_WEB/core/install.php $SITE_WEB/core/rebuild.php $SITE_WEB/core/modules/statistics/statistics.php" ) -eq 1 ]; then
  grep -Rl "<?php" "$SITE_WEB"
  echo "${MSG_ERROR} there are PHP files (non-stub files) in the web directory"
  exit 1
else
  echo "${MSG_OK} there are no PHP files (non-stub files) in the web directory"
fi

##
# Install a Drupal package using Composer and check if the package's assets have been symlinked.
#
echo "${MSG_INFO} installing 'drupal/bootstrap' to check if the theme assets will be symlinked to web directory"
cd "$SITE_ROOT"
composer require --update-no-dev drupal/bootstrap || exit 1

BOOTSTRAP_SYMLINK="$SITE_WEB/themes/contrib/bootstrap/screenshot.png"
if [ ! -L "$BOOTSTRAP_SYMLINK" ]; then
  echo "${MSG_ERROR} '$BOOTSTRAP_SYMLINK' does not exist or it is not a symlink"
  exit 1
else
  echo "${MSG_OK} $BOOTSTRAP_SYMLINK has been symlinked"
fi

##
# Remove the Drupal package using "composer remove" and check if the package's assets have been removed from the "web" directory.
#
echo "${MSG_INFO} removing 'drupal/bootstrap' to check if the theme's assets will be removed from the web directory"

composer remove drupal/bootstrap || exit 1

if [ -d "$SITE_WEB/themes/contrib/bootstrap" ]; then
  echo "${MSG_ERROR} the 'drupal/bootstrap' assets still exist in the web directory after the package has been removed"
  exit 1
else
  echo "${MSG_OK} 'drupal/bootstrap' assets have been removed from the web directory"
fi

##
# Create a theme image, run the command 'composer drupal:paranoia' and check if the image has been symlinked.
#
touch "$SITE_APP/themes/travis-test-image.jpg"

# Rebuild web directory.
composer drupal:paranoia || exit 1

if [ ! -L "$SITE_WEB/themes/travis-test-image.jpg" ]; then
  echo "${MSG_ERROR} 'composer drupal:paranoia' command did not re-create the web directory with new symlinks"
  exit 1
else
  echo "${MSG_OK} 'composer drupal:paranoia' command re-created the web directory with new symlinks"
fi
