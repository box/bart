<?php
namespace Bart\Primitives;

use Bart\BaseTestCase;

class StringsTest extends BaseTestCase
{
    public function dataProviderTestTitleize()
    {
        return [
            ['hello-world', '-', '_', 'Hello_World'],
            ['hello-world', '-', null, 'Hello-World'],
            ['hello<<>>world', '<<>>', null, 'Hello<<>>World'],
        ];
    }

    public function dataProviderTestSummarizeWithoutSpecialSuffix()
    {
        $foxy = 'The quick brown fox jumps over the lazy dog';
        $foxyLen = strlen($foxy);
        return [
            [$foxy, $foxyLen, $foxy],
            [$foxy, $foxyLen + 1, $foxy],
            // Check against another type of white space
            [$foxy . "\n$foxy", $foxyLen + 5, $foxy . '...'],
            [$foxy, $foxyLen - 1, 'The quick brown fox jumps over the lazy...'],
            [$foxy, $foxyLen - 3, 'The quick brown fox jumps over the...'],
            [$foxy, $foxyLen - 5, 'The quick brown fox jumps over the...'],
            // A subject with no white space
            ['123456789', 5, '12...'],
        ];
    }

    public function dataProviderTestSummarizeWithSuffix()
    {
        $foxy = 'The quick brown fox jumps over the lazy dog';
        $foxyLen = strlen($foxy);
        return [
            [$foxy, $foxyLen, '...', $foxy],
            [$foxy, $foxyLen - 1, '*', 'The quick brown fox jumps over the lazy*'],
            // Just one character too long
            [$foxy . ' a', $foxyLen + 1, '*', 'The quick brown fox jumps over the lazy dog*'],
        ];
    }

    public function dataProviderTestStartsWith()
    {
        return [
            ['testString', 'test', true],
            ['m1.hostname', 'm1.', true],
            ['fullString', ' ', false],
            ['fullString', ' ', false],
            ['fullString', '', true],
            ['fullString', 'FULL', false],
            ['string', 'stringLonger', false],
            ['', '', true],
        ];
    }

    public function dataProviderTestEndsWith()
    {
        return [
            ['test.String.', '.', true],
            ['m1.hostname', 'm1.', false],
            ['m1.hostname', 'Name', false],
            ['stringsTest', 'est', true],
            ['stringsTest', '', true],
            ['stringsTest', ' ', false],
            ['stringsTest ', ' ', true],
            ['String', 'longerString', false],
            ['', '', true],
        ];
    }

    public function dataProviderTestEndsWithInvalidTypes()
    {
        return [
            ['string', null, 'string', 'NULL'],
            [null, null, 'NULL', 'NULL'],
            [245, 245, 'integer', 'integer'],
            [true, false, 'boolean', 'boolean'],
            ['', ['Test'], 'string', 'array'],
        ];
    }

    public function dataProviderTestStartsWithInvalidTypes()
    {
        return [
            [null, null, 'NULL', 'NULL'],
            [245, null, 'integer', 'NULL'],
            [245, false, 'integer', 'boolean'],
            [true, ['Test'], 'boolean', 'array'],
        ];
    }

    /**
     * @dataProvider dataProviderTestStartsWith
     */
    public function testStartsWith($fullString, $subString, $expectedBool)
    {
        $this->assertEquals($expectedBool, Strings::startsWith($fullString, $subString));

    }

    /**
     * @dataProvider dataProviderTestEndsWith
     */
    public function testEndsWith($fullString, $subString, $expectedBool)
    {
        $this->assertEquals($expectedBool, Strings::endsWith($fullString, $subString));

    }

    /**
     * @dataProvider dataProviderTestStartsWithInvalidTypes
     */
    public function testStartsWithInvalidTypes($fullString, $subString, $fullStringType, $subStringType)
    {
        $exceptionMsg = $this->getInvalidTypeExceptionMsg($fullStringType, $subStringType);
        $this->assertThrows('\Bart\Primitives\PrimitivesException', $exceptionMsg, function()
        use ($fullString, $subString) {
            Strings::startsWith($fullString, $subString);
        });
    }

    /**
     * @dataProvider dataProviderTestEndsWithInvalidTypes
     */
    public function testEndsWithInvalidTypes($fullString, $subString, $fullStringType, $subStringType)
    {
        $exceptionMsg = $this->getInvalidTypeExceptionMsg($fullStringType, $subStringType);
        $this->assertThrows('\Bart\Primitives\PrimitivesException', $exceptionMsg, function()
        use ($fullString, $subString) {
            Strings::endsWith($fullString, $subString);
        });
    }

    /**
     * @dataProvider dataProviderTestTitleize
     */
    public function testTitleize($subject, $delimiter, $replacement, $expected_title)
    {
        $this->assertEquals($expected_title, Strings::titleize($subject, $delimiter, $replacement));
    }

    /**
     * @dataProvider dataProviderTestSummarizeWithoutSpecialSuffix
     */
    public function testSummarizeWithoutSpecialSuffix($subject, $maxLength, $expected)
    {
        $this->assertEquals($expected, Strings::summarize($subject, $maxLength));
    }

    /**
     * @dataProvider dataProviderTestSummarizeWithSuffix
     */
    public function testSummarizeWithSuffix($subject, $maxLength, $suffix, $expected)
    {
        $this->assertEquals($expected, Strings::summarize($subject, $maxLength, $suffix));
    }

    /**
     * @param $fullStringType
     * @param $subStringType
     * @return string
     */
    private function getInvalidTypeExceptionMsg($fullStringType, $subStringType)
    {
        return 'Both passed in arguments must be of type "string". The type of argument $fullString is currently '
            . $fullStringType . ' while the type of argument $subString is currently ' . $subStringType . '.';
    }
}
