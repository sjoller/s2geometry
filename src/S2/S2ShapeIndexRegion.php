<?php

	namespace Sjoller\S2Geometry\S2;

	/**
	 * S2ShapeIndexRegion is a class that provides a way to approximate arbitrary geometry collections.
	 * It wraps an S2ShapeIndex and provides methods to check containment and compute bounds.
	 */
	class S2ShapeIndexRegion implements S2Region {
		private S2ShapeIndex $index;
		private ?S2Cap $capBound = null;
		private ?S2LatLngRect $rectBound = null;

		public function __construct (S2ShapeIndex $index) {
			$this->index = $index;
		}

		/**
		 * Returns true if the given point is contained by any shape in the index.
		 */
		public function contains (S2Point $p): bool {
			$cellId = S2CellId::fromPoint($p);
			$cell = $this->index->cell($cellId);

			if ($cell === null) {
				return false;
			}

			foreach ($cell->clippedShapes() as $clippedShape) {
				if ($clippedShape->shape()->contains($p)) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Returns true if the given cell may intersect any shape in the index.
		 */
		public function mayIntersect (S2Cell $cell): bool {
			$cellId = $cell->id();
			$indexCell = $this->index->cell($cellId);

			if ($indexCell === null) {
				return false;
			}

			foreach ($indexCell->clippedShapes() as $clippedShape) {
				$shape = $clippedShape->shape();
				if ($shape->getCapBound()->intersects($cell->getCapBound()) &&
					$shape->getRectBound()->intersects($cell->getRectBound())) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Returns the minimal cap containing all shapes in the index.
		 */
		public function getCapBound (): S2Cap {
			if ($this->capBound === null) {
				$this->capBound = S2Cap::empty();

				for ($i = 0; $i < $this->index->numShapes(); $i++) {
					$shape = $this->index->shape($i);
					if ($shape !== null) {
						$this->capBound = $this->capBound->union($shape->getCapBound());
					}
				}
			}

			return $this->capBound;
		}

		/**
		 * Returns the minimal rectangle containing all shapes in the index.
		 */
		public function getRectBound (): S2LatLngRect {
			if ($this->rectBound === null) {
				$this->rectBound = S2LatLngRect::empty();

				for ($i = 0; $i < $this->index->numShapes(); $i++) {
					$shape = $this->index->shape($i);
					if ($shape !== null) {
						$this->rectBound = $this->rectBound->union($shape->getRectBound());
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
		 * Clears cached bounds.
		 */
		public function clearCache (): void {
			$this->capBound = null;
			$this->rectBound = null;
		}
	}