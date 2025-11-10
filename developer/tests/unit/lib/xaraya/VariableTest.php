<?php

use PHPUnit\Framework\TestCase;
use Xaraya\Services\xar;

final class VariableTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        xar::var()->init();
    }

    /**
     * See CategoriesProperty->checkInput()
     * @return void
     */
    public function testFetchArrayVar(): void
    {
        $_POST['testing'] = ['itemtype' => 1, 'categories' => [2, 3]];
        xar::req()->init(xar::req()->getConfig());
        xar::ctl()->init();

        $itemtype = null;
        //$this->assertTrue(xar::var()->fetch('testing["itemtype"]', 'int', $itemtype, 0, ixarVar::NOT_REQUIRED));
        $this->assertTrue(xar::var()->fetch('testing[itemtype]', 'int', $itemtype, 0, ixarVar::NOT_REQUIRED));
        $expected = $_POST['testing']['itemtype'];
        $this->assertEquals($expected, $itemtype);

        $categories = null;
        //$this->assertTrue(xar::var()->fetch('testing["categories"]', 'array', $categories, array(), ixarVar::NOT_REQUIRED));
        $this->assertTrue(xar::var()->fetch('testing[categories]', 'array', $categories, [], ixarVar::NOT_REQUIRED));
        $expected = $_POST['testing']['categories'];
        $this->assertEquals($expected, $categories);

        unset($_POST['testing']);
    }
}
