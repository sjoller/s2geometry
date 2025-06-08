<?php

	namespace Sjoller\S2Geometry\S2;

	use RuntimeException;

	/**
	 * S2Polyline represents a polyline (a sequence of connected line segments) on the sphere.
	 */
	class S2Polyline implements S2Region {
		/** @var S2Point[] */
		private array $vertices;

		/**
		 * Creates a new polyline from an array of vertices.
		 * The vertices must be unit length.
		 */
		public function __construct (array $vertices) {
			$this->vertices = $vertices;
		}

		/**
		 * Returns the number of vertices in the polyline.
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
		 * Returns all vertices of the polyline.
		 *
		 * @return S2Point[]
		 */
		public function vertices (): array {
			return $this->vertices;
		}

		/**
		 * Returns the length of the polyline.
		 */
		public function getLength(): float {
			$length = 0;
			for ($i = 0; $i < $this->numVertices() - 1; $i++) {
				$length += $this->vertex($i)->distance($this->vertex($i + 1));
			}
			return $length;
		}

		/**
		 * Returns true if the polyline contains the given point.
		 */
		public function contains (S2Point $p): bool {
			// Check if the point is equal to any vertex
			foreach ($this->vertices as $vertex) {
				if ($vertex->equals($p)) {
					return true;
				}
			}

			// Check if the point is on any edge
			for ($i = 0; $i < $this->numVertices() - 1; $i++) {
				if (S2EdgeUtil::contains($this->vertex($i), $this->vertex($i + 1), $p)) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Returns true if the polyline may intersect the given cell.
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
			for ($i = 0; $i < $this->numVertices() - 1; $i++) {
				$v0 = $this->vertex($i);
				$v1 = $this->vertex($i + 1);
				
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
		 * Returns a bounding cap for the polyline.
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
		 * Returns a bounding rectangle for the polyline.
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
		 * Returns the closest point on the polyline to the given point.
		 */
		public function getClosestPoint(S2Point $p): S2Point {
			$minDist = PHP_FLOAT_MAX;
			$closestPoint = null;

			for ($i = 0; $i < $this->numVertices() - 1; $i++) {
				$v0 = $this->vertex($i);
				$v1 = $this->vertex($i + 1);
				$edgePoint = S2EdgeUtil::getClosestPoint($v0, $v1, $p);
				$dist = $p->distance($edgePoint);

				if ($dist < $minDist) {
					$minDist = $dist;
					$closestPoint = $edgePoint;
				}
			}

			return $closestPoint;
		}

		/**
		 * Returns the distance from the given point to the polyline.
		 */
		public function getDistance(S2Point $p): S1Angle {
			return S1Angle::fromRadians($p->distance($this->getClosestPoint($p)));
		}

		/**
		 * Returns true if the polyline intersects the given polyline.
		 */
		public function intersects (S2Polyline $other): bool {
			// Check if any edge of this polyline intersects any edge of the other polyline
			for ($i = 0; $i < $this->numVertices() - 1; $i++) {
				for ($j = 0; $j < $other->numVertices() - 1; $j++) {
					if (S2EdgeUtil::intersects(
						$this->vertex($i),
						$this->vertex($i + 1),
						$other->vertex($j),
						$other->vertex($j + 1)
					)) {
						return true;
					}
				}
			}

			return false;
		}

		/**
		 * Returns a string representation of the polyline.
		 */
		public function __toString (): string {
			$vertices = array_map(fn($v) => $v->__toString(), $this->vertices);

			return 'S2Polyline(' . implode(', ', $vertices) . ')';
		}
	}