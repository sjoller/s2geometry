<?php

	namespace Sjoller\S2Geometry\S2;

	/**
	 * S2Cap represents a spherical cap, i.e. a portion of the sphere cut off by a plane.
	 */
	class S2Cap implements S2Region {
		private S2Point $axis;
		private float $height;

		public function __construct (S2Point $axis, float $height) {
			$this->axis = $axis;
			$this->height = $height;
		}

		/**
		 * Create a cap given its axis and the cap height.
		 */
		public static function fromAxisHeight (S2Point $axis, float $height): self {
			return new self($axis, $height);
		}

		/**
		 * Create a cap given its axis and the angle from the axis to the cap edge.
		 */
		public static function fromAxisAngle (S2Point $axis, float $angle): self {
			return new self($axis, 1 - cos($angle));
		}

		/**
		 * Create a cap that contains the given point.
		 */
		public static function fromPoint (S2Point $point): self {
			return new self($point, 0);
		}

		/**
		 * Create an empty cap.
		 */
		public static function empty (): self {
			return new self(new S2Point(1, 0, 0), -1);
		}

		/**
		 * Create a full cap.
		 */
		public static function full (): self {
			return new self(new S2Point(1, 0, 0), 2);
		}

		/**
		 * Get the axis of the cap.
		 */
		public function axis (): S2Point {
			return $this->axis;
		}

		/**
		 * Get the height of the cap.
		 */
		public function height (): float {
			return $this->height;
		}

		/**
		 * Get the angle from the axis to the cap edge.
		 */
		public function angle (): float {
			return acos(1 - $this->height);
		}

		/**
		 * Returns true if the cap is empty.
		 */
		public function isEmpty (): bool {
			return $this->height < 0;
		}

		/**
		 * Returns true if the cap is full.
		 */
		public function isFull (): bool {
			return $this->height >= 2;
		}

		/**
		 * Returns the complement of this cap.
		 * The complement of a cap is the set of points that are not in the cap.
		 */
		public function complement (): S2Cap {
			if ($this->isFull()) {
				return S2Cap::empty();
			}
			if ($this->isEmpty()) {
				return S2Cap::full();
			}

			return new S2Cap($this->axis->neg(), 2 - $this->height);
		}

		/**
		 * Returns the intersection of this cap with another cap.
		 * The intersection is the set of points that are in both caps.
		 */
		public function intersect (S2Cap $other): S2Cap {
			// If either cap is empty, the intersection is empty
			if ($this->isEmpty() || $other->isEmpty()) {
				return S2Cap::empty();
			}

			// If either cap is full, the intersection is the other cap
			if ($this->isFull()) {
				return $other;
			}
			if ($other->isFull()) {
				return $this;
			}

			// Compute the intersection
			$axis = $this->axis->add($other->axis);
			$height = min($this->height, $other->height);

			return new S2Cap($axis->normalize(), $height);
		}

		/**
		 * Returns the union of this cap with another cap.
		 * The union is the set of points that are in either cap.
		 */
		public function union (S2Cap $other): S2Cap {
			// If either cap is full, the union is full
			if ($this->isFull() || $other->isFull()) {
				return S2Cap::full();
			}

			// If either cap is empty, the union is the other cap
			if ($this->isEmpty()) {
				return $other;
			}
			if ($other->isEmpty()) {
				return $this;
			}

			// Compute the union
			$axis = $this->axis->add($other->axis);
			$height = max($this->height, $other->height);

			return new S2Cap($axis->normalize(), $height);
		}

		/**
		 * Returns true if this cap contains another cap.
		 */
		public function containsCap (S2Cap $other): bool {
			if ($this->isFull() || $other->isEmpty()) {
				return true;
			}
			if ($this->isEmpty() || $other->isFull()) {
				return false;
			}

			$angle = $this->axis->angle($other->axis);

			return $angle + $other->angle() <= $this->angle();
		}

		/**
		 * Returns true if the region contains the given point.
		 */
		public function contains (S2Point $p): bool {
			if ($this->isEmpty()) {
				return false;
			}
			if ($this->isFull()) {
				return true;
			}

			$angle = $this->axis->angle($p);
			return $angle <= $this->angle();
		}

		/**
		 * Returns true if this cap intersects another cap.
		 */
		public function intersects (S2Cap $other): bool {
			if ($this->isEmpty() || $other->isEmpty()) {
				return false;
			}
			if ($this->isFull() || $other->isFull()) {
				return true;
			}

			$angle = $this->axis->angle($other->axis);

			return $angle <= $this->angle() + $other->angle();
		}

		/**
		 * Returns true if this cap is approximately equal to another cap.
		 */
		public function equals (S2Cap $other, float $maxError = 1e-15): bool {
			return $this->axis->equals($other->axis, $maxError) &&
				abs($this->height - $other->height) <= $maxError;
		}

		/**
		 * Returns true if the cap may intersect the given cell.
		 */
		public function mayIntersect (S2Cell $cell): bool {
			return $cell->getCenter()->dot($this->axis) >= 1 - $this->height;
		}

		/**
		 * Returns a bounding cap that contains the region.
		 */
		public function getCapBound (): S2Cap {
			return $this;
		}

		/**
		 * Returns the bounding rectangle of this cap.
		 */
		public function getRectBound (): S2LatLngRect {
			$axisLatLng = $this->axis->toLatLng();
			$lat = $axisLatLng->latRadians();
			$lng = $axisLatLng->lngRadians();
			$angle = $this->angle();

			// If the cap is empty, return an empty rectangle.
			if ($this->isEmpty()) {
				return S2LatLngRect::empty();
			}

			// If the cap is full, return a full rectangle.
			if ($this->isFull()) {
				return S2LatLngRect::full();
			}

			// Compute the latitude range.
			$minLat = max(-M_PI_2, $lat - $angle);
			$maxLat = min(M_PI_2, $lat + $angle);

			// If the cap includes a pole, the longitude range is full.
			if ($minLat <= -M_PI_2 || $maxLat >= M_PI_2) {
				return new S2LatLngRect(
					new R1Interval($minLat, $maxLat),
					new S1Interval(-M_PI, M_PI)
				);
			}

			// Otherwise, compute the longitude range.
			$dlng = asin(sin($angle) / cos($lat));
			$minLng = fmod($lng - $dlng + M_PI, 2 * M_PI) - M_PI;
			$maxLng = fmod($lng + $dlng + M_PI, 2 * M_PI) - M_PI;

			return new S2LatLngRect(
				new R1Interval($minLat, $maxLat),
				new S1Interval($minLng, $maxLng)
			);
		}
	}