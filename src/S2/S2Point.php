<?php

	namespace Sjoller\S2Geometry\S2;

	/**
	 * S2Point represents a point on the unit sphere as a 3D vector.
	 * Points are represented as unit-length vectors, so any point P on the unit sphere
	 * has the property that P·P = 1.
	 */
	class S2Point {
		private float $x;
		private float $y;
		private float $z;

		public function __construct (float $x, float $y, float $z) {
			$this->x = $x;
			$this->y = $y;
			$this->z = $z;
		}

		public function getX (): float {
			return $this->x;
		}

		public function getY (): float {
			return $this->y;
		}

		public function getZ (): float {
			return $this->z;
		}

		/**
		 * Returns the dot product of this point with the given point.
		 */
		public function dot (S2Point $that): float {
			return $this->x * $that->x + $this->y * $that->y + $this->z * $that->z;
		}

		/**
		 * Returns the cross product of this point with the given point.
		 */
		public function cross (S2Point $that): S2Point {
			return new S2Point(
				$this->y * $that->z - $this->z * $that->y,
				$this->z * $that->x - $this->x * $that->z,
				$this->x * $that->y - $this->y * $that->x
			);
		}

		/**
		 * Returns the angle between this point and the given point.
		 * Uses a numerically stable method to compute the angle.
		 */
		public function angle (S2Point $that): float {
			$dot = $this->dot($that);
			$cross = $this->cross($that);
			$crossNorm = sqrt($cross->dot($cross));

			// Use atan2 for better numerical stability
			return atan2($crossNorm, $dot);
		}

		/**
		 * Returns the distance between this point and the given point.
		 */
		public function distance (S2Point $that): float {
			return $this->angle($that);
		}

		/**
		 * Returns true if this point is approximately equal to the given point.
		 */
		public function equals (S2Point $that, float $maxError = 1e-15): bool {
			return abs($this->x - $that->x) <= $maxError &&
				abs($this->y - $that->y) <= $maxError &&
				abs($this->z - $that->z) <= $maxError;
		}

		/**
		 * Returns a normalized version of this point.
		 */
		public function normalize(): S2Point {
			$norm = sqrt($this->x * $this->x + $this->y * $this->y + $this->z * $this->z);
			if ($norm == 0) {
				return new S2Point(0, 0, 0);
			}

			return new S2Point($this->x / $norm, $this->y / $norm, $this->z / $norm);
		}

		/**
		 * Converts this point to S2LatLng coordinates.
		 */
		public function toLatLng(): S2LatLng {
			$lat = asin(max(-1.0, min(1.0, $this->z)));
			$lng = atan2($this->y, $this->x);

			return S2LatLng::fromRadians($lat, $lng);
		}

		/**
		 * Multiplies this point by a scalar value.
		 */
		public function mul(float $scalar): S2Point {
			return new S2Point($this->x * $scalar, $this->y * $scalar, $this->z * $scalar);
		}

		/**
		 * Adds another point to this point.
		 */
		public function add(S2Point $that): S2Point {
			return new S2Point($this->x + $that->x, $this->y + $that->y, $this->z + $that->z);
		}

		/**
		 * Returns the negation of this point.
		 */
		public function neg(): S2Point {
			return new S2Point(-$this->x, -$this->y, -$this->z);
		}
	}