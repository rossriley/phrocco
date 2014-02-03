<?php
namespace Phrocco\Tests;

use Phrocco\Phrocco;

class PhroccoBasicTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {

    }

    /**
     * Test of the basic class loader. This description also performs a use as a test comment.
     *
     * @return void
     **/

    public function testLoad()
    {
        $test = new Phrocco("php",__FILE__);
        $test->parse();
        $this->assertTrue(count($test->sections["code"])>0);
        $this->assertTrue(count($test->sections["docs"])>0);
        print_r($test); exit;
    }

}