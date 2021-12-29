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

is_directory_symlinked(){
  local directory_to_check="$1"
  [[ -L "${directory_to_check}" && -d "${directory_to_check}" ]]
}

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
PHP_FILES=($( grep -Rl "<?php" "$SITE_WEB" ))

for PHP_FILE_PATH in ${PHP_FILES[*]}; do
  IS_STUB_FILE='false'

  for STUB_FILE_PATH in ${STUB_FILES[*]}; do
    if [ "$PHP_FILE_PATH" = "$SITE_ROOT/$STUB_FILE_PATH" ]; then
      IS_STUB_FILE='true'
      break
    fi
  done

  if [ "$IS_STUB_FILE" = 'false' ]; then
    echo "${MSG_ERROR} there are PHP files (non-stub files) in the web directory: $PHP_FILE_PATH"
    exit 1
  fi
done
echo "${MSG_OK} there are no PHP files (non-stub files) in the web directory"

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
touch "$SITE_APP/themes/test-image.jpg"

# Rebuild web directory.
composer drupal:paranoia || exit 1

if [ ! -L "$SITE_WEB/themes/test-image.jpg" ]; then
  echo "${MSG_ERROR} 'composer drupal:paranoia' command did not re-create the web directory with new symlinks"
  exit 1
else
  echo "${MSG_OK} 'composer drupal:paranoia' command re-created the web directory with new symlinks"
fi

##
# Simulate a multisites configuration, run the command 'composer drupal:paranoia'
# and check if the public files directory has been symlinked.
#
echo "${MSG_INFO} Create a \"site1\" folder to check if the public files directory has been symlinked."

mkdir -p "${SITE_APP}"/sites/{default,site1}/files

# Rebuild web directory.
composer drupal:paranoia || exit 1

is_directory_symlinked "$SITE_WEB/sites/site1/files" && is_directory_symlinked "$SITE_WEB/sites/default/files"
if [[ "$?" != 0 ]]; then
  echo "${MSG_ERROR} 'composer drupal:paranoia' command did not re-create the web directory with extra symlinks on public files folder"
  exit 1
else
  echo "${MSG_OK} 'composer drupal:paranoia' command re-created the web directory with extra symlinks on public files folder"
fi

##
# Create a "customfile.txt", configure the 'asset-files' extra key,
# run the command 'composer drupal:paranoia' and check if the file has been symlinked.
#
echo "${MSG_INFO} Create a \"customfile.txt\" and configure the 'asset-files' extra key to check if the file has been symlinked."

touch "$SITE_APP/customfile.txt"
composer config extra.drupal-paranoia.asset-files token_list_files; sed -i -e "s/\"token_list_files\"/\[\"customfile.txt\"\]/" composer.json

# Rebuild web directory.
composer drupal:paranoia || exit 1

if [ ! -L "$SITE_WEB/customfile.txt" ]; then
  echo "${MSG_ERROR} 'composer drupal:paranoia' command did not re-create the web directory with extra symlinks"
  exit 1
else
  echo "${MSG_OK} 'composer drupal:paranoia' command re-created the web directory with extra symlinks"
fi

##
# Create a "customfile.php", configure the 'asset-files' extra key,
# run the command 'composer drupal:paranoia' and check if the file has not been symlinked.
#
echo "${MSG_INFO} Create a \"customfile.php\" and configure the 'asset-files' extra key to check if the file has not been symlinked."

touch "$SITE_APP/customfile.php"
composer config extra.drupal-paranoia.asset-files token_list_files; sed -i -e "s/\"token_list_files\"/\[\"customfile.php\"\]/" composer.json

# Rebuild web directory.
composer drupal:paranoia || exit 1

if [ -L "$SITE_WEB/customfile.php" ]; then
  echo "${MSG_ERROR} 'composer drupal:paranoia' command re-created the web directory with WRONG extra symlinks"
  exit 1
else
  echo "${MSG_OK} 'composer drupal:paranoia' command did not re-create the web directory with WRONG extra symlinks"
fi

##
# Test "excludes" config.
# Run the command 'composer drupal:paranoia' and check if the excluded paths were not symlinked or stubbed.
#
echo "${MSG_INFO} Add paths to 'excludes' config and check if they have not been stubbed or symlinked."
composer config extra.drupal-paranoia.excludes token_list_files; sed -i -e "s/\"token_list_files\"/\[\"core\/install.php\"\,\"core\/tests\"\]/" composer.json

# Rebuild web directory.
composer drupal:paranoia || exit 1

if [ -f "$SITE_WEB/core/install.php" ] || [ -d "$SITE_WEB/core/tests" ]; then
  echo "${MSG_ERROR} 'composer drupal:paranoia' command re-created the web directory with excluded files and folders"
  exit 1
else
  echo "${MSG_OK} 'composer drupal:paranoia' command did not re-create the web directory with excluded files and folders"
fi
