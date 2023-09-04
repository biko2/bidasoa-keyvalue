<?php

namespace Drupal\bidasoa_keyvalue\Services;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\CachedStorage;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;

class BidasoaKeyValueTranslatorService {
  protected $PREFIX = 'bidasoa_keyvalue.keyvalue.';

  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  protected ConfigFactory $configFactory;
  protected CachedStorage $configStorage;
  protected CacheBackendInterface $cacheConfig;

  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    LanguageManagerInterface $languageManager,
    ConfigFactory $configFactory,
    CachedStorage $configStorage,
    CacheBackendInterface $cacheConfig,

  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->languageManager = $languageManager;
    $this->configFactory = $configFactory;
    $this->configStorage = $configStorage;
    $this->cacheConfig = $cacheConfig;
  }

  public function t(string $id,array $args = [], array $options = []):string {
    if(!isset($option['langcode']))
        $langcode = $options['langcode'] ?? $this->languageManager->getCurrentLanguage()->getId();
    $language = $this->languageManager->getLanguage($langcode);

    $this->cacheConfig->invalidateAll();

    $this->languageManager->setConfigOverrideLanguage($language);
    $id = $this->PREFIX . $id;
    if(!$this->configStorage->exists($id))
      return $id;

    $config = $this->configStorage->read($id);
    /* @var \Drupal\Core\Config\Entity\ConfigEntityInterface $translatedConfigEntity */
    $translatedConfigEntity = $this->entityTypeManager
      ->getStorage('keyvalue')
      ->load($config['id']);
    $label = $translatedConfigEntity->get('label');
    foreach($args as $key => $value){
      $label = str_replace($key,$value,$label);
    }
    return str_replace(array_keys($args),array_values($args),$label);
  }
}
