<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Form;

use Interop\Container\ContainerInterface;
use Zend\Form\Element;
use Zend\ServiceManager\AbstractPluginManager;
use Zend\ServiceManager\ConfigInterface;
use Zend\ServiceManager\Exception\InvalidServiceException;
use Zend\ServiceManager\Factory\InvokableFactory;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Stdlib\InitializableInterface;

/**
 * Plugin manager implementation for form elements.
 *
 * Enforces that elements retrieved are instances of ElementInterface.
 */
class FormElementManager extends AbstractPluginManager
{
    /**
     * Aliases for default set of helpers
     *
     * @var array
     */
    protected $aliases = [
        'button'         => Element\Button::class,
        'Button'         => Element\Button::class,
        'captcha'        => Element\Captcha::class,
        'Captcha'        => Element\Captcha::class,
        'checkbox'       => Element\Checkbox::class,
        'Checkbox'       => Element\Checkbox::class,
        'collection'     => Element\Collection::class,
        'Collection'     => Element\Collection::class,
        'color'          => Element\Color::class,
        'Color'          => Element\Color::class,
        'csrf'           => Element\Csrf::class,
        'Csrf'           => Element\Csrf::class,
        'date'           => Element\Date::class,
        'Date'           => Element\Date::class,
        'dateselect'     => Element\DateSelect::class,
        'DateSelect'     => Element\DateSelect::class,
        'datetime'       => Element\DateTime::class,
        'DateTime'       => Element\DateTime::class,
        'datetimelocal'  => Element\DateTimeLocal::class,
        'DateTimeLocal'  => Element\DateTimeLocal::class,
        'datetimeselect' => Element\DateTimeSelect::class,
        'DateTimeSelect' => Element\DateTimeSelect::class,
        'element'        => Element::class,
        'Element'        => Element::class,
        'email'          => Element\Email::class,
        'Email'          => Element\Email::class,
        'fieldset'       => Fieldset::class,
        'Fieldset'       => Fieldset::class,
        'file'           => Element\File::class,
        'File'           => Element\File::class,
        'form'           => Form::class,
        'Form'           => Form::class,
        'hidden'         => Element\Hidden::class,
        'Hidden'         => Element\Hidden::class,
        'image'          => Element\Image::class,
        'Image'          => Element\Image::class,
        'month'          => Element\Month::class,
        'Month'          => Element\Month::class,
        'monthselect'    => Element\MonthSelect::class,
        'MonthSelect'    => Element\MonthSelect::class,
        'multicheckbox'  => Element\MultiCheckbox::class,
        'MultiCheckbox'  => Element\MultiCheckbox::class,
        'number'         => Element\Number::class,
        'Number'         => Element\Number::class,
        'password'       => Element\Password::class,
        'Password'       => Element\Password::class,
        'radio'          => Element\Radio::class,
        'Radio'          => Element\Radio::class,
        'range'          => Element\Range::class,
        'Range'          => Element\Range::class,
        'select'         => Element\Select::class,
        'Select'         => Element\Select::class,
        'submit'         => Element\Submit::class,
        'Submit'         => Element\Submit::class,
        'text'           => Element\Text::class,
        'Text'           => Element\Text::class,
        'textarea'       => Element\Textarea::class,
        'Textarea'       => Element\Textarea::class,
        'time'           => Element\Time::class,
        'Time'           => Element\Time::class,
        'url'            => Element\Url::class,
        'Url'            => Element\Url::class,
        'week'           => Element\Week::class,
        'Week'           => Element\Week::class,
    ];

    /**
     * Factories for default set of helpers
     *
     * @var array
     */
    protected $factories = [
        Element\Button::class         => InvokableFactory::class,
        Element\Captcha::class        => InvokableFactory::class,
        Element\Checkbox::class       => InvokableFactory::class,
        Element\Collection::class     => InvokableFactory::class,
        Element\Color::class          => InvokableFactory::class,
        Element\Csrf::class           => InvokableFactory::class,
        Element\Date::class           => InvokableFactory::class,
        Element\DateSelect::class     => InvokableFactory::class,
        Element\DateTime::class       => InvokableFactory::class,
        Element\DateTimeLocal::class  => InvokableFactory::class,
        Element\DateTimeSelect::class => InvokableFactory::class,
        Element::class                => InvokableFactory::class,
        Element\Email::class          => InvokableFactory::class,
        Fieldset::class               => InvokableFactory::class,
        Element\File::class           => InvokableFactory::class,
        Form::class                   => InvokableFactory::class,
        Element\Hidden::class         => InvokableFactory::class,
        Element\Image::class          => InvokableFactory::class,
        Element\Month::class          => InvokableFactory::class,
        Element\MonthSelect::class    => InvokableFactory::class,
        Element\MultiCheckbox::class  => InvokableFactory::class,
        Element\Number::class         => InvokableFactory::class,
        Element\Password::class       => InvokableFactory::class,
        Element\Radio::class          => InvokableFactory::class,
        Element\Range::class          => InvokableFactory::class,
        Element\Select::class         => InvokableFactory::class,
        Element\Submit::class         => InvokableFactory::class,
        Element\Text::class           => InvokableFactory::class,
        Element\Textarea::class       => InvokableFactory::class,
        Element\Time::class           => InvokableFactory::class,
        Element\Url::class            => InvokableFactory::class,
        Element\Week::class           => InvokableFactory::class,
    ];

    /**
     * Don't share form elements by default
     *
     * @var bool
     */
    protected $sharedByDefault = false;

    protected $instanceOf = ElementInterface::class;

    /**
     * @param null|ConfigInterface|ContainerInterface $configOrContainerInstance
     * @param array $v3config If $configOrContainerInstance is a container, this
     *     value will be passed to the parent constructor.
     */
    public function __construct($configInstanceOrParentLocator = null, array $v3config = [])
    {
        parent::__construct($configInstanceOrParentLocator, $v3config);

        $this->addInitializer([$this, 'injectFactory']);
        $this->addInitializer([$this, 'callElementInit']);
    }

    /**
     * Inject the factory to any element that implements FormFactoryAwareInterface
     *
     * @param mixed $first
     * @param mixed $second
     */
    public function injectFactory($first, $second)
    {
        if ($first instanceof ContainerInterface) {
            $container = $first;
            $instance = $second;
        } else {
            $container = $second;
            $instance = $first;
        }
        if ($instance instanceof FormFactoryAwareInterface) {
            $factory = $instance->getFormFactory();
            $factory->setFormElementManager($this);

            if ($container instanceof ServiceLocatorInterface && $container->has('InputFilterManager')) {
                $inputFilters = $container->get('InputFilterManager');
                $factory->getInputFilterFactory()->setInputFilterManager($inputFilters);
            }
        }
    }

    /**
     * Call init() on any element that implements InitializableInterface
     *
     * @internal param $element
     */
    public function callElementInit($first, $second)
    {
        if ($first instanceof ContainerInterface) {
            $instance = $second;
        } else {
            $instance = $first;
        }
        if ($instance instanceof InitializableInterface) {
            $instance->init();
        }
    }

    /**
     * Validate the plugin is of the expected type (v3).
     *
     * Validates against `$instanceOf`.
     *
     * @param  mixed $instance
     * @throws InvalidServiceException
     * @return void
     */
    public function validate($instance)
    {
        if (! $instance instanceof $this->instanceOf) {
            throw new InvalidServiceException(sprintf(
                '%s can only create instances of %s; %s is invalid',
                get_class($this),
                $this->instanceOf,
                (is_object($instance) ? get_class($instance) : gettype($instance))
            ));
        }
    }

    /**
     * Validate the plugin is of the expected type (v2).
     *
     * Proxies to `validate()`.
     *
     * @param mixed $instance
     * @throws InvalidServiceException
     */
    public function validatePlugin($instance)
    {
        $this->validate($instance);
    }

    /**
     * Retrieve a service from the manager by name
     *
     * Allows passing an array of options to use when creating the instance.
     * createFromInvokable() will use these and pass them to the instance
     * constructor if not null and a non-empty array.
     *
     * @param  string $name
     * @param  string|array $options
     * @param  bool $usePeeringServiceManagers
     * @return object
     */
    public function get($name, $options = [], $usePeeringServiceManagers = true)
    {
        if (is_string($options)) {
            $options = ['name' => $options];
        }
        return parent::get($name, $options, $usePeeringServiceManagers);
    }

    /**
     * Attempt to create an instance via an invokable class
     *
     * Overrides parent implementation by passing $creationOptions to the
     * constructor, if non-null.
     *
     * @param  string $canonicalName
     * @param  string $requestedName
     * @return null|\stdClass
     * @throws ServiceNotCreatedException If resolved class does not exist
     */
    protected function createFromInvokable($canonicalName, $requestedName)
    {
        $invokable = $this->invokableClasses[$canonicalName];

        if (null === $this->creationOptions
            || (is_array($this->creationOptions) && empty($this->creationOptions))
        ) {
            $instance = new $invokable();
        } else {
            if (isset($this->creationOptions['name'])) {
                $name = $this->creationOptions['name'];
            } else {
                $name = $requestedName;
            }

            if (isset($this->creationOptions['options'])) {
                $options = $this->creationOptions['options'];
            } else {
                $options = $this->creationOptions;
            }

            $instance = new $invokable($name, $options);
        }

        return $instance;
    }

    /**
     * Try to pull hydrator from the creation context, or instantiates it from its name
     *
     * @param  string $hydratorName
     * @return mixed
     * @throws Exception\DomainException
     */
    public function getHydratorFromName($hydratorName)
    {
        if ($this->creationContext) {
            // v3
            $services = $this->creationContext;
        } else {
            // v2
            $services = $this->serviceLocator;
        }

        if ($services && $services->has('HydratorManager')) {
            $hydrators = $services->get('HydratorManager');
            if ($hydrators->has($hydratorName)) {
                return $hydrators->get($hydratorName);
            }
        }

        if ($services && $services->has($hydratorName)) {
            return $services->get($hydratorName);
        }

        if (!class_exists($hydratorName)) {
            throw new Exception\DomainException(sprintf(
                'Expects string hydrator name to be a valid class name; received "%s"',
                $hydratorName
            ));
        }

        $hydrator = new $hydratorName;
        return $hydrator;
    }

    /**
     * Try to pull factory from the creation context, or instantiates it from its name
     *
     * @param  string $factoryName
     * @return mixed
     * @throws Exception\DomainException
     */
    public function getFactoryFromName($factoryName)
    {
        if ($this->creationContext) {
            // v3
            $services = $this->creationContext;
        } else {
            // v2
            $services = $this->serviceLocator;
        }

        if ($services && $services->has($factoryName)) {
            return $services->get($factoryName);
        }

        if (!class_exists($factoryName)) {
            throw new Exception\DomainException(sprintf(
                'Expects string factory name to be a valid class name; received "%s"',
                $factoryName
            ));
        }

        $factory = new $factoryName;
        return $factory;
    }
}
