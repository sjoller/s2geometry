<?php

	namespace Sjoller\S2Geometry\S2;

	/**
	 * S2RegionDifference is a class that provides a way to compute the difference between regions.
	 * It implements the S2Region interface and represents the set of points that are in the first
	 * region but not in the second region.
	 */
	class S2RegionDifference implements S2Region {
		private S2Region $regionA;
		private S2Region $regionB;
		private ?S2Cap $capBound = null;
		private ?S2LatLngRect $rectBound = null;

		public function __construct (S2Region $regionA, S2Region $regionB) {
			$this->regionA = $regionA;
			$this->regionB = $regionB;
		}

		/**
		 * Returns true if the given point is in regionA but not in regionB.
		 */
		public function contains (S2Point $p): bool {
			return $this->regionA->contains($p) && !$this->regionB->contains($p);
		}

		/**
		 * Returns true if the given cell may intersect the difference region.
		 * This is a conservative check - it may return true even if the cell doesn't
		 * actually intersect the difference.
		 */
		public function mayIntersect (S2Cell $cell): bool {
			// First check if the cell intersects regionA's bounds
			if (!$this->regionA->mayIntersect($cell)) {
				return false;
			}

			// If regionB is empty, the difference is just regionA
			if ($this->regionB instanceof S2RegionUnion && $this->regionB->isEmpty()) {
				return true;
			}

			// Check if the cell is completely contained by regionB
			$cellCap = $cell->getCapBound();
			$cellRect = $cell->getRectBound();

			// If the cell is completely contained by regionB's bounds, we need to check
			// if any part of the cell is not in regionB
			if ($this->regionB->getCapBound()->contains($cell->getCenter()) &&
				$this->regionB->getRectBound()->contains($cell->getCenter())) {
				// Check if any point in the cell is not in regionB
				$vertices = $cell->getVertices();
				foreach ($vertices as $vertex) {
					if (!$this->regionB->contains($vertex)) {
						return true;
					}
				}

				return false;
			}

			return true;
		}

		/**
		 * Returns the minimal cap containing the difference region.
		 * This is a conservative bound - it may be larger than necessary.
		 */
		public function getCapBound (): S2Cap {
			if ($this->capBound === null) {
				$capA = $this->regionA->getCapBound();
				$capB = $this->regionB->getCapBound();

				// If regionA is empty, the difference is empty
				if ($capA->isEmpty()) {
					$this->capBound = new S2Cap(new S2Point(1, 0, 0), -1);
				}
				// If regionB is empty, the difference is just regionA
				else {
					$this->capBound = $capA;
				}
			}

			return $this->capBound;
		}

		/**
		 * Returns the minimal rectangle containing the difference region.
		 * This is a conservative bound - it may be larger than necessary.
		 */
		public function getRectBound (): S2LatLngRect {
			if ($this->rectBound === null) {
				$rectA = $this->regionA->getRectBound();
				$rectB = $this->regionB->getRectBound();

				// If regionA is empty, the difference is empty
				if ($rectA->isEmpty()) {
					$this->rectBound = S2LatLngRect::empty();
				}
				// If regionB is empty, the difference is just regionA
				else {
					$this->rectBound = $rectA;
				}
			}

			return $this->rectBound;
		}

		/**
		 * Returns the first region (regionA).
		 */
		public function getRegionA (): S2Region {
			return $this->regionA;
		}

		/**
		 * Sets the first region (regionA).
		 */
		public function setRegionA (S2Region $regionA): self {
			$this->regionA = $regionA;
			$this->clearCache();

			return $this;
		}

		/**
		 * Returns the second region (regionB).
		 */
		public function getRegionB (): S2Region {
			return $this->regionB;
		}

		/**
		 * Sets the second region (regionB).
		 */
		public function setRegionB (S2Region $regionB): self {
			$this->regionB = $regionB;
			$this->clearCache();

			return $this;
		}

		/**
		 * Returns whether the difference is empty.
		 * This is a conservative check - it may return false even if the difference
		 * is actually empty.
		 */
		public function isEmpty (): bool {
			// If regionA is empty, the difference is empty
			if ($this->regionA instanceof S2RegionUnion && $this->regionA->isEmpty()) {
				return true;
			}

			// If regionB contains all of regionA, the difference is empty
			$capA = $this->regionA->getCapBound();
			$rectA = $this->regionA->getRectBound();
			$capB = $this->regionB->getCapBound();
			$rectB = $this->regionB->getRectBound();

			// Check if regionB's bounds contain the center points of regionA's bounds
			$capACenter = $capA->axis();
			$rectACenter = S2LatLng::fromPoint($rectA->getCenter()->toPoint());
			return $capB->contains($capACenter) && $rectB->contains($rectACenter->toPoint());
		}

		/**
		 * Clears cached bounds.
		 */
		public function clearCache (): void {
			$this->capBound = null;
			$this->rectBound = null;
		}
	}