# S2 Geometry Library for PHP

This is a PHP port of Google's S2 Geometry library. The S2 Geometry library is designed to have good performance for spatial operations on the sphere, and to be robust against numerical errors.

## Installation

```bash
composer require sjoller/s2geometry
```

## Usage

```php
use Sjoller\S2Geometry\S2LatLng;
use Sjoller\S2Geometry\S2Cell;
use Sjoller\S2Geometry\S2LatLngRect;
use Sjoller\S2Geometry\S2RegionCoverer;
use Sjoller\S2Geometry\S2Point;
use Sjoller\S2Geometry\S2Polygon;
use Sjoller\S2Geometry\S2Loop;

// Create a point from latitude and longitude
$point = S2LatLng::fromDegrees(37.7749, -122.4194);

// Convert to S2Point
$s2Point = $point->toPoint();

// Create a cell from a point
$cell = S2Cell::fromPoint($s2Point);

// Create a region coverer
$coverer = new S2RegionCoverer();
$coverer->setMaxCells(10);

// Get covering cells for a region
$covering = $coverer->getCovering($cell);
```

## Features

- Point representation on the unit sphere
- Latitude/longitude conversion
- Cell-based spatial indexing
- Region covering
- Polygon operations
- Distance calculations
- Area calculations

## Requirements

- PHP 7.4 or higher

## License

This project is licensed under the Apache License 2.0 - see the LICENSE file for details.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request. 