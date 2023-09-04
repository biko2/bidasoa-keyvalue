<?php

namespace Drupal\bidasoa_keyvalue\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the keyvalue entity.
 *
 *
 * @see annotation
 * @see Drupal\Core\Annotation\Translation
 *
 *
 * @ConfigEntityType(
 *   id = "keyvalue",
 *   label = @Translation("Literal"),
 *   label_collection = @Translation("Literals"),
 *   label_singular = @Translation("literal"),
 *   label_plural = @Translation("literals"),
 *   label_count = @PluralTranslation(
 *     singular = "@count key value",
 *     plural = "@count key values",
 *   ),
 *   admin_permission = "administer keyvalue",
 *   handlers = {
 *     "access" = "Drupal\bidasoa_keyvalue\KeyValueAccessController",
 *     "list_builder" = "Drupal\bidasoa_keyvalue\Controller\KeyValueListBuilder",
 *     "form" = {
 *       "add" = "Drupal\bidasoa_keyvalue\Form\KeyValueAddForm",
 *       "edit" = "Drupal\bidasoa_keyvalue\Form\KeyValueEditForm",
 *       "delete" = "Drupal\bidasoa_keyvalue\Form\KeyValueDeleteForm"
 *     }
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/regional/keyvalue/manage/{keyvalue}",
 *     "delete-form" = "/admin/config/regional/keyvalue/manage/{keyvalue}/delete"
 *   },
 *   config_export = {
 *     "id",
 *     "uuid",
 *     "label",
 *   }
 * )
 */
class KeyValue extends ConfigEntityBase {

  /**
   * The key.
   *
   * @var string
   */
  public $id;

  /**
   *
   * @var string
   */
  public $uuid;

  /**
   *
   * @var string
   */
  public $label;


}
