<?php
/** 
 * @package Pajarotin\Compose
 * @author Alberto Mora Cao <gmlamora@gmail.com>
 * @copyright 2023 Alberto Mora Cao
 * @version $Revision: 0.0.3 $ 
 * @license https://mit-license.org/ MIT
 */

namespace Pajarotin\Test\Compose;
use PHPUnit\Framework\TestCase;
use Pajarotin\Compose\Compose as Compose;

require_once(dirname(__FILE__) . '/../../vendor/autoload.php');

// Parts used to build our Frankenstein test classes:
interface donorInterface {
    public function ctm();
}

trait donorTrait {
    private $etp = 'private trait property';
    public function ctm() {
        return $this->etp;
    }
}

trait fullDonorTrait {
    use donorTrait;
    protected $dtp = 'protected trait property';
    protected function dtm() {
        return $this->dtp;
    }
}

trait abstractTrait {
    protected $abs;
    abstract protected function getAbs($param = null);
}

class DeltaAbstract {
    protected function getAbs($param = null) {
        return $this->abs;
    }
}

class BaseClass {
    protected $base = 'base';
}

class DonorClassA implements donorInterface {
    use donorTrait;

    private const ec = 1;
    protected const dc = 2;
    public const cc = 3;

    private ?int $ep = 4;
    protected ?int $dp = 5;
    public ?int $cp = 6;

    private static int $esp = 7;
    protected static int $dsp = 8;
    public static int $csp = 9;

    public function __construct() {
        $this->ep = 40;
        $this->dp = 50;
        $this->cp = 60;
    
        static::$esp = 70;
        static::$dsp = 80;
        static::$csp = 90;
    }

    public function ctm() {
        return 'donorInterface.ctm';
    }

    private function em() {
        return $this->ep;
    }
    protected function dm() {
        return $this->dp;
    }
    public function cm() {
        return $this->cp;
    }

    private static function esm() {
        return static::$esp;
    }
    protected static function dsm() {
        return static::$dsp;
    }
    public static function csm() {
        return static::$csp;
    }

    private function &rem() {
        return $this->ep;
    }
    protected function &rdm() {
        return $this->dp;
    }
    public function &rcm() {
        return $this->cp;
    }

    private static function &resm() {
        return static::$esp;
    }
    protected static function &rdsm() {
        return static::$dsp;
    }
    public static function &rcsm() {
        return static::$csp;
    }
}
// End of: Parts used to build our Frankenstein test classes:

final class TestCompose extends TestCase
{
    public function testClassInterface() {
        $className = 'TestClassInterface';
        $namespace = 'Pajarotin\\Test\\Compose';
        $compose = new Compose($className, $namespace);
        $compose->addInterface('donorInterface', 'Pajarotin\\Test\\Compose');
        $method = <<<METHOD
        () {
            return 'ctmValue';
        }
        METHOD;
        $compose->addMethod('ctm', $method, $visibility = Compose::PUBLIC, $scope = Compose::INSTANCE, $overriding = Compose::OVERRIDABLE, $returnsReference = false);
        $compose->compile();
        $reflection = new \ReflectionClass($namespace . '\\' . $className);
        
        $interfaces = $reflection->getInterfaces();
        $this->assertIsArray($interfaces);
        $this->assertEquals(1, count($interfaces));
        if ($interfaces) {
            foreach($interfaces as $interface) {
                $this->assertEquals('Pajarotin\\Test\\Compose\\donorInterface', $interface->name);
            }
        }
    }

    public function testClassExtends() {
        $className = 'TestClassExtends';
        $namespace = 'Pajarotin\\Test\\Compose';
        $compose = new Compose($className, $namespace);
        $compose->extends('BaseClass', 'Pajarotin\\Test\\Compose');
        $compose->compile();
        $reflection = new \ReflectionClass($namespace . '\\' . $className);
        
        $parentClass = $reflection->getParentClass();
        $this->assertEquals('Pajarotin\\Test\\Compose', $parentClass->getNamespaceName());
        $this->assertEquals('Pajarotin\\Test\\Compose\\BaseClass', $parentClass->getName());
    }

    public function testClassTrait() {
        $className = 'TestClassTrait';
        $namespace = 'Pajarotin\\Test\\Compose';
        $compose = new Compose($className, $namespace);
        $compose->addTrait('donorTrait', 'Pajarotin\\Test\\Compose');
        $compose->compile();
        $reflection = new \ReflectionClass($namespace . '\\' . $className);
        
        $traits = $reflection->getTraits();
        $this->assertIsArray($traits);
        $this->assertEquals(1, count($traits));
        if ($traits) {
            foreach($traits as $trait) {
                $this->assertEquals('Pajarotin\\Test\\Compose', $trait->getNamespaceName());
                $this->assertEquals('Pajarotin\\Test\\Compose\\donorTrait', $trait->getName());
            }
        }
    }

    public function testClassConstant() {
        $className = 'TestClassConstant';
        $namespace = 'Pajarotin\\Test\\Compose';
        $compose = new Compose($className, $namespace);
        $compose->addConstant('PUB_CONST', 'PUB_CONST_VALUE', $visibility = Compose::PUBLIC);
        $compose->addConstant('PRO_CONST', 'PRO_CONST_VALUE', $visibility = Compose::PROTECTED);
        $compose->addConstant('PRI_CONST', 'PRI_CONST_VALUE', $visibility = Compose::PRIVATE);
        $compose->compile();
        $reflection = new \ReflectionClass($namespace . '\\' . $className);

        $this->assertEquals(true, $reflection->hasConstant('PUB_CONST'));
        $this->assertEquals(true, $reflection->hasConstant('PRO_CONST'));
        $this->assertEquals(true, $reflection->hasConstant('PRI_CONST'));

        $constants = $reflection->getConstants();
        $this->assertIsArray($constants);
        $this->assertEquals(3, count($constants));
        $this->assertEquals('PUB_CONST_VALUE', $constants['PUB_CONST']);
        $this->assertEquals('PRO_CONST_VALUE', $constants['PRO_CONST']);
        $this->assertEquals('PRI_CONST_VALUE', $constants['PRI_CONST']);
    }

    public function testClassProperty() {
        $className = 'TestClassProperty';
        $namespace = 'Pajarotin\\Test\\Compose';
        $compose = new Compose($className, $namespace);

        $compose->addProperty('publicInstanceProperty', true, 'publicInstancePropertyValue', $visibility = Compose::PUBLIC, $scope = Compose::INSTANCE, $type = null);
        $compose->addProperty('protectedInstanceProperty', true, 'protectedInstancePropertyValue', $visibility = Compose::PROTECTED, $scope = Compose::INSTANCE, $type = null);
        $compose->addProperty('privateInstanceProperty', true, 'privateInstancePropertyValue', $visibility = Compose::PRIVATE, $scope = Compose::INSTANCE, $type = null);

        $compose->addProperty('publicStaticProperty', true, 'publicStaticPropertyValue', $visibility = Compose::PUBLIC, $scope = Compose::STATIC, $type = null);
        $compose->addProperty('protectedStaticProperty', true, 'protectedStaticPropertyValue', $visibility = Compose::PROTECTED, $scope = Compose::STATIC, $type = null);
        $compose->addProperty('privateStaticProperty', true, 'privateStaticPropertyValue', $visibility = Compose::PRIVATE, $scope = Compose::STATIC, $type = null);
        $compose->compile();

        $reflection = new \ReflectionClass($namespace . '\\' . $className);
        $properties = $reflection->getProperties();
        $this->assertIsArray($properties);
        $this->assertEquals(6, count($properties));
        $checked = 0;
        foreach($properties as $property) {
            if ($property->getName() == 'publicInstanceProperty') {
                $this->assertEquals(true, $property->hasDefaultValue());
                $this->assertEquals('publicInstancePropertyValue', $property->getDefaultValue());
                $this->assertEquals(true, $property->isPublic());
                $this->assertEquals(false, $property->isProtected());
                $this->assertEquals(false, $property->isPrivate());
                $this->assertEquals(false, $property->isStatic());
                $checked++;
            }
            if ($property->getName() == 'protectedInstanceProperty') {
                $this->assertEquals(true, $property->hasDefaultValue());
                $this->assertEquals('protectedInstancePropertyValue', $property->getDefaultValue());
                $this->assertEquals(false, $property->isPublic());
                $this->assertEquals(true, $property->isProtected());
                $this->assertEquals(false, $property->isPrivate());
                $this->assertEquals(false, $property->isStatic());
                $checked++;
            }
            if ($property->getName() == 'privateInstanceProperty') {
                $this->assertEquals(true, $property->hasDefaultValue());
                $this->assertEquals('privateInstancePropertyValue', $property->getDefaultValue());
                $this->assertEquals(false, $property->isPublic());
                $this->assertEquals(false, $property->isProtected());
                $this->assertEquals(true, $property->isPrivate());
                $this->assertEquals(false, $property->isStatic());
                $checked++;
            }
            if ($property->getName() == 'publicStaticProperty') {
                $this->assertEquals(true, $property->hasDefaultValue());
                $this->assertEquals('publicStaticPropertyValue', $property->getDefaultValue());
                $this->assertEquals(true, $property->isPublic());
                $this->assertEquals(false, $property->isProtected());
                $this->assertEquals(false, $property->isPrivate());
                $this->assertEquals(true, $property->isStatic());
                $checked++;
            }
            if ($property->getName() == 'protectedStaticProperty') {
                $this->assertEquals(true, $property->hasDefaultValue());
                $this->assertEquals('protectedStaticPropertyValue', $property->getDefaultValue());
                $this->assertEquals(false, $property->isPublic());
                $this->assertEquals(true, $property->isProtected());
                $this->assertEquals(false, $property->isPrivate());
                $this->assertEquals(true, $property->isStatic());
                $checked++;
            }
            if ($property->getName() == 'privateStaticProperty') {
                $this->assertEquals(true, $property->hasDefaultValue());
                $this->assertEquals('privateStaticPropertyValue', $property->getDefaultValue());
                $this->assertEquals(false, $property->isPublic());
                $this->assertEquals(false, $property->isProtected());
                $this->assertEquals(true, $property->isPrivate());
                $this->assertEquals(true, $property->isStatic());
                $checked++;
            }
        }
        $this->assertEquals(6, $checked);
    }

    public function testClassMethod() {
        $className = 'TestClassMethod';
        $namespace = 'Pajarotin\\Test\\Compose';
        $compose = new Compose($className, $namespace);

        $method = <<<METHOD
() {
    return 'publicInstanceMethodValue';
}
METHOD;
        $compose->addMethod('publicInstanceMethod', $method, $visibility = Compose::PUBLIC, $scope = Compose::INSTANCE, $overriding = Compose::OVERRIDABLE, $returnsReference = false);
        
        $method = <<<METHOD
() {
    return 'protectedInstanceMethodValue';
}
METHOD;
        $compose->addMethod('protectedInstanceMethod', $method, $visibility = Compose::PROTECTED, $scope = Compose::INSTANCE, $overriding = Compose::OVERRIDABLE, $returnsReference = false);

        $method = <<<METHOD
() {
    return 'privateInstanceMethodValue';
}
METHOD;
        $compose->addMethod('privateInstanceMethod', $method, $visibility = Compose::PRIVATE, $scope = Compose::INSTANCE, $overriding = Compose::OVERRIDABLE, $returnsReference = false);

        $method = <<<METHOD
() {
    return 'publicStaticMethodValue';
}
METHOD;        
        $compose->addMethod('publicStaticMethod', $method, $visibility = Compose::PUBLIC, $scope = Compose::STATIC, $overriding = Compose::OVERRIDABLE, $returnsReference = false);

        $method = <<<METHOD
() {
    return 'protectedStaticMethodValue';
}
METHOD;
        $compose->addMethod('protectedStaticMethod', $method, $visibility = Compose::PROTECTED, $scope = Compose::STATIC, $overriding = Compose::OVERRIDABLE, $returnsReference = false);

        $method = <<<METHOD
() {
    return 'privateStaticMethodValue';
}
METHOD;
        $compose->addMethod('privateStaticMethod', $method, $visibility = Compose::PRIVATE, $scope = Compose::STATIC, $overriding = Compose::OVERRIDABLE, $returnsReference = false);

        $compose->compile();
        $reflection = new \ReflectionClass($namespace . '\\' . $className);
        $methods = $reflection->getMethods();
        $this->assertIsArray($methods);
        $this->assertEquals(6, count($methods));
        $checked = 0;
        foreach($methods as $method) {
            if ($method->getName() == 'publicInstanceMethod') {
                $this->assertEquals(true, $method->isPublic());
                $this->assertEquals(false, $method->isProtected());
                $this->assertEquals(false, $method->isPrivate());
                $this->assertEquals(false, $method->isStatic());
                $checked++;
            }
            if ($method->getName() == 'protectedInstanceMethod') {
                $this->assertEquals(false, $method->isPublic());
                $this->assertEquals(true, $method->isProtected());
                $this->assertEquals(false, $method->isPrivate());
                $this->assertEquals(false, $method->isStatic());
                $checked++;
            }
            if ($method->getName() == 'privateInstanceMethod') {
                $this->assertEquals(false, $method->isPublic());
                $this->assertEquals(false, $method->isProtected());
                $this->assertEquals(true, $method->isPrivate());
                $this->assertEquals(false, $method->isStatic());
                $checked++;
            }
            if ($method->getName() == 'publicStaticMethod') {
                $this->assertEquals(true, $method->isPublic());
                $this->assertEquals(false, $method->isProtected());
                $this->assertEquals(false, $method->isPrivate());
                $this->assertEquals(true, $method->isStatic());
                $checked++;
            }
            if ($method->getName() == 'protectedStaticMethod') {
                $this->assertEquals(false, $method->isPublic());
                $this->assertEquals(true, $method->isProtected());
                $this->assertEquals(false, $method->isPrivate());
                $this->assertEquals(true, $method->isStatic());
                $checked++;
            }
            if ($method->getName() == 'privateStaticMethod') {
                $this->assertEquals(false, $method->isPublic());
                $this->assertEquals(false, $method->isProtected());
                $this->assertEquals(true, $method->isPrivate());
                $this->assertEquals(true, $method->isStatic());
                $checked++;
            }
        }
        $this->assertEquals(6, $checked);
    }

    public function testFuseClassConstant() {
        $className = 'TestFuseClassConstant';
        $namespace = 'Pajarotin\\Test\\Compose';
        $compose = new Compose($className, $namespace);

        $compose->fuseClassConstant('DonorClassA', 'ec', 'Pajarotin\\Test\\Compose');
        $compose->fuseClassConstant('DonorClassA', 'dc', 'Pajarotin\\Test\\Compose');
        $compose->fuseClassConstant('DonorClassA', 'cc', 'Pajarotin\\Test\\Compose');

        $compose->compile();
        $reflection = new \ReflectionClass($namespace . '\\' . $className);

        $this->assertEquals(true, $reflection->hasConstant('ec'));
        $this->assertEquals(true, $reflection->hasConstant('dc'));
        $this->assertEquals(true, $reflection->hasConstant('cc'));

        $constants = $reflection->getConstants();
        $this->assertIsArray($constants);
        $this->assertEquals(3, count($constants));
        $this->assertEquals(1, $constants['ec']);
        $this->assertEquals(2, $constants['dc']);
        $this->assertEquals(3, $constants['cc']);
    }

    public function testFuseClassProperty() {
        $className = 'TestFuseClassProperty';
        $namespace = 'Pajarotin\\Test\\Compose';
        $compose = new Compose($className, $namespace);

        $compose->fuseClassProperty('DonorClassA', 'ep', 'Pajarotin\\Test\\Compose');
        $compose->fuseClassProperty('DonorClassA', 'dp', 'Pajarotin\\Test\\Compose');
        $compose->fuseClassProperty('DonorClassA', 'cp', 'Pajarotin\\Test\\Compose');

        $compose->fuseClassProperty('DonorClassA', 'esp', 'Pajarotin\\Test\\Compose');
        $compose->fuseClassProperty('DonorClassA', 'dsp', 'Pajarotin\\Test\\Compose');
        $compose->fuseClassProperty('DonorClassA', 'csp', 'Pajarotin\\Test\\Compose');

        $compose->compile();
        $reflection = new \ReflectionClass($namespace . '\\' . $className);
        $properties = $reflection->getProperties();
        $this->assertIsArray($properties);
        $this->assertEquals(6, count($properties));
        $checked = 0;
        foreach($properties as $property) {
            if ($property->getName() == 'cp') {
                $this->assertEquals(true, $property->hasDefaultValue());
                $this->assertEquals(6, $property->getDefaultValue());
                $this->assertEquals(true, $property->isPublic());
                $this->assertEquals(false, $property->isProtected());
                $this->assertEquals(false, $property->isPrivate());
                $this->assertEquals(false, $property->isStatic());
                $checked++;
            }
            if ($property->getName() == 'dp') {
                $this->assertEquals(true, $property->hasDefaultValue());
                $this->assertEquals(5, $property->getDefaultValue());
                $this->assertEquals(false, $property->isPublic());
                $this->assertEquals(true, $property->isProtected());
                $this->assertEquals(false, $property->isPrivate());
                $this->assertEquals(false, $property->isStatic());
                $checked++;
            }
            if ($property->getName() == 'ep') {
                $this->assertEquals(true, $property->hasDefaultValue());
                $this->assertEquals(4, $property->getDefaultValue());
                $this->assertEquals(false, $property->isPublic());
                $this->assertEquals(false, $property->isProtected());
                $this->assertEquals(true, $property->isPrivate());
                $this->assertEquals(false, $property->isStatic());
                $checked++;
            }
            if ($property->getName() == 'csp') {
                $this->assertEquals(true, $property->hasDefaultValue());
                $this->assertEquals(9, $property->getDefaultValue());
                $this->assertEquals(true, $property->isPublic());
                $this->assertEquals(false, $property->isProtected());
                $this->assertEquals(false, $property->isPrivate());
                $this->assertEquals(true, $property->isStatic());
                $checked++;
            }
            if ($property->getName() == 'dsp') {
                $this->assertEquals(true, $property->hasDefaultValue());
                $this->assertEquals(8, $property->getDefaultValue());
                $this->assertEquals(false, $property->isPublic());
                $this->assertEquals(true, $property->isProtected());
                $this->assertEquals(false, $property->isPrivate());
                $this->assertEquals(true, $property->isStatic());
                $checked++;
            }
            if ($property->getName() == 'esp') {
                $this->assertEquals(true, $property->hasDefaultValue());
                $this->assertEquals(7, $property->getDefaultValue());
                $this->assertEquals(false, $property->isPublic());
                $this->assertEquals(false, $property->isProtected());
                $this->assertEquals(true, $property->isPrivate());
                $this->assertEquals(true, $property->isStatic());
                $checked++;
            }
        }
        $this->assertEquals(6, $checked);
    }

    public function testFuseClassMethod() {
        $className = 'TestFuseClassMethod';
        $namespace = 'Pajarotin\\Test\\Compose';
        $compose = new Compose($className, $namespace);

        $compose->fuseClassProperty('DonorClassA', 'ep', 'Pajarotin\\Test\\Compose');
        $compose->fuseClassProperty('DonorClassA', 'dp', 'Pajarotin\\Test\\Compose');
        $compose->fuseClassProperty('DonorClassA', 'cp', 'Pajarotin\\Test\\Compose');

        $compose->fuseClassProperty('DonorClassA', 'esp', 'Pajarotin\\Test\\Compose');
        $compose->fuseClassProperty('DonorClassA', 'dsp', 'Pajarotin\\Test\\Compose');
        $compose->fuseClassProperty('DonorClassA', 'csp', 'Pajarotin\\Test\\Compose');

        $compose->fuseClassMethod('DonorClassA', 'em', 'Pajarotin\\Test\\Compose');
        $compose->fuseClassMethod('DonorClassA', 'dm', 'Pajarotin\\Test\\Compose');
        $compose->fuseClassMethod('DonorClassA', 'cm', 'Pajarotin\\Test\\Compose');

        $compose->fuseClassMethod('DonorClassA', 'esm', 'Pajarotin\\Test\\Compose');
        $compose->fuseClassMethod('DonorClassA', 'dsm', 'Pajarotin\\Test\\Compose');
        $compose->fuseClassMethod('DonorClassA', 'csm', 'Pajarotin\\Test\\Compose');

        $compose->fuseClassMethod('DonorClassA', 'rem', 'Pajarotin\\Test\\Compose');
        $compose->fuseClassMethod('DonorClassA', 'rdm', 'Pajarotin\\Test\\Compose');
        $compose->fuseClassMethod('DonorClassA', 'rcm', 'Pajarotin\\Test\\Compose');

        $compose->fuseClassMethod('DonorClassA', 'resm', 'Pajarotin\\Test\\Compose');
        $compose->fuseClassMethod('DonorClassA', 'rdsm', 'Pajarotin\\Test\\Compose');
        $compose->fuseClassMethod('DonorClassA', 'rcsm', 'Pajarotin\\Test\\Compose');

        $compose->compile();
        $reflection = new \ReflectionClass($namespace . '\\' . $className);

        $methods = $reflection->getMethods();
        $this->assertIsArray($methods);
        $this->assertEquals(12, count($methods));
        $checked = 0;
        foreach($methods as $method) {
            if ($method->getName() == 'cm') {
                $this->assertEquals(true, $method->isPublic());
                $this->assertEquals(false, $method->isProtected());
                $this->assertEquals(false, $method->isPrivate());
                $this->assertEquals(false, $method->isStatic());
                $this->assertEquals(false, $method->returnsReference());
                $checked++;
            }
            if ($method->getName() == 'dm') {
                $this->assertEquals(false, $method->isPublic());
                $this->assertEquals(true, $method->isProtected());
                $this->assertEquals(false, $method->isPrivate());
                $this->assertEquals(false, $method->isStatic());
                $this->assertEquals(false, $method->returnsReference());
                $checked++;
            }
            if ($method->getName() == 'em') {
                $this->assertEquals(false, $method->isPublic());
                $this->assertEquals(false, $method->isProtected());
                $this->assertEquals(true, $method->isPrivate());
                $this->assertEquals(false, $method->isStatic());
                $this->assertEquals(false, $method->returnsReference());
                $checked++;
            }
            if ($method->getName() == 'csm') {
                $this->assertEquals(true, $method->isPublic());
                $this->assertEquals(false, $method->isProtected());
                $this->assertEquals(false, $method->isPrivate());
                $this->assertEquals(true, $method->isStatic());
                $this->assertEquals(false, $method->returnsReference());
                $checked++;
            }
            if ($method->getName() == 'dsm') {
                $this->assertEquals(false, $method->isPublic());
                $this->assertEquals(true, $method->isProtected());
                $this->assertEquals(false, $method->isPrivate());
                $this->assertEquals(true, $method->isStatic());
                $this->assertEquals(false, $method->returnsReference());
                $checked++;
            }
            if ($method->getName() == 'esm') {
                $this->assertEquals(false, $method->isPublic());
                $this->assertEquals(false, $method->isProtected());
                $this->assertEquals(true, $method->isPrivate());
                $this->assertEquals(true, $method->isStatic());
                $this->assertEquals(false, $method->returnsReference());
                $checked++;
            }
            if ($method->getName() == 'rcm') {
                $this->assertEquals(true, $method->isPublic());
                $this->assertEquals(false, $method->isProtected());
                $this->assertEquals(false, $method->isPrivate());
                $this->assertEquals(false, $method->isStatic());
                $this->assertEquals(true, $method->returnsReference());
                $checked++;
            }
            if ($method->getName() == 'rdm') {
                $this->assertEquals(false, $method->isPublic());
                $this->assertEquals(true, $method->isProtected());
                $this->assertEquals(false, $method->isPrivate());
                $this->assertEquals(false, $method->isStatic());
                $this->assertEquals(true, $method->returnsReference());
                $checked++;
            }
            if ($method->getName() == 'rem') {
                $this->assertEquals(false, $method->isPublic());
                $this->assertEquals(false, $method->isProtected());
                $this->assertEquals(true, $method->isPrivate());
                $this->assertEquals(false, $method->isStatic());
                $this->assertEquals(true, $method->returnsReference());
                $checked++;
            }
            if ($method->getName() == 'rcsm') {
                $this->assertEquals(true, $method->isPublic());
                $this->assertEquals(false, $method->isProtected());
                $this->assertEquals(false, $method->isPrivate());
                $this->assertEquals(true, $method->isStatic());
                $this->assertEquals(true, $method->returnsReference());
                $checked++;
            }
            if ($method->getName() == 'rdsm') {
                $this->assertEquals(false, $method->isPublic());
                $this->assertEquals(true, $method->isProtected());
                $this->assertEquals(false, $method->isPrivate());
                $this->assertEquals(true, $method->isStatic());
                $this->assertEquals(true, $method->returnsReference());
                $checked++;
            }
            if ($method->getName() == 'resm') {
                $this->assertEquals(false, $method->isPublic());
                $this->assertEquals(false, $method->isProtected());
                $this->assertEquals(true, $method->isPrivate());
                $this->assertEquals(true, $method->isStatic());
                $this->assertEquals(true, $method->returnsReference());
                $checked++;
            }
        }
        $this->assertEquals(12, $checked);
    }

    public function testFuseClass() {
        $className = 'TestFuseClass';
        $namespace = 'Pajarotin\\Test\\Compose';
        $compose = new Compose($className, $namespace);

        $compose->fuseClass('DonorClassA', 'Pajarotin\\Test\\Compose');

        $compose->compile();
        $reflection = new \ReflectionClass($namespace . '\\' . $className);
        
        $interfaces = $reflection->getInterfaces();
        $this->assertIsArray($interfaces);
        $this->assertEquals(1, count($interfaces));
        if ($interfaces) {
            foreach($interfaces as $interface) {
                $this->assertEquals('Pajarotin\\Test\\Compose\\donorInterface', $interface->name);
            }
        }

        $traits = $reflection->getTraits();
        $this->assertIsArray($traits);
        $this->assertEquals(count($traits), 1);
        if ($traits) {
            foreach($traits as $trait) {
                $this->assertEquals('Pajarotin\\Test\\Compose', $trait->getNamespaceName(), );
                $this->assertEquals('Pajarotin\\Test\\Compose\\donorTrait', $trait->getName());
            }
        }

        $this->assertEquals(true, $reflection->hasConstant('ec'));
        $this->assertEquals(true, $reflection->hasConstant('dc'));
        $this->assertEquals(true, $reflection->hasConstant('cc'));

        $constants = $reflection->getConstants();
        $this->assertIsArray($constants);
        $this->assertEquals(3, count($constants));
        $this->assertEquals(1, $constants['ec']);
        $this->assertEquals(2, $constants['dc']);
        $this->assertEquals(3, $constants['cc']);

        $properties = $reflection->getProperties();
        $this->assertIsArray($properties);
        $this->assertEquals(7, count($properties)); // Don't forget trait
        $checked = 0;
        foreach($properties as $property) {
            if ($property->getName() == 'cp') {
                $this->assertEquals(true, $property->hasDefaultValue());
                $this->assertEquals(6, $property->getDefaultValue());
                $this->assertEquals(true, $property->isPublic());
                $this->assertEquals(false, $property->isProtected());
                $this->assertEquals(false, $property->isPrivate());
                $this->assertEquals(false, $property->isStatic());
                $checked++;
            }
            if ($property->getName() == 'dp') {
                $this->assertEquals(true, $property->hasDefaultValue());
                $this->assertEquals(5, $property->getDefaultValue());
                $this->assertEquals(false, $property->isPublic());
                $this->assertEquals(true, $property->isProtected());
                $this->assertEquals(false, $property->isPrivate());
                $this->assertEquals(false, $property->isStatic());
                $checked++;
            }
            if ($property->getName() == 'ep') {
                $this->assertEquals(true, $property->hasDefaultValue());
                $this->assertEquals(4, $property->getDefaultValue());
                $this->assertEquals(false, $property->isPublic());
                $this->assertEquals(false, $property->isProtected());
                $this->assertEquals(true, $property->isPrivate());
                $this->assertEquals(false, $property->isStatic());
                $checked++;
            }
            if ($property->getName() == 'csp') {
                $this->assertEquals(true, $property->hasDefaultValue());
                $this->assertEquals(9, $property->getDefaultValue());
                $this->assertEquals(true, $property->isPublic());
                $this->assertEquals(false, $property->isProtected());
                $this->assertEquals(false, $property->isPrivate());
                $this->assertEquals(true, $property->isStatic());
                $checked++;
            }
            if ($property->getName() == 'dsp') {
                $this->assertEquals(true, $property->hasDefaultValue());
                $this->assertEquals(8, $property->getDefaultValue());
                $this->assertEquals(false, $property->isPublic());
                $this->assertEquals(true, $property->isProtected());
                $this->assertEquals(false, $property->isPrivate());
                $this->assertEquals(true, $property->isStatic());
                $checked++;
            }
            if ($property->getName() == 'esp') {
                $this->assertEquals(true, $property->hasDefaultValue());
                $this->assertEquals(7, $property->getDefaultValue());
                $this->assertEquals(false, $property->isPublic());
                $this->assertEquals(false, $property->isProtected());
                $this->assertEquals(true, $property->isPrivate());
                $this->assertEquals(true, $property->isStatic());
                $checked++;
            }

            if ($property->getName() == 'etp') {
                $this->assertEquals(true, $property->hasDefaultValue());
                $this->assertEquals('private trait property', $property->getDefaultValue());
                $this->assertEquals(false, $property->isPublic());
                $this->assertEquals(false, $property->isProtected());
                $this->assertEquals(true, $property->isPrivate());
                $this->assertEquals(false, $property->isStatic());
                $checked++;
            }
        }
        $this->assertEquals(7, $checked);

        $methods = $reflection->getMethods();
        $this->assertIsArray($methods);
        $this->assertEquals(14, count($methods));   // Including Trait an Constructor
        $checked = 0;
        foreach($methods as $method) {
            if ($method->getName() == 'cm') {
                $this->assertEquals(true, $method->isPublic());
                $this->assertEquals(false, $method->isProtected());
                $this->assertEquals(false, $method->isPrivate());
                $this->assertEquals(false, $method->isStatic());
                $this->assertEquals(false, $method->returnsReference());
                $checked++;
            }
            if ($method->getName() == 'dm') {
                $this->assertEquals(false, $method->isPublic());
                $this->assertEquals(true, $method->isProtected());
                $this->assertEquals(false, $method->isPrivate());
                $this->assertEquals(false, $method->isStatic());
                $this->assertEquals(false, $method->returnsReference());
                $checked++;
            }
            if ($method->getName() == 'em') {
                $this->assertEquals(false, $method->isPublic());
                $this->assertEquals(false, $method->isProtected());
                $this->assertEquals(true, $method->isPrivate());
                $this->assertEquals(false, $method->isStatic());
                $this->assertEquals(false, $method->returnsReference());
                $checked++;
            }
            if ($method->getName() == 'csm') {
                $this->assertEquals(true, $method->isPublic());
                $this->assertEquals(false, $method->isProtected());
                $this->assertEquals(false, $method->isPrivate());
                $this->assertEquals(true, $method->isStatic());
                $this->assertEquals(false, $method->returnsReference());
                $checked++;
            }
            if ($method->getName() == 'dsm') {
                $this->assertEquals(false, $method->isPublic());
                $this->assertEquals(true, $method->isProtected());
                $this->assertEquals(false, $method->isPrivate());
                $this->assertEquals(true, $method->isStatic());
                $this->assertEquals(false, $method->returnsReference());
                $checked++;
            }
            if ($method->getName() == 'esm') {
                $this->assertEquals(false, $method->isPublic());
                $this->assertEquals(false, $method->isProtected());
                $this->assertEquals(true, $method->isPrivate());
                $this->assertEquals(true, $method->isStatic());
                $this->assertEquals(false, $method->returnsReference());
                $checked++;
            }
            if ($method->getName() == 'rcm') {
                $this->assertEquals(true, $method->isPublic());
                $this->assertEquals(false, $method->isProtected());
                $this->assertEquals(false, $method->isPrivate());
                $this->assertEquals(false, $method->isStatic());
                $this->assertEquals(true, $method->returnsReference());
                $checked++;
            }
            if ($method->getName() == 'rdm') {
                $this->assertEquals(false, $method->isPublic());
                $this->assertEquals(true, $method->isProtected());
                $this->assertEquals(false, $method->isPrivate());
                $this->assertEquals(false, $method->isStatic());
                $this->assertEquals(true, $method->returnsReference());
                $checked++;
            }
            if ($method->getName() == 'rem') {
                $this->assertEquals(false, $method->isPublic());
                $this->assertEquals(false, $method->isProtected());
                $this->assertEquals(true, $method->isPrivate());
                $this->assertEquals(false, $method->isStatic());
                $this->assertEquals(true, $method->returnsReference());
                $checked++;
            }
            if ($method->getName() == 'rcsm') {
                $this->assertEquals(true, $method->isPublic());
                $this->assertEquals(false, $method->isProtected());
                $this->assertEquals(false, $method->isPrivate());
                $this->assertEquals(true, $method->isStatic());
                $this->assertEquals(true, $method->returnsReference());
                $checked++;
            }
            if ($method->getName() == 'rdsm') {
                $this->assertEquals(false, $method->isPublic());
                $this->assertEquals(true, $method->isProtected());
                $this->assertEquals(false, $method->isPrivate());
                $this->assertEquals(true, $method->isStatic());
                $this->assertEquals(true, $method->returnsReference());
                $checked++;
            }
            if ($method->getName() == 'resm') {
                $this->assertEquals(false, $method->isPublic());
                $this->assertEquals(false, $method->isProtected());
                $this->assertEquals(true, $method->isPrivate());
                $this->assertEquals(true, $method->isStatic());
                $this->assertEquals(true, $method->returnsReference());
                $checked++;
            }
            
            if ($method->getName() == 'ctm') {
                $this->assertEquals(true, $method->isPublic());
                $this->assertEquals(false, $method->isProtected());
                $this->assertEquals(false, $method->isPrivate());
                $this->assertEquals(false, $method->isStatic());
                $this->assertEquals(false, $method->returnsReference());
                $checked++;
            }
        }
        $this->assertEquals(13, $checked);
    }

    public function testClassDeferredCompilation() {
        $className = 'TestClassDeferredCompilation';
        $namespace = 'Pajarotin\\Test\\Compose';
        $compose = new Compose($className, $namespace);
        $compose->deferredCompilation();

        $exists = class_exists($namespace . '\\' . $className, false);
        $this->assertEquals(false, $exists);

        $class = new TestClassDeferredCompilation();

        $exists = class_exists($namespace . '\\' . $className, false);
        $this->assertEquals(true, $exists);
    }

    public function testClassDeferredBuild() {
        $className = 'TestClassDeferredBuild';
        $namespace = 'Pajarotin\\Test\\Compose';
        $compose = new Compose($className, $namespace);
        $compose->deferredBuild(function($compose) {
            $compose->addProperty('publicInstanceProperty', true, 'publicInstancePropertyValue', $visibility = Compose::PUBLIC, $scope = Compose::INSTANCE, $type = null);
        });

        $exists = class_exists($namespace . '\\' . $className, false);
        $this->assertEquals(false, $exists);

        $class = new TestClassDeferredBuild();

        $exists = class_exists($namespace . '\\' . $className, false);
        $this->assertEquals(true, $exists);
    }

    public function testClassRemoveConstant() {
        $className = 'TestClassRemoveConstant';
        $namespace = 'Pajarotin\\Test\\Compose';
        $compose = new Compose($className, $namespace);
        $compose->addConstant('PUB_CONST', 'PUB_CONST_VALUE', $visibility = Compose::PUBLIC);
        $compose->removeConstant('PUB_CONST');
        $compose->compile();        
        $reflection = new \ReflectionClass($namespace . '\\' . $className);

        $this->assertEquals(false, $reflection->hasConstant('PUB_CONST'));
    }

    public function testClassRemoveProperty() {
        $className = 'TestClassRemoveProperty';
        $namespace = 'Pajarotin\\Test\\Compose';
        $compose = new Compose($className, $namespace);

        $compose->addProperty('publicInstanceProperty', true, 'publicInstancePropertyValue', $visibility = Compose::PUBLIC, $scope = Compose::INSTANCE, $type = null);
        $compose->removeProperty('publicInstanceProperty');
        $compose->compile();

        $reflection = new \ReflectionClass($namespace . '\\' . $className);
        $properties = $reflection->getProperties();
        $this->assertIsArray($properties);
        $this->assertEquals(0, count($properties));
    }

    public function testClassRemoveMethod() {
        $className = 'TestClassRemoveMethod';
        $namespace = 'Pajarotin\\Test\\Compose';
        $compose = new Compose($className, $namespace);

        $method = <<<METHOD
() {
    return 'publicInstanceMethodValue';
}
METHOD;
        $compose->addMethod('publicInstanceMethod', $method, $visibility = Compose::PUBLIC, $scope = Compose::INSTANCE, $overriding = Compose::OVERRIDABLE, $returnsReference = false);
        $compose->removeMethod('publicInstanceMethod');

        $compose->compile();
        $reflection = new \ReflectionClass($namespace . '\\' . $className);
        $methods = $reflection->getMethods();
        $this->assertIsArray($methods);
        $this->assertEquals(0, count($methods));
    }

    public function testChainedComposition() {
        $className = 'IntermediateFuseClass';
        $namespace = 'Pajarotin\\Test\\Compose';
        $compose = new Compose($className, $namespace);
        $compose->fuseClass('DonorClassA', 'Pajarotin\\Test\\Compose');
        $compose->deferredCompilation();

        $className = 'TestChainedComposition';
        $compose = new Compose($className, $namespace);
        $compose->fuseClass('IntermediateFuseClass', 'Pajarotin\\Test\\Compose');
        $compose->deferredCompilation();

        $final = new TestChainedComposition();
        $this->assertEquals(60, $final->cm());
    }

    public function testStaticClassCreation() {
        $className = 'TestStaticClassCreation';
        $namespace = 'Pajarotin\\Test\\Compose';
        $compose = Compose::newClass($className, $namespace);
        $compose->compile();

        $exists = class_exists($namespace . '\\' . $className, false);
        $this->assertEquals(true, $exists);
    }

    public function testStaticTraitCreation() {
        $traitName = 'testStaticTraitCreation';
        $namespace = 'Pajarotin\\Test\\Compose';
        $compose = Compose::newTrait($traitName, $namespace);
        $compose->fuseClass('fullDonorTrait', $namespace);
        $compose->compile();

        $exists = trait_exists($namespace . '\\' . $traitName, false);
        $this->assertEquals(true, $exists);

        $reflection = new \ReflectionClass($namespace . '\\' . $traitName);

        $traits = $reflection->getTraits();
        $this->assertIsArray($traits);
        $this->assertEquals(count($traits), 0);

        $properties = $reflection->getProperties();
        $this->assertIsArray($properties);
        $this->assertEquals(2, count($properties)); // Don't forget trait
        $checked = 0;
        foreach($properties as $property) {
            if ($property->getName() == 'etp') {
                $this->assertEquals(true, $property->hasDefaultValue());
                $this->assertEquals('private trait property', $property->getDefaultValue());
                $this->assertEquals(false, $property->isPublic());
                $this->assertEquals(false, $property->isProtected());
                $this->assertEquals(true, $property->isPrivate());
                $this->assertEquals(false, $property->isStatic());
                $checked++;
            }
            if ($property->getName() == 'dtp') {
                $this->assertEquals(true, $property->hasDefaultValue());
                $this->assertEquals('protected trait property', $property->getDefaultValue());
                $this->assertEquals(false, $property->isPublic());
                $this->assertEquals(true, $property->isProtected());
                $this->assertEquals(false, $property->isPrivate());
                $this->assertEquals(false, $property->isStatic());
                $checked++;
            }
        }
        $this->assertEquals(2, $checked);

        $methods = $reflection->getMethods();
        $this->assertIsArray($methods);
        $this->assertEquals(2, count($methods));
        $checked = 0;
        foreach($methods as $method) {
            if ($method->getName() == 'ctm') {
                $this->assertEquals(true, $method->isPublic());
                $this->assertEquals(false, $method->isProtected());
                $this->assertEquals(false, $method->isPrivate());
                $this->assertEquals(false, $method->isStatic());
                $checked++;
            }
            if ($method->getName() == 'dtm') {
                $this->assertEquals(false, $method->isPublic());
                $this->assertEquals(true, $method->isProtected());
                $this->assertEquals(false, $method->isPrivate());
                $this->assertEquals(false, $method->isStatic());
                $checked++;
            }
        }
        $this->assertEquals(2, $checked);
    }

    public function testAbstractClassCreation() {
        $className = 'TestAbstractClassCreation';
        $namespace = 'Pajarotin\\Test\\Compose';
        $compose = Compose::newClass($className, $namespace);
        $compose->isAbstract(true);
        $compose->compile();

        $exists = class_exists($namespace . '\\' . $className, false);
        $this->assertEquals(true, $exists);

        $reflection = new \ReflectionClass($namespace . '\\' . $className);
        $this->assertEquals(true, $reflection->isAbstract());
    }

    public function testFinalClassCreation() {
        $className = 'TestFinalClassCreation';
        $namespace = 'Pajarotin\\Test\\Compose';
        $compose = Compose::newClass($className, $namespace);
        $compose->isFinal(true);
        $compose->compile();

        $exists = class_exists($namespace . '\\' . $className, false);
        $this->assertEquals(true, $exists);

        $reflection = new \ReflectionClass($namespace . '\\' . $className);
        $this->assertEquals(true, $reflection->isFinal());
    }

    public function testReadOnlyClassCreation() {
        $className = 'TestReadOnlyClassCreation';
        $namespace = 'Pajarotin\\Test\\Compose';
        $compose = Compose::newClass($className, $namespace);
        $isReadOnly = false;
        if (version_compare(PHP_VERSION, '8.1') >= 0) {
            $isReadOnly = true;
        }        
        if (version_compare(PHP_VERSION, '7.4') >= 0) {
            $compose->addProperty('rop', false, null, $visibility = Compose::PRIVATE, $scope = Compose::INSTANCE, $type = 'string', $isReadOnly);
        }
        if (version_compare(PHP_VERSION, '8.2') >= 0) {
            $compose->isReadOnly(true);
        }
        $compose->compile();

        $exists = class_exists($namespace . '\\' . $className, false);
        $this->assertEquals(true, $exists);

        $reflection = new \ReflectionClass($namespace . '\\' . $className);
        if (method_exists($reflection, 'isReadOnly')) {
            $this->assertEquals(true, $reflection->isReadOnly());
        }
        
        if (version_compare(PHP_VERSION, '7.4') >= 0) {        
            $property = $reflection->getProperty('rop');
            $this->assertEquals(true, is_object($property));

            if (method_exists($property, 'isReadOnly')) {
                $this->assertEquals(true, $property->isReadOnly());
            }
        }
    }

    public function testAbstract() {
        $className = 'BaseAbstract';
        $namespace = 'Pajarotin\\Test\\Compose';
        $compose = Compose::newClass($className, $namespace);
        $compose->fuseClass('abstractTrait', $namespace);
        $compose->isAbstract(true);
        $compose->compile();

        $exists = class_exists($namespace . '\\' . $className, false);
        $this->assertEquals(true, $exists);
        $reflection = new \ReflectionClass($namespace . '\\' . $className);
        $this->assertEquals(true, $reflection->isAbstract());

        $className = 'TestAbstract';
        $compose = Compose::newClass($className, $namespace);
        $compose->extends('BaseAbstract', $namespace);
        $compose->fuseClassMethod('DeltaAbstract', 'getAbs', $namespace);
        $compose->compile();

        $exists = class_exists($namespace . '\\' . $className, false);
        $this->assertEquals(true, $exists);

        $reflection = new \ReflectionClass($namespace . '\\' . $className);
        $method = $reflection->getMethod('getAbs');
        $this->assertEquals(true, is_object($method));
        $this->assertEquals(false, $method->isAbstract());
        
        $property = $reflection->getProperty('abs');
        $this->assertEquals(true, is_object($property));
    }
}
