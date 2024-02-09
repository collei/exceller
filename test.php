<?php
require('vendor/autoload.php');

use Collei\Exceller\Exceller;

$excel = new Exceller('Teste.xlsx');

echo 'Ok';

echo '<br>';

echo '<pre>' . print_r(compact('excel'), true) . '</pre>';
