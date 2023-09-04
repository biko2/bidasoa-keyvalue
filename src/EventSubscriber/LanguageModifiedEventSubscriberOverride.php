<?php

namespace Drupal\bidasoa_keyvalue\EventSubscriber;

use Drupal\static_export\EventSubscriber\LanguageModifiedEventSubscriber;

class LanguageModifiedEventSubscriberOverride extends LanguageModifiedEventSubscriber{
  protected static function affectsLanguages(string $configName): bool {

    return (
      $configName === 'language.negotiation' ||
      $configName === 'system.site' ||
      $configName === 'language.types' ||
      str_starts_with($configName, 'bidasoa_keyvalue.keyvalue.') ||
      str_starts_with($configName, 'language.entity.')
    );
  }
}
