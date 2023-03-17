<?php

namespace GeograPHP\Operation\Distance;

use GeograPHP\Constants;
use GeograPHP\Geometry\Point;

/**
 * Represents the bounding box of a geometry.
 */
class Haversine
{
    private Point $p1;
    private Point $p2;
    private $distance;

    public function __construct(Point $p1 = null, Point $p2 = null) {
        if ($p1 !== null) {
            $this->p1 = $p1;
            $this->p2 = $p2;
        }
        return $this;
    }

    public function calculate() {
        list ($p1X, $p1Y) = $this->p1->get_xy();
        list ($p2X, $p2Y) = $this->p2->get_xy();
        $diffY = deg2rad($p2Y-$p1Y);
        $diffX = deg2rad($p2X-$p1X);

        $Xh = pow(sin($diffX*0.5), 2);
        $Yh = pow(sin($diffY*0.5), 2);

        $tmp = cos(deg2rad($p1Y))*cos(deg2rad($p2Y));

        $this->distance = 2*Constants::EARTH_RADIUS_METRES*asin(sqrt($Yh+$tmp*$Xh));

        return $this->distance;
    }

}