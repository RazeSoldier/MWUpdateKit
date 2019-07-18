<?php
/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 */

use RazeSoldier\MWUpKit\MediaWiki\{
    ExtensionList,
    ExtensionInstance
};
use PHPUnit\Framework\TestCase;

class ExtensionListTest extends TestCase
{
    /**
     * @return ExtensionList[]
     */
    public static function extInstanceProvider() : array
    {
        static $res;
        if ($res !== null) {
            return $res;
        }
        $res = [
            ExtensionInstance::newExtensionByPath('/mw/extensions/3D'),
            ExtensionInstance::newExtensionByPath('/mw/extensions/4D'),
            ExtensionInstance::newExtensionByPath('/mw/extensions/Test'),
            ExtensionInstance::newExtensionByPath('/mw/extensions/Hello'),
        ];
        return $res;
    }

    public function testInternalIndexSync()
    {
        $list = new ExtensionList(self::extInstanceProvider());
        $this->assertSame([
            '3D', '4D', 'Test', 'Hello'
        ], $this->getPropertyValue(ExtensionList::class, 'listIndex', $list));

        // Test synchrony after added a value
        $list[] = ExtensionInstance::newExtensionByPath('/mw/extensions/Cite');
        $this->assertSame([
            '3D', '4D', 'Test', 'Hello', 'Cite'
        ], $this->getPropertyValue(ExtensionList::class, 'listIndex', $list));

        // Test synchrony after removed a value
        unset($list['Cite']);
        $this->assertSame([
            '3D', '4D', 'Test', 'Hello'
        ], $this->getPropertyValue(ExtensionList::class, 'listIndex', $list));
    }

    public function testOffsetExists()
    {
        $list = new ExtensionList(self::extInstanceProvider());
        $this->assertTrue(isset($list['3D']));
        $this->assertFalse(isset($list['9D']));
    }

    public function testOffsetGet()
    {
        $ext = self::extInstanceProvider();
        $list = new ExtensionList($ext);
        $this->assertSame($ext[0], $list['3D']);
    }

    public function testCount()
    {
        $list = new ExtensionList(self::extInstanceProvider());
        $this->assertSame(4, count($list));
    }

    public function testForeach()
    {
        $i = 0;
        $expected = ['3D', '4D', 'Test', 'Hello'];
        $list = new ExtensionList(self::extInstanceProvider());
        foreach ($list as $key => $item) {
            $this->assertSame($expected[$i], $key);
            $this->assertSame($expected[$i], $item->getName());
            $i++;
        }
    }

    public static function failDataProvider() : array
    {
        return [
            'test-offset' => [
                '$offset is not a string',
                1.1, ExtensionInstance::newExtensionByPath('/mw/Cite'),
            ],
            'test-value' => [
                '$value is not ExtensionInstance',
                null, 233
            ],
        ];
    }

    /**
     * @dataProvider failDataProvider
     * @param string $failMsg
     * @param $offset
     * @param $value
     */
    public function testAddValueFail(string $failMsg, $offset, $value)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($failMsg);
        $list = new ExtensionList;
        $list[$offset] = $value;
    }

    private function getPropertyValue(string $classname, string $propertyName, $obj)
    {
        $prop = new \ReflectionProperty($classname, $propertyName);
        $prop->setAccessible(true);
        return $prop->getValue($obj);
    }
}
