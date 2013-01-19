<?php if (!defined('TL_ROOT')) die('You can not access this file directly!');

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2010 Leo Feyer
 *
 * Formerly known as TYPOlight Open Source CMS.
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at <http://www.gnu.org/licenses/>.
 *
 * PHP version 5
 * @copyright  Isotope eCommerce Workgroup 2009-2011
 * @author     Kamil Kuzminski <kamil.kuzminski@gmail.com>
 * @license    http://opensource.org/licenses/lgpl-3.0.html
 */


/**
 * Add a palette to tl_iso_payment_modules
 */
$GLOBALS['TL_DCA']['tl_iso_payment_modules']['palettes']['payu'] = '{type_legend},type,name,label;{note_legend:hide},note;{config_legend},new_order_status,minimum_total,maximum_total,countries,shipping_modules,product_types;{gateway_legend},payu_id,payu_authKey,payu_key1,payu_key2;{price_legend:hide},price,tax_class;{enabled_legend},debug,enabled';


/**
 * Add fields to tl_iso_payment_modules
 */
$GLOBALS['TL_DCA']['tl_iso_payment_modules']['fields']['payu_id'] = array
(
	'label'			=> &$GLOBALS['TL_LANG']['tl_iso_payment_modules']['payu_id'],
	'inputType'		=> 'text',
	'eval'			=> array('mandatory'=>true, 'rgxp'=>'digit', 'tl_class'=>'w50')
);

$GLOBALS['TL_DCA']['tl_iso_payment_modules']['fields']['payu_authKey'] = array
(
	'label'			=> &$GLOBALS['TL_LANG']['tl_iso_payment_modules']['payu_authKey'],
	'inputType'		=> 'text',
	'eval'			=> array('mandatory'=>true, 'maxlength'=>7, 'tl_class'=>'w50')
);

$GLOBALS['TL_DCA']['tl_iso_payment_modules']['fields']['payu_key1'] = array
(
	'label'			=> &$GLOBALS['TL_LANG']['tl_iso_payment_modules']['payu_key1'],
	'inputType'		=> 'text',
	'eval'			=> array('mandatory'=>true, 'maxlength'=>32, 'tl_class'=>'w50')
);

$GLOBALS['TL_DCA']['tl_iso_payment_modules']['fields']['payu_key2'] = array
(
	'label'			=> &$GLOBALS['TL_LANG']['tl_iso_payment_modules']['payu_key2'],
	'inputType'		=> 'text',
	'eval'			=> array('mandatory'=>true, 'maxlength'=>32, 'tl_class'=>'w50')
);

?>