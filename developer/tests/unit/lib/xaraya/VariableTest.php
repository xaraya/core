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
        $_POST['test'] = ['itemtype' => 1, 'categories' => [2, 3]];

        $itemtype = null;
        $categories = null;
        //$this->assertTrue(xarVar::fetch('module["itemtype"]', 'int', $itemtype, 0, xarVar::NOT_REQUIRED));
        $this->assertTrue(xarVar::fetch('module[itemtype]', 'int', $itemtype, 0, xarVar::NOT_REQUIRED));
        $expected = $_POST['test']['itemtype'];
        $this->assertEquals($expected, $itemtype);

        //$this->assertTrue(xarVar::fetch('module["categories"]', 'array', $categories, array(), xarVar::NOT_REQUIRED));
        $this->assertTrue(xarVar::fetch('module[categories]', 'array', $categories, array(), xarVar::NOT_REQUIRED));
        $expected = $_POST['test']['categories'];
        $this->assertEquals($expected, $categories);

        unset($_POST['test']);
    }
}
