<?php
/**
 * @package     Joomla - > Site and Administrator payment info
 * @subpackage  com_jgive
 * @subpackage 	Trangell_Saman
 * @copyright   trangell team => https://trangell.com
 * @copyright   Copyright (C) 2017 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die('Restricted access');
?>
<div class="tjcpg-wrapper">
<form action="<?php echo $vars->sendUrl ?>"  method="post" id="paymentForm">
<input type="hidden" name="Amount" value="<?php echo $vars->totalAmount; ?>" />
<input type="hidden" name="MID" value="<?php echo $vars->merchantId; ?>" />
<input type="hidden" name="ResNum" value="<?php echo $vars->reservationNumber; ?>" />
<input type="hidden" name="RedirectURL" value="<?php echo $vars->callBackUrl; ?>" />
		<div class="form-actions">
			<input name='submit' type='submit' class="btn btn-success btn-large" value="<?php echo JText::_('پرداخت'); ?>" >
		</div>
	</div>
</form>
</div>

