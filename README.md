# Compose – A tool for class/trait creation, in a "Frankenstein way", for PHP

## Features
 - Flag composed class as abstract
 - Flag composed class as final
 - Set composed class/trait namespace
 - Set composed class base class
 - Add/Remove interface to composed class
 - Add/Remove trait to composed class/trait
 - Add/Remove constant to composed class
 - Add/Remove property to composed class/trait
 - Add/Remove method to composed class/trait
 - Add donor class properties and methods to composed class/trait
 - Add donor trait properties and methods to composed class/trait
 - Add donor class interfaces, traits, constants, properties and methods to composed class
 - Add donor class constant to composed class
 - Deferred composed class/trait compilation (evaluation of composed code), linked to php autoload system
 - Deferred composed class/trait build (configuration of composed class and), linked to php autoload system
 - Update constant before compilation
 - Update property before compilation
 - Update method before compilation
 - ¡ FULLY EXPERIMENTAL CODE !

##  "Favor composition over inheritance"
This tool allows composing a class or a trait by code, with chunks of previous classes or traits
 - constants can be added or copied from a previous class (only to a class)
 - properties can be added or copied from a previous class or trait
 - methods can be added or copied from a previous class or trait
 - traits can be added or copied from a previous class
 - interfaces can be added or copied from a previous class (only to a class)
 - Before compilation any part can be removed
 - Before compilation constants and properties can also be updated

These features can be used to automate changes to third parties source code, without resorting to editing it directly.

## License
This software is distributed under the [MIT] license. Please read [LICENSE](https://github.com/pajarotin/compose/blob/main/LICENSE) for information on the software availability and distribution.

## Example

```php
<?php
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

$desired = new DesiredFinalClass();
$desired->setData('Bad Wolf');
$val = $desired->getData();

echo($val);
```

## Changelog
See [changelog](changelog.md).

## Issues
 - EXPERIMENTAL CODE, NOT RECOMMENDED FOR PRODUCTION
 - Composition is completely "textual", chunks of source code lines are copied to generate composed code
 - Using the same donor classes namespace is recommended for better source code compatibility
 - Copied source code can break if it uses __FILE__, __DIR__ or uses its donor class name
 - Extraction of functions source code is not very wise. Adding a Closure as method fails if the closure is inlined as argument of addMethod. Must be defined as shown:
```php
// VALID
$closure = function($value) {
    $this->data = $value;
};
$compose->addMethod('setData', $closure, Compose::PRIVATE, Compose::INSTANCE, Compose::OVERRIDABLE, false);

// INVALID
$compose->addMethod('setData', function($value) { $this->data = $value; }, Compose::PRIVATE, Compose::STATIC, Compose::OVERRIDABLE, false);
```
 
