<?php
/**
 * The MetaModels extension allows the creation of multiple collections of custom items,
 * each with its own unique set of selectable attributes, with attribute extendability.
 * The Front-End modules allow you to build powerful listing and filtering of the
 * data in each collection.
 *
 * PHP version 5
 * @package    MetaModels
 * @subpackage Tests
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @copyright  The MetaModels team.
 * @license    LGPL.
 * @filesource
 */

namespace MetaModels\Test\Attribute;

use MetaModels\Attribute\Events\CreateAttributeFactoryEvent;
use MetaModels\Attribute\Factory;
use MetaModels\Attribute\IAttributeTypeFactory;
use MetaModels\Attribute\IFactory;
use MetaModels\Test\Attribute\Mock\AttributeFactoryMocker;
use MetaModels\Test\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Test the attribute factory.
 *
 * @package MetaModels\Test\Filter\Setting
 */
class FactoryTest extends TestCase
{
    /**
     * Test to add an attribute factory to a factory and retrieve it again.
     *
     * @return void
     */
    public function testCreateFactoryFiresEvent()
    {
        $eventDispatcher = $this->mockEventDispatcher(CreateAttributeFactoryEvent::NAME, 1);
        $factory         = new Factory($eventDispatcher);

        $this->assertSame($eventDispatcher, $factory->getEventDispatcher());
    }

    /**
     * Test to add an attribute factory to a factory and retrieve it again.
     *
     * @return void
     */
    public function testAddTypeFactoryAndGetTypeFactory()
    {
        $factory = new Factory($this->mockEventDispatcher());

        $this->assertNull($factory->getTypeFactory('test'));
        $attributeFactory = $this->mockAttributeFactory('test', true, false, false);

        $this->assertSame(
            $factory,
            $factory->addTypeFactory($attributeFactory)
        );

        $this->assertSame($attributeFactory, $factory->getTypeFactory('test'));
    }

    /**
     * Test a single attribute type mock.
     *
     * @param IFactory $factory          The factory to test.
     *
     * @param string   $attributeFactory The attribute type factory to test.
     *
     * @param bool     $shouldTranslated Flag if the attribute factory should say the type is translated.
     *
     * @param bool     $shouldSimple     Flag if the attribute factory should say the type is simple.
     *
     * @param bool     $shouldComplex    Flag if the attribute factory should say the type is complex.
     *
     * @return void
     */
    protected function mockFactoryTester($factory, $attributeFactory, $shouldTranslated, $shouldSimple, $shouldComplex)
    {
        $this->assertSame(
            true,
            $factory->attributeTypeMatchesFlags(
                $attributeFactory,
                IAttributeTypeFactory::FLAG_ALL
            ),
            $attributeFactory . '.FLAG_ALL'
        );

        $this->assertSame(
            $shouldTranslated,
            $factory->attributeTypeMatchesFlags(
                $attributeFactory,
                IAttributeTypeFactory::FLAG_INCLUDE_TRANSLATED
            ),
            $attributeFactory . '.FLAG_INCLUDE_TRANSLATED'
        );

        $this->assertSame(
            $shouldSimple,
            $factory->attributeTypeMatchesFlags(
                $attributeFactory,
                IAttributeTypeFactory::FLAG_INCLUDE_SIMPLE
            ),
            $attributeFactory . '.FLAG_INCLUDE_SIMPLE'
        );

        $this->assertSame(
            $shouldComplex,
            $factory->attributeTypeMatchesFlags(
                $attributeFactory,
                IAttributeTypeFactory::FLAG_INCLUDE_COMPLEX
            ),
            $attributeFactory . '.FLAG_INCLUDE_COMPLEX'
        );
    }

    /**
     * Test that the method attributeTypeMatchesFlags() works correctly.
     *
     * @return void
     */
    public function testAttributeTypeMatchesFlags()
    {
        $factory = new Factory($this->mockEventDispatcher());
        $factory->addTypeFactory($this->mockAttributeFactory('test_translated', true, false, false));
        $factory->addTypeFactory($this->mockAttributeFactory('test_simple', false, true, false));
        $factory->addTypeFactory($this->mockAttributeFactory('test_complex', false, false, true));

        $this->mockFactoryTester($factory, 'test_translated', true, false, false);
        $this->mockFactoryTester($factory, 'test_simple', false, true, false);
        $this->mockFactoryTester($factory, 'test_complex', false, false, true);
    }

    /**
     * Test that the method attributeTypeMatchesFlags() works correctly.
     *
     * @return void
     */
    public function testGetTypeNames()
    {
        $factory = new Factory($this->mockEventDispatcher());

        $this->assertSame(
            array(),
            $factory->getTypeNames(IAttributeTypeFactory::FLAG_ALL),
            'FLAG_ALL'
        );

        $this->assertSame(
            array(),
            $factory->getTypeNames(IAttributeTypeFactory::FLAG_INCLUDE_TRANSLATED),
            'FLAG_INCLUDE_TRANSLATED'
        );

        $this->assertSame(
            array(),
            $factory->getTypeNames(IAttributeTypeFactory::FLAG_INCLUDE_SIMPLE),
            'FLAG_INCLUDE_SIMPLE'
        );

        $this->assertSame(
            array(),
            $factory->getTypeNames(IAttributeTypeFactory::FLAG_INCLUDE_COMPLEX),
            'FLAG_INCLUDE_COMPLEX'
        );

        $this->assertSame(
            array(),
            $factory->getTypeNames(IAttributeTypeFactory::FLAG_ALL_UNTRANSLATED),
            'FLAG_ALL_UNTRANSLATED'
        );

        $factory->addTypeFactory($this->mockAttributeFactory('test_translated', true, false, false));
        $factory->addTypeFactory($this->mockAttributeFactory('test_simple', false, true, false));
        $factory->addTypeFactory($this->mockAttributeFactory('test_complex', false, false, true));

        $this->assertSame(
            array(
                'test_translated',
                'test_simple',
                'test_complex',
            ),
            $factory->getTypeNames(IAttributeTypeFactory::FLAG_ALL),
            'FLAG_ALL'
        );

        $this->assertSame(
            array(
                'test_translated',
            ),
            $factory->getTypeNames(IAttributeTypeFactory::FLAG_INCLUDE_TRANSLATED),
            'FLAG_INCLUDE_TRANSLATED'
        );

        $this->assertSame(
            array(
                'test_simple',
            ),
            $factory->getTypeNames(IAttributeTypeFactory::FLAG_INCLUDE_SIMPLE),
            'FLAG_INCLUDE_SIMPLE'
        );

        $this->assertSame(
            array(
                'test_complex',
            ),
            $factory->getTypeNames(IAttributeTypeFactory::FLAG_INCLUDE_COMPLEX),
            'FLAG_INCLUDE_COMPLEX'
        );

        $this->assertSame(
            array(
                'test_simple',
                'test_complex',
            ),
            $factory->getTypeNames(IAttributeTypeFactory::FLAG_ALL_UNTRANSLATED),
            'FLAG_ALL_UNTRANSLATED'
        );
    }

    /**
     * Mock an attribute type factory.
     *
     * @param string $typeName   The type name to mock.
     *
     * @param bool   $translated Flag if the type shall be translated.
     *
     * @param bool   $simple     Flag if the type shall be simple.
     *
     * @param bool   $complex    Flag if the type shall be complex.
     *
     * @param string $class      Name of the class to instantiate when createInstance() is called.
     *
     * @return IAttributeTypeFactory
     */
    protected function mockAttributeFactory($typeName, $translated, $simple, $complex, $class = 'stdClass')
    {
        return AttributeFactoryMocker::mockAttributeFactory($this, $typeName, $translated, $simple, $complex, $class);
    }

    /**
     * Mock an event dispatcher.
     *
     * @param string $expectedEvent The name of the expected event.
     *
     * @param int    $expectedCount The amount how often this event shall get dispatched.
     *
     * @return EventDispatcherInterface
     */
    protected function mockEventDispatcher($expectedEvent = '', $expectedCount = 0)
    {
        $eventDispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcher');

        if ($expectedEvent) {
            $eventDispatcher
                ->expects($this->exactly($expectedCount))
                ->method('dispatch')
                ->with($this->equalTo($expectedEvent));
        }

        return $eventDispatcher;
    }
}
