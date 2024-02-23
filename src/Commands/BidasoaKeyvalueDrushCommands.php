<?php

namespace Drupal\bidasoa_keyvalue\Commands;

use Drupal\bidasoa_keyvalue\Services\BidasoaKeyValueImportService;
use Drupal\Core\Language\LanguageInterface;
use Drush\Commands\DrushCommands;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
class BidasoaKeyvalueDrushCommands extends DrushCommands {

  /**
   * Bidasoa KeyValue Import service.
   *
   * @var \Drupal\bidasoa_keyvalue\Services\BidasoaKeyValueImportService
   */
  protected $bidasoa_key_value_import_service;

  /**
   * Constructor.
   *
   * @param \Drupal\bidasoa_keyvalue\Services\BidasoaKeyValueImportService $bidasoaKeyValueImportService
   *   Bidasoa KeyValue Import service.
   */
  public function __construct(BidasoaKeyValueImportService $bidasoaKeyValueImportService) {
    $this->bidasoa_key_value_import_service = $bidasoaKeyValueImportService;
  }

  /**
   * Command to import Bidasoa Keyvalues from given route.
   *
   * @command bidasoa_keyvalue:import:from-route
   * @param string $route Front repository route to fetch file.
   * @aliases bkv:i:fr
   * @usage bidasoa_keyvalue:import:from-route
   *   Update Bidasoa Keyvalues from given route.
   */
  public function updateKeyvaluesFromRoute(string $route) {
    try {
      if (!$this->bidasoa_key_value_import_service->isValidFileRoute($route)) {
        $this->logger()->error(dt('The provided path does not correspond to a file'));
        return;
      }

      // Check if file is valid JSON.
      $json = $this->bidasoa_key_value_import_service->getJsonFileContentFromRoute($route, FALSE);
      if ($json === NULL && json_last_error() !== JSON_ERROR_NONE) {
        $this->logger()->error(dt('The provided path does not correspond to a JSON file'));
        return;
      }

      // Get language.
      $language = $this->bidasoa_key_value_import_service->getLanguageFromRoute($route);
      if (!$language instanceof LanguageInterface) {
        $this->logger()->error(dt('The provided route language could not be obtained'));
        return;
      }

      $keyvalues = $this->bidasoa_key_value_import_service->getKeyValuesFromJson($json);
      $keyvalues = (is_array($keyvalues) && !empty($keyvalues)) ? $this->bidasoa_key_value_import_service->removeExistingKeyValuesFromGivenKeyValues($keyvalues) : [];

      if (!is_array($keyvalues) || empty($keyvalues)) {
        $this->logger()->success(dt('No new Keyvalues found in JSON file'));
        return;
      }

      $result_log = 'Keyvalues import finished: ';
      $result_log_lenght = strlen($result_log);
      $results = $this->bidasoa_key_value_import_service->importKeyValues($keyvalues, $language);
      foreach ($results as $key => $value) {
        if (empty($value)) {
          continue;
        }

        switch ($key) {
          case 'success':
            $result_log .= '@success successfully imported, ';
            break;

          case 'already':
            $result_log .= '@already have not been imported because they already exist, ';
            break;

          case 'error':
            $result_log .= '@error have not been imported because an error occurred, for more information see the process log, ';
            break;
        }
      }
      if ($result_log_lenght === strlen($result_log)) {
        $result_log .= 'Nothing has been imported';
      }

      $this->logger()->success(dt(preg_replace('/, $/', '', $result_log), ['@success' => $results['success'], '@already' => $results['already'], '@error' => $results['error']]));
    }
    catch (\Exception $e) {
      $this->logger()->error(dt('Error found: @e', ['@e' => $e]));
    }
  }

}
