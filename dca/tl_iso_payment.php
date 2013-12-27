<?php

/**
 * isotope_payu extension for Contao Open Source CMS
 *
 * Copyright (C) 2013 Codefog
 *
 * @package isotope_payu
 * @author  Codefog <http://codefog.pl>
 * @author  Kamil Kuzminski <kamil.kuzminski@codefog.pl>
 * @license LGPL
 */


/**
 * Add a palette to tl_iso_payment
 */
$GLOBALS['TL_DCA']['tl_iso_payment']['palettes']['payu'] = '{type_legend},name,label,type;{note_legend:hide},note;{config_legend},new_order_status,minimum_total,maximum_total,countries,shipping_modules,product_types;{gateway_legend},payu_id,payu_authKey,payu_key1,payu_key2;{price_legend:hide},price,tax_class;{enabled_legend},debug,enabled';


/**
 * Add fields to tl_iso_payment
 */
$GLOBALS['TL_DCA']['tl_iso_payment']['fields']['payu_id'] = array
(
	'label'			=> &$GLOBALS['TL_LANG']['tl_iso_payment']['payu_id'],
	'inputType'		=> 'text',
	'eval'			=> array('mandatory'=>true, 'rgxp'=>'digit', 'tl_class'=>'w50'),
	'sql'           => "varchar(10) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_iso_payment']['fields']['payu_authKey'] = array
(
	'label'			=> &$GLOBALS['TL_LANG']['tl_iso_payment']['payu_authKey'],
	'inputType'		=> 'text',
	'eval'			=> array('mandatory'=>true, 'maxlength'=>7, 'tl_class'=>'w50'),
	'sql'           => "varchar(7) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_iso_payment']['fields']['payu_key1'] = array
(
	'label'			=> &$GLOBALS['TL_LANG']['tl_iso_payment']['payu_key1'],
	'inputType'		=> 'text',
	'eval'			=> array('mandatory'=>true, 'maxlength'=>32, 'tl_class'=>'w50'),
	'sql'           => "varchar(32) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_iso_payment']['fields']['payu_key2'] = array
(
	'label'			=> &$GLOBALS['TL_LANG']['tl_iso_payment']['payu_key2'],
	'inputType'		=> 'text',
	'eval'			=> array('mandatory'=>true, 'maxlength'=>32, 'tl_class'=>'w50'),
	'sql'           => "varchar(32) NOT NULL default ''"
);
