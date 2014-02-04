<?php
namespace Phrocco\Tests;

use Phrocco\PhroccoGroup;

class PhroccoGroupTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {

    }

    /**
     * Test of the basic class loader. This description also performs a use as a test comment.
     **/

    public function testDirectoryScan()
    {
        $group = new PhroccoGroup(["i"=>__DIR__]);
        $group->process();

        $this->assertEquals(count($group->sources),3);
        foreach($group->group as $file=>$handler) {
            $this->assertInstanceOf("Phrocco\\Phrocco", $handler);
        }
    }

}