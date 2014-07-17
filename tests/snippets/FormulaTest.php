<?php

namespace rockunit\snippets;

use rock\template\snippets\Formula;
use rockunit\template\TemplateCommon;

class FormulaTest extends TemplateCommon
{
    protected function calculatePath()
    {
        $this->path = __DIR__ . '/data';
    }

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        static::clearRuntime();
    }


    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();
        static::clearRuntime();
    }

    public function testGet()
    {
        $this->assertSame(
            $this->template->replace('[[Formula
                        ?subject=`:num - 1`
                        ?operands=`{"num" : "[[+num]]"}`
                    ]]',
                    ['num'=> 8]
            ),
            '7'
        );

        $this->assertSame($this->template->getSnippet(Formula::className(), ['subject' => ':num - 1', 'operands' => ['num' => 8]]), 7);
    }
}
 