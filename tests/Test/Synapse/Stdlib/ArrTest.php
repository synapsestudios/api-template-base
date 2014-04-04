<?php

namespace Test\Synapse\Stdlib;

use Synapse\Stdlib\Arr;
use PHPUnit_Framework_TestCase;
use Symfony\Component\HttpFoundation\Request;
use ArrayObject;
use ArrayIterator;
use stdClass;

/**
 * Tests the Arr lib that's shipped with kohana
 *
 * @group kohana
 * @group kohana.core
 * @group kohana.core.arr
 *
 * @package    Kohana
 * @category   Tests
 * @author     Kohana Team
 * @author     BRMatt <matthew@sigswitch.com>
 * @copyright  (c) 2008-2012 Kohana Team
 * @license    http://kohanaframework.org/license
 */
class ArrTest extends PHPUnit_Framework_TestCase
{
    /**
     * Provides test data for testCallback()
     *
     * @return array
     */
    public function providerCallback()
    {
        return [
            // Tests....
            // That no parameters returns null
            ['function', ['function', null]],
            // That we can get an array of parameters values
            ['function(1,2,3)', ['function', ['1', '2', '3']]],
            // That it's not just using the callback "function"
            ['different_name(harry,jerry)', ['different_name', ['harry', 'jerry']]],
            // That static callbacks are parsed into arrays
            ['kohana::appify(this)', [['kohana', 'appify'], ['this']]],
            // Spaces are preserved in parameters
            ['deal::make(me, my mate )', [['deal', 'make'], ['me', ' my mate ']]]
            // TODO: add more cases
        ];
    }

    /**
     * Tests Arr::callback()
     *
     * @test
     * @dataProvider providerCallback
     * @param string $str       String to parse
     * @param array  $expected  Callback and its parameters
     */
    public function testCallback($str, $expected)
    {
        $result = Arr::callback($str);

        $this->assertSame(2, count($result));
        $this->assertSame($expected, $result);
    }

    /**
     * Provides test data for testExtract
     *
     * @return array
     */
    public function providerExtract()
    {
        return [
            [
                ['kohana' => 'awesome', 'blueflame' => 'was'],
                ['kohana', 'cakephp', 'symfony'],
                null,
                ['kohana' => 'awesome', 'cakephp' => null, 'symfony' => null]
            ],
            // I realise noone should EVER code like this in real life,
            // but unit testing is very very very very boring
            [
                ['chocolate cake' => 'in stock', 'carrot cake' => 'in stock'],
                ['carrot cake', 'humble pie'],
                'not in stock',
                ['carrot cake' => 'in stock', 'humble pie' => 'not in stock'],
            ],
            [
                // Source Array
                ['level1' => ['level2a' => 'value 1', 'level2b' => 'value 2']],
                // Paths to extract
                ['level1.level2a', 'level1.level2b'],
                // Default
                null,
                // Expected Result
                ['level1' => ['level2a' => 'value 1', 'level2b' => 'value 2']],
            ],
            [
                // Source Array
                ['level1a' => ['level2a' => 'value 1'], 'level1b' => ['level2b' => 'value 2']],
                // Paths to extract
                ['level1a', 'level1b.level2b'],
                // Default
                null,
                // Expected Result
                ['level1a' => ['level2a' => 'value 1'], 'level1b' => ['level2b' => 'value 2']],
            ],
            [
                // Source Array
                ['level1a' => ['level2a' => 'value 1'], 'level1b' => ['level2b' => 'value 2']],
                // Paths to extract
                ['level1a', 'level1b.level2b', 'level1c', 'level1d.notfound'],
                // Default
                'default',
                // Expected Result
                [
                    'level1a' => ['level2a' => 'value 1'],
                    'level1b' => ['level2b' => 'value 2'],
                    'level1c' => 'default',
                    'level1d' => ['notfound' => 'default']],
            ],
        ];
    }

    /**
     * Tests Arr::extract()
     *
     * @test
     * @dataProvider providerExtract
     * @param array $array
     * @param array $paths
     * @param mixed $default
     * @param array $expected
     */
    public function testExtract(array $array, array $paths, $default, $expected)
    {
        $array = Arr::extract($array, $paths, $default);

        $this->assertSame(count($expected), count($array));
        $this->assertSame($expected, $array);
    }

    /**
     * Provides test data for testPluck
     *
     * @return array
     */
    public function providerPluck()
    {
        return [
            [
                [
                    ['id' => 20, 'name' => 'John Smith'],
                    ['name' => 'Linda'],
                    ['id' => 25, 'name' => 'Fred'],
                ],
                'id',
                [20, 25],
            ],
        ];
    }

    /**
     * Tests Arr::pluck()
     *
     * @test
     * @dataProvider providerPluck
     * @param array $array
     * @param string $key
     * @param array $expected
     */
    public function testPluck(array $array, $key, $expected)
    {
        $array = Arr::pluck($array, $key);

        $this->assertSame(count($expected), count($array));
        $this->assertSame($expected, $array);
    }

    /**
     * Provides test data for testGet()
     *
     * @return array
     */
    public function providerGet()
    {
        return [
            [['uno', 'dos', 'tress'], 1, null, 'dos'],
            [['we' => 'can', 'make' => 'change'], 'we', null, 'can'],
            [['uno', 'dos', 'tress'], 10, null, null],
            [['we' => 'can', 'make' => 'change'], 'he', null, null],
            [['we' => 'can', 'make' => 'change'], 'he', 'who', 'who'],
            [['we' => 'can', 'make' => 'change'], 'he', ['arrays'], ['arrays']],
        ];
    }

    /**
     * Tests Arr::get()
     *
     * @test
     * @dataProvider providerGet()
     * @param array          $array      Array to look in
     * @param string|integer $key        Key to look for
     * @param mixed          $default    What to return if $key isn't set
     * @param mixed          $expected   The expected value returned
     */
    public function testGet(array $array, $key, $default, $expected)
    {
        $this->assertSame($expected, Arr::get($array, $key, $default));
    }

    /**
     * Provides test data for testIsAssoc()
     *
     * @return array
     */
    public function providerIsAssoc()
    {
        return [
            [['one', 'two', 'three'], false],
            [['one' => 'o clock', 'two' => 'o clock', 'three' => 'o clock'], true],
        ];
    }

    /**
     * Tests Arr::isAssoc()
     *
     * @test
     * @dataProvider providerIsAssoc
     * @param array   $array     Array to check
     * @param boolean $expected  Is $array assoc
     */
    public function testIsAssoc(array $array, $expected)
    {
        $this->assertSame($expected, Arr::isAssoc($array));
    }

    /**
     * Provides test data for testIsArray()
     *
     * @return array
     */
    public function providerIsArray()
    {
        $array = ['one', 'two', 'three'];

        return [
            [$array, true],
            [new ArrayObject($array), true],
            [new ArrayIterator($array), true],
            ['not an array', false],
            [new stdClass, false],
        ];
    }

    /**
     * Tests Arr::isArray()
     *
     * @test
     * @dataProvider providerIsArray
     * @param mixed   $value     Value to check
     * @param boolean $expected  Is $value an array?
     */
    public function testIsArray($array, $expected)
    {
        $this->assertSame($expected, Arr::isArray($array));
    }

    public function testMoreThanTwoArrayMerge()
    {
        $original = ['name' => 'mary', 'children' => ['fred', 'paul']];

        $expected = ['name' => 'ann', 'children' => ['fred', 'paul', 'sally', 'mark'], 'dogs'];

        $merge_1  = ['name' => 'sue', 'children' => ['sally']];
        $merge_2  = ['name' => 'ann', 'children' => ['mark']];
        $merge_3  = ['dogs'];

        $this->assertEquals($expected, Arr::merge($original, $merge_1, $merge_2, $merge_3));
    }

    public function providerMerge()
    {
        return [
            // Test how it merges arrays and sub arrays with assoc keys
            [
                ['name' => 'mary', 'children' => ['fred', 'paul', 'sally', 'jane']],
                ['name' => 'john', 'children' => ['fred', 'paul', 'sally', 'jane']],
                ['name' => 'mary', 'children' => ['jane']],
            ],
            // See how it merges sub-arrays with numerical indexes
            [
                [['test1'], ['test2'], ['test3']],
                [['test1'], ['test2']],
                [['test2'], ['test3']],
            ],
            [
                [[['test1']], [['test2']], [['test3']]],
                [[['test1']], [['test2']]],
                [[['test2']], [['test3']]],
            ],
            [
                ['a' => ['test1','test2'], 'b' => ['test2','test3']],
                ['a' => ['test1'], 'b' => ['test2']],
                ['a' => ['test2'], 'b' => ['test3']],
            ],
            [
                ['digits' => [0, 1, 2, 3]],
                ['digits' => [0, 1]],
                ['digits' => [2, 3]],
            ],
            // See how it manages merging items with numerical indexes
            [
                [0, 1, 2, 3],
                [0, 1],
                [2, 3],
            ],
            // Try and get it to merge assoc. arrays recursively
            [
                ['foo' => 'bar', ['temp' => 'life']],
                ['foo' => 'bin', ['temp' => 'name']],
                ['foo' => 'bar', ['temp' => 'life']],
            ],
            // Bug #3139
            [
                ['foo' => ['bar']],
                ['foo' => 'bar'],
                ['foo' => ['bar']],
            ],
            [
                ['foo' => 'bar'],
                ['foo' => ['bar']],
                ['foo' => 'bar'],
            ],
            // data set #9
            // Associative, Associative
            [
                ['a' => 'K', 'b' => 'K', 'c' => 'L'],
                ['a' => 'J', 'b' => 'K'],
                ['a' => 'K', 'c' => 'L'],
            ],
            // Associative, Indexed
            [
                ['a' => 'J', 'b' => 'K', 'L'],
                ['a' => 'J', 'b' => 'K'],
                ['K', 'L'],
            ],
            // Associative, Mixed
            [
                ['a' => 'J', 'b' => 'K', 'K', 'c' => 'L'],
                ['a' => 'J', 'b' => 'K'],
                ['K', 'c' => 'L'],
            ],
            // data set #12
            // Indexed, Associative
            [
                ['J', 'K', 'a' => 'K', 'c' => 'L'],
                ['J', 'K'],
                ['a' => 'K', 'c' => 'L'],
            ],
            // Indexed, Indexed
            [
                ['J', 'K', 'L'],
                ['J', 'K'],
                ['K', 'L'],
            ],
            // Indexed, Mixed
            [
                ['K', 'K', 'c' => 'L'],
                ['J', 'K'],
                ['K', 'c' => 'L'],
            ],
            // data set #15
            // Mixed, Associative
            [
                ['a' => 'K', 'K', 'c' => 'L'],
                ['a' => 'J', 'K'],
                ['a' => 'K', 'c' => 'L'],
            ],
            // Mixed, Indexed
            [
                ['a' => 'J', 'K', 'L'],
                ['a' => 'J', 'K'],
                ['J', 'L'],
            ],
            // Mixed, Mixed
            [
                ['a' => 'K', 'L'],
                ['a' => 'J', 'K'],
                ['a' => 'K', 'L'],
            ],
            // Bug #3141
            [
                ['servers' => [['1.1.1.1', 4730], ['2.2.2.2', 4730]]],
                ['servers' => [['1.1.1.1', 4730]]],
                ['servers' => [['2.2.2.2', 4730]]],
            ],
        ];
    }

    /**
     *
     * @test
     * @dataProvider providerMerge
     */
    public function testMerge($expected, $array1, $array2)
    {
        $this->assertSame($expected, Arr::merge($array1, $array2));
    }

    /**
     * Provides test data for testPath()
     *
     * @return array
     */
    public function providerPath()
    {
        $array = [
            'foobar' => ['definition' => 'lost'],
            'kohana' => 'awesome',
            'users'  => [
                1 => ['name' => 'matt'],
                2 => ['name' => 'john', 'interests' => ['hocky' => ['length' => 2], 'football' => []]],
                3 => 'frank', // Issue #3194
            ],
            'object' => new ArrayObject(['iterator' => true]), // Iterable object should work exactly the same
        ];

        return [
            // Tests returns normal values
            [$array['foobar'], $array, 'foobar'],
            [$array['kohana'], $array, 'kohana'],
            [$array['foobar']['definition'], $array, 'foobar.definition'],
            // Custom delimiters
            [$array['foobar']['definition'], $array, 'foobar/definition', null, '/'],
            // We should be able to use null as a default, returned if the key DNX
            [null, $array, 'foobar.alternatives',  null],
            [null, $array, 'kohana.alternatives',  null],
            // Try using a string as a default
            ['nothing', $array, 'kohana.alternatives', 'nothing'],
            // Make sure you can use arrays as defaults
            [['far', 'wide'], $array, 'cheese.origins', ['far', 'wide']],
            // Ensures path() casts ints to actual integers for keys
            [$array['users'][1]['name'], $array, 'users.1.name'],
            // Test that a wildcard returns the entire array at that "level"
            [$array['users'], $array, 'users.*'],
            // Now we check that keys after a wilcard will be processed
            [[0 => [0 => 2]], $array, 'users.*.interests.*.length'],
            // See what happens when it can't dig any deeper from a wildcard
            [null, $array, 'users.*.fans'],
            // Starting wildcards, issue #3269
            [['matt', 'john'], $array['users'], '*.name'],
            // Path as array, issue #3260
            [$array['users'][2]['name'], $array, ['users', 2, 'name']],
            [$array['object']['iterator'], $array, 'object.iterator'],
        ];
    }

    /**
     * Tests Arr::path()
     *
     * @test
     * @dataProvider providerPath
     * @param string  $path       The path to follow
     * @param mixed   $default    The value to return if dnx
     * @param boolean $expected   The expected value
     * @param string  $delimiter  The path delimiter
     */
    public function testPath($expected, $array, $path, $default = null, $delimiter = null)
    {
        $this->assertSame($expected, Arr::path($array, $path, $default, $delimiter));
    }

    /**
     * Provides test data for testSetPath()
     *
     * @return array
     */
    public function providerSetPath()
    {
        return [
            // Tests returns normal values
            [['foo' => 'bar'], [], 'foo', 'bar'],
            [['kohana' => ['is' => 'awesome']], [], 'kohana.is', 'awesome'],
            [['kohana' => ['is' => 'cool', 'and' => 'slow']],
                ['kohana' => ['is' => 'cool']], 'kohana.and', 'slow'],
            // Custom delimiters
            [['kohana' => ['is' => 'awesome']], [], 'kohana/is', 'awesome', '/'],
            // Ensures set_path() casts ints to actual integers for keys
            [['foo' => ['bar']], ['foo' => ['test']], 'foo.0', 'bar'],
            // Tests if it allows arrays
            [['kohana' => ['is' => 'awesome']], [], ['kohana', 'is'], 'awesome'],
            [['foo' => [['baz' => 'bar']]], ['foo' => [['baz' => 'test']]], 'foo.0.baz', 'bar'],
        ];
    }

    /**
     * Tests Arr::setPath()
     *
     * @test
     * @dataProvider providerSetPath
     * @param string  $path       The path to follow
     * @param boolean $expected   The expected value
     * @param string  $delimiter  The path delimiter
     */
    public function testSetPath($expected, $array, $path, $value, $delimiter = null)
    {
        Arr::setPath($array, $path, $value, $delimiter);

        $this->assertSame($expected, $array);
    }

    public function testRangeWithZeroStepValue()
    {
        $expected = [];

        $this->assertSame($expected, Arr::range(0, 100));
    }

    /**
     * Provides test data for testRange()
     *
     * @return array
     */
    public function providerRange()
    {
        return [
            [1, 2],
            [1, 100],
            [25, 10],
        ];
    }

    /**
     * Tests Arr::range()
     *
     * @dataProvider providerRange
     * @param integer $step  The step between each value in the array
     * @param integer $max   The max value of the range (inclusive)
     */
    public function testRange($step, $max)
    {
        $range = Arr::range($step, $max);

        $this->assertSame((int) floor($max / $step), count($range));

        $current = $step;

        foreach ($range as $key => $value) {
            $this->assertSame($key, $value);
            $this->assertSame($current, $key);
            $this->assertLessThanOrEqual($max, $key);
            $current += $step;
        }
    }

    /**
     * Provides test data for testUnshift()
     *
     * @return array
     */
    public function providerUnshift()
    {
        return [
            [['one' => '1', 'two' => '2'], 'zero', '0'],
            [['step 1', 'step 2', 'step 3'], 'step 0', 'wow'],
        ];
    }

    /**
     * Tests Arr::unshift()
     *
     * @test
     * @dataProvider providerUnshift
     * @param array $array
     * @param string $key
     * @param mixed $value
     */
    public function testUnshift(array $array, $key, $value)
    {
        $original = $array;

        Arr::unshift($array, $key, $value);

        $this->assertNotSame($original, $array);
        $this->assertSame(count($original) + 1, count($array));
        $this->assertArrayHasKey($key, $array);

        $this->assertSame($value, reset($array));
        $this->assertSame(key($array), $key);
    }

    /**
     * Provies test data for testOverwrite
     *
     * @return array Test Data
     */
    public function providerOverwrite()
    {
        return [
            [
                ['name' => 'Henry', 'mood' => 'tired', 'food' => 'waffles', 'sport' => 'checkers'],
                ['name' => 'John', 'mood' => 'bored', 'food' => 'bacon', 'sport' => 'checkers'],
                ['name' => 'Matt', 'mood' => 'tired', 'food' => 'waffles'],
                ['name' => 'Henry', 'age' => 18],
            ],
        ];
    }

    /**
     *
     * @test
     * @dataProvider providerOverwrite
     */
    public function testOverwrite($expected, $arr1, $arr2, $arr3 = [], $arr4 = [])
    {
        $this->assertSame($expected, Arr::overwrite($arr1, $arr2, $arr3, $arr4));
    }

    /**
     * Provides test data for testMap
     *
     * @return array Test Data
     */
    public function providerMap()
    {
        return [
            ['strip_tags', ['<p>foobar</p>'], null, ['foobar']],
            ['strip_tags', [['<p>foobar</p>'], ['<p>foobar</p>']], null, [['foobar'], ['foobar']]],
            [
                'strip_tags',
                [
                    'foo' => '<p>foobar</p>',
                    'bar' => '<p>foobar</p>',
                ],
                null,
                [
                    'foo' => 'foobar',
                    'bar' => 'foobar',
                ],
            ],
            [
                'strip_tags',
                [
                    'foo' => '<p>foobar</p>',
                    'bar' => '<p>foobar</p>',
                ],
                ['foo'],
                [
                    'foo' => 'foobar',
                    'bar' => '<p>foobar</p>',
                ],
            ],
            [
                [
                    'strip_tags',
                    'trim',
                ],
                [
                    'foo' => '<p>foobar </p>',
                    'bar' => '<p>foobar</p>',
                ],
                null,
                [
                    'foo' => 'foobar',
                    'bar' => 'foobar',
                ],
            ],
        ];
    }

    /**
     *
     * @test
     * @dataProvider providerMap
     */
    public function testMap($method, $source, $keys, $expected)
    {
        $this->assertSame($expected, Arr::map($method, $source, $keys));
    }

    /**
     * Provides test data for testFlatten
     *
     * @return array Test Data
     */
    public function providerFlatten()
    {
        return [
            [['set' => ['one' => 'something'], 'two' => 'other'], ['one' => 'something', 'two' => 'other']],
            [['set' => ['something'], 'two' => 'other'], ['something', 'two' => 'other']],
        ];
    }

    /**
     *
     * @test
     * @dataProvider providerFlatten
     */
    public function testFlatten($source, $expected)
    {
        $this->assertSame($expected, Arr::flatten($source));
    }
}
