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

namespace MetaModels\Test\Filter\Setting;

use MetaModels\Filter\Setting\Simple;

/**
 * Test simple filter settings.
 */
class SimpleTest extends TestCase
{
    /**
     * Mock a Simple filter setting.
     *
     * @param array  $properties The initialization data.
     *
     * @param string $tableName  The table name of the MetaModel to mock.
     *
     * @return Simple
     */
    protected function mockSimpleFilterSetting($properties = array(), $tableName = 'mm_unittest')
    {
        $setting = $this->getMock(
            'MetaModels\Filter\Setting\Simple',
            array(),
            array($this->mockFilterSetting($tableName), $properties)
        );

        return $setting;
    }

    /**
     * Add a parameter to the url, if it is auto_item, it will get prepended.
     *
     * @param Simple $instance The instance.
     *
     * @param string $url      The url built so far.
     *
     * @param string $name     The parameter name.
     *
     * @param mixed  $value    The parameter value.
     *
     * @return string.
     */
    protected function addUrlParameter($instance, $url, $name, $value)
    {
        $reflection = new \ReflectionMethod($instance, 'addUrlParameter');
        $reflection->setAccessible(true);
        return $reflection->invoke($instance, $url, $name, $value);
    }

    /**
     * Internal convenience method to call the protected generateSql method on the customSql instance.
     *
     * @param Simple $instance  The instance.
     *
     * @param array  $params    The filter url parameter array.
     *
     * @param string $paramName The filter url parameter name.
     *
     * @return string
     */
    protected function buildFilterUrl($instance, $params, $paramName)
    {
        $reflection = new \ReflectionMethod($instance, 'buildFilterUrl');
        $reflection->setAccessible(true);
        return $reflection->invoke($instance, $params, $paramName);
    }

    /**
     * Test adding of filter url parameters.
     *
     * @return void
     */
    public function testAddUrlParameter()
    {
        $setting = $this->mockSimpleFilterSetting();

        $this->assertEquals(
            '/foo/a/A/b/B',
            $this->addUrlParameter($setting, '/a/A/b/B', 'auto_item', 'foo'),
            'auto_item'
        );
        $this->assertEquals(
            '/a/A/b/B/bar/foo',
            $this->addUrlParameter($setting, '/a/A/b/B', 'bar', 'foo'),
            'bar'
        );
        $this->assertEquals(
            '/a/A/b/B/bar/%%25foo',
            $this->addUrlParameter($setting, '/a/A/b/B', 'bar', '%foo'),
            'bar with percent'
        );
        $this->assertEquals(
            '/a/A/b/B/bar/%%24foo',
            $this->addUrlParameter($setting, '/a/A/b/B', 'bar', '$foo'),
            'bar with dollar'
        );
    }

    /**
     * Test building of filter urls.
     *
     * @return void
     */
    public function testBuildFilterUrl()
    {
        $setting = $this->mockSimpleFilterSetting();

        $this->assertEquals(
            '%s/a/A/b/B',
            $this->buildFilterUrl($setting, array('a' => 'A', 'b' => 'B', 'auto_item' => 'AUTO'), 'auto_item'),
            'auto_item'
        );
        $this->assertEquals(
            '/AUTO/a/A%s',
            $this->buildFilterUrl($setting, array('a' => 'A', 'b' => 'B', 'auto_item' => 'AUTO'), 'b'),
            'b'
        );
        $this->assertEquals(
            '/AUTO%s/b/B',
            $this->buildFilterUrl($setting, array('a' => 'A', 'b' => 'B', 'auto_item' => 'AUTO'), 'a'),
            'a'
        );
        $this->assertEquals(
            '/AUTO/a/A/b/B%s',
            $this->buildFilterUrl($setting, array('a' => 'A', 'b' => 'B', 'auto_item' => 'AUTO'), 'c'),
            'c'
        );
        $this->assertEquals(
            '%s/a/A/b/B',
            $this->buildFilterUrl($setting, array('a' => 'A', 'b' => 'B'), 'auto_item'),
            'auto_item 2'
        );
        $this->assertEquals(
            '%s',
            $this->buildFilterUrl($setting, array(), 'auto_item'),
            'auto_item 3'
        );
        $this->assertEquals(
            '%s',
            $this->buildFilterUrl($setting, array('auto_item' => 'AUTO'), 'auto_item'),
            'auto_item 4'
        );
    }
}
