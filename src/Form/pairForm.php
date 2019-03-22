<?php

/**
 * @file
 * Contains \Drupal\latch\Form\pairForm
 */
namespace Drupal\latch\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\latch\Controller\DefaultController;

/**
 * Configure latch settings for this site.
 */
class pairForm extends FormBase {
  /** 
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'latch_pair_form';
  }

  /** 
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('latch.settings');
    $uid = \Drupal::currentUser()->id();

    if ($config->get('latch_appid') == '' | $config->get('latch_secret') == '') {
      $form['insert_config'] = array(
        '#markup' => '<p>' . t('Please insert your <a href=":latch-settings">Application ID and Secret</a> before to pair with Latch', [':latch-settings' => \Drupal::url('latch.settings')]) . '</p>',
      );
    } else {
      if (DefaultController::getLatchId($uid)){
          // Latch info
          $form['latch_info'] = array(
            '#markup' => '<p>' . t('User already paired') . '</p>'
          );
          //Unpair
          $form['latch_pair'] = array(
              '#type' => 'submit',
              '#value' => t('Unpair'),
          );

      } else {
          // Latch info
          $form['latch_info'] = array(
            '#markup' => '<p>' . t('Generate and insert token to pair your account') . '</p>'
          );

          // Page title field
          $form['latch_token'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Latch Pairing Token'),
            '#required' => TRUE,
          );
          //Pair
          $form['latch_pair'] = array(
              '#type' => 'submit',
              '#value' => t('Pair Account'),
          );
      }
    }

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
    $token = $form_state->getValue('latch_token');
    $uid = \Drupal::currentUser()->id();
    $latch_account = DefaultController::getLatchId($uid);

    if ($latch_account){
      DefaultController::unpairAccount($latch_account);
    } else {
      DefaultController::pairAccount($token);
    }
  }
}
