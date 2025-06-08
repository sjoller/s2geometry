<?php

	namespace Sjoller\S2Geometry\S2;

	/**
	 * S2RegionSymmetricDifference is a class that represents the symmetric difference between two S2 regions.
	 * It implements the S2Region interface and represents the set of points that are in exactly one
	 * of the two regions (i.e., in regionA but not in regionB, or in regionB but not in regionA).
	 */
	class S2RegionSymmetricDifference implements S2Region {
		private S2Region $regionA;
		private S2Region $regionB;
		private ?S2Cap $capBound = null;
		private ?S2LatLngRect $rectBound = null;

		public function __construct (S2Region $regionA, S2Region $regionB) {
			$this->regionA = $regionA;
			$this->regionB = $regionB;
		}

		/**
		 * Returns true if the given point is in exactly one of the regions.
		 */
		public function contains (S2Point $p): bool {
			$inA = $this->regionA->contains($p);
			$inB = $this->regionB->contains($p);

			return $inA !== $inB; // XOR operation
		}

		/**
		 * Returns true if the given cell may intersect the symmetric difference.
		 * This is a conservative check - it may return true even if the cell doesn't
		 * actually intersect the symmetric difference.
		 */
		public function mayIntersect (S2Cell $cell): bool {
			// If the cell doesn't intersect either region, it can't intersect the symmetric difference
			if (!$this->regionA->mayIntersect($cell) && !$this->regionB->mayIntersect($cell)) {
				return false;
			}

			// If the cell intersects both regions, we need to check if any part of the cell
			// is in exactly one of the regions
			if ($this->regionA->mayIntersect($cell) && $this->regionB->mayIntersect($cell)) {
				$vertices = $cell->getVertices();
				$inA = false;
				$inB = false;

				foreach ($vertices as $vertex) {
					if ($this->regionA->contains($vertex)) {
						$inA = true;
					}
					if ($this->regionB->contains($vertex)) {
						$inB = true;
					}
					if ($inA !== $inB) { // If any vertex is in exactly one region
						return true;
					}
				}
			}

			return true;
		}

		/**
		 * Returns the minimal cap containing the symmetric difference.
		 * This is a conservative bound - it may be larger than necessary.
		 */
		public function getCapBound (): S2Cap {
			if ($this->capBound === null) {
				$capA = $this->regionA->getCapBound();
				$capB = $this->regionB->getCapBound();
				$this->capBound = $capA->union($capB);
			}

			return $this->capBound;
		}

		/**
		 * Returns the minimal rectangle containing the symmetric difference.
		 * This is a conservative bound - it may be larger than necessary.
		 */
		public function getRectBound (): S2LatLngRect {
			if ($this->rectBound === null) {
				$rectA = $this->regionA->getRectBound();
				$rectB = $this->regionB->getRectBound();
				$this->rectBound = $rectA->union($rectB);
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
		 * Returns whether the symmetric difference is empty.
		 * This is a conservative check - it may return false even if the symmetric
		 * difference is actually empty.
		 */
		public function isEmpty (): bool {
			// If both regions are empty, the symmetric difference is empty
			if (($this->regionA instanceof S2RegionUnion && $this->regionA->isEmpty()) &&
				($this->regionB instanceof S2RegionUnion && $this->regionB->isEmpty())) {
				return true;
			}

			// If the regions are identical, the symmetric difference is empty
			$capA = $this->regionA->getCapBound();
			$rectA = $this->regionA->getRectBound();
			$capB = $this->regionB->getCapBound();
			$rectB = $this->regionB->getRectBound();

			return $capA->equals($capB) && $rectA->equals($rectB);
		}

		/**
		 * Clears cached bounds.
		 */
		public function clearCache (): void {
			$this->capBound = null;
			$this->rectBound = null;
		}
	}