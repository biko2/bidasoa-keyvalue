<?php

namespace Drupal\bidasoa_keyvalue\Form;

use Drupal;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Symfony\Component\DependencyInjection\ContainerInterface;


class KeyValueFormBase extends EntityForm {

  /**
   * An entity query factory for the keyvalue entity type.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityStorage;

  /**
   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_storage
   *   An entity query factory for the keyvalue entity type.
   */
  public function __construct(EntityStorageInterface $entity_storage) {
    $this->entityStorage = $entity_storage;
  }

  /**
   * Factory method for KeyValueFormBase.
   *
   */
  public static function create(ContainerInterface $container) {
    $form = new static($container->get('entity_type.manager')->getStorage('keyvalue'));
    $form->setMessenger($container->get('messenger'));
    return $form;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::form().
   *
   * Builds the entity add/edit form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   An associative array containing the current state of the form.
   *
   * @return array
   *   An associative array containing the keyvalue add/edit form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get anything we need from the base class.
    $form = parent::buildForm($form, $form_state);

    $keyvalue = $this->entity;

    // Build the form.
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 1020,
      '#default_value' => $keyvalue->label(),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('Key'),
      '#default_value' => $keyvalue->id(),
      '#machine_name' => [
        'exists' => [$this, 'exists'],
        'replace_pattern' => '([^aA-zZ0-9_\.]+)|(^custom$)',
        'error' => 'The machine-readable name must be unique, and can only contain lowercase letters, numbers, and underscores. Additionally, it can not be the reserved word "custom".',
      ],
      '#disabled' => !$keyvalue->isNew(),
    ];

    // Return the form.
    return $form;
  }

  /**
   * Checks for an existing keyvalue.
   *
   * @param string|int $entity_id
   *   The entity ID.
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return bool
   *   TRUE if this format already exists, FALSE otherwise.
   */
  public function exists($entity_id, array $element, FormStateInterface $form_state) {
    // Use the query factory to build a new keyvalue entity query.
    $query = $this->entityStorage->getQuery();

    // Query the entity ID to see if its in use.
    $result = $query->condition('id', $element['#field_prefix'] . $entity_id)
      ->execute();

    // We don't need to return the ID, only if it exists or not.
    return (bool) $result;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::actions().
   *
   * To set the submit button text, we need to override actions().
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   An associative array containing the current state of the form.
   *
   * @return array
   *   An array of supported actions for the current entity form.
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    // Get the basic actins from the base class.
    $actions = parent::actions($form, $form_state);

    // Change the submit button text.
    $actions['submit']['#value'] = $this->t('Save');
    $actions['submit']['#submit'][] = "::export";
    // Return the result.
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Add code here to validate your config entity's form elements.
    // Nothing to do here.
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::save().
   *
   * Saves the entity. This is called after submit() has built the entity from
   * the form values. Do not override submit() as save() is the preferred
   * method for entity form controllers.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   An associative array containing the current state of the form.
   */
  public function save(array $form, FormStateInterface $form_state) {
    // EntityForm provides us with the entity we're working on.
    $keyvalue = $this->getEntity();

    // Drupal already populated the form values in the entity object. Each
    // form field was saved as a public variable in the entity class. PHP
    // allows Drupal to do this even if the method is not defined ahead of
    // time.
    $status = $keyvalue->save();

    // Grab the URL of the new entity. We'll use it in the message.
    $url = $keyvalue->toUrl();

    // Create an edit link.
    $edit_link = Link::fromTextAndUrl($this->t('Edit'), $url)->toString();

    if ($status == SAVED_UPDATED) {
      // If we edited an existing entity...
      $this->messenger()->addMessage($this->t('KeyValue %label has been updated.', ['%label' => $keyvalue->label()]));
      $this->logger('contact')->notice('KeyValue %label has been updated.', ['%label' => $keyvalue->label(), 'link' => $edit_link]);
    }
    else {
      // If we created a new entity...
      $this->messenger()->addMessage($this->t('KeyValue %label has been added.', ['%label' => $keyvalue->label()]));
      $this->logger('contact')->notice('KeyValue %label has been added.', ['%label' => $keyvalue->label(), 'link' => $edit_link]);
    }
    // Redirect the user back to the listing route after the save operation.
    $form_state->setRedirect('entity.keyvalue.list');
  }
  public function export(array $form, FormStateInterface $form_state){
    foreach(Drupal::service('language_manager')->getLanguages() as $language){
      $this->exportLanguage($language->getId());
    }
  }
  private function exportLanguage(string $langcode){
    /** @var \Drupal\static_export\Exporter\Type\Locale\LocaleExporterPluginManager $localeExporterPluginManager */
    $localeExporterPluginManager = \Drupal::service('plugin.manager.static_locale_exporter');
    $execOptions = [
      'standalone' => TRUE,
      'log-to-file' => TRUE,
      'lock' => TRUE,
      'build' => FALSE,
    ];
    try {
      /** @var \Drupal\static_export\Exporter\Type\Locale\LocaleExporterPluginBase $keyValueExporter */
      $keyValueExporter = $localeExporterPluginManager->getDefaultInstance();
      $keyValueExporter->setMustRequestBuild($execOptions['build']);
      /** @var \Drupal\static_export\File\FileCollectionGroup $fileCollectionGroup */
      $fileCollectionGroup = $keyValueExporter->export(
        ['langcode' => $langcode],
        $execOptions['standalone'],
        $execOptions['log-to-file'],
        $execOptions['lock']
      );
      /** @var \Drupal\static_export\File\FileCollectionFormatter $fileFormatter */
      $fileFormatter = \Drupal::service('static_export.file_collection_formatter');
      /** @var \Drupal\Core\Messenger\Messenger $messenger */
      $messenger = \Drupal::service('messenger');
      foreach($fileCollectionGroup->getFileCollections() as $collection){
        $fileFormatter->setFileCollection($collection);
        foreach($fileFormatter->getTextLines() as $line){
          $messenger->addMessage($line);
        }

      }

    }
    catch (StaticSuiteUserException | PluginException $e) {
      $messenger = \Drupal::service('messenger');
      $messenger->addError($e->getMessage());
    }
  }

}
