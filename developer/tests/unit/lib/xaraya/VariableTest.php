<?php

use PHPUnit\Framework\TestCase;

final class VariableTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        xarVar::init();
    }

    /**
     * See CategoriesProperty->checkInput()
     * @return void
     */
    public function testFetchArrayVar(): void
    {
        $_POST['testing'] = ['itemtype' => 1, 'categories' => [2, 3]];

        $itemtype = null;
        //$this->assertTrue(xarVar::fetch('testing["itemtype"]', 'int', $itemtype, 0, xarVar::NOT_REQUIRED));
        $this->assertTrue(xarVar::fetch('testing[itemtype]', 'int', $itemtype, 0, xarVar::NOT_REQUIRED));
        $expected = $_POST['testing']['itemtype'];
        $this->assertEquals($expected, $itemtype);

        $categories = null;
        //$this->assertTrue(xarVar::fetch('testing["categories"]', 'array', $categories, array(), xarVar::NOT_REQUIRED));
        $this->assertTrue(xarVar::fetch('testing[categories]', 'array', $categories, [], xarVar::NOT_REQUIRED));
        $expected = $_POST['testing']['categories'];
        $this->assertEquals($expected, $categories);

        unset($_POST['testing']);
    }
}
