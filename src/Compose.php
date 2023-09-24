<?php
namespace Pajarotin\Compose;
/** 
 * @package Pajarotin\Compose
 * @author Alberto Mora Cao <gmlamora@gmail.com>
 * @copyright 2023 Alberto Mora Cao
 * @version $Revision: 0.1.0 $ 
 * @license https://mit-license.org/ MIT
 * 
 * "Favor composition over inheritance"
 * 
 * Compose class is an experimental tool with the previous catch-phrase in mind
 * Allows building classes or traits dynamically in a "Frankenstein way", extracting parts from others classes or traits.
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
 *   - isReadOnly($flag)
 *   - setNamespace($namespace)
 *   - extends($className, $namespace = '')
 *   - addInterface($interfaceName, $namespace = '')
 *   - removeInterface($interfaceName, $namespace = '')
 *   - addTrait($traitName, $namespace = '')
 *   - removeTrait($traitName, $namespace = '')
 *   - addConstant($name, $value, $flags = Compose::PUBLIC)
 *   - removeConstant($name)
 *   - addProperty($name, $defaultValue, $flags = Compose::PUBLIC | Compose::INSTANCE | Compose::READ_WRITE, $type = null)
 *   - removeProperty($name)
 *   - addMethod($name, $value, $flags = Compose::PUBLIC | Compose::INSTANCE | Compose::OVERRIDABLE)
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
     * type: class
     * @const TYPE_CLASS 0
     */
    const TYPE_CLASS = 0;

    /**
     * type: trait
     * @const TYPE_TRAIT 1
     */
    const TYPE_TRAIT = 1;

    /**
     * Scope: instance, regular method or property
     * @const INSTANCE 1
     */
    const INSTANCE = 1;

    /**
     * Scope: class, static method or property
     * @const STATIC 2
     */
    const STATIC = 2;

    /**
     * Visibility: public method, property or constant
     * @const PUBLIC 4
     */
    const PUBLIC = 4;

    /**
     * Visibility: protected method, property or constant
     * @const PROTECTED 8
     */
    const PROTECTED = 8;

    /**
     * Visibility: private method, property or constant
     * @const PRIVATE 16
     */
    const PRIVATE = 16;

    /**
     * Overriding: class or method is not abstract nor final
     * @const OVERRIDABLE 32
     */
    const OVERRIDABLE = 32;

    /**
     * Overriding: class or method is abstract
     * @const ABSTRACT 64
     */
    const ABSTRACT = 64;

    /**
     * Overriding: class or method is final
     * @const FINAL 128
     */
    const FINAL = 128;

    /**
     * Updating value, forces null
     * @const ISNULL 256
     */
    const ISNULL = 256;

    /**
     * Updating modificator, forces read only
     * @const READ_ONLY 512
     */
    const READ_ONLY = 512;

    /**
     * Updating modificator, forces read and write
     * @const READ_WRITE 1024
     */
    const READ_WRITE = 1024;

    /**
     * Updating modificator, erases default value
     * @const NO_DEFAULT_VALUE 2048
     */
    const NO_DEFAULT_VALUE = 2048;

    /**
     * Updating modificator, erases type info
     * @const NO_TYPE 4096
     */
    const NO_TYPE = 4096;

    /**
     * Updating modificator, method returns value
     * @const RETURNS_VALUE 8192
     */
    const RETURNS_VALUE = 8192;

    /**
     * Updating modificator, method returns reference
     * @const RETURNS_REFERENCE 16384
     */
    const RETURNS_REFERENCE = 16384;

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
     * Indicates wether composed class is read only
     * @var bool $readOnly
     */
    protected $readOnly = null;
    
    /**
     * Indicates which type of object is being composed
     * @var bool $type
     */
    protected $type = self::TYPE_CLASS;

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
        $this->abstract = false;
        $this->final = false;
        $this->readOnly = false;
        $this->className = $className;
        $this->namespace = $namespace;
        $this->interfaces = [];
        $this->traits = [];
        $this->constants = [];
        $this->properties = [];
        $this->methods = [];
    }

    /**
     * Creates a Compose object for the desired composed class indicated by $namespace\$className 
     * @param string $className
     * @param string $namespace
     */
    public static function newClass($className, $namespace = null) {
        $compose = new static($className, $namespace);
        $compose->type = static::TYPE_CLASS;
        return $compose;
    }
    
    /**
     * Creates a Compose object for the desired composed trait indicated by $namespace\$traitName 
     * @param string $traitName
     * @param string $namespace
     */
    public static function newTrait($traitName, $namespace = null) {
        $compose = new static($traitName, $namespace);
        $compose->type = static::TYPE_TRAIT;
        return $compose;
    }

    /**
     * Return the composed class/trait namespace
     * @return string
     */
    public function getNamespace() {
        return $this->namespace;
    }

    /**
     * Sets the composed class/trait namespace
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
     * Returns the composed trait name
     * @return string
     */
    public function getTraitName() {
        return $this->className;
    }

    /**
     * Sets chunk of code going after the namespace declaration and before the composed class/trait definition.
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
        if ($this->type === static::TYPE_TRAIT) {
            return $this;
        }
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
        if ($this->type === static::TYPE_TRAIT) {
            return $this;
        }
        if ($flag) {
            $this->final = true;
        } else {
            $this->final = false;
        }
        return $this;
    }

    /**
     * A truish $flag indicates read only composed class
     * @param bool $flag
     * @return Pajarotin\Compose\Compose
     */
    public function isReadOnly($flag) {
        if ($this->type === static::TYPE_TRAIT) {
            return $this;
        }
        if ($flag) {
            $this->readOnly = true;
        } else {
            $this->readOnly = false;
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
        if ($this->type === static::TYPE_TRAIT) {
            return $this;
        }
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
        if ($this->type === static::TYPE_TRAIT) {
            return $this;
        }
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
        if (strlen($name)) {
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
     * Adds a trait to the composed class/trait
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
        if (strlen($name)) {
            if (array_key_exists($name, $this->traits)) {
                return [$this->traits[$name]];
            }
            return [];
        }
        return $this->traits;
    }

    /**
     * Removes indicated trait from the composed class/trait
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
     * @param int $flags bitmap of Compose::PUBLIC|Compose::PROTECTED|Compose::PRIVATE
     * @return Pajarotin\Compose\Compose
     */
    public function addConstant($name, $value, $flags = Compose::PUBLIC) {
        if (!strlen($name) || $this->type === static::TYPE_TRAIT) {
            return $this;
        }
        $std = new \stdClass();
        $std->name = $name;
        $std->value = $value;
        $visibility =  static::filterVisibility($flags);
        if ($visibility !== null) {
            $std->visibility = $visibility;
        } else {
            $std->visibility = static::PUBLIC;
        }
        $this->constants[$name] = $std;
        return $this;
    }


    /**
     * Update constant properties
     * @param string $name
     * @param string $newName if null don't change name
     * @param mixed $value    if null don't change value, unles flag Compose::ISNULL is specified
     * @param int $flags bitmap of:
     *      Compose::PUBLIC|Compose::PROTECTED|Compose::PRIVATE changes visibility
     *      Compose::ISNULL sets null as constant value
     * if null don't change visibility
     * @return Pajarotin\Compose\Compose
     */
    public function updateConstant($name, $newName, $value, $flags) {
        $old = null;
        foreach($this->constants as $item) {
            if ($item->name === $name) {
                $old = $item;
                break;
            } 
        }
        if (!$old) {
            return $this;
        }
        $update = new \stdClass();
        if ($newName !== null) {
            $update->name = $newName;
        } else {
            $update->name = $old->name;
        }
        if ($value !== null || ($flags & static::ISNULL)) {
            $update->value = $value;            
        } else {
            $update->value = $old->value;
        }
        $visibility =  static::filterVisibility($flags);
        if ($visibility !== null) {
            $update->visibility = $visibility;
        } else {
            $update->visibility = $old->visibility;
        }
        $this->removeConstant($name);
        $this->constants[$update->name] = $update;
        return $this;
    }

    /**
     * retrieves defined constants info, filtered by $name if indicated
     * @param $name
     * @return stdClass[]
     */
    public function getConstants($name = null) {
        if (strlen($name)) {
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
     * Adds a property to the composed class/trait
     * @param string $name
     * @param mixed $defaultValue if null no default value, unless flag Compose::ISNULL is used
     * @param int $flags bitmap of:
     *      Compose::PUBLIC|Compose::PROTECTED|Compose::PRIVATE if not indicated defaults to Compose::PUBLIC
     *      Compose::INSTANCE|Compose::STATIC if not indicated defaults to Compose::INSTANCE
     *      Compose::NO_DEFAULT_VALUE if set, indicates no default value, the $defaultValue parameter is ignored
     *      Compose::ISNULL if $defaultValue is null indicates null default value
     *      Compose::READ_ONLY|Compose::READ_WRITE if not indicated, defaults to Compose::READ_WRITE
     * @param string $type if null or Compose::NO_TYPE is set, this param is ignored
     * @return Pajarotin\Compose\Compose
     */
    public function addProperty($name, $defaultValue, $flags = Compose::PUBLIC | Compose::INSTANCE, $type = null) {
        if (!strlen($name)) {
            return $this;
        }
        $std = new \stdClass();
        $std->name = $name;

        if ($flags & static::NO_DEFAULT_VALUE) {
            $std->hasDefaultValue = false;
            $std->defaultValue = null;
        } else if ($defaultValue !== null || ($flags & static::ISNULL)) {
            $std->hasDefaultValue = true;
            $std->defaultValue = $defaultValue;
        } else {
            $std->hasDefaultValue = false;
            $std->defaultValue = null;
        }

        $visibility =  static::filterVisibility($flags);
        if ($visibility !== null) {
            $std->visibility = $visibility;
        } else {
            $std->visibility = Compose::PUBLIC;
        }
        
        $scope = static::filterScope($flags);
        if ($scope !== null) {
            $std->scope = $scope;
        } else {
            $std->scope = static::INSTANCE;
        }

        if ($flags & static::NO_TYPE) {
            $std->type = null;
        } else {
            $std->type = $type;
        }

        $readOnly = static::filterReadOnly($flags);
        if ($readOnly !== null) {
            $std->readOnly = $readOnly;
        } else {
            $std->readOnly = false;
        }

        $this->properties[$name] = $std;
        return $this;
    }

    /**
     * Updates a property configuration
     * @param string $name
     * @param string $newName if null don't change name
     * @param mixed $defaultValue if null don't change default value, unless flag Compose::ISNULL is used
     * @param int $flags bitmap of:
     *      Compose::PUBLIC|Compose::PROTECTED|Compose::PRIVATE if not indicated old one is mantained
     *      Compose::INSTANCE|Compose::STATIC  if not indicated old one is mantained
     *      Compose::NO_DEFAULT_VALUE if set, erases default value, the $defaultValue parameter is ignored
     *      Compose::ISNULL if $defaultValue is null indicates new null default value
     *      Compose::READ_ONLY|Compose::READ_WRITE if not indicated defaults to old one
     * @param string $type is null defaults to old one, if Compose::NO_TYPE is set old type is erased
     * @return Pajarotin\Compose\Compose
     */
    public function updateProperty($name, $newName, $defaultValue, $flags = null, $type = null) {
        $old = null;
        foreach($this->properties as $item) {
            if ($item->name === $name) {
                $old = $item;
                break;
            } 
        }
        if (!$old) {
            return $this;
        }
        $update = new \stdClass();
        if ($newName !== null) {
            $update->name = $newName;
        } else {
            $update->name = $old->name;
        }

        if ($flags & static::NO_DEFAULT_VALUE) {
            $update->hasDefaultValue = false;
            $update->defaultValue = null;
        } else if ($defaultValue !== null || ($flags & static::ISNULL)) {
            $update->hasDefaultValue = true;
            $update->defaultValue = $defaultValue;
        } else {
            $update->hasDefaultValue = $old->hasDefaultValue;
            $update->defaultValue = $old->defaultValue;
        }

        $visibility = static::filterVisibility($flags);
        if ($visibility !== null) {
            $update->visibility = $visibility;
        } else {
            $update->visibility = $old->visibility;
        }

        $scope = static::filterScope($flags);
        if ($scope !== null) {
            $update->scope = $scope;
        } else {
            $update->scope = $old->scope;
        }

        if ($flags & static::NO_TYPE) {
            $update->type = null;
        } else if ($type !== null) {
            $update->type = $type;
        } else {
            $update->type = $old->type;
        }
        
        $readOnly = static::filterReadOnly($flags);
        if ($readOnly !== null) {
            $update->readOnly = $readOnly;
        } else {
            $update->readOnly = $old->readOnly;
        }

        $this->removeProperty($name);
        $this->properties[$update->name] = $update;
        return $this;
    }

    /**
     * retrieves defined properties info, filtered by $name if indicated
     * @param $name
     * @return stdClass[]
     */
    public function getProperties($name = null) {
        if (strlen($name)) {
            if (array_key_exists($name, $this->properties)) {
                return [$this->properties[$name]];
            }
            return [];
        }
        return $this->properties;
    }

    /**
     * Removes indicated property from the composed class/trait
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
     * Adds a method to the composed class/trait
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
     * @param int $flags bitmap of:
     *      Compose::PUBLIC|Compose::PROTECTED|Compose::PRIVATE if not indicated defaults to Compose::PUBLIC
     *      Compose::INSTANCE|Compose::STATIC if not indicated defaults to Compose::INSTANCE
     *      Compose::OVERRIDABLE|Compose::FINAL if not indicated, defaults to Compose::OVERRIDABLE
     *      Compose::ABSTRACT indicates method is abstract
     *      Compose::RETURNS_VALUE|Compose::RETURNS_REFERENCE if not indicated, defaults to Compose::RETURNS_VALUE
     * @param boolean $returnsReference
     * @return Pajarotin\Compose\Compose
     */
    public function addMethod($name, $value, $flags = Compose::PUBLIC | Compose::INSTANCE | Compose::OVERRIDABLE | Compose::RETURNS_VALUE) {
        if (!strlen($name)) {
            return $this;
        }
        if (is_a($value, 'Closure')) {
            $method = new \ReflectionFunction($value);
            $code = static::readMethod($method);
            $value = static::extractYummy($code, $flags & static::ABSTRACT);
        }
        $std = new \stdClass();
        $std->name = $name;
        $std->value = $value;
        $visibility =  static::filterVisibility($flags);
        if ($visibility !== null) {
            $std->visibility = $visibility;
        } else {
            $std->visibility = Compose::PUBLIC;
        }
        
        $scope = static::filterScope($flags);
        if ($scope !== null) {
            $std->scope = $scope;
        } else {
            $std->scope = static::INSTANCE;
        }
        
        $overriding = static::filterOverriding($flags);
        if ($overriding !== null) {
            $std->overriding = $overriding;
        } else {
            $std->overriding = static::OVERRIDABLE;
        }
        if ($flags & static::ABSTRACT) {
            $std->overriding |= static::ABSTRACT;
        }

        $returnsReference = static::filterReturnsReference($flags);
        if ($returnsReference !== null) {
            $std->returnsReference = $returnsReference;
        } else {
            $std->returnsReference = false;
        }

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
        if (strlen($name)) {
            if (array_key_exists($name, $this->methods)) {
                return [$this->methods[$name]];
            }
            return [];
        }
        return $this->methods;
    }

    /**
     * Removes indicated method from the composed class/trait
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
     * - interfaces (doesn't apply to traits)
     * - traits (doesn't apply to traits)
     * - constants (doesn't apply to traits)
     * - properties
     * - methods
     * From the class / trait indicated in the arguments to the composed class/trait
     * Something similiar to the the javascript operation: Object.assign({}, a);
     * But with classes, NO with objects
     * @param string $className can also be a trait name
     * @param string $namespace
     * @return Pajarotin\Compose\Compose
     */
    public function fuseClass($className, $namespace = null) {
        $reflection = static::getClassReflection($className, $namespace);
        // $extension = $reflection->getExtension();
        if ($this->type === static::TYPE_CLASS 
            && ($interfaces = $reflection->getInterfaces())) {
            foreach($interfaces as $interface) {
                if (!in_array($interface->name, $this->interfaces)) {
                    $this->interfaces[] = $interface->name;
                }
            }
        }

        if ($this->type === static::TYPE_CLASS 
            && ($traits = $reflection->getTraits())) {
            foreach($traits as $trait) {
                if (!in_array($trait->name, $this->traits)) {
                    $this->traits[] = $trait->name;
                }
            }
        }

        if ($this->type === static::TYPE_CLASS 
            && ($constants = $reflection->getConstants(\ReflectionClassConstant::IS_PRIVATE))) {
            foreach($constants as $name => $value) {
                $this->addConstant($name, $value, static::PRIVATE);
            }
        }
        if ($this->type === static::TYPE_CLASS 
            &&  ($constants = $reflection->getConstants(\ReflectionClassConstant::IS_PROTECTED))) {
            foreach($constants as $name => $value) {
                $this->addConstant($name, $value, static::PROTECTED);
            }
        }
        if ($this->type === static::TYPE_CLASS 
            && ($constants = $reflection->getConstants(\ReflectionClassConstant::IS_PUBLIC))) {
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
        if ($this->type === static::TYPE_TRAIT) {
            return $this;
        }
        $reflection = static::getClassReflection($className, $namespace);
        $fused = false;
        if (!$fused && ($constants = $reflection->getConstants(\ReflectionClassConstant::IS_PRIVATE))) {
            if (array_key_exists($name, $constants)) {
                $this->addConstant($name, $constants[$name], static::PRIVATE);
                $fused = true;
            }
        }
        if (!$fused && ($constants = $reflection->getConstants(\ReflectionClassConstant::IS_PROTECTED))) {
            if (array_key_exists($name, $constants)) {
                $this->addConstant($name, $constants[$name], static::PROTECTED);
                $fused = true;
            }
        }
        if (!$fused && ($constants = $reflection->getConstants(\ReflectionClassConstant::IS_PUBLIC))) {
            if (array_key_exists($name, $constants)) {
                $this->addConstant($name, $constants[$name], static::PUBLIC);
                $fused = true;
            }
        }
        return $this;
    }

    /**
     * Copies property from donor class/trait indicated in the arguments, to the composed class/trait
     * @param string $className can also be a trait name
     * @param string $name property name in donor class/trait
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
     * Copies method from donor class/trait indicated in the arguments, to the composed class/trait
     * @param string $className can also be a trait name
     * @param string $name method name in donor class
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
     * Retrieves generated source code for the indicated class/trait
     * Does not trigger deferred compilation/build
     * @param $className can also be a trait name
     * @param $namespace
     * @return string
     */
    public static function getComposedClassSource($className, $namespace) {
        $fullName = static::normalize($className, $namespace);
        if (!array_key_exists($fullName, static::$compiledCache)) {
            return null;
        }
        return implode(PHP_EOL, static::$compiledCache[$fullName]);
    }

    /**
     * Registers current composed class/trait for deferred compilation
     * Composed class/trait will be compiled, when required by the php autoload system
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
     * signals Compose to compile the indicated $class_name, can also be a trait name
     * Composed class/trait must have been previously registered with deferredCompilation()
     * @param string $class_name can also be a trait name
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
     * Registers the build code contained in the Closure passed as $build for deferred build
     * Composed class/trait will be built, when required by the php autoload system
     * Example:
     * $compose = new Compose('ComposedClass');
     * $compose->setNamespace('Pajarotin);
     * $compose->deferredBuild(function($compose){
     *     $compose->fuseClass('DonorClassA', 'Pajarotin');
     * });
     * 
     * @param string $class_name can also be a trait name
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
     * signals Compose to build the indicated $class_name, can also be a trait name
     * Composed class/trait build code must have been previously registered with deferredBuild()
     * @param string $class_name can also be a trait name
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
     * Setups in the composed class/trait the indicated property
     * @param ReflectionProperty[] $property
     */
    protected function fuseProperty($property) {
        if (!$property) {
            return;
        }
        $name = $property->name;
        $flags = 0;
        $defaultValue = null;
        if ($property->hasDefaultValue()) {
            $defaultValue = $property->getDefaultValue();
        } else {
            $flags |= static::NO_DEFAULT_VALUE;
        }
        
        if ($property->isPrivate()) {
            $flags |= static::PRIVATE;
        }
        if ($property->isProtected()) {
            $flags |= static::PROTECTED;
        }
        if ($property->isPublic()) {
            $flags |= static::PUBLIC;
        }

        if ($property->isStatic()) {
            $flags |= static::STATIC;
        } else {
            $flags |= static::INSTANCE;
        }

        $type = null;
        if (method_exists($property, 'hasType') && $property->hasType()) {
            $type = $property->getType()->__toString();
        }

        if (method_exists($property, 'isReadOnly') && $property->isReadOnly()) {
            $flags |= static::READ_ONLY;
        }
        
        $this->addProperty($name, $defaultValue, $flags, $type);
    }

    /**
     * Setups in the composed class/trait the indicated method
     * @param ReflectionMethod $method
     */
    protected function fuseMethod($method) {
        if (!$method) {
            return;
        }
        $name = $method->name;
        $flags = 0;
        $code = static::readMethod($method);
        $value = static::extractYummy($code, $method->isAbstract());
        
        if ($method->isPrivate()) {
            $flags |= static::PRIVATE;
        } else if ($method->isProtected()) {
            $flags |= static::PROTECTED;
        } else if ($method->isPublic()) {
            $flags |= static::PUBLIC;
        }

        if ($method->isStatic()) {
            $flags |= static::STATIC;
        } else {
            $flags |= static::INSTANCE;
        }

        if ($method->isAbstract()) {
            $flags |= static::ABSTRACT;
        }

        if ($method->isFinal()) {
            $flags |= static::FINAL;
        } else {
            $flags |= static::OVERRIDABLE;
        }

        if ($method->returnsReference()) {
            $flags |= static::RETURNS_REFERENCE;
        } else {
            $flags |= static::RETURNS_VALUE;
        }
    
        $this->addMethod($name, $value, $flags);
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
     * @param string $isAbstract
     * @return string
     */
    protected static function extractYummy($code, $isAbstract = false) {
        $start = strpos($code, '(');
        $end = false;
        if ($isAbstract) {
            $end = strpos($code, '{');
            if ($end !== false) {   // Discard function body
                $code = substr($code, 0, $end);
            }
            $end = strrpos($code, ')');
        } else {
            $end = strrpos($code, '}');
        }
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
     * Builds the source code for the composed class/trait
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
        if ($this->final && $this->type === static::TYPE_CLASS) {
            $class .= $sep . 'final';
            $sep = ' ';
        }
        if ($this->readOnly && $this->type === static::TYPE_CLASS) {
            $class .= $sep . 'readonly';
            $sep = ' ';
        }
        if ($this->abstract && $this->type === static::TYPE_CLASS) {
            $class .= $sep . 'abstract';
            $sep = ' ';
        }

        if ($this->type === static::TYPE_CLASS) {
            $class .= $sep . 'class ' . $this->className;
        } else if ($this->type === static::TYPE_TRAIT) {
            $class .= $sep . 'trait ' . $this->className;
        }
        if ($this->extends && $this->type === static::TYPE_CLASS) {
            $cleaned = $this->cleanCurrentNamespace($this->extends);
            $class .= ' extends ' . $cleaned;
        }

        if ($this->type === static::TYPE_CLASS) {
            $sep = PHP_EOL . "\timplements ";
            foreach($this->interfaces as $interface) {
                $cleaned = $this->cleanCurrentNamespace($interface);
                $class .= $sep . $cleaned;
                $sep = ', ' . PHP_EOL . "\t";
            }
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

        if ($this->type === static::TYPE_CLASS) {
            foreach($this->constants as $constant) {
                $visibility = static::visibilityToString($constant->visibility);
                $value = static::valueToString($constant->value);
                $class .= "\t" 
                        . $visibility
                        .' const '
                        . $constant->name . ' = ' . $value . ';' . PHP_EOL;
            }
        }

        foreach($this->properties as $property) {
            $visibility = static::visibilityToString($property->visibility);
            $scope = static::scopeToString($property->scope);
            $type = $property->type;
            $readOnly = $property->readOnly;
            $hasDefaultValue = $property->hasDefaultValue;
            $defaultValue = static::valueToString($property->defaultValue);
            $class .= "\t" 
                    . $visibility
                    . ($scope ? ' ' . $scope : '')
                    . ($readOnly ? ' readonly' : '')
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
            if ($method->overriding & static::ABSTRACT) {
                $class .= ';';    
            }
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
        $result = '';
        $sep = ' ';
        if ($overriding & static::OVERRIDABLE) {
            // NOP
        }
        if ($overriding & static::FINAL) {
            $result .= $sep . 'final';
            $sep = ' ';
        }
        if ($overriding & static::ABSTRACT) {
            $result .= $sep . 'abstract';
            $sep = ' ';
        }
        return $result;
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

    /**
     * Extracts visibility modifier: public, protected, private 
     * If multiple flags are set public takes preference over protected and protected over private. 
     * If none is set returns null
     * @param int $visibility
     * @param int|null
     */
    protected static function filterVisibility($visibility) {
        if ($visibility & static::PUBLIC) {
            return static::PUBLIC;
        } else if ($visibility & static::PROTECTED) {
            return static::PROTECTED;
        } else if ($visibility & static::PRIVATE) {
            return static::PRIVATE;
        }
        return null;
    }

    /**
     * Extracts scope modifier: instance, static 
     * If multiple flags are set instance takes preference over static.
     * If none is set returns null
     * @param int $scope
     * @param int|null
     */
    protected static function filterScope($scope) {
        if ($scope & static::INSTANCE) {
            return static::INSTANCE;
        } else if ($scope & static::STATIC) {
            return static::STATIC;
        }
        return null;
    }

    /**
     * Extracts read/write modifier: read only, read and write
     * If multiple flags are set read only takes preference over read and write
     * If none is set returns null
     * @param int $readOnly
     * @param int|null
     */
    protected static function filterReadOnly($readOnly) {
        if ($readOnly & static::READ_ONLY) {
            return true;
        } else if ($readOnly & static::READ_WRITE) {
            return false;
        }
        return null;
    }

    /**
     * Extracts overriding modifier: final, overridable
     * If multiple flags are set final takes preference over overridable
     * If none is set returns null
     * @param int $overriding
     * @param int|null
     */
    protected static function filterOverriding($overriding) {
        if ($overriding & static::FINAL) {
            return static::FINAL;
        } else if ($overriding & static::OVERRIDABLE) {
            return static::OVERRIDABLE;
        }
        return null;
    }

    /**
     * Extracts "method returns reference" flag: true/false
     * If multiple flags are set reference takes preference over value
     * If none is set returns null
     * @param int $returnsReference
     * @param bool|null
     */
    protected static function filterReturnsReference($returnsReference) {
        if ($returnsReference & static::RETURNS_REFERENCE) {
            return true;
        } else if ($returnsReference & static::RETURNS_VALUE) {
            return false;
        }
        return null;
    }
}
