<?php

namespace Drupal\bidasoa_keyvalue\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Language\LanguageInterface;

/**
 * Provides a listing of keyvalue entities.
 *
 */
class KeyValueListBuilder extends ConfigEntityListBuilder {

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
    $entities = parent::load();
    ksort($entities);
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
    $row['machine_name'] = strtolower($entity->id());
    $row['label'] = $entity->label();

    return $row + parent::buildRow($entity);
  }

  /**
   *
   * @return array
   *   Renderable array.
   */
  public function render() {
    //$build[] = $this->renderSearchForm(); //Disabled for now.
    $build[] = parent::render();
    return $build;
  }
  private function renderSearchForm() {
    $build['search'] = [
      '#type' => 'search',
      '#title' => $this->t('Search'),
      '#placeholder' => $this->t('Keyword')
    ];
    $build['#attached']['library'][] = 'bidasoa_keyvalue/bidasoa_keyvalue.search';

    return $build;
  }

}
