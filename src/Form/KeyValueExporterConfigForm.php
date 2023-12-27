<?php

namespace Drupal\bidasoa_keyvalue\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\static_export\Exporter\Output\Config\ExporterOutputConfigFactoryInterface;
use Drupal\static_export\Exporter\Output\Formatter\OutputFormatterPluginManagerInterface;
use Drupal\static_export\Exporter\Type\Config\ConfigExporterPluginManagerInterface;
use Drupal\static_export\Form\ConstrainedExporterSettingsFormTrait;
use Drupal\static_export\Form\OutputFormatterDependentConfigFormBase;
use Drupal\static_suite\Utility\SettingsUrlResolverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class KeyValueExporterConfigForm extends OutputFormatterDependentConfigFormBase {
  use ConstrainedExporterSettingsFormTrait;

  /**
   * The config exporter manager.
   *
   * @var \Drupal\static_export\Exporter\Type\Config\ConfigExporterPluginManagerInterface
   */
  protected $configExporterManager;

  /**
   * The config output configuration factory.
   *
   * @var \Drupal\static_export\Exporter\Output\Config\ExporterOutputConfigFactoryInterface
   */
  protected $configExporterOutputConfigFactory;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The factory for configuration objects.
   * @param \Drupal\static_suite\Utility\SettingsUrlResolverInterface $settingsUrlResolver
   *   The settings URL resolver.
   * @param \Drupal\static_export\Exporter\Type\Config\ConfigExporterPluginManagerInterface $configExporterManager
   *   The config exporter manager.
   * @param \Drupal\static_export\Exporter\Output\Formatter\OutputFormatterPluginManagerInterface $outputFormatterManager
   *   The static output formatter manager.
   * @param \Drupal\static_export\Exporter\Output\Config\ExporterOutputConfigFactoryInterface $configExporterOutputConfigFactory
   *   The config output configuration factory.
   */
  public function __construct(ConfigFactoryInterface $configFactory, SettingsUrlResolverInterface $settingsUrlResolver, ConfigExporterPluginManagerInterface $configExporterManager, OutputFormatterPluginManagerInterface $outputFormatterManager, ExporterOutputConfigFactoryInterface $configExporterOutputConfigFactory) {
    parent::__construct($configFactory);
    $this->settingsUrlResolver = $settingsUrlResolver;
    $this->configExporterManager = $configExporterManager;
    $this->outputFormatterManager = $outputFormatterManager;
    $this->configExporterOutputConfigFactory = $configExporterOutputConfigFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('static_suite.settings_url_resolver'),
      $container->get('plugin.manager.static_config_exporter'),
      $container->get('plugin.manager.static_output_formatter'),
      $container->get('static_export.config_exporter_output_config_factory'),
    );
  }

  protected function getEditableConfigNames() {
    return ['bidasoa_keyvalue.settings'];
  }

  public function getFormId() {
    return 'keyvalue_locale_exporter_config';
  }
  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('bidasoa_keyvalue.settings');

    /*$formatDefinitions = $this->outputFormatterManager->getDefinitions();
    $formatOptions = [];
    foreach ($formatDefinitions as $formatterDefinition) {
      $formatOptions[$formatterDefinition['id']] = $formatterDefinition['label'];
    }*/
    $formatOptions = [
      "default" => $this->t("Default"),
      "i18next" => $this->t("i18next")
    ];
    $form['format'] = [
      '#type' => 'select',
      '#title' => $this->t('Export format'),
      '#options' => $formatOptions,
      '#default_value' => ($config->get('format') != null) ?  $config->get('format') : 'default',
      '#description' => $this->t('Format for the exported data.'),
      '#required' => TRUE,
    ];

     return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Config\Config $config */
    $config = $this->config('bidasoa_keyvalue.settings');
    $config
      ->set('format', $form_state->getValue('format'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
