<?php

namespace Drupal\bidasoa_keyvalue\Plugin\static_export\Exporter\Locale;

use Drupal\Core\Cache\Cache;
use Drupal\static_export\Exporter\Output\Config\ExporterOutputConfigInterface;
use Drupal\static_export\Exporter\Type\Locale\LocaleExporterPluginBase;
use Drupal\static_suite\StaticSuiteUserException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Exporter for localized strings.
 *
 * @StaticLocaleExporter(
 *  id = "keyvalue_locale",
 *  label = @Translation("KeyValue exporter"),
 *  description = @Translation("Exports keyvalue localized strings to filesystem."),
 * )
 */
class BidasoaKeyValueLocaleExporter extends LocaleExporterPluginBase {

  /**
   * Language Manager.
   *
   * @var \Drupal\Core\Language\LanguageManager
   */
  protected $languageManager;

  protected $mustRequestBuild = TRUE;
  protected $entityTypeManager;
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->languageManager = $container->get("language_manager");
    $instance->entityTypeManager = $container->get("entity_type.manager");
    return $instance;
  }

  public function getExporterItem() {
    $langcode = $this->options['langcode'];
    if (empty($langcode)) {
      return NULL;
    }
    return $this->exporterItem;
  }

  /**
   * {@inheritdoc}
   */
  public function getExporterItemId() {
    return 'bidasoa_keyvalue_exporter';
  }

  /**
   * {@inheritdoc}
   */
  public function getExporterItemLabel() {
    return "Bidasoa key/value exporter ["  . $this->options['langcode'] . "]";
  }
  /**
   * {@inheritdoc}
   */
  public function checkParams(array $options): bool {
    $langcode = $options['langcode'];
    if (!isset($langcode)) {
      throw new StaticSuiteUserException("Param 'langcode' is not defined.");
    }

    $enabledLanguages = $this->languageManager->getLanguages();
    if (empty($enabledLanguages[$langcode])) {
      throw new StaticSuiteUserException("Language 'langcode' is not enabled on this site.");
    }

    return TRUE;
  }
  /**
   * Tell whether this exporter should always write.
   *
   * @return bool
   *   True if write is forced.
   */
  public function isForceWrite(): bool {
    return $this->isForceWrite;
  }

  /**
   * Flag to indicate that this exporter should always write.
   *
   * @param bool $isForceWrite
   *   Flag for always write.
   */
  public function setIsForceWrite(bool $isForceWrite) {
    $this->isForceWrite = $isForceWrite;
  }
  /**
   * {@inheritdoc}
   */
  protected function getOutputDefinition(): ExporterOutputConfigInterface {
    $config = $this->configFactory->get('static_export.settings');
    $filename = 'locale.' . $this->options['langcode'];
    $format = $config->get('exportable_locale.format');

    // Load the OutputFormatter plugin definition to get its extension.
    $definitions = $this->outputFormatterManager->getDefinitions();
    $extension = !empty($definitions[$format]) ? $definitions[$format]['extension'] : $format;

    $language = $this->languageManager->getLanguage($this->options['langcode']);
    return $this->exporterOutputConfigFactory->create('', $filename, $extension, $language, $format);
  }


  /**
   * {@inheritdoc}
   *
   * Get key_value data.
   */
  protected function calculateDataFromResolver() {
    $configFactory = \Drupal::service('config.factory');
    /* @var \Drupal\Core\Config\CachedStorage $configManager*/
    $configManager = \Drupal::service('config.storage');
    $configNames = $configManager->listAll('bidasoa_keyvalue.keyvalue');

    $configCache = \Drupal::service('cache.config');
    $configCache->invalidateAll();
    $output = [];
    $currentLanguage = $this->languageManager->getConfigOverrideLanguage();
    $language = $this->languageManager->getLanguage($this->options['langcode']);
    $this->languageManager->setConfigOverrideLanguage($language);
    foreach($configNames as $name){
      //Cache::invalidateTags([$name]);
      $configFactory->reset($name);
      $config = $configManager->read($name);
      /* @var \Drupal\Core\Config\Entity\ConfigEntityInterface $translatedConfigEntity */
      $translatedConfigEntity = $this->entityTypeManager
        ->getStorage('keyvalue')
        ->load($config['id']);
      $output[strtolower($config['id'])] = $translatedConfigEntity->get('label');
    }
    $this->languageManager->setConfigOverrideLanguage($currentLanguage);
    return $output;
  }
}
