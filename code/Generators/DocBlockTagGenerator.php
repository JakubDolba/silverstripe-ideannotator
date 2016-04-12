<?php

use phpDocumentor\Reflection\DocBlock\Tag;

/**
 * Class DocBlockTagGenerator
 *
 * @package IDEAnnotator/Generators
 */
class DocBlockTagGenerator
{
    /**
     * @var array
     * Available properties to generate docblocks for.
     */
    protected static $propertyTypes = array(
        'Owner',
        'DB',
        'HasOne',
        'BelongsTo',
        'HasMany',
        'ManyMany',
        'BelongsManyMany',
        'Extensions',
    );

    /**
     * All classes that subclass Object
     * @var array
     */
    protected $extensionClasses;

    /**
     * The current class we are working with
     * @var string
     */
    protected $className = '';

    /**
     * List all the generated tags form the various generateSomeORMProperies methods
     * @see $this->getSupportedTagTypes();
     * @var array
     */
    protected $tags = array();

    /**
     * DocBlockTagGenerator constructor.
     *
     * @param string $className
     */
    public function __construct($className)
    {
        $this->className        = $className;
        $this->extensionClasses = (array)ClassInfo::subclassesFor('Object');
        $this->tags             = $this->getSupportedTagTypes();

        $this->generateORMProperties();
    }

    /**
     * @return phpDocumentor\Reflection\DocBlock\Tag[]
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * Returns the generated Tag objects as a string
     * with asterix and newline \n
     * @return string
     */
    public function getTagsAsString()
    {
        $tagString = '';

        foreach($this->tags as $tagType) {
            foreach($tagType as $tag) {
                $tagString .= ' * ' . $tag . "\n";
            }
        }

        return $tagString;
    }

    /**
     * Reset the tag list after each run
     */
    public function getSupportedTagTypes()
    {
        return array(
            'properties'=> array(),
            'methods'   => array(),
            'mixins'    => array(),
            'other'     => array()
        );
    }

    /**
     * Generates all ORM Properties
     */
    protected function generateORMProperties()
    {
        foreach (self::$propertyTypes as $type) {
            $function = 'generateORM' . $type . 'Properties';
            $this->{$function}($this->className);
        }
    }

    /**
     * Generate the Owner-properties for extensions.
     *
     * @param string $className
     */
    protected function generateORMOwnerProperties($className)
    {
        $owners = array();
        foreach ($this->extensionClasses as $class) {
            $config = Config::inst()->get($class, 'extensions', Config::UNINHERITED);
            if ($config !== null && in_array($className, $config, null)) {
                $owners[] = $class;
            }
        }
        if (count($owners)) {
            $owners[] = $className;
            $tag = implode("|", $owners) . " \$owner";
            $this->tags['properties'][$tag] = new Tag('property', $tag);
        }
    }

    /**
     * Generate the $db property values.
     *
     * @param DataObject|DataExtension $className
     */
    protected function generateORMDBProperties($className)
    {
        if ($fields = (array)Config::inst()->get($className, 'db', Config::UNINHERITED)) {
            foreach ($fields as $fieldName => $dataObjectName) {
                $prop = 'string';

                $fieldObj = Object::create_from_string($dataObjectName, $fieldName);

                if ($fieldObj instanceof Int || $fieldObj instanceof DBInt) {
                    $prop = 'int';
                } elseif ($fieldObj instanceof Boolean) {
                    $prop = 'boolean';
                } elseif ($fieldObj instanceof Float || $fieldObj instanceof DBFloat || $fieldObj instanceof Decimal) {
                    $prop = 'float';
                }
                $tag = "$prop \$$fieldName";
                $this->tags['properties'][$tag] = new Tag('property', $tag);
            }
        }
    }

    /**
     * Generate the $belongs_to property values.
     *
     * @param DataObject|DataExtension $className
     */
    protected function generateORMBelongsToProperties($className)
    {
        if ($fields = (array)Config::inst()->get($className, 'belongs_to', Config::UNINHERITED)) {
            foreach ($fields as $fieldName => $dataObjectName) {
                $tag = $dataObjectName . " \$$fieldName";
                $this->tags['methods'][$tag] = new Tag('method', $tag);
            }
        }
    }

    /**
     * Generate the $has_one property and method values.
     *
     * @param DataObject|DataExtension $className
     */
    protected function generateORMHasOneProperties($className)
    {
        if ($fields = (array)Config::inst()->get($className, 'has_one', Config::UNINHERITED)) {
            foreach ($fields as $fieldName => $dataObjectName) {
                $tag = "int \${$fieldName}ID";
                $this->tags['properties'][$tag] = new Tag('property', $tag);

                $tag = "{$dataObjectName} {$fieldName}()";
                $this->tags['methods'][$tag] = new Tag('method', $tag);
            }
        }
    }

    /**
     * Generate the $has_many method values.
     *
     * @param DataObject|DataExtension $className
     */
    protected function generateORMHasManyProperties($className)
    {
        $this->generateTagsForDataLists(
            Config::inst()->get($className, 'has_many', Config::UNINHERITED),
            'DataList'
        );
    }

    /**
     * Generate the $many_many method values.
     *
     * @param DataObject|DataExtension $className
     */
    protected function generateORMManyManyProperties($className)
    {
        $this->generateTagsForDataLists(
            Config::inst()->get($className, 'many_many', Config::UNINHERITED),
            'ManyManyList'
        );
    }

    /**
     * Generate the $belongs_many_many method values.
     *
     * @param DataObject|DataExtension $className
     */
    protected function generateORMBelongsManyManyProperties($className)
    {
        $this->generateTagsForDataLists(
            Config::inst()->get($className, 'belongs_many_many', Config::UNINHERITED),
            'ManyManyList'
        );
    }

    /**
     * Generate the mixins for DataExtensions
     *
     * @param DataObject|DataExtension $className
     */
    protected function generateORMExtensionsProperties($className)
    {
        if ($fields = (array)Config::inst()->get($className, 'extensions', Config::UNINHERITED)) {
            foreach ($fields as $fieldName) {
                $this->tags['mixins'][$fieldName] = new Tag('mixin',$fieldName);
            }
        }
    }

    /**
     * @param array $fields
     * @param string $listType
     */
    protected function generateTagsForDataLists($fields, $listType = 'DataList')
    {
        if(!empty($fields)) {
            foreach ((array)$fields as $fieldName => $dataObjectName) {
                $tag = "{$listType}|{$dataObjectName}[] {$fieldName}()";
                $this->tags['methods'][$tag] = new Tag('method', $tag);
            }
        }
    }
}
