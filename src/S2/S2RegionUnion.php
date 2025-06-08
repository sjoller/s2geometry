<?php

	namespace Sjoller\S2Geometry\S2;

	/**
	 * S2RegionUnion is a class that represents the union of multiple S2 regions.
	 * It implements the S2Region interface and provides methods to compute the union
	 * of any number of regions.
	 */
	class S2RegionUnion implements S2Region {
		private array $regions;
		private ?S2Cap $capBound = null;
		private ?S2LatLngRect $rectBound = null;

		public function __construct (array $regions = []) {
			$this->regions = array_filter($regions, function ($region) {
				return $region instanceof S2Region;
			});
		}

		/**
		 * Adds a region to the union.
		 */
		public function add (S2Region $region): self {
			$this->regions[] = $region;
			$this->clearCache();

			return $this;
		}

		/**
		 * Removes a region from the union.
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
		 * Returns all regions in the union.
		 */
		public function getRegions (): array {
			return $this->regions;
		}

		/**
		 * Returns the number of regions in the union.
		 */
		public function size (): int {
			return count($this->regions);
		}

		/**
		 * Returns whether the union is empty.
		 */
		public function isEmpty (): bool {
			return empty($this->regions);
		}

		/**
		 * Clears all regions from the union.
		 */
		public function clear (): self {
			$this->regions = [];
			$this->clearCache();

			return $this;
		}

		/**
		 * Checks if a point is contained in any of the regions.
		 */
		public function contains (S2Point $p): bool {
			foreach ($this->regions as $region) {
				if ($region->contains($p)) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Checks if a cell may intersect any of the regions.
		 */
		public function mayIntersect (S2Cell $cell): bool {
			foreach ($this->regions as $region) {
				if ($region->mayIntersect($cell)) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Returns the bounding cap of the union.
		 */
		public function getCapBound (): S2Cap {
			if ($this->capBound === null) {
				if ($this->isEmpty()) {
					$this->capBound = S2Cap::empty();
				}
				else {
					$this->capBound = $this->regions[0]->getCapBound();
					for ($i = 1; $i < count($this->regions); $i++) {
						$this->capBound = $this->capBound->union($this->regions[$i]->getCapBound());
					}
				}
			}

			return $this->capBound;
		}

		/**
		 * Returns the bounding rectangle of the union.
		 */
		public function getRectBound (): S2LatLngRect {
			if ($this->rectBound === null) {
				if ($this->isEmpty()) {
					$this->rectBound = S2LatLngRect::empty();
				}
				else {
					$this->rectBound = $this->regions[0]->getRectBound();
					for ($i = 1; $i < count($this->regions); $i++) {
						$this->rectBound = $this->rectBound->union($this->regions[$i]->getRectBound());
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