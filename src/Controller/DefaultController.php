<?php
/**
 * @file
 * Contains \Drupal\latch\Controller\DefaultController.
 */
namespace Drupal\latch\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\latch\LatchApp as Latch;

/**
 * Default controller for the latch module.
 */
class DefaultController extends ControllerBase {

	public function getLatchId($uid) {
	    $query = db_query("SELECT * FROM {latch} WHERE uid=:uid", array(':uid' => $uid));
	    $result = $query->fetchObject();
	    return ($result) ? $result->latch_account : NULL;
	}

	public function pairAccount($token) {
		$config = \Drupal::config('latch.settings');
		$appid = $config->get('latch_appid');
		$secret = $config->get('latch_secret');
		$uid = \Drupal::currentUser()->id();

		$api = new Latch($appid, $secret);
		$pairResponse = $api->pair($token);
		$responseData = $pairResponse->getData();

		if (!empty($responseData)) {
	        $accountId = $responseData->{"accountId"};
	    }
	    if (!empty($accountId)) {
	        //OK PAIRING
	        db_insert('latch')->fields(array(
	            'uid' => $uid,
	            'latch_account' => $accountId
	        ))->execute();
	        drupal_set_message('Pairing success');
	    } else {
	        //NOT PAIRING
	        drupal_set_message('Pairing token not valid', 'error');
	    }
	}

	public function unpairAccount($latch_account) {
		$config = \Drupal::config('latch.settings');
		$appid = $config->get('latch_appid');
		$secret = $config->get('latch_secret');
		$uid = \Drupal::currentUser()->id();

		$api = new Latch($appid, $secret);
		$pairResponse = $api->unpair($latch_account);

		db_delete('latch')->condition('uid', $uid)->execute();
		drupal_set_message('Unpairing success');
	}

	public function getLatchStatus($latch_account) {
	    $appid = \Drupal::config('latch.settings')->get('latch_appid');
	    $secret = \Drupal::config('latch.settings')->get('latch_secret');
	    if (!empty($appid) && !empty($secret)) {
	        $api = new Latch($appid, $secret);
	        return $api->status($latch_account);
	    } else {
	        return new LatchResponse("");
	    }
	}

	public function processStatusResponse($statusResponse, $account) {
	    $appid = \Drupal::config('latch.settings')->get('latch_appid');
	    $responseData = $statusResponse->getData();
	    $responseError = $statusResponse->getError();

	    // If something goes wrong, disable Latch temporary or permanently to prevent blocking the user
	    if (empty($statusResponse) || (empty($responseData) && empty($responseError))) {
	        // Login success to prevent DoS to the user         
	    } else {
	        if (!empty($responseError) && $responseError->getCode() == 201) {
	            // If the account is externally unpaired, apply the changes in database            
	            db_delete('latch')->condition('uid', $account->id());
	        }
	        if (!empty($responseData) && DefaultController::isStatusOn($responseData, $appid)) {
	            // LOGIN OK + STATUS = on
	            if (DefaultController::isSecondFactorEnabled($responseData, $appid)) {
	                $otp = $responseData->{"operations"}->{$appid}->{"two_factor"}->{"token"};
	                DefaultController::storeSecondFactor($otp, $account->id());
	                session_destroy(); // The user cannot be authenticated yet
	                echo DefaultController::buildHTML(\Drupal::formBuilder()->getForm('Drupal\latch\Form\oneTimeForm'));
	                die();
	            }
	        } else {
	            // LOGIN OK + STATUS = off
	            // TODO: Show same error of invalid credentials
	            user_logout();
	        }
	    }
	}

	public function isStatusOn($responseData, $appid) {
	    return $responseData->{"operations"}->{$appid}->{"status"} === "on";
	}

	public function isSecondFactorEnabled($responseData, $appid) {
	    return property_exists($responseData->{"operations"}->{$appid}, "two_factor");
	}

	public function userProperlyLogged($account) {
	    if (!empty($_POST['latch_otp'])) {
	        DefaultController::checkSecondFactor($account);
	    } else {
	        $latch_account = DefaultController::getLatchId($account->id());
	        if ($latch_account) {
	            $statusResponse = DefaultController::getLatchStatus($latch_account);
	            DefaultController::processStatusResponse($statusResponse, $account);
	        }
	    }
	}

	public function checkSecondFactor($account) {
	    $storedToken = DefaultController::retrieveSecondFactor($account->id());
	    DefaultController::removeSecondFactor($account->id());
	    if ($_POST['latch_otp'] != $storedToken) {
	    	// TODO: Show same error of invalid credentials
	        user_logout();
	    }
	}

/*
 * Inserts the rendered second factor form into an HTML document.
 */
	public function buildHTML($htmlStructure) {
	    global $base_url;
	    $module_name = drupal_get_path('module', 'latch');
		return '<html><head>'
	            . '<style>'
	            . '.twoFactorContainer { display:block; width:300px; margin: 5% auto 0 auto; text-align: center; border: solid 1px rgb(184, 184, 184); border-radius:5px}'
	            . '.twoFactorHeader {float:left; background: #00b9be; color: #FFF; width:100%; border-top-left-radius: 5px; border-top-right-radius: 5px; font-family: sans-serif;}'
	            . '.twoFactorHeader h3 {float: left; margin-left: 10px;}'
	            . '.twoFactorHeader img {width: 45px; height: auto; float:left; margin-top: 5px; margin-left:20px}'
	            . '.twoFactorForm {clear:left; padding-top:10px;}'
	            . 'input {margin-top:10px}'
	            . 'input[type="submit"] {width:auto;}'
	            . '#edit-latch-otp {width:100px;}'
	            . '</style>'
	            . '</head><body>'
	            . '<div class="twoFactorContainer">'
	            . '<div class="twoFactorHeader"><img src="' . $base_url . '/' . $module_name . '/symbol.png"><h3>' . t('One-Time Password') . '</h3></div><div class="twoFactorForm">'
	            . drupal_render($htmlStructure)
	            . '</div></body></html>';
	}

	public function storeSecondFactor($otp, $uid) {
	    db_update('latch')->fields(array('two_factor' => $otp))->condition('uid', $uid)->execute();
	}

	public function retrieveSecondFactor($uid) {
	    $query = db_query("SELECT * FROM {latch} WHERE uid=:uid", array(':uid' => $uid));
	    $userLatchData = $query->fetchObject();
	    return ($userLatchData) ? $userLatchData->two_factor : NULL;
	}

	public function removeSecondFactor($uid) {
	    db_update('latch')->fields(array('two_factor' => NULL))->condition('uid', $uid)->execute();
	}
}
