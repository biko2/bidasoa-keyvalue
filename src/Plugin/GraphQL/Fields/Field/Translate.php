<?php

namespace Drupal\bidasoa_keyvalue\Plugin\GraphQL\Fields\Field;

use Drupal\bidasoa_keyvalue\Services\BidasoaKeyValueTranslatorService;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\graphql\GraphQL\Execution\ResolveContext;
use Drupal\graphql\Plugin\GraphQL\Fields\FieldPluginBase;

use Drupal\graphql_core\Plugin\GraphQL\Interfaces\Entity\Entity;
use Drupal\node\NodeInterface;
use GraphQL\Type\Definition\ResolveInfo;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Retrieve a menu links route object.
 *
 * @GraphQLField(
 *   id = "Translate",
 *   secure = true,
 *   name = "Translate",
 *   type = "String",
 *   arguments = {
 *     "key" = "String!",
 *     "variables" = "String"
 *   },
 *   parents = {"Entity"}
 * )
 */
class Translate extends FieldPluginBase implements ContainerFactoryPluginInterface{
  protected BidasoaKeyValueTranslatorService $bidasoaKeyValueTranslatorService;
  public function __construct(array $configuration, string $plugin_id, $plugin_definition, BidasoaKeyValueTranslatorService $bidasoaKeyvalueTranslatorService) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configuration = $configuration;
    $this->pluginId = $plugin_id;
    $this->pluginDefinition = $plugin_definition;
    $this->bidasoaKeyValueTranslatorService = $bidasoaKeyvalueTranslatorService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('bidasoa_keyvalue.translator')
    );
  }
  /**
   * {@inheritdoc}
   */
  public function resolveValues($value, array $args, ResolveContext $context, ResolveInfo $info) {
    if ($value instanceof ContentEntityInterface) {
      $langcode = $context->getContext('language', $info);
      $variablesArray = [];

      if(!is_null($args['variables'])) {
        $variables = explode(',', $args['variables']);
        $evens = array_values(array_filter($variables, [
          $this,
          'evenCmp'
        ], ARRAY_FILTER_USE_KEY));
        $odds = array_values(array_filter($variables, [
          $this,
          'oddCmp'
        ], ARRAY_FILTER_USE_KEY));

        foreach ($odds as &$odd) {
          if ($value->hasField($odd)) {
            $odd = $value->get($odd)->getString();
          }
        }

        foreach ($evens as $index => $val) {
          $variablesArray[$val] = $odds[$index];
        }
      }
      yield $this->bidasoaKeyValueTranslatorService->t($args['key'],$variablesArray, ['langcode'=> $langcode]);
    }
  }
  private function oddCmp($input)
  {
    return $input & 1;
  }

  // comparator function to filter odd elements
  private function evenCmp($input)
  {
    return !($input & 1);
  }

}
