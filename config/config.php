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
 * Extension version
 */
@define('ISOTOPE_PAYU_VERSION', '2.0');
@define('ISOTOPE_PAYU_BUILD', '1');


/**
 * Payment methods
 */
\Isotope\Model\Payment::registerModelType('payu', 'Isotope\Model\Payment\PayU');
