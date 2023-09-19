<?php
namespace Pajarotin\Compose;
/** 
 * @package Pajarotin\Compose
 * @author Alberto Mora Cao <gmlamora@gmail.com>
 * @copyright 2023 Alberto Mora Cao
 * @version $Revision: 0.0.2 $ 
 * @license https://mit-license.org/ MIT
 * 
 * "Favor composition over inheritance"
 * 
 * Compose class is an experimental tool with the previous catch-phrase in mind
 * Allows building classes dynamically in a "Frankenstein way", extracting parts from others classes.
 * 
 * Can be useful to tweak external code, leaving original classes untouched. 
 * Think of those external classes, with an badly located private keyword, in need of a little "Delta".
 * 
 * DICLAIMER: ¡ FULLY EXPERIMENTAL CODE !
 * 
 * For example:
 * 
 *  <?php
 * namespace Pajarotin\Test\Compose;
 * use Pajarotin\Compose\Compose as Compose;
 * require_once(dirname(__FILE__) . '/../../src/Compose.php');
 * 
 * // Suppose DonorSampleClass is a third party sourcecode, we shouldn't change,
 * // but we need the hexadecimal value returned by getData
 * // We can override getData in derived class, but we cannot inherit property: $data and method: hash()
 * 
 * class DonorSampleClass {
 *     private $data;
 *     private function hash()  {
 *         return bin2hex($this->data);
 *     }
 *     public function getData() {
 *         return $this->data;
 *     }
 *     public function setData($value) {
 *         $this->data = $value;
 *     }
 * }
 * 
 * // We can define the desired behaviour in a dummy "Delta" class:
 * class DeltaCompose {
 *     public function getData() {
 *         return $this->hash();
 *     }
 * }
 * 
 * // Instead of changing the visibility in DonorSampleClass and inheriting, we compose:
 * $compose = new Compose('DesiredFinalClass', 'Pajarotin\\Test\\Compose');
 * $compose->fuseClass('DonorSampleClass', 'Pajarotin\\Test\\Compose');
 * $compose->fuseClass('DeltaCompose', 'Pajarotin\\Test\\Compose');
 * $compose->deferredCompilation();
 * 
 * $desired = new DesiredFinalClass();
 * $desired->setData('Bad Wolf');
 * $val = $desired->getData();
 * 
 * echo($val);
 * 
 * 
 * All supported "Frankenstein" operations:
 *
 *   - isAbstract($flag)
 *   - isFinal($flag)
 *   - setNamespace($namespace)
 *   - extends($className, $namespace = '')
 *   - addInterface($interfaceName, $namespace = '')
 *   - removeInterface($interfaceName, $namespace = '')
 *   - addTrait($traitName, $namespace = '')
 *   - removeTrait($traitName, $namespace = '')
 *   - addConstant($name, $value, $visibility = Compose::PUBLIC)
 *   - removeConstant($name)
 *   - addProperty($name, $hasDefaultValue, $defaultValue, $visibility = Compose::PRIVATE, $scope = Compose::INSTANCE, $type = null)
 *   - removeProperty($name)
 *   - addMethod($name, $value, $visibility = Compose::PRIVATE, $scope = Compose::INSTANCE, $overriding = Compose::OVERRIDABLE, $returnsReference = false)
 *   - removeMethod($name)
 *   - fuseClass($className, $namespace)
 *   - fuseClassConstant($className, $name, $namespace)
 *   - fuseClassProperty($className, $name, $namespace)
 *   - fuseClassMethod($className, $name, $namespace)
 * 
 * A couple of excentricities:
 *   - deferredCompilation
 *     The compile operation, can be registered to be executed only as required by php autoload system
 *   - deferredBuild
 *     The build, can be registered to be executed only as required by php autoload system
 */
class Compose {

    /**
     * Scope: instance, regular method or property
     * @const INSTANCE 0
     */
    const INSTANCE = 0;

    /**
     * Scope: class, static method or property
     * @const STATIC 1
     */
    const STATIC = 1;

    /**
     * Visibility: public method, property or constant
     * @const PUBLIC 2
     */
    const PUBLIC = 2;

    /**
     * Visibility: protected method, property or constant
     * @const PROTECTED 4
     */
    const PROTECTED = 4;

    /**
     * Visibility: private method, property or constant
     * @const PRIVATE 8
     */
    const PRIVATE = 8;

    /**
     * Overriding: class or method is not abstract nor final
     * @const OVERRIDABLE 16
     */
    const OVERRIDABLE = 16;

    /**
     * Overriding: class or method is abstract
     * @const ABSTRACT 16
     */
    const ABSTRACT = 32;

    /**
     * Overriding: class or method is final
     * @const FINAL 64
     */
    const FINAL = 64;

    /**
     * Composed class namespace
     * @var string $namespace
     */
    protected $namespace = null;

    /**
     * Chunk of raw php code string going after the namespace declaration and the class definition.
     * For global constants, namespaces aliases, etc
     * @var string $header
     */
    protected $header = null;

    /**
     * Indicates wether composed class is abstract
     * @var bool $abstract
     */
    protected $abstract = null;

    /**
     * Indicates wether composed class is final
     * @var bool $final
     */
    protected $final = null;

    /**
     * Indicates composed class name
     * @var string $className
     */
    protected $className = null;

    /**
     * Indicates composed class base class
     * @var string $extends
     */
    protected $extends = null;

    /**
     * Array of interfaces implemented by composed class
     * @var string[] $interfaces
     */
    protected $interfaces = null;

    /**
     * Array of traits used by composed class
     * @var string[] $traits
     */
    protected $traits = null;

    /**
     * Array of stdClass items, with info of constants, defined for the composed class
     * @var stdClass[] $constants
     */
    protected $constants = null;

    /**
     * Array of stdClass items, with info of properties, defined for the composed class
     * @var stdClass[] $properties
     */
    protected $properties = null;

    /**
     * Array of stdClass items, with info of methods, defined for the composed class
     * @var stdClass[] $methods
     */
    protected $methods = null;

    /**
     * Array of files content, cached by filename
     * @var array $filesCache
     */
    protected static $filesCache = [];

    /**
     * Array of compiled source classes
     * Used to compose new classes with previously composed ones
     * @var array $filesCache
     */
    protected static $compiledCache = [];

    /**
     * deferredCompilation() method stores Compose class objects in this array
     * indexed by full class name. Those objects compile method will be executed
     * by the signalCompilation static method.
     * That method is automaticalley called by the php autoload mechanism
     * @var array $deferredCompilations
     */
    protected static $deferredCompilations = [];

    /**
     * deferredBuilds() method stores Compose class objects, and its building Closure, 
     * in this array indexed by full class name. 
     * Build closure will be executed on its Compose object by the signalBuild static method.
     * That method is automaticalley called by the php autoload mechanism
     * @var array $deferredBuilds
     */
    protected static $deferredBuilds = [];

    /**
     * Creates a Compose object for the desired composed class indicated by $namespace\$className 
     * @param string $className
     * @param string $namespace
     */
    public function __construct($className, $namespace = null) {
        $this->final = false;
        $this->abstract = false;
        $this->className = $className;
        $this->namespace = $namespace;
        $this->interfaces = [];
        $this->traits = [];
        $this->constants = [];
        $this->properties = [];
        $this->methods = [];
    }

    /**
     * Return the composed class namespace
     * @return string
     */
    public function getNamespace() {
        return $this->namespace;
    }

    /**
     * Sets the composed class namespace
     * @param string $namespace
     * @return Pajarotin\Compose\Compose
     */
    public function setNamespace($namespace) {
        $this->namespace = $namespace;
        return $this;
    }

    /**
     * Returns the composed class name
     * @return string
     */
    public function getClassName() {
        return $this->className;
    }

    /**
     * Sets chunk of code going after the namespace declaration and before the composed class definition.
     * For global constants, namespaces aliases, etc
     * @param string $header
     * @return Pajarotin\Compose\Compose
     */
    public function withHeader($header) {
        $this->header = $header;
        return $this;
    }

    /**
     * A truish $flag indicates abstract composed class
     * @param bool $flag
     * @return Pajarotin\Compose\Compose
     */
    public function isAbstract($flag) {
        if ($flag) {
            $this->abstract = true;
        } else {
            $this->abstract = false;
        }
        return $this;
    }

    /**
     * A truish $flag indicates final composed class
     * @param bool $flag
     * @return Pajarotin\Compose\Compose
     */
    public function isFinal($flag) {
        if ($flag) {
            $this->final = true;
        } else {
            $this->final = false;
        }
        return $this;
    }

    /**
     * Setups a base class for the composed class
     * @param string $className
     * @param string $namespace
     * @return Pajarotin\Compose\Compose
     */
    public function extends($className, $namespace = null) {
        $extends = static::normalize($className, $namespace);
        $this->extends = $extends;
        return $this;
    }

    /**
     * Adds a interface to the composed class
     * @param string $interfaceName
     * @param string $namespace
     * @return Pajarotin\Compose\Compose
     */
    public function addInterface($interfaceName, $namespace = '') {
        $interface = static::normalize($interfaceName, $namespace);
        if ($interface===null) {
            return $this;
        }
        if (!in_array($interface, $this->interfaces)) {
            $this->interfaces[] = $interface;
        }
        return $this;
    }

    /**
     * retrieves defined interfaces, filtered by $name if indicated
     * @param $name
     * @return string[]
     */
    public function getInterfaces($name = null) {
        if ($name) {
            if (array_key_exists($name, $this->interfaces)) {
                return [$this->interfaces[$name]];
            }
            return [];
        }
        return $this->interfaces;
    }

    /**
     * Removes indicated interface from the composed class
     * @param string $interfaceName
     * @param string $namespace
     * @return Pajarotin\Compose\Compose
     */
    public function removeInterface($interfaceName, $namespace = '') {
        $interface = static::normalize($interfaceName, $namespace);
        if ($interface===null) {
            return $this;
        }
        $reinterfaces = [];
        foreach($this->interfaces as $item) {
            if ($item === $interface) {
                continue;
            }
            $reinterfaces[] = $interface;
        }
        $this->interfaces = $reinterfaces;
        return $this;
    }

    /**
     * Adds a trait to the composed class
     * @param string $traitName
     * @param string $namespace
     * @return Pajarotin\Compose\Compose
     */
    public function addTrait($traitName, $namespace = '') {
        $trait = static::normalize($traitName, $namespace);
        if ($trait===null) {
            return $this;
        }
        if (!in_array($trait, $this->traits)) {
            $this->traits[] = $trait;
        }
        return $this;
    }

    /**
     * retrieves defined traits, filtered by $name if indicated
     * @param $name
     * @return string[]
     */
    public function getTraits($name = null) {
        if ($name) {
            if (array_key_exists($name, $this->traits)) {
                return [$this->traits[$name]];
            }
            return [];
        }
        return $this->traits;
    }

    /**
     * Removes indicated trait from the composed class
     * @param string $traitName
     * @param string $namespace
     * @return Pajarotin\Compose\Compose
     */
    public function removeTrait($traitName, $namespace = '') {
        $trait = static::normalize($traitName, $namespace);
        if ($trait===null) {
            return $this;
        }
        $retraits = [];
        foreach($this->traits as $item) {
            if ($item === $trait) {
                continue;
            }
            $retraits[] = $trait;
        }
        $this->traits = $retraits;
        return $this;
    }

    /**
     * Adds a constant to the composed class
     * @param string $name
     * @param mixed $value
     * @param Compose::PUBLIC|Compose::PROTECTED|Compose::PRIVATE $visibility
     * @return Pajarotin\Compose\Compose
     */
    public function addConstant($name, $value, $visibility = Compose::PUBLIC) {
        if (!strlen($name)) {
            return $this;
        }
        $std = new \stdClass();
        $std->name = $name;
        $std->value = $value;
        $std->visibility = $visibility;
        $this->constants[$name] = $std;
        return $this;
    }

    /**
     * retrieves defined constants info, filtered by $name if indicated
     * @param $name
     * @return stdClass[]
     */
    public function getConstants($name = null) {
        if ($name) {
            if (array_key_exists($name, $this->constants)) {
                return [$this->constants[$name]];
            }
            return [];
        }
        return $this->constants;
    }

    /**
     * Removes indicated constant from the composed class
     * @param string $name
     * @return Pajarotin\Compose\Compose
     */
    public function removeConstant($name) {
        if (!strlen($name)) {
            return $this;
        }
        if (array_key_exists($name, $this->constants)) {
            unset($this->constants[$name]);
        }
        return $this;
    }

    /**
     * Adds a property to the composed class
     * @param string $name
     * @param bool $hasDefaultValue
     * @param mixed $defaultValue
     * @param Compose::PUBLIC|Compose::PROTECTED|Compose::PRIVATE $visibility
     * @param Compose::INSTANCE|Compose::STATIC $scope
     * @param string $type
     * @return Pajarotin\Compose\Compose
     */
    public function addProperty($name, $hasDefaultValue, $defaultValue, $visibility = Compose::PRIVATE, $scope = Compose::INSTANCE, $type = null) {
        if (!strlen($name)) {
            return $this;
        }
        $std = new \stdClass();
        $std->name = $name;
        $std->hasDefaultValue = $hasDefaultValue;
        $std->defaultValue = $defaultValue;
        $std->visibility = $visibility;
        $std->scope = $scope;
        $std->type = $type;
        $this->properties[$name] = $std;
        return $this;
    }

    /**
     * retrieves defined properties info, filtered by $name if indicated
     * @param $name
     * @return stdClass[]
     */
    public function getProperties($name = null) {
        if ($name) {
            if (array_key_exists($name, $this->properties)) {
                return [$this->properties[$name]];
            }
            return [];
        }
        return $this->properties;
    }

    /**
     * Removes indicated property from the composed class
     * @param string $name
     * @return Pajarotin\Compose\Compose
     */
    public function removeProperty($name) {
        if (!strlen($name)) {
            return $this;
        }
        if (array_key_exists($name, $this->properties)) {
            unset($this->properties[$name]);
        }
        return $this;
    }

    /**
     * Adds a method to the composed class
     * @param string $name In the PHP way, method names are case insensitive
     * @param string | Closure $value
     *  If $value IS A STRING shouldn't include function name. For example: 
     *  "(x) { 
     *       if (x < 0) {
     *          return -x;
     *      } else {
     *          return x;
     *      }
     *  }"
     *  If $value IS A CLOSURE must be defined in its own line
     *  Defining the closure inline in the arguments of addMethod won't work
     *  due to limitations in the source extraction code
     * @param Compose::PUBLIC|Compose::PROTECTED|Compose::PRIVATE $visibility
     * @param Compose::INSTANCE|Compose::STATIC $scope
     * @param Compose::OVERRIDABLE|Compose::ABSTRACT|Compose::FINAL  $overriding
     * @param boolean $returnsReference
     * @return Pajarotin\Compose\Compose
     */
    public function addMethod($name, $value, $visibility = Compose::PRIVATE, $scope = Compose::INSTANCE, $overriding = Compose::OVERRIDABLE, $returnsReference = false) {
        if (is_a($value, 'Closure')) {
            $method = new \ReflectionFunction($value);
            $code = static::readMethod($method);
            $value = static::extractYummy($code);
        }
        if ($overriding == static::ABSTRACT) {
            $pos = strpos($value, '{');
            if ($pos !== false) {
                $value = trim(substr($value, 0, $pos));
            }
        }
        $std = new \stdClass();
        $std->name = $name;
        $std->value = $value;
        $std->visibility = $visibility;
        $std->scope = $scope;
        $std->overriding = $overriding;
        $std->returnsReference = $returnsReference;
        $lowerName = strtolower($name);
        $this->methods[$lowerName] = $std;
        return $this;
    }

    /**
     * retrieves defined methods info, filtered by $name if indicated
     * @param $name
     * @return stdClass[]
     */
    public function getMethods($name = null) {
        if ($name) {
            if (array_key_exists($name, $this->methods)) {
                return [$this->methods[$name]];
            }
            return [];
        }
        return $this->methods;
    }

    /**
     * Removes indicated method from the composed class
     * @param string $name In the PHP way, method names are case insensitive
     * @return Pajarotin\Compose\Compose
     */
    public function removeMethod($name) {
        if (!strlen($name)) {
            return $this;
        }
        $lowerName = strtolower($name);
        if (array_key_exists($lowerName, $this->methods)) {
            unset($this->methods[$lowerName]);
        }
        return $this;
    }

    /**
     * Copies:
     * - interfaces
     * - traits
     * - constants
     * - properties
     * - methods
     * From the class indicated in the arguments to the composed class
     * Something similiar to the the javascript operation: Object.assign({}, a);
     * But with classes, NO with objects
     * @param string $className
     * @param string $namespace
     * @return Pajarotin\Compose\Compose
     */
    public function fuseClass($className, $namespace = null) {
        $reflection = static::getClassReflection($className, $namespace);
        // $extension = $reflection->getExtension();
        if ($interfaces = $reflection->getInterfaces()) {
            foreach($interfaces as $interface) {
                if (!in_array($interface->name, $this->interfaces)) {
                    $this->interfaces[] = $interface->name;
                }
            }
        }

        if ($traits = $reflection->getTraits()) {
            foreach($traits as $trait) {
                if (!in_array($trait->name, $this->traits)) {
                    $this->traits[] = $trait->name;
                }
            }
        }

        if ($constants = $reflection->getConstants(\ReflectionClassConstant::IS_PRIVATE)) {
            foreach($constants as $name => $value) {
                $this->addConstant($name, $value, static::PRIVATE);
            }
        }
        if ($constants = $reflection->getConstants(\ReflectionClassConstant::IS_PROTECTED)) {
            foreach($constants as $name => $value) {
                $this->addConstant($name, $value, static::PROTECTED);
            }
        }
        if ($constants = $reflection->getConstants(\ReflectionClassConstant::IS_PUBLIC)) {
            foreach($constants as $name => $value) {
                $this->addConstant($name, $value, static::PUBLIC);
            }
        }

        if ($properties = $reflection->getProperties()) {
            foreach($properties as $property) {
                $this->fuseProperty($property);
            }
        }
        
        if ($methods = $reflection->getMethods()) {
            foreach($methods as $method) {
                $this->fuseMethod($method);
            }
        }
        return $this;
    }

    /**
     * Copies constant from donor class indicated in the arguments, to the composed class
     * @param string $className
     * @param string $name constant name in donor class
     * @param string $namespace
     * @return Pajarotin\Compose\Compose
     */
    public function fuseClassConstant($className, $name, $namespace = null) {
        $reflection = static::getClassReflection($className, $namespace);
        $fused = false;
        if (!$fused && ($constants = $reflection->getConstants(\ReflectionClassConstant::IS_PRIVATE))) {
            if (array_key_exists($name, $constants)) {
                $this->addConstant($name, $constants[$name], static::PRIVATE);
                $fused =true;
            }
        }
        if (!$fused && ($constants = $reflection->getConstants(\ReflectionClassConstant::IS_PROTECTED))) {
            if (array_key_exists($name, $constants)) {
                $this->addConstant($name, $constants[$name], static::PROTECTED);
                $fused =true;
            }
        }
        if (!$fused && ($constants = $reflection->getConstants(\ReflectionClassConstant::IS_PUBLIC))) {
            if (array_key_exists($name, $constants)) {
                $this->addConstant($name, $constants[$name], static::PUBLIC);
                $fused =true;
            }
        }
        return $this;
    }

    /**
     * Copies constant from donor class indicated in the arguments, to the composed class
     * @param string $className
     * @param string $name constant name in donor class
     * @param string $namespace
     * @return Pajarotin\Compose\Compose
     */
    public function fuseClassProperty($className, $name, $namespace = null) {
        $reflection = static::getClassReflection($className, $namespace);
        $properties = $reflection->getProperties();
        foreach($properties as $property) {
            if ($property->name != $name) {
                continue;
            }
            $this->fuseProperty($property);
        }
        return $this;
    }

    /**
     * Copies method from donor class indicated in the arguments, to the composed class
     * @param string $className
     * @param string $name constant name in donor class
     * @param string $namespace
     * @return Pajarotin\Compose\Compose
     */
    public function fuseClassMethod($className, $name, $namespace = null) {
        $reflection = static::getClassReflection($className, $namespace);
        $methods = $reflection->getMethods();
        $lower = strtolower($name);
        foreach($methods as $method) {
            if (strtolower($method->name) != $lower) {
                continue;
            }
            $this->fuseMethod($method);
        }
        return $this;
    }

    /**
     * Given the indicated $className and $namespace, retrieves the reflection class Object associated
     * @param string $className
     * @param string $namespace
     * @return ReflectionClass
     */
    protected function getClassReflection($className, $namespace) {
        $fullName = static::normalize($className, $namespace);
        if ($fullName===null) {
            return null;
        }
        return new \ReflectionClass($fullName);
    }

    /**
     * Generates source code with the configurated data, evaluates it, and returns a ReflectionClass for the new composed class
     * @return ReflectionClass
     */
    public function compile() {
        $fullName = static::normalize($this->className, $this->namespace);
        if (!class_exists($fullName, false)) {
            $code = $this->code();
            static::$compiledCache[$fullName] = explode(PHP_EOL, $code);
            eval($code);
        }
        return new \ReflectionClass($fullName);
    }

    /**
     * Registers current composed class for compilation
     * Composed class will be compiled, when required by the php autoload system
     */
    public function deferredCompilation() {
        $first = true;
        if (count(static::$deferredCompilations)) {
            $first = false;
        }

        $fullName = static::normalize($this->className, $this->namespace);
        static::$deferredCompilations[$fullName] = $this;

        if ($first) {
            spl_autoload_register(function ($class_name) {
                return static::signalCompilation($class_name);
            });
        }
    }

    /**
     * signals Compose to compile the indicated $class_name
     * Composed class must have been previously registered with deferredCompilation()
     * @param string $class_name
     * @return bool
     */
    public static function signalCompilation($class_name) {
        if (class_exists($class_name, false)) {
            return true;
        }
        if (array_key_exists($class_name, static::$deferredCompilations) 
            && ($compose = static::$deferredCompilations[$class_name])) {
            if ($compose->compile()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Registers the build code contained in the Closure passed as $build
     * Composed class will be built, when required by the php autoload system
     * Example:
     * $compose = new Compose('ComposedClass');
     * $compose->setNamespace('Pajarotin);
     * $compose->deferredBuild(function($compose){
     *     $compose->fuseClass('DonorClassA', 'Pajarotin');
     * });
     * 
     * @param string $class_name
     * @return bool
     */
    public function deferredBuild($build) {
        $first = true;
        if (count(static::$deferredBuilds)) {
            $first = false;
        }

        $fullName = static::normalize($this->className, $this->namespace);
        $std = new \stdClass();
        $std->compose = $this;
        $std->build = $build;
        static::$deferredBuilds[$fullName] = $std;

        if ($first) {
            spl_autoload_register(function ($class_name) {
                return static::signalBuild($class_name);
            });
        }
    }

    /**
     * signals Compose to build the indicated $class_name
     * Composed class build code must have been previously registered with deferredBuild()
     * @param string $class_name
     * @return bool
     */
    public static function signalBuild($class_name) {
        if (class_exists($class_name, false)) {
            return true;
        }
        if (array_key_exists($class_name, static::$deferredBuilds) 
            && ($std = static::$deferredBuilds[$class_name])) {
            ($std->build)($std->compose);
            if ($std->compose->compile()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Setups in the composed class the indicated constant
     * @param array $name
     * @param mixed $value
     * @param Compose::PUBLIC|Compose::PROTECTED|Compose::PRIVATE $visibility
     */
    protected function fuseConstant($name, $value, $visibility) {
        if (!$name) {
            return;
        }
        $this->addConstant($name, $value, $visibility);
    }

    /**
     * Setups in the composed class the indicated property
     * @param ReflectionProperty[] $property
     */
    protected function fuseProperty($property) {
        if (!$property) {
            return;
        }
        $name = $property->name;
        $hasDefaultValue = $property->hasDefaultValue();
        $defaultValue = null;
        if ($hasDefaultValue) {
            $defaultValue = $property->getDefaultValue();
        }
        
        $visibility = static::PRIVATE;
        if ($property->isPrivate()) {
            $visibility = static::PRIVATE;
        }
        if ($property->isProtected()) {
            $visibility = static::PROTECTED;
        }
        if ($property->isPublic()) {
            $visibility = static::PUBLIC;
        }

        $scope = static::INSTANCE;
        if ($property->isStatic()) {
            $scope = static::STATIC;
        }

        $type = null;
        if (method_exists($property, 'hasType') && $property->hasType()) {
            $type = $property->getType()-> __toString();
        }
        
        $this->addProperty($name, $hasDefaultValue, $defaultValue, $visibility, $scope, $type);
    }

    /**
     * Setups in the composed class the indicated method
     * @param ReflectionMethod $method
     */
    protected function fuseMethod($method) {
        if (!$method) {
            return;
        }
        $name = $method->name;
        $code = static::readMethod($method);
        $value = static::extractYummy($code);
        
        $visibility = static::PRIVATE;
        if ($method->isPrivate()) {
            $visibility = static::PRIVATE;
        }
        if ($method->isProtected()) {
            $visibility = static::PROTECTED;
        }
        if ($method->isPublic()) {
            $visibility = static::PUBLIC;
        }

        $scope = static::INSTANCE;
        if ($method->isStatic()) {
            $scope = static::STATIC;
        }

        $overriding = static::OVERRIDABLE;
        if ($method->isAbstract()) {
            $overriding = static::ABSTRACT;
        }
        if ($method->isFinal()) {
            $overriding = static::FINAL;
        }

        $returnsReference = $method->returnsReference();
    
        $this->addMethod($name, $value, $visibility, $scope, $overriding, $returnsReference);
    }

    /**
     * Returns the source code of the method.
     * NOT VERY WISE, reads full lines and retrieves extra code before and after the method, if any.
     * @param ReflectionMethod $methods
     * @return string;
     */    
    protected static function readMethod($method) {
        if (array_key_exists($method->class, static::$compiledCache)) {
            $lines = static::$compiledCache[$method->class];
        } else {
            $filename = $method->getFileName();
            $lines = static::readFileLines($filename);
        }
        if ($lines === null) {
            return null;
        }
        $start = $method->getStartLine() - 1;
        $end = $method->getEndLine() - 1;
        $chunk = array_slice($lines, $start, $end - $start + 1);
        return implode('', $chunk);
    }

    /**
     * Given the source code of a method, extracts the "Yummy". For example, given:
     * function double(x) {
     *     return 2*x;
     * }
     * 
     * extractYummy would return:
     * (x) {
     *     return 2*x;
     * }
     * 
     * @param string $code
     * @return string
     */
    protected static function extractYummy($code) {
        $start = strpos($code, '(');
        $end = strrpos($code, '}');
        return substr($code, $start, $end - $start + 1);        
    }

    /**
     * Reads filename content indicated in the path to an string array of file lines
     * @param string $filename
     * @return bool|string[] returns content as string array or false in case of error
     */
    protected static function readFileLines($filename) {
        $lines = false;
        if (array_key_exists($filename, static::$filesCache)) {
            $lines = static::$filesCache[$filename];
        } else {
            $lines = file($filename);
            if ($lines !== false) {
                static::$filesCache[$filename] = $lines;
            } else {
                return null;
            }
        }
        return $lines;
    }

    /**
     * Builds the source code for the composed class
     * @return string
     */
    protected function code() {
        $class = '';
        if ($this->namespace) {
            $class .= 'namespace ' . $this->namespace . ';' . PHP_EOL . PHP_EOL;
        }

        if ($this->header) {
            $class .= $this->header . PHP_EOL . PHP_EOL;
        }
        $sep = '';
        if ($this->final) {
            $class .= $sep . 'final';
            $sep = ' ';
        }
        if ($this->abstract) {
            $class .= $sep . 'abstract';
            $sep = ' ';
        }
        $class .= $sep . 'class ' . $this->className;
        if ($this->extends) {
            $cleaned = $this->cleanCurrentNamespace($this->extends);
            $class .= ' extends ' . $cleaned;
        }

        $sep = PHP_EOL . "\timplements ";
        foreach($this->interfaces as $interface) {
            $cleaned = $this->cleanCurrentNamespace($interface);
            $class .= $sep . $cleaned;
            $sep = ', ' . PHP_EOL . "\t";
        }

        $class .= ' {' . PHP_EOL;
        $sep = "\tuse ";
        foreach($this->traits as $trait) {
            $cleaned = $this->cleanCurrentNamespace($trait);
            $class .= $sep . $cleaned;
            $sep = ', ' . PHP_EOL . "\t";
        }
        if ($this->traits) {
            $class .= ';' . PHP_EOL . PHP_EOL;
        }

        foreach($this->constants as $constant) {
            $visibility = static::visibilityToString($constant->visibility);
            $value = static::valueToString($constant->value);
            $class .= "\t" 
                    . $visibility
                    .' const '
                    . $constant->name . ' = ' . $value . ';' . PHP_EOL;
        }

        foreach($this->properties as $property) {
            $visibility = static::visibilityToString($property->visibility);
            $scope = static::scopeToString($property->scope);
            $type = $property->type;
            $hasDefaultValue = $property->hasDefaultValue;
            $defaultValue = static::valueToString($property->defaultValue);
            $class .= "\t" 
                    . $visibility
                    . ($scope ? ' ' . $scope : '')
                    . ($type ? ' ' . $type : '')
                    . ' $' . $property->name
                    . ($hasDefaultValue ? ' = ' . $defaultValue : '')
                    . ';' . PHP_EOL;
        }

        foreach($this->methods as $method) {
            $overriding = static::overridingToString($method->overriding);
            $visibility = static::visibilityToString($method->visibility);
            $scope = static::scopeToString($method->scope);
            $value = $method->value;
            
            $sep = '';
            $class .= "\t" ;
            if ($overriding) {
                $class .= $sep . $overriding;
                $sep = ' ';
            }
            if ($visibility) {
                $class .= $sep . $visibility;
                $sep = ' ';
            }
            if ($scope) {
                $class .= $sep . $scope;
                $sep = ' ';
            }
            $class .= $sep . 'function ' . ($method->returnsReference ? '&' : '') . $method->name . $method->value;
            $class .= PHP_EOL;
        }

        $class .= '}' . PHP_EOL;
        $class .= PHP_EOL;
        return $class;
    }

    /**
     * returns the keyword associated with the indicated $overriding
     * @param Compose::OVERRIDABLE|Compose::ABSTRACT|Compose::FINAL  $overriding
     */
    protected static function overridingToString($overriding) {
        switch($overriding) {
            case static::OVERRIDABLE:
                return '';
                break;
            case static::ABSTRACT:
                return 'abstract';
                break;
            case static::FINAL:
                return 'final';
                break;
        }
        return '';
    }

    /**
     * returns the keyword associated with the indicated $visibility
     * @param Compose::PUBLIC|Compose::PROTECTED|Compose::PRIVATE $visibility
     */
    protected static function visibilityToString($visibility) {
        switch($visibility) {
            case static::PRIVATE:
                return 'private';
                break;
            case static::PROTECTED:
                return 'protected';
                break;
            case static::PUBLIC:
                return 'public';
                break;
        }
        return 'public';
    }

    /**
     * returns the keyword associated with the indicated $scope
     * @param Compose::INSTANCE|Compose::STATIC $scope
     */
    protected static function scopeToString($scope) {
        switch($scope) {
            case static::STATIC:
                return 'static';
                break;
            case static::INSTANCE:
                break;
        }
        return '';
    }

    /**
     * Returns the php code string for the indicated value;
     * @param mixed $value
     * @return string
     */
    protected static function valueToString($value) {
        if ($value === null) {
            return 'null';
        }
        return var_export($value, true);
    }

    /**
     * concatenates namespace and item name: class name, trait name or interface name
     * @param string $itemName
     * @param string $namespace
     * @return [] 
     */
    protected static function normalize($itemName, $namespace) {
        if ($itemName===null || !strlen($itemName)) {
            return null;
        }
        $item = $itemName;
        if ($namespace !== null && strlen($namespace)) {
            $item = $namespace . '\\' . $itemName;
        }
        return $item;
    }

    /**
     * if current namespace is the same as the element full name prefix, this method cleans it
     * @param string $fullName
     * @return string
     */
    protected function cleanCurrentNamespace($fullName) {
        if ($this->namespace) {
            $pos = strpos($fullName, $this->namespace . '\\');
            if ($pos === 0) {
                return substr($fullName, strlen($this->namespace) + 1);
            }
        }
        return $fullName;
    }
}
