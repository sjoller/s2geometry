<?php

	namespace Sjoller\S2Geometry\S2;

	/**
	 * S2Edge represents an edge in the S2 geometry system.
	 */
	class S2Edge implements S2Region {
		private S2Point $v0;
		private S2Point $v1;

		public function __construct (S2Point $v0, S2Point $v1) {
			$this->v0 = $v0;
			$this->v1 = $v1;
		}

		/**
		 * Get the start point of the edge.
		 */
		public function getStart (): S2Point {
			return $this->v0;
		}

		/**
		 * Get the end point of the edge.
		 */
		public function getEnd (): S2Point {
			return $this->v1;
		}

		/**
		 * Check if a point is contained in this edge.
		 */
		public function contains (S2Point $p): bool {
			return S2EdgeUtil::contains($this->v0, $this->v1, $p);
		}

		/**
		 * Check if this edge may intersect with a given cell.
		 */
		public function mayIntersect (S2Cell $cell): bool {
			// First check if the cell contains either endpoint.
			if ($cell->contains($this->v0) || $cell->contains($this->v1)) {
				return true;
			}

			// Then check if the edge intersects any of the cell's edges.
			$vertices = $cell->getVertices();
			for ($i = 0; $i < 4; $i++) {
				$v0 = $vertices[$i];
				$v1 = $vertices[($i + 1) % 4];
				if (S2EdgeUtil::intersects($this->v0, $this->v1, $v0, $v1)) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Get a bounding cap that contains this edge.
		 */
		public function getCapBound (): S2Cap {
			// The bounding cap is centered at the midpoint of the edge.
			$mid = new S2Point(
				($this->v0->getX() + $this->v1->getX()) * 0.5,
				($this->v0->getY() + $this->v1->getY()) * 0.5,
				($this->v0->getZ() + $this->v1->getZ()) * 0.5
			);
			$mid = $mid->normalize();
			
			$dx = $this->v0->getX() - $this->v1->getX();
			$dy = $this->v0->getY() - $this->v1->getY();
			$dz = $this->v0->getZ() - $this->v1->getZ();
			$radius = sqrt($dx * $dx + $dy * $dy + $dz * $dz) * 0.5;

			return S2Cap::fromAxisAngle($mid, $radius);
		}

		/**
		 * Get a bounding rectangle that contains this edge.
		 */
		public function getRectBound (): S2LatLngRect {
			$rect = S2LatLngRect::fromPoint($this->v0->toLatLng());
			return $rect->union(S2LatLngRect::fromPoint($this->v1->toLatLng()));
		}

		/**
		 * Get the length of this edge.
		 */
		public function getLength (): float {
			$dx = $this->v0->getX() - $this->v1->getX();
			$dy = $this->v0->getY() - $this->v1->getY();
			$dz = $this->v0->getZ() - $this->v1->getZ();
			return sqrt($dx * $dx + $dy * $dy + $dz * $dz);
		}

		/**
		 * Get the midpoint of this edge.
		 */
		public function getMidpoint (): S2Point {
			$mid = new S2Point(
				($this->v0->getX() + $this->v1->getX()) * 0.5,
				($this->v0->getY() + $this->v1->getY()) * 0.5,
				($this->v0->getZ() + $this->v1->getZ()) * 0.5
			);
			return $mid->normalize();
		}

		/**
		 * Get the direction of this edge.
		 */
		public function getDirection (): S2Point {
			$dx = $this->v1->getX() - $this->v0->getX();
			$dy = $this->v1->getY() - $this->v0->getY();
			$dz = $this->v1->getZ() - $this->v0->getZ();
			$dir = new S2Point($dx, $dy, $dz);
			return $dir->normalize();
		}

		/**
		 * Check if this edge intersects with another edge.
		 */
		public function intersects (S2Edge $other): bool {
			return S2EdgeUtil::intersects($this->v0, $this->v1, $other->v0, $other->v1);
		}

		/**
		 * Get the intersection point of this edge with another edge.
		 * Returns null if the edges do not intersect.
		 */
		public function getIntersection (S2Edge $other): ?S2Point {
			return S2EdgeUtil::getIntersection($this->v0, $this->v1, $other->v0, $other->v1);
		}
	}