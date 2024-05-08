<?php

namespace Drupal\bidasoa_keyvalue\Services;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Psr\Log\LoggerInterface;

/**
 * Bidasoa KeyValue Import service.
 */
class BidasoaKeyValueImportService {

  use StringTranslationTrait;

  /**
   * Language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $language_manager;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entity_type_manager;

  /**
   * Entity type manager "keyvalue" storage query.
   *
   * @var \Drupal\Core\Config\Entity\Query\Query
   */
  protected $entity_type_manager_keyvalue_query;

  /**
   * Entity type manager "keyvalue" storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorage
   */
  protected $entity_type_manager_keyvalue_storage;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   Language manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   */
  public function __construct(LanguageManagerInterface $languageManager, EntityTypeManagerInterface $entityTypeManager) {
    $this->language_manager = $languageManager;
    $this->entity_type_manager = $entityTypeManager;
    $this->entity_type_manager_keyvalue_storage = $entityTypeManager->getStorage('keyvalue');
    $this->entity_type_manager_keyvalue_query = $this->entity_type_manager_keyvalue_storage->getQuery();
  }

  /**
   * Validate file route.
   *
   * @param string $route
   *   File route to validate.
   *
   * @return boolean
   *   Validation.
   */
  public function isValidFileRoute(string $route): bool {
    return file_exists($route);
  }

  /**
   * Get system installed language from given route.
   *
   * @param string $route
   *   File route to retrieve language from.
   *
   * @return \Drupal\Core\Language\Language|null
   *   Detected language.
   */
  public function getLanguageFromRoute(string $route): Language|NULL {
    $route_language = $route_language_max = 0;

    $languages = $this->language_manager->getLanguages();
    foreach ($languages as $langcode => $language) {
      $route_language_matches = preg_match_all('/(\/(' . $langcode . ')\/)|(\.(' . $langcode . ')\.)/', $route);
      if (is_int($route_language_matches) && $route_language_max < $route_language_matches) {
        $route_language = $language;
        $route_language_max = $route_language_matches;
      }
    }

    return ($route_language instanceof LanguageInterface) ? $route_language : NULL;
  }

  /**
   * Get JSON data from given file route.
   *
   * @param string $route
   *   File route to get data from.
   * @param boolean $validate_route = TRUE
   *   Validate file route.
   *
   * @return mixed
   *   JSON data.
   */
  public function getJsonFileContentFromRoute(string $route, bool $validate_route = TRUE) : mixed {
    $json = [];

    try {
      if ($validate_route && !$this->isValidFileRoute($route)) {
        return $json;
      }

      $json = Json::decode(file_get_contents($route));
    }
    catch (\Exception $e) {
      $this->logger()->error($e->getMessage());
    }

    return $json;
  }

  /**
   * Retrieve KeyValues from JSON data.
   *
   * @param mixed $json
   *   JSON to get data from.
   *
   * @return array
   *   KeyValues.
   */
  public function getKeyValuesFromJson(mixed $json): array {
    $keyvalues = [];

    if (!empty($json)) {
      while (!empty($json)) {
        foreach($json as $key => $data) {
          unset($json[$key]);

          if (is_array($data)) {
            $data_keys = array_keys($data);
            foreach ($data_keys as $data_key) {
              $json[$key . '.' . $data_key] = $data[$data_key];
            }
          }
          elseif (is_string($data)) {
            $keyvalues[$key] = $data;
          }
        }
      }
    }

    return $keyvalues;
  }

  /**
   * Remove existing system KeyValues from given KeyValues.
   *
   * @param array $keyvalues
   *   KeyValues.
   *
   * @return array
   *   Unique KeyValues.
   */
  public function removeExistingKeyValuesFromGivenKeyValues(array $keyvalues): array {
    return array_diff(array_unique($keyvalues), $this->entity_type_manager_keyvalue_query->execute() ?? []);
  }

  /**
   * Import KeyValues.
   *
   * @param array $keyvalues
   *   KeyValues.
   * @param \Drupal\Core\Language\LanguageInterface|string $language
   *   KeyValues' language code.
   *
   * @return array
   *   Operation results.
   */
  public function importKeyValues(array $keyvalues, LanguageInterface|string $language): array {
    $results['success'] = $results['already'] = $results['error'] = 0;

    $language = $this->getLangcodeFromLanguage($language);
    if (!is_string($language)) {
      throw new \Exception('The provided language for importing the KeyValues is not valid.');
    }

    foreach ($keyvalues as $id => $keyvalue) {
      try {
        if (!is_string($keyvalue)) {
          $this->logger()->error('Error found: Keyvalue is not a string');
          $results['error']++;
          continue;
        }
        if (!empty($this->entity_type_manager_keyvalue_storage->loadByProperties(['id' => $id, 'langcode' => $language]))) {
          $results['already']++;
          continue;
        }
        if (!empty($this->entity_type_manager_keyvalue_storage->loadByProperties(['id' => $id]))
          && empty($this->entity_type_manager_keyvalue_storage->loadByProperties(['id' => $id, 'langcode' => $language]))) {
          // ToDo: Implement a system for creating translations.
          continue;
        }

        (!empty($this->createKeyValue($id, $keyvalue, $language))) ? $results['success']++ : $results['error']++;
      }
      catch (\Exception $e) {
        $this->logger()->error('Error found: @e', ['@e' => $e]);
        $results['error']++;
      }
    }

    return $results;
  }

  /**
   * Create KeyValue.
   *
   * @param string $id
   *   KeyValue ID.
   * @param string $keyvalue
   *   KeyValue label.
   * @param \Drupal\Core\Language\LanguageInterface|string $language
   *   KeyValue language code.
   *
   * @return mixed
   *   KeyValue.
   */
  protected function createKeyValue(string $id, string $keyvalue, LanguageInterface|string $language): mixed {
    $language = $this->getLangcodeFromLanguage($language);
    if (!is_string($language)) {
      throw new \Exception('The provided language for cerating the KeyValue is not valid.');
    }

    try {
      $keyvalue = $this->entity_type_manager_keyvalue_storage->create([
        'id' => $id,
        'label' => $keyvalue,
        'langcode' => $language,
      ]);

      $keyvalue->save();
      return $keyvalue;
    }
    catch (\Exception $e) {
      $this->logger()->error('Error found: @e', ['@e' => $e]);
    }

    return NULL;
  }

  /**
   * Get Bidasoa KeyValue's assigned logger.
   *
   * @return \Psr\Log\LoggerInterface
   *   Logger.
   */
  private function logger(): LoggerInterface {
    return \Drupal::logger('bidasoa_keyvalue');
  }

  /**
   * Get langcode from given language.
   * This function can also be used for validating a langcode.
   *
   * @param \Drupal\Core\Language\LanguageInterface|string $language
   *   Language to get langcode from.
   *
   * @return string|null
   *   Langcode.
   */
  private function getLangcodeFromLanguage(LanguageInterface|string $language): string|NULL {
    if ($language instanceof LanguageInterface) {
      return $language->getId();
    }

    $language = $this->language_manager->getLanguage($language);
    if ($language instanceof LanguageInterface) {
      return $language->getId();
    }

    return NULL;
  }

}
