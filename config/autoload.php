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
 * Register PSR-0 namespace
 */
NamespaceClassLoader::add('Isotope', 'system/modules/isotope_payu/library');


/**
 * Register the templates
 */
TemplateLoader::addFiles(array
(
    'iso_payment_payu' => 'system/modules/isotope_payu/templates/payment'
));
