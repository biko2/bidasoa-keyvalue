<?php

namespace Drupal\bidasoa_keyvalue\EventSubscriber;

use Drupal\core_event_dispatcher\Event\Form\FormAlterEvent;
use Drupal\language\Config\LanguageConfigOverrideCrudEvent;
use Drupal\language\Config\LanguageConfigOverrideEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class ExampleFormEventSubscribers.
 *
 * Don't forget to define your class as a service and tag it as
 * an "event_subscriber":
 *
 * services:
 *  hook_event_dispatcher.example_form_subscribers:
 *   class: Drupal\hook_event_dispatcher\ExampleFormEventSubscribers
 *   tags:
 *     - { name: event_subscriber }
 */
class ConfigTranslationFormEventSubscribers implements EventSubscriberInterface {


  /**
   * Alter node form.
   *
   * @param \Drupal\core_event_dispatcher\Event\Form\FormAlterEvent $event
   *   The event.
   */
  public function alterConfigTranslationForm(FormAlterEvent $event): void {
    $form = &$event->getForm();
    foreach (array_keys($form['actions']) as $action) {
      if ($action != 'preview' && isset($form['actions'][$action]['#type']) && $form['actions'][$action]['#type'] === 'submit') {
        $form['actions'][$action]['#submit'][] = '::submitForm';
        $form['actions'][$action]['#submit'][] = 'bidasoa_keyvalue_submit_handler';
      }
    }
  }
  public function languageOverride(LanguageConfigOverrideCrudEvent $event){

    $override = $event->getLanguageConfigOverride();
    $override->getLangcode();
  }
  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      'hook_event_dispatcher.form_config_translation_edit.alter' => 'alterConfigTranslationForm',
      'hook_event_dispatcher.form_config_translation_add.alter' => 'alterConfigTranslationForm',
      LanguageConfigOverrideEvents::SAVE_OVERRIDE => 'languageOverride'
    ];
  }

}
