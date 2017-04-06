<?php

/**
 * @file
 * Contains \Drupal\latch\Form\oneTimeForm
 */
namespace Drupal\latch\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure latch settings for this site.
 */
class oneTimeForm extends FormBase {
  /** 
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'latch_one_time_form';
  }

  /** 
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Page title field
    $form['latch_otp'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Please, insert One-Time Password here:'),
      '#size' => 20,
      '#required' => TRUE,
    );

    $form['name'] = array(
        '#type' => 'hidden',
        '#value' => $_POST['name'] // It's automatically encoded by the form builder
    );
    
    $form['pass'] = array(
        '#type' => 'hidden',
        '#value' => $_POST['pass'] // It's automatically encoded by the form builder
    );
    
    //OTP
    $form['latch_otp_submit'] = array(
        '#type' => 'submit',
        '#value' => t('Log in'),
        '#weight' => 2
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

  }
}