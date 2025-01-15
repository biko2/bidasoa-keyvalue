<?php

namespace Drupal\bidasoa_keyvalue\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a listing of keyvalue entities.
 *
 */
class KeyValueListBuilder extends ConfigEntityListBuilder implements FormInterface {

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Constructs a new BlockListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, FormBuilderInterface $form_builder) {
    parent::__construct($entity_type, $storage);
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('form_builder'),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getModuleName() {
    return 'bidasoa_keyvalue';
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    $search = \Drupal::request()->query->get('search');
    $entities = parent::load();

    if ($search) {
      $this->limit = 0;
      // Cargamos de nuevo todas entidades sin limite.
      $entities = parent::load();

      // Filtra las entidades por el termino de búsqueda.
      $entities = array_filter($entities, function($entity) use ($search) {
        return stripos($entity->label(), $search) !== FALSE ||  stripos($entity->id(), $search);
      });
    }

    return $entities;
  }

  /**
   * Builds the header row for the entity listing.
   *
   * @return array
   *   A render array structure of header strings.
   *
   * @see \Drupal\Core\Entity\EntityListController::render()
   */
  public function buildHeader() {
    $header['machine_name'] =
      $this->t('Key');
    $header['label'] =
      $this->t('Label');
    return $header + parent::buildHeader();
  }

  /**
   * Builds a row for an entity in the entity listing.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which to build the row.
   *
   * @return array
   *   A render array of the table row for displaying the entity.
   *
   * @see \Drupal\Core\Entity\EntityListController::render()
   */
  public function buildRow(EntityInterface $entity) {
    $row['machine_name'] = $entity->id();
    $row['label'] = $entity->label();

    return $row + parent::buildRow($entity);
  }

  /**
   * Render build list and form.
   *
   * @return array
   *   Render the list and form.
   */
  public function render() {
    $build[] = $this->formBuilder->getForm($this);
    $build[] = parent::render();
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'key_value_form_list';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['search'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Filter'),
      '#default_value' => \Drupal::request()->query->get('search'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Filter'),
    ];

    $form['actions']['reset'] = [
      '#type' => 'submit',
      '#value' => $this->t('Reset'),
      '#submit' => [[static::class, 'resetForm']],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public static function resetForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('<current>');
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // TODO: Implement validateForm() method.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $search = trim($form_state->getValue('search'));
    $form_state->setRedirect('<current>', [], ['query' => ['search' => $search]]);
  }

}
