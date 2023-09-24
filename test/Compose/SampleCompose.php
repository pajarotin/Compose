<?php
/** 
 * @package Pajarotin\Compose
 * @author Alberto Mora Cao <gmlamora@gmail.com>
 * @copyright 2023 Alberto Mora Cao
 * @version $Revision: 0.1.1 $ 
 * @license https://mit-license.org/ MIT
 */

namespace Pajarotin\Test\Compose;
use Pajarotin\Compose\Compose as Compose;
require_once(dirname(__FILE__) . '/../../src/Compose.php');

// Suppose DonorSampleClass is a third party sourcecode, we shouldn't change
// but we need the hexadecimal value returned by getData
// We can override getData in derived class, but we cannot inherit property: $data and method: hash()
class DonorSampleClass {
    private $data;
    private function hash()  {
        return bin2hex($this->data);
    }
    public function getData() {
        return $this->data;
    }
    public function setData($value) {
        $this->data = $value;
    }
}

// We can define the desired behaviour in a dummy "Delta" class:
class DeltaCompose {
    public function getData() {
        return $this->hash();
    }
}

// Instead of changing the visibility in DonorSampleClass and inheriting, we compose:
$compose = new Compose('DesiredFinalClass', 'Pajarotin\\Test\\Compose');
$compose->fuseClass('DonorSampleClass', 'Pajarotin\\Test\\Compose');
$compose->fuseClass('DeltaCompose', 'Pajarotin\\Test\\Compose');
$compose->deferredCompilation();
/*
$desired = new DesiredFinalClass();
$desired->setData('Bad Wolf');
$val = $desired->getData();

echo($val);
*/
$compose = new Compose('WhatIf', 'Pajarotin\\Test\\Compose');
$compose->fuseClass('DesiredFinalClass', 'Pajarotin\\Test\\Compose');
$compose->compile();

$desired = new DesiredFinalClass();
$desired->setData('Bad Wolf');
$val = $desired->getData();

echo($val);
