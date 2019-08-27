<?php

use RazeSoldier\MWUpKit\MediaWiki\MWVersion;
use PHPUnit\Framework\TestCase;

class MWVersionTest extends TestCase
{
    /**
     * @expectedException UnexpectedValueException
     * @expectedExceptionMessage Invalid MW version: 1*35
     */
    public function testException()
    {
        new MWVersion('1*35');
    }

    public static function versionProvider() : array
    {
        return [
            ['1.22.3', '1.22'],
            ['1.31.0', '1.31'],
            ['1.33', '1.33'],
        ];
    }

    /**
     * @dataProvider versionProvider
     */
    public function testGetMainPart(string $version, string $expected)
    {
        $this->assertSame($expected, (new MWVersion($version))->getMainPart());
    }
}
