<?php

	namespace Sjoller\S2Geometry\S2;

	use InvalidArgumentException;

	/**
	 * S2LatLngRect represents a rectangle in latitude/longitude space.
	 */
	class S2LatLngRect implements S2Region {
		private R1Interval $lat;
		private S1Interval $lng;

		/**
		 * Creates a new S2LatLngRect with the given latitude and longitude intervals.
		 */
		public function __construct (R1Interval $lat, S1Interval $lng) {
			$this->lat = $lat;
			$this->lng = $lng;
		}

		/**
		 * Creates a new S2LatLngRect from two points.
		 */
		public static function fromPointPair (S2LatLng $p1, S2LatLng $p2): self {
			return new self(
				new R1Interval($p1->latRadians(), $p2->latRadians()),
				new S1Interval($p1->lngRadians(), $p2->lngRadians())
			);
		}

		/**
		 * Creates a new S2LatLngRect from a single point.
		 */
		public static function fromPoint (S2LatLng $p): self {
			return new self(
				new R1Interval($p->latRadians(), $p->latRadians()),
				new S1Interval($p->lngRadians(), $p->lngRadians())
			);
		}

		/**
		 * Creates a new S2LatLngRect that contains all points.
		 */
		public static function full (): self {
			return new self(
				new R1Interval(-M_PI_2, M_PI_2),
				new S1Interval(-M_PI, M_PI)
			);
		}

		/**
		 * Creates a new empty S2LatLngRect.
		 */
		public static function empty (): self {
			return new self(
				R1Interval::empty(),
				S1Interval::empty()
			);
		}

		/**
		 * Returns the latitude interval.
		 */
		public function lat (): R1Interval {
			return $this->lat;
		}

		/**
		 * Returns the longitude interval.
		 */
		public function lng (): S1Interval {
			return $this->lng;
		}

		/**
		 * Returns the low corner of the rectangle.
		 */
		public function lo (): S2LatLng {
			return S2LatLng::fromRadians($this->lat->lo(), $this->lng->lo());
		}

		/**
		 * Returns the high corner of the rectangle.
		 */
		public function hi (): S2LatLng {
			return S2LatLng::fromRadians($this->lat->hi(), $this->lng->hi());
		}

		/**
		 * Returns true if the rectangle is empty.
		 */
		public function isEmpty (): bool {
			return $this->lat->isEmpty() || $this->lng->isEmpty();
		}

		/**
		 * Returns true if the rectangle is full.
		 */
		public function isFull (): bool {
			return $this->lat->lo() == -M_PI_2 && 
				$this->lat->hi() == M_PI_2 &&
				$this->lng->isFull();
		}

		/**
		 * Returns true if the given point is contained in this rectangle.
		 */
		public function contains (S2Point $p): bool {
			$ll = $p->toLatLng();
			return $this->lat->contains($ll->latRadians()) && $this->lng->contains($ll->lngRadians());
		}

		/**
		 * Returns true if the given point is contained in this rectangle.
		 */
		public function containsLatLng (S2LatLng $ll): bool {
			return $this->lat->contains($ll->latRadians()) && $this->lng->contains($ll->lngRadians());
		}

		/**
		 * Returns true if the given rectangle is contained in this rectangle.
		 */
		public function containsRect (S2LatLngRect $other): bool {
			return $this->containsLatLng($other->lo()) && 
				$this->containsLatLng($other->hi()) &&
				$this->containsLatLng(S2LatLng::fromRadians($other->lat->lo(), $other->lng->hi())) &&
				$this->containsLatLng(S2LatLng::fromRadians($other->lat->hi(), $other->lng->lo()));
		}

		/**
		 * Returns true if the rectangle may intersect with the given cell.
		 */
		public function mayIntersect (S2Cell $cell): bool {
			return $this->intersects($cell->getRectBound());
		}

		/**
		 * Returns true if this rectangle intersects the given rectangle.
		 */
		public function intersects (S2LatLngRect $other): bool {
			return $this->lat->intersects($other->lat) && $this->lng->intersects($other->lng);
		}

		/**
		 * Returns the intersection of this rectangle and the given rectangle.
		 */
		public function intersection (S2LatLngRect $other): S2LatLngRect {
			return new S2LatLngRect(
				$this->lat->intersection($other->lat),
				$this->lng->intersection($other->lng)
			);
		}

		/**
		 * Returns the union of this rectangle and the given rectangle.
		 */
		public function union (S2LatLngRect $other): S2LatLngRect {
			return new S2LatLngRect(
				$this->lat->union($other->lat),
				$this->lng->union($other->lng)
			);
		}

		/**
		 * Returns a rectangle that has been expanded by the given amount.
		 * The expansion is applied to both latitude and longitude.
		 */
		public function expanded (S1Angle $margin): self {
			$newLat = new R1Interval(
				$this->lat->lo() - $margin->radians(),
				$this->lat->hi() + $margin->radians()
			);

			$newLng = new S1Interval(
				$this->lng->lo() - $margin->radians(),
				$this->lng->hi() + $margin->radians()
			);

			return new self($newLat, $newLng);
		}

		/**
		 * Returns true if this rectangle is approximately equal to another rectangle.
		 */
		public function equals (S2LatLngRect $other, float $maxError = 1e-15): bool {
			return abs($this->lat->lo() - $other->lat->lo()) <= $maxError &&
				abs($this->lat->hi() - $other->lat->hi()) <= $maxError &&
				abs($this->lng->lo() - $other->lng->lo()) <= $maxError &&
				abs($this->lng->hi() - $other->lng->hi()) <= $maxError;
		}

		/**
		 * Returns a bounding cap that contains the rectangle.
		 */
		public function getCapBound (): S2Cap {
			if ($this->isEmpty()) {
				return new S2Cap(new S2Point(1, 0, 0), -1);
			}

			$center = $this->getCenter();
			$lat0 = $this->lat->lo();
			$lat1 = $this->lat->hi();
			$lng0 = $this->lng->lo();
			$lng1 = $this->lng->hi();

			// Compute the maximum distance from the center to any point.
			$maxDist = 0;
			$maxDist = max($maxDist, $center->toPoint()->angle(S2LatLng::fromRadians($lat0, $lng0)->toPoint()));
			$maxDist = max($maxDist, $center->toPoint()->angle(S2LatLng::fromRadians($lat0, $lng1)->toPoint()));
			$maxDist = max($maxDist, $center->toPoint()->angle(S2LatLng::fromRadians($lat1, $lng0)->toPoint()));
			$maxDist = max($maxDist, $center->toPoint()->angle(S2LatLng::fromRadians($lat1, $lng1)->toPoint()));

			return S2Cap::fromAxisHeight($center->toPoint(), 1 - cos($maxDist));
		}

		/**
		 * Returns the center of the rectangle.
		 */
		public function getCenter (): S2LatLng {
			return S2LatLng::fromRadians($this->lat->center(), $this->lng->center());
		}

		/**
		 * Returns a bounding rectangle that contains the region.
		 * For S2LatLngRect, this is just the rectangle itself.
		 */
		public function getRectBound (): S2LatLngRect {
			return $this;
		}

		/**
		 * Returns the size of the rectangle.
		 */
		public function getSize (): S2LatLng {
			return S2LatLng::fromRadians($this->lat->length(), $this->lng->length());
		}

		/**
		 * Returns the vertex of this rectangle.
		 */
		public function getVertex (int $i): S2LatLng {
			switch ($i) {
				case 0:
					return S2LatLng::fromRadians($this->lat->lo(), $this->lng->lo());
				case 1:
					return S2LatLng::fromRadians($this->lat->lo(), $this->lng->hi());
				case 2:
					return S2LatLng::fromRadians($this->lat->hi(), $this->lng->hi());
				case 3:
					return S2LatLng::fromRadians($this->lat->hi(), $this->lng->lo());
				default:
					throw new InvalidArgumentException("Invalid vertex index: $i");
			}
		}

		/**
		 * Returns the vertices of this rectangle.
		 */
		public function getVertices (): array {
			return [
				$this->getVertex(0),
				$this->getVertex(1),
				$this->getVertex(2),
				$this->getVertex(3)
			];
		}

		/**
		 * Returns the area of this rectangle.
		 */
		public function getArea (): float {
			if ($this->isEmpty()) {
				return 0;
			}

			return $this->lat->length() * $this->lng->length();
		}

		/**
		 * Returns the perimeter of this rectangle.
		 */
		public function getPerimeter (): float {
			if ($this->isEmpty()) {
				return 0;
			}

			return 2 * ($this->lat->length() + $this->lng->length());
		}

		/**
		 * Returns the distance from the given point to this rectangle.
		 */
		public function distance (S2Point $p): float {
			// If the point is inside the rectangle, the distance is 0
			if ($this->contains($p)) {
				return 0;
			}

			// Find the closest point on each edge
			$minDist = PHP_FLOAT_MAX;
			$vertices = $this->getVertices();
			for ($i = 0; $i < 4; $i++) {
				$v0 = $vertices[$i];
				$v1 = $vertices[($i + 1) % 4];
				$dist = S2EdgeUtil::distance($p, $v0->toPoint(), $v1->toPoint());
				$minDist = min($minDist, $dist->radians());
			}

			return $minDist;
		}

		/**
		 * Returns the distance from this rectangle to another rectangle.
		 */
		public function distanceToRect (S2LatLngRect $other): float {
			// If the rectangles intersect, the distance is 0
			if ($this->intersects($other)) {
				return 0;
			}

			// Find the closest point on each edge
			$minDist = PHP_FLOAT_MAX;
			$v1 = $this->getVertex(0);
			$v2 = $this->getVertex(1);
			$v3 = $other->getVertex(0);
			$v4 = $other->getVertex(1);

			$dist1 = S2EdgeUtil::distance($v1->toPoint(), $v2->toPoint(), $v3->toPoint());
			$dist2 = S2EdgeUtil::distance($v1->toPoint(), $v2->toPoint(), $v4->toPoint());
			$dist3 = S2EdgeUtil::distance($v3->toPoint(), $v4->toPoint(), $v1->toPoint());
			$dist4 = S2EdgeUtil::distance($v3->toPoint(), $v4->toPoint(), $v2->toPoint());

			$minDist = min($minDist, $dist1->radians(), $dist2->radians(), $dist3->radians(), $dist4->radians());

			return $minDist;
		}

		/**
		 * Returns the maximum distance from this rectangle to the given point.
		 */
		public function getMaxDistance (S2Point $p): float {
			$center = $this->getCenter();
			$lat0 = $this->lat->lo();
			$lat1 = $this->lat->hi();
			$lng0 = $this->lng->lo();
			$lng1 = $this->lng->hi();

			// The maximum distance is the maximum distance to any of the vertices.
			$maxDist = 0;
			$maxDist = max($maxDist, $center->toPoint()->angle($p->toLatLng()->toPoint()));
			$maxDist = max($maxDist, $center->toPoint()->angle(S2LatLng::fromRadians($lat0, $lng0)->toPoint()));
			$maxDist = max($maxDist, $center->toPoint()->angle(S2LatLng::fromRadians($lat0, $lng1)->toPoint()));
			$maxDist = max($maxDist, $center->toPoint()->angle(S2LatLng::fromRadians($lat1, $lng0)->toPoint()));
			$maxDist = max($maxDist, $center->toPoint()->angle(S2LatLng::fromRadians($lat1, $lng1)->toPoint()));

			return $maxDist;
		}

		/**
		 * Returns the maximum distance from this rectangle to the given rectangle.
		 */
		public function getMaxDistanceToRect (S2LatLngRect $other): float {
			// The maximum distance is the maximum distance between any pair of vertices.
			$maxDist = 0;
			$vertices1 = $this->getVertices();
			$vertices2 = $other->getVertices();
			for ($i = 0; $i < 4; $i++) {
				$v1 = $vertices1[$i];
				for ($j = 0; $j < 4; $j++) {
					$v2 = $vertices2[$j];
					$dist = $v1->toPoint()->angle($v2->toPoint());
					$maxDist = max($maxDist, $dist);
				}
			}

			return $maxDist;
		}

		/**
		 * Returns a string representation of this rectangle.
		 */
		public function __toString (): string {
			return "[" . $this->lo() . ", " . $this->hi() . "]";
		}
	}