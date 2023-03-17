<?php

namespace GeograPHP\Tests\Parser;

use GeograPHP\Constants;
use GeograPHP\Parser\EWKT\Parser;
use GeograPHP\Parser\EWKT\Tokenizer;
use GeograPHP\Parser\EWKT\FormatError;
use GeograPHP\Geometry\Point;
use GeograPHP\Geometry\LineString;
use GeograPHP\Geometry\Polygon;
use GeograPHP\Geometry\MultiPoint;
use GeograPHP\Geometry\MultiLineString;
use GeograPHP\Geometry\MultiPolygon;
use GeograPHP\Geometry\GeometryCollection;

class ParserTest extends \PHPUnit\Framework\TestCase
{
    protected $parser;

    protected function setUp(): void
    {
        $this->parser = new Parser();
    }

    public function test_tokenizer()
    {
        $t = new Tokenizer("MULTIPOINT(5.5  6, 0 -7.88 ) ");

        $this->assertEquals('MULTIPOINT', $t->check_next_token());
        $this->assertEquals('MULTIPOINT', $t->get_next_token());
        $this->assertEquals('(', $t->get_next_token());
        $this->assertEquals('5.5', $t->check_next_token());
        $this->assertEquals('5.5', $t->get_next_token());
        $this->assertEquals('6', $t->get_next_token());
        $this->assertEquals(',', $t->get_next_token());
        $this->assertEquals('0', $t->get_next_token());
        $this->assertEquals('-7.88', $t->get_next_token());
        $this->assertEquals(')', $t->get_next_token());
        $this->assertEquals(null, $t->get_next_token());
        $this->assertEquals(null, $t->check_next_token());
        $t->done();
    }

    public function test_fail_truncated_data()
    {
        $this->expectException('GeograPHP\Parser\EWKT\FormatError');
        $point = $this->parser->parse('POINT(4.5');
    }

    public function test_fail_extra_data()
    {
        $this->expectException('GeograPHP\Parser\EWKT\FormatError');
        // Added asdf to the end
        $point = $this->parser->parse('POINT(3.4 4)asdf');
    }

    public function test_fail_bad_geometry_type()
    {
        $this->expectException('GeograPHP\Parser\EWKT\FormatError');
        $point = $this->parser->parse('BOGUS(3 4 5)');
    }

    public function test_fail_no_m()
    {
        $this->expectException('GeograPHP\Parser\EWKT\FormatError');
        // Turned on with_m flag, but no m coordinate
        $point = $this->parser->parse('POINTM(3 4)');
    }

    public function test_point2()
    {
        $point = $this->parser->parse('SRID=444;POINT(3 5)');
        $this->assertTrue($point instanceof Point);
        $this->assertEquals(444, $point->srid);
        $this->assertEquals(3, $point->x);
        $this->assertEquals(5, $point->y);
        $this->assertEquals(false, $point->with_z);
        $this->assertEquals(false, $point->with_m);
    }

    public function test_point3z()
    {
        $point = $this->parser->parse('SRID=444;POINT(3 5 -7.333)');
        $this->assertTrue($point instanceof Point);
        $this->assertEquals(444, $point->srid);
        $this->assertEquals(3, $point->x);
        $this->assertEquals(5, $point->y);
        $this->assertEquals(-7.333, $point->z);
    }

    public function test_point3m()
    {
        $point = $this->parser->parse('SRID=444;POINTM(3 5 -7.333)');
        $this->assertTrue($point instanceof Point);
        $this->assertEquals(444, $point->srid);
        $this->assertEquals(3, $point->x);
        $this->assertEquals(5, $point->y);
        $this->assertEquals(-7.333, $point->m);
    }

    public function test_point4()
    {
        $point = $this->parser->parse('SRID=444;POINT(3 5 -7.333 105.677777)');
        $this->assertTrue($point instanceof Point);
        $this->assertEquals(444, $point->srid);
        $this->assertEquals(3, $point->x);
        $this->assertEquals(5, $point->y);
        $this->assertEquals(-7.333, $point->z);
        $this->assertEquals(105.677777, $point->m);
    }

    public function test_point_no_srid()
    {
        $point = $this->parser->parse('POINT(3 5)');
        $this->assertTrue($point instanceof Point);
        $this->assertEquals(Constants::DEFAULT_SRID, $point->srid);
        $this->assertEquals(3, $point->x);
        $this->assertEquals(5, $point->y);
    }

    public function test_linestring()
    {
        $coords = array(array(3, 5, 1.04, 4), array(-5.55, 3.14, 25.5, 5));

        // 2d
        $line = $this->parser->parse('LINESTRING(3 5, -5.55 3.14)');
        $this->assertTrue($line instanceof LineString);
        $this->assertEquals(Constants::DEFAULT_SRID, $line->srid);
        $this->assertEquals(2, count($line->points));
        $this->assertEquals(LineString::from_array($coords), $line);

        // 3dz
        $line = $this->parser->parse('SRID=444;LINESTRING(3 5 1.04, -5.55 3.14 25.5)');
        $this->assertTrue($line instanceof LineString);
        $this->assertEquals(LineString::from_array($coords, 444, true), $line);

        // 3dm
        $line = $this->parser->parse('SRID=444;LINESTRINGM(3 5 1.04, -5.55 3.14 25.5)');
        $this->assertTrue($line instanceof LineString);
        $this->assertEquals(LineString::from_array($coords, 444, false, true), $line);

        // 4d
        $line = $this->parser->parse('SRID=444;LINESTRING(3 5 1.04 4, -5.55 3.14 25.5 5)');
        $this->assertTrue($line instanceof LineString);
        $this->assertEquals(LineString::from_array($coords, 444, true, true), $line);
    }

    public function test_polygon()
    {
        $ring1_coords = array(array(0,0,0,4),
                        array(0,5,1,3),
                        array(5,5,2,2),
                        array(5,0,1,1),
                        array(0,0,0,4));
        $ring2_coords = array(array(1,1,0,-2),
                        array(1,4,1,-3),
                        array(4,4,2,-4),
                        array(4,1,1,-5),
                        array(1,1,0,-2));

        // 2d
        $poly = $this->parser->parse('SRID=444;POLYGON((0 0, 0 5, 5 5, 5 0, 0 0),(1 1, 1 4, 4 4, 4 1, 1 1))');
        $this->assertTrue($poly instanceof Polygon);
        $this->assertEquals(444, $poly->srid);
        $this->assertEquals(Polygon::from_array(array($ring1_coords, $ring2_coords), 444), $poly);

        // 3dz
        $poly = $this->parser->parse('SRID=444;POLYGON((0 0 0, 0 5 1, 5 5 2, 5 0 1, 0 0 0),(1 1 0, 1 4 1, 4 4 2, 4 1 1, 1 1 0))');
        $this->assertTrue($poly instanceof Polygon);
        $this->assertEquals(Polygon::from_array(array($ring1_coords, $ring2_coords), 444, true), $poly);

        // 3dm
        $poly = $this->parser->parse('SRID=444;POLYGONM((0 0 0, 0 5 1, 5 5 2, 5 0 1, 0 0 0),(1 1 0, 1 4 1, 4 4 2, 4 1 1, 1 1 0))');
        $this->assertTrue($poly instanceof Polygon);
        $this->assertEquals(Polygon::from_array(array($ring1_coords, $ring2_coords), 444, false, true), $poly);

        // 4d
        $poly = $this->parser->parse('SRID=444;POLYGON((0 0 0 4, 0 5 1 3, 5 5 2 2, 5 0 1 1, 0 0 0 4),(1 1 0 -2, 1 4 1 -3, 4 4 2 -4, 4 1 1 -5, 1 1 0 -2))');
        $this->assertTrue($poly instanceof Polygon);
        $this->assertEquals(Polygon::from_array(array($ring1_coords, $ring2_coords), 444, true, true), $poly);
    }

    public function test_multipoint()
    {
        $coords = array(array(3, 5, 1.04, 4), array(-5.55, 3.14, 25.5, 5));

        // 2d
        $line = $this->parser->parse('MULTIPOINT( (3 5 ), ( -5.55 3.14) )');
        $this->assertTrue($line instanceof MultiPoint);
        $this->assertEquals(Constants::DEFAULT_SRID, $line->srid);
        $this->assertEquals(2, count($line->points));
        $this->assertEquals(MultiPoint::from_array($coords), $line);

        // 3dz
        $line = $this->parser->parse('SRID=444;MULTIPOINT( (3 5 1.04 ), ( -5.55 3.14  25.5) )');
        $this->assertTrue($line instanceof MultiPoint);
        $this->assertEquals(444, $line->srid);
        $this->assertEquals(MultiPoint::from_array($coords, 444, true), $line);

        // 3dm
        $line = $this->parser->parse('SRID=444;MULTIPOINTM( (3 5 1.04 ), ( -5.55 3.14  25.5) )');
        $this->assertTrue($line instanceof MultiPoint);
        $this->assertEquals(MultiPoint::from_array($coords, 444, false, true), $line);

        // 4d
        $line = $this->parser->parse('SRID=444;MULTIPOINT((3 5 1.04 4),(-5.55 3.14 25.5 5))');
        $this->assertTrue($line instanceof MultiPoint);
        $this->assertEquals(MultiPoint::from_array($coords, 444, true, true), $line);

        // 3dz - PostGIS format
        $line = $this->parser->parse('SRID=444;MULTIPOINT(3 5 1.04, -5.55 3.14 25.5)');
        $this->assertTrue($line instanceof MultiPoint);
        $this->assertEquals(444, $line->srid);
        $this->assertEquals(MultiPoint::from_array($coords, 444, true), $line);
    }

    public function test_multilinestring()
    {
        $line1_coords = array(array(0,0,0,4),
                              array(0,5,1,3),
                              array(5,5,2,2),
                              array(5,0,1,1),
                              array(0,0,0,4));
        $line2_coords = array(array(1,1,0,-2),
                              array(1,4,1,-3),
                              array(4,4,2,-4),
                              array(4,1,1,-5),
                              array(1,1,0,-2));

        // 2d
        $line = $this->parser->parse('SRID=444;MULTILINESTRING((0 0, 0 5, 5 5, 5 0, 0 0), (1 1, 1 4, 4 4, 4 1, 1 1))');
        $this->assertTrue($line instanceof MultiLineString);
        $this->assertEquals(444, $line->srid);
        $this->assertEquals(MultiLineString::from_array(array($line1_coords, $line2_coords), 444), $line);

        // 3dz
        $line = $this->parser->parse('SRID=444;MULTILINESTRING ((0 0 0  , 0 5 1, 5 5 2 , 5 0 1, 0  0 0),(1 1 0 , 1 4 1, 4 4 2, 4 1 1, 1 1 0) ) ');
        $this->assertTrue($line instanceof MultiLineString);
        $this->assertEquals(MultiLineString::from_array(array($line1_coords, $line2_coords), 444, true), $line);

        // 3dm
        $line = $this->parser->parse('SRID=444;MULTILINESTRINGM ((0 0 0  , 0 5 1, 5 5 2 , 5 0 1, 0  0 0),(1 1 0 , 1 4 1, 4 4 2, 4 1 1, 1 1 0) ) ');
        $this->assertTrue($line instanceof MultiLineString);
        $this->assertEquals(MultiLineString::from_array(array($line1_coords, $line2_coords), 444, false, true), $line);

        // 4d
        $line = $this->parser->parse('SRID=444;MULTILINESTRING((0 0 0 4, 0 5 1 3, 5 5 2 2, 5 0 1 1, 0 0 0 4),(1 1 0 -2, 1 4 1 -3, 4 4 2 -4, 4 1 1 -5, 1 1 0 -2))');
        $this->assertTrue($line instanceof MultiLineString);
        $this->assertEquals(MultiLineString::from_array(array($line1_coords, $line2_coords), 444, true, true), $line);
    }

    public function test_multipolygon()
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

        // 2d
        $poly = $this->parser->parse('SRID=444;MULTIPOLYGON(((0 0, 0 5, 5 5, 5 0, 0 0),(1 1, 1 4, 4 4, 4 1, 1 1)),((6 6, 6 10, 10 10, 10 6, 6 6)))');
        $this->assertTrue($poly instanceof MultiPolygon);
        $this->assertEquals(MultiPolygon::from_array(array(array($ring1_coords, $ring2_coords), array($ring3_coords)), 444), $poly);

        // 3dz
        $poly = $this->parser->parse('SRID=444;MULTIPOLYGON(((0 0 0, 0 5 1, 5 5 2, 5 0 1, 0 0 0),(1 1 0, 1 4 1, 4 4 2, 4 1 1, 1 1 0)),((6 6 0, 6 10 1, 10 10 2, 10 6 1, 6 6 0)))');
        $this->assertTrue($poly instanceof MultiPolygon);
        $this->assertEquals(MultiPolygon::from_array(array(array($ring1_coords, $ring2_coords), array($ring3_coords)), 444, true), $poly);

        // 3dm
        $poly = $this->parser->parse('SRID=444;MULTIPOLYGONM(((0 0 0, 0 5 1, 5 5 2, 5 0 1, 0 0 0),(1 1 0, 1 4 1, 4 4 2, 4 1 1, 1 1 0)),((6 6 0, 6 10 1, 10 10 2, 10 6 1, 6 6 0)))');
        $this->assertTrue($poly instanceof MultiPolygon);
        $this->assertEquals(MultiPolygon::from_array(array(array($ring1_coords, $ring2_coords), array($ring3_coords)), 444, false, true), $poly);

        // 4d
        $poly = $this->parser->parse('SRID=444;MULTIPOLYGON(((0 0 0 4, 0 5 1 3, 5 5 2 2, 5 0 1 1, 0 0 0 4),(1 1 0 4, 1 4 1 3, 4 4 2 2, 4 1 1 1, 1 1 0 4)),((6 6 0 4, 6 10 1 3, 10 10 2 2, 10 6 1 1, 6 6 0 4)))');
        $this->assertTrue($poly instanceof MultiPolygon);
        $this->assertEquals(MultiPolygon::from_array(array(array($ring1_coords, $ring2_coords), array($ring3_coords)), 444, true, true), $poly);
    }

    public function test_geometrycollection()
    {
        // 2d point and linestring
        $coll = $this->parser->parse('SRID=444;GEOMETRYCOLLECTION(POINT(4 -5), LINESTRING(1.1 2.2, 3.3 4.4))');
        $this->assertTrue($coll instanceof GeometryCollection);
        $this->assertEquals(GeometryCollection::from_geometries(array(Point::from_xy(4, -5, 444), LineString::from_array(array(array(1.1, 2.2), array(3.3, 4.4)), 444)), 444), $coll);

        // 3dm
        $coll = $this->parser->parse('SRID=444;GEOMETRYCOLLECTIONM(POINT(4 -5 3), LINESTRING(1.1 2.2 3, 3.3 4.4 3))');
        $this->assertTrue($coll instanceof GeometryCollection);
        $this->assertEquals(GeometryCollection::from_geometries(array(Point::from_xym(4, -5, 3, 444), LineString::from_array(array(array(1.1, 2.2, 3), array(3.3, 4.4, 3)), 444, false, true)), 444, false, true), $coll);
        $this->assertEquals(444, $coll->geometries[1]->srid);
        $this->assertTrue($coll->geometries[0]->with_m);
    }
}
