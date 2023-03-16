<?php

namespace GeograPHP\Tests\Geometry;

use GeograPHP\Geometry\Polygon;
use GeograPHP\Geometry\MultiPolygon;

class MultiPolygonTest extends \PHPUnit\Framework\TestCase
{
    public function test_multipolygon_create()
    {
        $ring1_coords = array(array(0,0,0),
                    array(0,5,1),
                    array(5,5,2),
                    array(5,0,1),
                    array(0,0,0));
        $ring2_coords = array(array(1,1,0),
                    array(1,4,1),
                    array(4,4,2),
                    array(4,1,1),
                    array(1,1,0));
        $ring3_coords = array(array(6,6,0),
                    array(6,10,1),
                    array(10,10,2),
                    array(10,6,1),
                    array(6,6,0));

        $poly1 = Polygon::from_array(array($ring1_coords, $ring2_coords), 444, true);
        $poly2 = Polygon::from_array(array($ring3_coords), 444, true);

        $mp = MultiPolygon::from_polygons(array($poly1, $poly2), 444, true);
        $this->assertTrue($mp instanceof MultiPolygon);

        $this->assertEquals(2, count($mp->polygons));
        $this->assertEquals(2, count($mp->polygons[0]->rings));
        $this->assertEquals(1, count($mp->polygons[1]->rings));
        $this->assertEquals(10, $mp->polygons[1]->rings[0]->points[2]->x);
        $this->assertEquals(10, $mp->polygons[1]->rings[0]->points[2]->y);
        $this->assertEquals(2, $mp->polygons[1]->rings[0]->points[2]->z);

        $mp = MultiPolygon::from_array(array(array($ring1_coords, $ring2_coords), array($ring3_coords)), 444, true);
        $this->assertTrue($mp instanceof MultiPolygon);
        $this->assertEquals(2, count($mp->polygons));
    }

    public function test_multipolygon_ewkb()
    {
        $ring1_coords = array(array(0,0,0,4),
                    array(0,5,1,3),
                    array(5,5,2,2),
                    array(5,0,1,1),
                    array(0,0,0,4));
        $ring2_coords = array(array(1,1,0,4),
                    array(1,4,1,3),
                    array(4,4,2,2),
                    array(4,1,1,1),
                    array(1,1,0,4));
        $ring3_coords = array(array(6,6,0,4),
                    array(6,10,1,3),
                    array(10,10,2,2),
                    array(10,6,1,1),
                    array(6,6,0,4));

      // No Z values
        $mp = MultiPolygon::from_array(array(array($ring1_coords, $ring2_coords), array($ring3_coords)), 444);
        $this->assertEquals('0106000020BC0100000200000001030000000200000005000000000000000000000000000000000000000000000000000000000000000000144000000000000014400000000000001440000000000000144000000000000000000000000000000000000000000000000005000000000000000000F03F000000000000F03F000000000000F03F0000000000001040000000000000104000000000000010400000000000001040000000000000F03F000000000000F03F000000000000F03F010300000001000000050000000000000000001840000000000000184000000000000018400000000000002440000000000000244000000000000024400000000000002440000000000000184000000000000018400000000000001840', $mp->to_hexewkb());

      // Z value
        $mp = MultiPolygon::from_array(array(array($ring1_coords, $ring2_coords), array($ring3_coords)), 444, true);
        $this->assertEquals('01060000A0BC010000020000000103000080020000000500000000000000000000000000000000000000000000000000000000000000000000000000000000001440000000000000F03F00000000000014400000000000001440000000000000004000000000000014400000000000000000000000000000F03F00000000000000000000000000000000000000000000000005000000000000000000F03F000000000000F03F0000000000000000000000000000F03F0000000000001040000000000000F03F0000000000001040000000000000104000000000000000400000000000001040000000000000F03F000000000000F03F000000000000F03F000000000000F03F00000000000000000103000080010000000500000000000000000018400000000000001840000000000000000000000000000018400000000000002440000000000000F03F00000000000024400000000000002440000000000000004000000000000024400000000000001840000000000000F03F000000000000184000000000000018400000000000000000', $mp->to_hexewkb());

      // M value
        $mp = MultiPolygon::from_array(array(array($ring1_coords, $ring2_coords), array($ring3_coords)), 444, false, true);
        $this->assertEquals('0106000060BC010000020000000103000040020000000500000000000000000000000000000000000000000000000000000000000000000000000000000000001440000000000000F03F00000000000014400000000000001440000000000000004000000000000014400000000000000000000000000000F03F00000000000000000000000000000000000000000000000005000000000000000000F03F000000000000F03F0000000000000000000000000000F03F0000000000001040000000000000F03F0000000000001040000000000000104000000000000000400000000000001040000000000000F03F000000000000F03F000000000000F03F000000000000F03F00000000000000000103000040010000000500000000000000000018400000000000001840000000000000000000000000000018400000000000002440000000000000F03F00000000000024400000000000002440000000000000004000000000000024400000000000001840000000000000F03F000000000000184000000000000018400000000000000000', $mp->to_hexewkb());

      // Z+M
        $mp = MultiPolygon::from_array(array(array($ring1_coords, $ring2_coords), array($ring3_coords)), 444, true, true);
        $this->assertEquals('01060000E0BC0100000200000001030000C00200000005000000000000000000000000000000000000000000000000000000000000000000104000000000000000000000000000001440000000000000F03F0000000000000840000000000000144000000000000014400000000000000040000000000000004000000000000014400000000000000000000000000000F03F000000000000F03F000000000000000000000000000000000000000000000000000000000000104005000000000000000000F03F000000000000F03F00000000000000000000000000001040000000000000F03F0000000000001040000000000000F03F000000000000084000000000000010400000000000001040000000000000004000000000000000400000000000001040000000000000F03F000000000000F03F000000000000F03F000000000000F03F000000000000F03F0000000000000000000000000000104001030000C00100000005000000000000000000184000000000000018400000000000000000000000000000104000000000000018400000000000002440000000000000F03F0000000000000840000000000000244000000000000024400000000000000040000000000000004000000000000024400000000000001840000000000000F03F000000000000F03F0000000000001840000000000000184000000000000000000000000000001040', $mp->to_hexewkb());
    }

    public function test_multipolygon_ewkt()
    {
        $ring1_coords = array(array(0,0,0,4),
                    array(0,5,1,3),
                    array(5,5,2,2),
                    array(5,0,1,1),
                    array(0,0,0,4));
        $ring2_coords = array(array(1,1,0,4),
                    array(1,4,1,3),
                    array(4,4,2,2),
                    array(4,1,1,1),
                    array(1,1,0,4));
        $ring3_coords = array(array(6,6,0,4),
                    array(6,10,1,3),
                    array(10,10,2,2),
                    array(10,6,1,1),
                    array(6,6,0,4));

      // No Z values
        $mp = MultiPolygon::from_array(array(array($ring1_coords, $ring2_coords), array($ring3_coords)), 444);
        $this->assertEquals('SRID=444;MULTIPOLYGON(((0 0,0 5,5 5,5 0,0 0),(1 1,1 4,4 4,4 1,1 1)),((6 6,6 10,10 10,10 6,6 6)))', $mp->to_ewkt());

      // Z value
        $mp = MultiPolygon::from_array(array(array($ring1_coords, $ring2_coords), array($ring3_coords)), 444, true);
        $this->assertEquals('SRID=444;MULTIPOLYGON(((0 0 0,0 5 1,5 5 2,5 0 1,0 0 0),(1 1 0,1 4 1,4 4 2,4 1 1,1 1 0)),((6 6 0,6 10 1,10 10 2,10 6 1,6 6 0)))', $mp->to_ewkt());

      // M value
        $mp = MultiPolygon::from_array(array(array($ring1_coords, $ring2_coords), array($ring3_coords)), 444, false, true);
        $this->assertEquals('SRID=444;MULTIPOLYGONM(((0 0 0,0 5 1,5 5 2,5 0 1,0 0 0),(1 1 0,1 4 1,4 4 2,4 1 1,1 1 0)),((6 6 0,6 10 1,10 10 2,10 6 1,6 6 0)))', $mp->to_ewkt());

      // Z+M
        $mp = MultiPolygon::from_array(array(array($ring1_coords, $ring2_coords), array($ring3_coords)), 444, true, true);
        $this->assertEquals('SRID=444;MULTIPOLYGON(((0 0 0 4,0 5 1 3,5 5 2 2,5 0 1 1,0 0 0 4),(1 1 0 4,1 4 1 3,4 4 2 2,4 1 1 1,1 1 0 4)),((6 6 0 4,6 10 1 3,10 10 2 2,10 6 1 1,6 6 0 4)))', $mp->to_ewkt());
    }
}
