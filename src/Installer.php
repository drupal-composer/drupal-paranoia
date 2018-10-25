<?php

namespace DrupalComposer\DrupalParanoia;

use Composer\Composer;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UninstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\Installer\PackageEvent;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Util\Filesystem as ComposerFilesystem;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;
use Symfony\Component\Finder\Finder;

/**
 * Class Installer.
 *
 * @package DrupalComposer\DrupalParanoia
 */
class Installer {

  /**
   * Composer object.
   *
   * @var \Composer\Composer
   */
  protected $composer;

  /**
   * IO object.
   *
   * @var \Composer\IO\IOInterface
   */
  protected $io;

  /**
   * Asset file types.
   *
   * @var array
   */
  public $assetFileTypes;

  /**
   * Front controllers.
   *
   * @var array
   */
  public $frontControllers = [
    'index.php',
    'core/install.php',
    'core/rebuild.php',
    'core/modules/statistics/statistics.php',
  ];

  /**
   * Flag indicating whether is to run the paranoia installation or not.
   *
   * @var bool
   */
  public $isToRunInstallation = FALSE;

  /**
   * The app directory path relative to the root package.
   *
   * @var string
   */
  public $appDir;

  /**
   * The web directory path relative to the root package.
   *
   * @var string
   */
  public $webDir;

  /**
   * Installer constructor.
   *
   * @param \Composer\Composer $composer
   *   The Composer object.
   * @param \Composer\IO\IOInterface $io
   *   The IO object.
   */
  public function __construct(Composer $composer, IOInterface $io) {
    $this->composer = $composer;
    $this->io = $io;

    $extra = $this->composer->getPackage()->getExtra();

    if (!isset($extra['drupal-app-dir'])) {
      throw new \RuntimeException('Please configure drupal-app-dir in your composer.json');
    }

    if (!isset($extra['drupal-web-dir'])) {
      throw new \RuntimeException('Please configure drupal-web-dir in your composer.json');
    }

    $this->appDir = $extra['drupal-app-dir'];
    $this->webDir = $extra['drupal-web-dir'];

    $this->setAssetFileTypes();
  }

  /**
   * Checks whether the package from the event is Drupal type.
   *
   * @param \Composer\Installer\PackageEvent $event
   *   The composer event object.
   *
   * @return bool
   *   Returns TRUE in case of success, FALSE otherwise.
   */
  public function isDrupalPackage(PackageEvent $event) {
    $operation = $event->getOperation();

    $package = NULL;
    if ($operation instanceof InstallOperation || $operation instanceof UninstallOperation) {
      $package = $operation->getPackage();
    }
    elseif ($operation instanceof UpdateOperation) {
      $package = $operation->getTargetPackage();
    }

    if (!$package) {
      return FALSE;
    }

    if (!$package instanceof PackageInterface) {
      return FALSE;
    }

    $package_type = $package->getType();
    if (!$package_type) {
      return FALSE;
    }

    if ($package->getName() == 'drupal-composer/drupal-paranoia') {
      return TRUE;
    }

    /*
     * Identify whether is a Drupal related package.
     *
     * Package types extracted from:
     * - https://www.drupal.org/docs/develop/using-composer/using-composer-to-manage-drupal-site-dependencies#managing-contributed
     * - https://www.drupal.org/docs/8/creating-custom-modules/add-a-composerjson-file
     *
     * @TODO: Add support for custom package types: 'oomphinc/composer-installers-extender' and 'davidbarratt/custom-installer'.
     */
    if ((bool) preg_match("/(drupal)?-(core|module|theme|library|profile|drush|custom-module|custom-theme)/", $package_type)) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Marks installation to be executed after an install or update command.
   *
   * @param \Composer\Installer\PackageEvent $event
   *   The PackageEvent object.
   */
  public function onPostPackageEvent(PackageEvent $event) {
    if ($this->isToRunInstallation) {
      /*
       * We already know that a Drupal package is being installed/updated,
       * we don't need to check the package again, the installation will be
       * executed on onPostCmdEvent().
       */
      return;
    }

    // Checks if the package that is being installed is Drupal type.
    if ($this->isDrupalPackage($event)) {
      /*
       * When Drupal packages are installed or updated we need to flag that
       * the paranoia installation needs to be executed in order to install
       * or remove the new asset files.
       */
      $this->isToRunInstallation = TRUE;
    }
  }

  /**
   * Post install command event to execute the installation.
   */
  public function onPostCmdEvent() {
    if ($this->isToRunInstallation) {
      $this->install();
    }
  }

  /**
   * Build and install the project assets.
   */
  public function install() {
    $fs = new SymfonyFilesystem();

    // Remove the web directory, it will be recreated with new asset files.
    if ($fs->exists($this->webDir)) {
      $fs->remove($this->webDir);
    }

    // Ensure that the app and web directories exist.
    $fs->mkdir(array($this->appDir, $this->webDir));

    // Create the stub files.
    foreach ($this->frontControllers as $fileName) {
      $this->createStubPhpFile($fileName);
    }

    // Symlink public files.
    $this->createPublicFilesSymlink();

    // Create symlinks.
    $this->createAssetSymlinks();

    $this->io->write("> drupal-paranoia: " . $this->webDir . " folder has been rebuilt.");
  }

  /**
   * Symlink the public files folder.
   */
  public function createPublicFilesSymlink() {
    $cfs = new ComposerFilesystem();
    $finder = new Finder();

    $finder->in($this->appDir . '/sites')
      ->depth(0);

    /** @var \Symfony\Component\Finder\SplFileInfo $directory */
    foreach ($finder->directories() as $directory) {
      $cfs->ensureDirectoryExists($this->webDir . '/sites/' . $directory->getFilename());
      $cfs->relativeSymlink($directory->getRealPath() . '/files', realpath($this->webDir) . '/sites/' . $directory->getFilename() . '/files');
    }
  }

  /**
   * Symlink the assets from the app to web directory.
   */
  public function createAssetSymlinks() {
    $finder = new Finder();

    $finder->ignoreDotFiles(FALSE)->in($this->appDir);

    foreach ($this->assetFileTypes as $name) {
      $finder->name($name);
    }
    $finder->exclude('sites/default/files');
    $finder->notName('*.inc');
    $finder->notName('*.install');
    $finder->notName('*.module');
    $finder->notName('*.php');
    $finder->notName('*.profile');
    $finder->notName('*.theme');

    $cfs = new ComposerFilesystem();

    foreach ($finder->files() as $file) {
      $cfs->ensureDirectoryExists($this->webDir . '/' . $file->getRelativePath());
      $cfs->relativeSymlink($file->getRealPath(), realpath($this->webDir) . '/' . $file->getRelativePathname());
    }
  }

  /**
   * Create a PHP stub file at web directory.
   *
   * @param string $path
   *   The PHP file from the app directory.
   */
  public function createStubPhpFile($path) {
    $appDir = realpath($this->appDir);
    $webDir = realpath($this->webDir);

    $endPath = (dirname($path) == '.') ? $appDir : $appDir . '/' . dirname($path);
    $startPath = (dirname($path) == '.') ? $webDir : $webDir . '/' . dirname($path);

    $fs = new SymfonyFilesystem();
    $relativePath = $fs->makePathRelative($endPath, $startPath);
    $filename = basename($path);

    $content = <<<EOF
<?php

chdir('$relativePath');
require './$filename';

EOF;

    $fs->dumpFile($webDir . '/' . $path, $content);
  }

  /**
   * Set the asset file types.
   */
  public function setAssetFileTypes() {
    $this->assetFileTypes = [
      'robots.txt',
      '.htaccess',
      '*.css',
      '*.eot',
      '*.ico',
      '*.gif',
      '*.jpeg',
      '*.jpg',
      '*.js',
      '*.otf',
      '*.png',
      '*.svg',
      '*.ttf',
      '*.woff',
      '*.woff2',
    ];

    // Allow people to extend the list from a composer extra key.
    $extra = $this->composer->getPackage()->getExtra();
    if (!empty($extra['drupal-asset-files'])) {
      $this->assetFileTypes = array_merge($this->assetFileTypes, $extra['drupal-asset-files']);
    }

    // Allow other plugins to alter the list of files.
    $event = new AssetFileTypesEvent($this->assetFileTypes, $this->composer, $this->io);
    $this->composer->getEventDispatcher()->dispatch($event->getName(), $event);
    $this->assetFileTypes = $event->getAssetFileTypes();
  }

}
