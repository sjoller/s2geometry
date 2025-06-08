<?php

	namespace Sjoller\S2Geometry\S2;

	/**
	 * S2Loop represents a single loop of vertices that can be part of a polygon.
	 * The vertices must be unit length and the loop must be closed (first and last vertex are the same).
	 */
	class S2Loop implements S2Region {
		/** @var S2Point[] */
		private array $vertices;

		/**
		 * Creates a new loop from an array of vertices.
		 * The vertices must be unit length and the loop must be closed.
		 */
		public function __construct (array $vertices) {
			$this->vertices = $vertices;
		}

		/**
		 * Returns the number of vertices in the loop.
		 */
		public function numVertices (): int {
			return count($this->vertices);
		}

		/**
		 * Returns the vertex at the given index.
		 */
		public function vertex (int $i): S2Point {
			return $this->vertices[$i];
		}

		/**
		 * Returns all vertices of the loop.
		 *
		 * @return S2Point[]
		 */
		public function vertices (): array {
			return $this->vertices;
		}

		/**
		 * Returns true if the loop contains the given point.
		 */
		public function contains (S2Point $p): bool {
			// Check if the point is equal to any vertex
			foreach ($this->vertices as $vertex) {
				if ($p->equals($vertex)) {
					return true;
				}
			}

			// Use the winding number algorithm to determine if the point is inside
			$winding = 0;
			for ($i = 0; $i < $this->numVertices(); $i++) {
				$v0 = $this->vertex($i);
				$v1 = $this->vertex(($i + 1) % $this->numVertices());

				// Skip degenerate edges
				if ($v0->equals($v1)) {
					continue;
				}

				// Compute the winding number contribution
				$cross = $v0->cross($v1)->dot($p);
				if ($cross > 0) {
					$winding++;
				}
				elseif ($cross < 0) {
					$winding--;
				}
			}

			return $winding != 0;
		}

		/**
		 * Returns true if the loop may intersect the given cell.
		 */
		public function mayIntersect (S2Cell $cell): bool {
			// Check if any vertex is inside the cell
			foreach ($this->vertices as $vertex) {
				if ($cell->contains($vertex)) {
					return true;
				}
			}

			// Check if any edge intersects the cell
			$cellVertices = $cell->getVertices();
			for ($i = 0; $i < $this->numVertices(); $i++) {
				$v0 = $this->vertex($i);
				$v1 = $this->vertex(($i + 1) % $this->numVertices());
				
				// Check if the edge intersects any of the cell's edges
				for ($j = 0; $j < 4; $j++) {
					$cellV0 = $cellVertices[$j];
					$cellV1 = $cellVertices[($j + 1) % 4];
					if (S2EdgeUtil::intersects($v0, $v1, $cellV0, $cellV1)) {
						return true;
					}
				}
			}

			return false;
		}

		/**
		 * Returns a bounding cap for the loop.
		 */
		public function getCapBound (): S2Cap {
			// Start with an empty cap
			$cap = S2Cap::empty();

			// Add each vertex to the cap
			foreach ($this->vertices as $vertex) {
				$cap = $cap->addPoint($vertex);
			}

			return $cap;
		}

		/**
		 * Returns a bounding rectangle for the loop.
		 */
		public function getRectBound (): S2LatLngRect {
			// Start with an empty rectangle
			$rect = S2LatLngRect::empty();

			// Add each vertex to the rectangle
			foreach ($this->vertices as $vertex) {
				$rect = $rect->addPoint(S2LatLng::fromPoint($vertex));
			}

			return $rect;
		}

		/**
		 * Returns the area of the loop.
		 */
		public function getArea(): float {
			$area = 0;
			for ($i = 0; $i < $this->numVertices(); $i++) {
				$v1 = $this->vertex($i);
				$v2 = $this->vertex(($i + 1) % $this->numVertices());
				$v0 = $this->vertex(($i + 2) % $this->numVertices());

				$a = $v1->distance($v2);
				$b = $v2->distance($v0);
				$c = $v0->distance($v1);

				$s = ($a + $b + $c) / 2;
				$area += sqrt($s * ($s - $a) * ($s - $b) * ($s - $c));
			}

			return $area;
		}

		/**
		 * Returns true if the loop is valid (vertices are unit length and loop is closed).
		 */
		public function isValid (): bool {
			if ($this->numVertices() < 3) {
				return false;
			}

			// Check if vertices are unit length
			foreach ($this->vertices as $vertex) {
				if (abs($vertex->norm() - 1) > 1e-14) {
					return false;
				}
			}

			// Check if loop is closed
			$first = $this->vertex(0);
			$last = $this->vertex($this->numVertices() - 1);
			if (!$first->equals($last)) {
				return false;
			}

			return true;
		}

		/**
		 * Returns true if the loop is normalized (area <= 2π).
		 */
		public function isNormalized(): bool {
			return $this->getArea() <= 2 * M_PI;
		}

		/**
		 * Returns a normalized version of this loop (area <= 2π).
		 * If the loop is already normalized, returns this loop.
		 */
		public function normalize(): S2Loop {
			if ($this->isNormalized()) {
				return $this;
			}

			// Create a new loop with reversed vertices
			$reversedVertices = array_reverse($this->vertices);
			return new S2Loop($reversedVertices);
		}

		/**
		 * Returns an inverted version of this loop (reversed vertex order).
		 */
		public function invert(): S2Loop {
			$reversedVertices = array_reverse($this->vertices);
			return new S2Loop($reversedVertices);
		}

		/**
		 * Returns a string representation of the loop.
		 */
		public function __toString (): string {
			$vertices = array_map(fn($v) => $v->__toString(), $this->vertices);

			return 'S2Loop(' . implode(', ', $vertices) . ')';
		}
	}