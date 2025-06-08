<?php

	namespace Sjoller\S2Geometry\S2;

	/**
	 * S2Polygon represents a polygon on the sphere, which can have holes and multiple shells.
	 */
	class S2Polygon implements S2Region {
    /** @var S2Loop[] */
		private array $loops;

		/**
		 * Creates a new polygon from an array of loops.
		 * The first loop is the outer shell, and subsequent loops are holes.
		 */
		public function __construct (array $loops) {
			$this->loops = $loops;
		}

		/**
		 * Returns the number of loops in the polygon.
		 */
		public function numLoops (): int {
			return count($this->loops);
		}

		/**
		 * Returns the loop at the given index.
		 */
		public function loop (int $i): S2Loop {
        return $this->loops[$i];
    }

    /**
		 * Returns all loops of the polygon.
		 *
		 * @return S2Loop[]
		 */
		public function loops (): array {
			return $this->loops;
		}

		/**
		 * Returns true if the polygon contains the given point.
		 */
		public function contains (S2Point $p): bool {
			// A point is contained if it's inside the outer loop and not inside any hole
			if (!$this->loops[0]->contains($p)) {
                return false;
			}

			// Check if the point is inside any hole
			for ($i = 1; $i < $this->numLoops(); $i++) {
				if ($this->loops[$i]->contains($p)) {
                return false;
            }
        }

            return true;
		}

		/**
		 * Returns true if the polygon may intersect the given cell.
		 */
		public function mayIntersect (S2Cell $cell): bool {
			// Check if any loop intersects the cell
        foreach ($this->loops as $loop) {
            if ($loop->mayIntersect($cell)) {
                return true;
            }
        }

        return false;
    }

		/**
		 * Returns a bounding cap for the polygon.
		 */
		public function getCapBound (): S2Cap {
			// Start with an empty cap
			$cap = S2Cap::empty();

			// Add each loop to the cap
			foreach ($this->loops as $loop) {
				$cap = $cap->addCap($loop->getCapBound());
			}

			return $cap;
		}

		/**
		 * Returns a bounding rectangle for the polygon.
		 */
		public function getRectBound (): S2LatLngRect {
			// Start with an empty rectangle
			$rect = S2LatLngRect::empty();

			// Add each loop to the rectangle
        foreach ($this->loops as $loop) {
				$rect = $rect->union($loop->getRectBound());
			}

			return $rect;
		}

		/**
		 * Returns the area of the polygon in steradians.
		 */
		public function getArea (): float {
			$area = 0.0;
			foreach ($this->loops as $i => $loop) {
				// Add the area of the outer loop, subtract the area of holes
				$area += ($i == 0 ? 1 : -1) * $loop->getArea();
			}

			return $area;
		}

		/**
		 * Returns true if the polygon contains the given polygon.
		 */
		public function containsPolygon (S2Polygon $other): bool {
			// Check if all points of the other polygon are contained
			foreach ($other->loops as $loop) {
				for ($i = 0; $i < $loop->numVertices(); $i++) {
					if (!$this->contains($loop->vertex($i))) {
						return false;
					}
				}
			}

			// Check if any edge of the other polygon intersects this polygon
			foreach ($other->loops as $loop) {
				for ($i = 0; $i < $loop->numVertices(); $i++) {
					$v0 = $loop->vertex($i);
					$v1 = $loop->vertex(($i + 1) % $loop->numVertices());
					if ($this->intersectsEdge($v0, $v1)) {
						return false;
					}
				}
			}

			return true;
		}

		/**
		 * Returns true if the polygon intersects the given polygon.
		 */
		public function intersects (S2Polygon $other): bool {
			// Check if any edge of this polygon intersects any edge of the other polygon
			foreach ($this->loops as $loop1) {
				for ($i = 0; $i < $loop1->numVertices(); $i++) {
					$v0 = $loop1->vertex($i);
					$v1 = $loop1->vertex(($i + 1) % $loop1->numVertices());
					if ($other->intersectsEdge($v0, $v1)) {
						return true;
					}
				}
			}

			return false;
		}

		/**
		 * Returns true if the polygon intersects the given edge.
		 */
		private function intersectsEdge (S2Point $v0, S2Point $v1): bool {
			// Check if the edge intersects any loop
			foreach ($this->loops as $loop) {
				for ($i = 0; $i < $loop->numVertices(); $i++) {
					$w0 = $loop->vertex($i);
					$w1 = $loop->vertex(($i + 1) % $loop->numVertices());
					if (S2EdgeUtil::intersects($v0, $v1, $w0, $w1)) {
						return true;
					}
				}
			}

			return false;
		}

		/**
		 * Returns a string representation of the polygon.
		 */
		public function __toString (): string {
			$loops = array_map(fn($l) => $l->__toString(), $this->loops);

			return 'S2Polygon(' . implode(', ', $loops) . ')';
		}
	}