<?php

	namespace Sjoller\S2Geometry\S2;

	/**
	 * S2AreaCentroid provides functionality for computing areas and centroids of regions on the sphere.
	 */
	class S2AreaCentroid {
		private float $area;
		private S2Point $centroid;

		/**
		 * Create a new S2AreaCentroid with the given area and centroid.
		 */
		public function __construct (float $area, S2Point $centroid) {
			$this->area = $area;
			$this->centroid = $centroid;
		}

		/**
		 * Get the area of the region.
		 */
		public function getArea (): float {
			return $this->area;
		}

		/**
		 * Get the centroid of the region.
		 */
		public function getCentroid (): S2Point {
			return $this->centroid;
		}

		/**
		 * Compute the area and centroid of a polygon.
		 */
		public static function getAreaAndCentroid (S2Polygon $polygon): self {
			$area = 0;
			$centroid = new S2Point(0, 0, 0);

			foreach ($polygon->loops() as $loop) {
				$loopArea = 0;
				$loopCentroid = new S2Point(0, 0, 0);
				$vertices = $loop->vertices();
				$n = count($vertices);

				for ($i = 0; $i < $n; $i++) {
					$v0 = $vertices[$i];
					$v1 = $vertices[($i + 1) % $n];
					$v2 = $vertices[($i + 2) % $n];

					// Compute the area of the triangle formed by the three vertices.
					$triangleArea = self::getTriangleArea($v0, $v1, $v2);
					$loopArea += $triangleArea;

					// Compute the centroid of the triangle.
					$triangleCentroid = self::getTriangleCentroid($v0, $v1, $v2);
					$loopCentroid = $loopCentroid->add($triangleCentroid->mul($triangleArea));
				}

				// Add the loop's contribution to the total area and centroid.
				$area += $loopArea;
				$centroid = $centroid->add($loopCentroid);
			}

			// Normalize the centroid by the total area.
			if ($area > 0) {
				$centroid = $centroid->mul(1 / $area);
			}

			return new self($area, $centroid);
		}

		/**
		 * Compute the area of a triangle on the sphere.
		 */
		private static function getTriangleArea (S2Point $a, S2Point $b, S2Point $c): float {
			// Compute the area using the spherical excess formula.
			$ab = $a->cross($b);
			$bc = $b->cross($c);
			$ca = $c->cross($a);

			$area = 2 * atan2(
					$ab->dot($c) * $a->dot($b->cross($c)),
					$ab->dot($ab) + $bc->dot($bc) + $ca->dot($ca) + $ab->dot($bc) + $bc->dot($ca) + $ca->dot($ab)
				);

			return abs($area);
		}

		/**
		 * Compute the centroid of a triangle on the sphere.
		 */
		private static function getTriangleCentroid (S2Point $a, S2Point $b, S2Point $c): S2Point {
			// The centroid is the normalized sum of the vertices.
			$centroid = $a->add($b->add($c));

			return $centroid->normalize();
		}

		/**
		 * Compute the area and centroid of a loop.
		 */
		public static function getLoopAreaAndCentroid (S2Loop $loop): self {
			$area = 0;
			$centroid = new S2Point(0, 0, 0);
			$vertices = $loop->vertices();
			$n = count($vertices);

			for ($i = 0; $i < $n; $i++) {
				$v0 = $vertices[$i];
				$v1 = $vertices[($i + 1) % $n];
				$v2 = $vertices[($i + 2) % $n];

				// Compute the area of the triangle formed by the three vertices.
				$triangleArea = self::getTriangleArea($v0, $v1, $v2);
				$area += $triangleArea;

				// Compute the centroid of the triangle.
				$triangleCentroid = self::getTriangleCentroid($v0, $v1, $v2);
				$centroid = $centroid->add($triangleCentroid->mul($triangleArea));
			}

			// Normalize the centroid by the total area.
			if ($area > 0) {
				$centroid = $centroid->mul(1 / $area);
			}

			return new self($area, $centroid);
		}

		/**
		 * Compute the area and centroid of a cell.
		 */
		public static function getCellAreaAndCentroid (S2Cell $cell): self {
			$area = 0;
			$centroid = new S2Point(0, 0, 0);
			$vertices = $cell->getVertices();

			for ($i = 0; $i < 4; $i++) {
				$v0 = $vertices[$i];
				$v1 = $vertices[($i + 1) % 4];
				$v2 = $vertices[($i + 2) % 4];

				// Compute the area of the triangle formed by the three vertices.
				$triangleArea = self::getTriangleArea($v0, $v1, $v2);
				$area += $triangleArea;

				// Compute the centroid of the triangle.
				$triangleCentroid = self::getTriangleCentroid($v0, $v1, $v2);
				$centroid = $centroid->add($triangleCentroid->mul($triangleArea));
			}

			// Normalize the centroid by the total area.
			if ($area > 0) {
				$centroid = $centroid->mul(1 / $area);
			}

			return new self($area, $centroid);
		}
	}