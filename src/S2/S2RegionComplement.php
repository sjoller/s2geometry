<?php

	namespace Sjoller\S2Geometry\S2;

	/**
	 * S2RegionComplement is a class that represents the complement of an S2 region.
	 * It implements the S2Region interface and represents the set of points that are
	 * not in the original region.
	 */
	class S2RegionComplement implements S2Region {
		private S2Region $region;
		private ?S2Cap $capBound = null;
		private ?S2LatLngRect $rectBound = null;

		public function __construct (S2Region $region) {
			$this->region = $region;
		}

		/**
		 * Returns true if the given point is not in the original region.
		 */
		public function contains (S2Point $p): bool {
			return !$this->region->contains($p);
		}

		/**
		 * Returns true if the given cell may intersect the complement.
		 * This is a conservative check - it may return true even if the cell doesn't
		 * actually intersect the complement.
		 */
		public function mayIntersect (S2Cell $cell): bool {
			// If the cell is completely contained by the original region,
			// it can't intersect the complement
			$cellCap = $cell->getCapBound();
			$cellRect = $cell->getRectBound();
			$regionCap = $this->region->getCapBound();
			$regionRect = $this->region->getRectBound();

			if ($regionCap->containsCap($cellCap) && $regionRect->containsRect($cellRect)) {
				return false;
			}

			// If the cell doesn't intersect the original region at all,
			// it must be in the complement
			if (!$this->region->mayIntersect($cell)) {
				return true;
			}

			// Otherwise, we need to check if any part of the cell is not in the region
			$vertices = $cell->getVertices();
			foreach ($vertices as $vertex) {
				if (!$this->region->contains($vertex)) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Returns the bounding cap of the complement.
		 * Since the complement is the entire sphere minus the original region,
		 * we return the full sphere cap.
		 */
		public function getCapBound (): S2Cap {
			if ($this->capBound === null) {
				$this->capBound = new S2Cap(new S2Point(1, 0, 0), 2);
			}

			return $this->capBound;
		}

		/**
		 * Returns the bounding rectangle of the complement.
		 * Since the complement is the entire sphere minus the original region,
		 * we return the full sphere rectangle.
		 */
		public function getRectBound (): S2LatLngRect {
			if ($this->rectBound === null) {
				$this->rectBound = S2LatLngRect::full();
			}

			return $this->rectBound;
		}

		/**
		 * Returns the original region.
		 */
		public function getRegion (): S2Region {
			return $this->region;
		}

		/**
		 * Sets the original region.
		 */
		public function setRegion (S2Region $region): self {
			$this->region = $region;
			$this->clearCache();

			return $this;
		}

		/**
		 * Returns whether the complement is empty.
		 * This is a conservative check - it may return false even if the complement
		 * is actually empty.
		 */
		public function isEmpty (): bool {
			// If the original region is the full sphere, the complement is empty
			$cap = $this->region->getCapBound();
			$rect = $this->region->getRectBound();

			return $cap->isFull() && $rect->isFull();
		}

		/**
		 * Clears cached bounds.
		 */
		public function clearCache (): void {
			$this->capBound = null;
			$this->rectBound = null;
		}
	}