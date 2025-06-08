<?php

	namespace Sjoller\S2Geometry\S2;

	/**
	 * S2ShapeIndexBufferedRegion is a class that provides a way to compute approximations
	 * that have been expanded by a given radius. It wraps an S2ShapeIndex and adds a buffer
	 * distance to create an expanded region.
	 */
	class S2ShapeIndexBufferedRegion implements S2Region {
		private S2ShapeIndex $index;
		private S1Angle $radius;
		private ?S2Cap $capBound = null;
		private ?S2LatLngRect $rectBound = null;

		public function __construct (S2ShapeIndex $index, S1Angle $radius) {
			$this->index = $index;
			$this->radius = $radius;
		}

		/**
		 * Returns true if the given point is within the buffered region.
		 */
		public function contains (S2Point $p): bool {
			// First check if the point is contained by any shape
			$cellId = S2CellId::fromPoint($p);
			$cell = $this->index->cell($cellId);

			if ($cell !== null) {
				foreach ($cell->clippedShapes() as $clippedShape) {
					$shape = $clippedShape->shape();
					if ($shape->contains($p)) {
						return true;
					}
				}
			}

			// Then check if the point is within the buffer radius of any shape
			for ($i = 0; $i < $this->index->numShapes(); $i++) {
				$shape = $this->index->shape($i);
				if ($shape === null) {
					continue;
				}

				// Check each edge of the shape
				for ($j = 0; $j < $shape->numEdges(); $j++) {
					$edge = $shape->edge($j);
					if ($this->isWithinRadius($p, $edge)) {
						return true;
					}
				}
			}

			return false;
		}

		/**
		 * Returns true if the given cell may intersect the buffered region.
		 */
		public function mayIntersect (S2Cell $cell): bool {
			// First check if the cell intersects any shape
			$cellId = $cell->id();
			$indexCell = $this->index->cell($cellId);

			if ($indexCell !== null) {
				foreach ($indexCell->clippedShapes() as $clippedShape) {
					$shape = $clippedShape->shape();
					if ($shape->getCapBound()->intersects($cell->getCapBound()) &&
						$shape->getRectBound()->intersects($cell->getRectBound())) {
						return true;
					}
				}
			}

			// Then check if the cell is within the buffer radius of any shape
			$cellCap = $cell->getCapBound();
			$expandedCap = S2Cap::fromAxisHeight(
				$cellCap->axis(),
				$cellCap->height() + $this->radius->radians()
			);

			for ($i = 0; $i < $this->index->numShapes(); $i++) {
				$shape = $this->index->shape($i);
				if ($shape === null) {
					continue;
				}

				if ($shape->getCapBound()->intersects($expandedCap)) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Returns the minimal cap containing the buffered region.
		 */
		public function getCapBound (): S2Cap {
			if ($this->capBound === null) {
				$this->capBound = S2Cap::empty();

				for ($i = 0; $i < $this->index->numShapes(); $i++) {
					$shape = $this->index->shape($i);
					if ($shape !== null) {
						$shapeCap = $shape->getCapBound();
						$expandedCap = S2Cap::fromAxisHeight(
							$shapeCap->axis(),
							$shapeCap->height() + $this->radius->radians()
						);
						$this->capBound = $this->capBound->union($expandedCap);
					}
				}
			}

			return $this->capBound;
		}

		/**
		 * Returns the minimal rectangle containing the buffered region.
		 */
		public function getRectBound (): S2LatLngRect {
			if ($this->rectBound === null) {
				$this->rectBound = S2LatLngRect::empty();

				for ($i = 0; $i < $this->index->numShapes(); $i++) {
					$shape = $this->index->shape($i);
					if ($shape !== null) {
						$shapeRect = $shape->getRectBound();
						$expandedRect = $this->expandRect($shapeRect);
						$this->rectBound = $this->rectBound->union($expandedRect);
					}
				}
			}

			return $this->rectBound;
		}

		/**
		 * Returns the underlying shape index.
		 */
		public function getIndex (): S2ShapeIndex {
			return $this->index;
		}

		/**
		 * Returns the buffer radius.
		 */
		public function getRadius (): S1Angle {
			return $this->radius;
		}

		/**
		 * Clears cached bounds.
		 */
		public function clearCache (): void {
			$this->capBound = null;
			$this->rectBound = null;
		}

		/**
		 * Checks if a point is within the buffer radius of an edge.
		 */
		private function isWithinRadius (S2Point $p, S2ShapeTypes $edge): bool {
			$distance = S2EdgeUtil::distance($edge->v0, $edge->v1, $p);

			return $distance->radians() <= $this->radius->radians();
		}

		/**
		 * Expands a rectangle by the buffer radius.
		 */
		private function expandRect (S2LatLngRect $rect): S2LatLngRect {
			$lat = $rect->lat();
			$lng = $rect->lng();

			// Expand latitude bounds
			$latExpansion = S1Angle::fromRadians($this->radius->radians());
			$newLat = new R1Interval(
				$lat->lo() - $latExpansion->radians(),
				$lat->hi() + $latExpansion->radians()
			);

			// Expand longitude bounds
			$lngExpansion = S1Angle::fromRadians($this->radius->radians() / cos($lat->lo()));
			$newLng = new S1Interval(
				$lng->lo() - $lngExpansion->radians(),
				$lng->hi() + $lngExpansion->radians()
			);

			return new S2LatLngRect($newLat, $newLng);
		}
	}