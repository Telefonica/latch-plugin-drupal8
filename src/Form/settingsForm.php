<?php

/**
 * @file
 * Contains \Drupal\latch\Form\settingsForm
 */
namespace Drupal\latch\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure latch settings for this site.
 */
class settingsForm extends ConfigFormBase {
  /** 
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'latch_form';
  }

  /** 
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $config = $this->config('latch.settings');

    // Latch info
    $form['latch_info'] = array(
      '#markup' => '<p>This is the page to set Latch configuration. To get register as a Latch application please visit: <a href="https://latch.elevenpaths.com" target="_blank">latch.elevenpaths.com</a></p>'
    );

    // Page title field
    $form['latch_appid'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Application ID'),
      '#default_value' => $config->get('latch_appid'),
      '#description' => $this->t('Insert your Application ID'),
      '#required' => TRUE,
    );

    // Source text field
    $form['latch_secret'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Secret'),
      '#default_value' => $config->get('latch_secret'),
      '#description' => $this->t('Insert your Secret'),
      '#required' => TRUE,
    );

    return $form;
  }

  /**
   * {@inheritdoc}.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /** 
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('latch.settings');
    $config->set('latch_appid', $form_state->getValue('latch_appid'));
    $config->set('latch_secret', $form_state->getValue('latch_secret'));
    $config->save();
    return parent::submitForm($form, $form_state);
  }

  /** 
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'latch.settings',
    ];
  }
}