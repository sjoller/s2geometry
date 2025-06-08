<?php

	namespace Sjoller\S2Geometry\S2;

	/**
	 * S2RegionIntersection is a class that represents the intersection of multiple S2 regions.
	 * It implements the S2Region interface and provides methods to compute the intersection
	 * of any number of regions.
	 */
	class S2RegionIntersection implements S2Region {
		private array $regions;
		private ?S2Cap $capBound = null;
		private ?S2LatLngRect $rectBound = null;

		public function __construct (array $regions = []) {
			$this->regions = array_filter($regions, function ($region) {
				return $region instanceof S2Region;
			});
		}

		/**
		 * Adds a region to the intersection.
		 */
		public function add (S2Region $region): self {
			$this->regions[] = $region;
			$this->clearCache();

			return $this;
		}

		/**
		 * Removes a region from the intersection.
		 */
		public function remove (S2Region $region): self {
			$index = array_search($region, $this->regions, true);
			if ($index !== false) {
				array_splice($this->regions, $index, 1);
				$this->clearCache();
			}

			return $this;
		}

		/**
		 * Returns all regions in the intersection.
		 */
		public function getRegions (): array {
			return $this->regions;
		}

		/**
		 * Returns the number of regions in the intersection.
		 */
		public function size (): int {
			return count($this->regions);
		}

		/**
		 * Returns whether the intersection is empty.
		 */
		public function isEmpty (): bool {
			return empty($this->regions);
		}

		/**
		 * Clears all regions from the intersection.
		 */
		public function clear (): self {
			$this->regions = [];
			$this->clearCache();

			return $this;
		}

		/**
		 * Checks if a point is contained in all regions.
		 */
		public function contains (S2Point $p): bool {
			foreach ($this->regions as $region) {
				if (!$region->contains($p)) {
					return false;
				}
			}

			return true;
		}

		/**
		 * Checks if a cell may intersect all regions.
		 */
		public function mayIntersect (S2Cell $cell): bool {
			foreach ($this->regions as $region) {
				if (!$region->mayIntersect($cell)) {
					return false;
				}
			}

			return true;
		}

		/**
		 * Returns the bounding cap of the intersection.
		 */
		public function getCapBound (): S2Cap {
			if ($this->capBound === null) {
				if ($this->isEmpty()) {
					$this->capBound = S2Cap::empty();
				}
				else {
					$this->capBound = $this->regions[0]->getCapBound();
					for ($i = 1; $i < count($this->regions); $i++) {
						$this->capBound = $this->capBound->intersect($this->regions[$i]->getCapBound());
					}
				}
			}

			return $this->capBound;
		}

		/**
		 * Returns the bounding rectangle of the intersection.
		 */
		public function getRectBound (): S2LatLngRect {
			if ($this->rectBound === null) {
				if ($this->isEmpty()) {
					$this->rectBound = S2LatLngRect::empty();
				}
				else {
					$this->rectBound = $this->regions[0]->getRectBound();
					for ($i = 1; $i < count($this->regions); $i++) {
						$this->rectBound = $this->rectBound->intersection($this->regions[$i]->getRectBound());
					}
				}
			}

			return $this->rectBound;
		}

		/**
		 * Clears cached bounds.
		 */
		private function clearCache (): void {
			$this->capBound = null;
			$this->rectBound = null;
		}
	}