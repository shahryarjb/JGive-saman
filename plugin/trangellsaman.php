<?php
/**
 * @package     Joomla - > Site and Administrator payment info
 * @subpackage  com_jgive
 * @subpackage 	Trangell_Saman
 * @copyright   trangell team => https://trangell.com
 * @copyright   Copyright (C) 2017 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
jimport('joomla.plugin.plugin');
require_once dirname(__FILE__) . '/trangellsaman/helper.php';
if (!class_exists ('checkHack')) {
	require_once( dirname(__FILE__) . '/trangellsaman/trangell_inputcheck.php');
}

class PlgPaymentTrangellSaman extends JPlugin
{

	public function __construct(& $subject, $config)
	{
		parent::__construct($subject, $config);

		// Set the language in the class
		$config = JFactory::getConfig();
	}

	public function buildLayoutPath($layout)
	{
		$layout = trim($layout);

		if (empty($layout))
		{
			$layout = 'default';
		}

		$app = JFactory::getApplication();
		$core_file = dirname(__FILE__) . '/' . $this->_name . '/' . 'tmpl' . '/' . $layout . '.php';
	
			return  $core_file;
	}

	public function buildLayout($vars, $layout = 'default' )
	{
		// Load the layout & push variables
		ob_start();
		$layout = $this->buildLayoutPath($layout);
		include $layout;
		$html = ob_get_contents();
		ob_end_clean();

		return $html;
	}

	public function onTP_GetInfo($config)
	{
		if (!in_array($this->_name, $config))
		{
			return;
		}

		$obj = new stdClass;
		$obj->name = $this->params->get('plugin_name');
		$obj->id = $this->_name;

		return $obj;
	}

	public function onTP_GetHTML($vars) {
		$vars->merchantId = $this->params->get('samanmerchantId');
		$vars->reservationNumber = time();
		$vars->totalAmount =  round($vars->amount,0);
		$vars->callBackUrl  = $vars->notify_url;
		$vars->sendUrl = "https://sep.shaparak.ir/Payment.aspx";
		$html = $this->buildLayout($vars);
		return $html;
	}

	public function onTP_Processpayment($data, $vars = array()) {
		$app	= JFactory::getApplication();		
		$jinput = $app->input;
		$resNum = $jinput->post->get('ResNum', '0', 'INT');
		$trackingCode = $jinput->post->get('TRACENO', '0', 'INT');
		$stateCode = $jinput->post->get('stateCode', '1', 'INT');
		
		$refNum = $jinput->post->get('RefNum', 'empty', 'STRING');
		if (checkHack::strip($refNum) != $refNum )
			$refNum = "illegal";
		$state = $jinput->post->get('State', 'empty', 'STRING');
		if (checkHack::strip($state) != $state )
			$state = "illegal";
		$cardNumber = $jinput->post->get('SecurePan', 'empty', 'STRING'); 
		if (checkHack::strip($cardNumber) != $cardNumber )
			$cardNumber = "illegal";
			
		$price = round($vars->amount,0);	
		$merchantId = $this->params->get('samanmerchantId');
			
			if (
				checkHack::checkNum($resNum) &&
				checkHack::checkNum($trackingCode) &&
				checkHack::checkNum($stateCode) 
			){
				if (isset($state) && ($state == 'OK' || $stateCode == 0)) {
					try {
						$out    = new SoapClient('https://sep.shaparak.ir/payments/referencepayment.asmx?WSDL');
						$resultCode    = $out->VerifyTransaction($refNum, $merchantId);
					
						if ($resultCode == $price) {
							$msg= plgPaymentTrangellSamanHelper::getGateMsg(1); 
							JFactory::getApplication()->enqueueMessage('<h2>'.$msg.'</h2>'.'<h3>'. $trackingCode .'شماره پیگری ' .'</h3>', 'Message');
							plgPaymentTrangellSamanHelper::saveComment(
									$this->params->get('plugin_name'), str_replace('JGOID-','',$vars->order_id),
										$trackingCode .'شماره پیگری ' 
										. '  '. ' شماره کارت ' . $cardNumber
								);
							$result                 = array(
							'transaction_id' => '',
							'order_id' => $vars->order_id,
							'status' => 'C',
							'total_paid_amt' => $vars->amount,
							'raw_data' => '',
							'error' => '',
							'return' => $vars->return
							);
							return $result;
						}
						else {
							$msg= plgPaymentTrangellSamanHelper::getGateMsg($state); 
							$link = JRoute::_(JUri::root().	'index.php?option=com_jgive&task=donations.cancel&processor=trangellsaman&order_id='.$vars->order_id,false);
							$app->redirect($link, '<h2>'.$msg.'</h2>', $msgType='Error'); 
							return false;	
						}
					}
					catch(\SoapFault $e)  {
						$msg= plgPaymentTrangellSamanHelper::getGateMsg('error'); 
						$link = JRoute::_(JUri::root().	'index.php?option=com_jgive&task=donations.cancel&processor=trangellsaman&order_id='.$vars->order_id,false);
						$app->redirect($link, '<h2>'.$msg.'</h2>', $msgType='Error'); 
						return false;
					}
				}
				else {
					$msg= plgPaymentTrangellSamanHelper::getGateMsg($state);
					$link = JRoute::_(JUri::root().	'index.php?option=com_jgive&task=donations.cancel&processor=trangellsaman&order_id='.$vars->order_id,false);
					$app->redirect($link, '<h2>'.$msg.'</h2>', $msgType='Error'); 
					return false;	
				}
		}
		else {
			$msg= plgPaymentTrangellSamanHelper::getGateMsg('hck2'); 
			$link = JRoute::_(JUri::root().	'index.php?option=com_jgive&task=donations.cancel&processor=trangellsaman&order_id='.$vars->order_id,false);
			$app->redirect($link, '<h2>'.$msg.'</h2>' , $msgType='Error'); 
			return false;	
		}
	}


	public function onTP_Storelog($data)
	{
		$log_write = $this->params->get('log_write', '0');

		if ($log_write == 1)
		{
			$log = plgPaymentTrangellSamanHelper::Storelog($this->_name, $data);
		}
	}
}
