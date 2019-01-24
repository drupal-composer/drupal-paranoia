# Changelog: Drupal Paranoia

## Releases

### 1.0.0-beta4, 2019-01-24
- [PR#13] Added new config 'excludes' to be able to exclude paths to be symlinked or stubbed in web folder.
- Performance enhancement: Added sites public files path to exclude list when searching for asset files. 

### 1.0.0-beta3, 2019-01-23
- [PR#12] Namespaced composer config. See: https://github.com/drupal-composer/drupal-paranoia/pull/12.

### 1.0.0-beta2, 2018-10-25
- [#9] Added support for multisite.
- [#9] Removed config `drupal-web-dir-public-files`.
- [11] Implemented event 'drupal-paranoia-post-command-run'.

### 1.0.0-beta1, 2018-07-13
- [#7] Added missing robots.txt file to the asset file types.
- Added optional event and extra config to extend the list of assets file types.
- Fixed code-style.

### 1.0.0-alpha2, 2018-04-18
- [#2] Exposed the plugin as custom command - drupal:paranoia.
- Implemented initial Travis tests.

### 1.0.0-alpha1, 2018-04-11
- Initial release.
