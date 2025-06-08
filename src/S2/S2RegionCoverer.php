<?php

	namespace Sjoller\S2Geometry\S2;

	/**
	 * S2RegionCoverer is a class that provides a way to cover regions with S2 cells.
	 * It can be used to find a set of cells that cover a region, with various options
	 * for controlling the coverage quality and cell count.
     */
	class S2RegionCoverer {
		private int $minLevel;
		private int $maxLevel;
		private int $maxCells;
		private bool $interiorCovering;

		public function __construct (
			int $minLevel = 0,
			int $maxLevel = S2::MAX_LEVEL,
			int $maxCells = 8,
			bool $interiorCovering = false
		) {
			$this->minLevel = max(0, min($minLevel, S2::MAX_LEVEL));
			$this->maxLevel = max($this->minLevel, min($maxLevel, S2::MAX_LEVEL));
			$this->maxCells = max(1, $maxCells);
			$this->interiorCovering = $interiorCovering;
		}

    /**
		 * Returns a covering of the given region.
     */
		public function getCovering (S2Region $region, S2CellUnion $covering): void {
			$this->getCoveringInternal($region, $covering);
		}

		/**
		 * Returns an interior covering of the given region.
		 */
		public function getInteriorCovering (S2Region $region, S2CellUnion $covering): void {
			$this->interiorCovering = true;
			$this->getCoveringInternal($region, $covering);
			$this->interiorCovering = false;
		}

    /**
		 * Returns an exterior covering of the given region.
     */
		public function getExteriorCovering (S2Region $region, S2CellUnion $covering): void {
        $this->interiorCovering = false;
			$this->getCoveringInternal($region, $covering);
    }

    /**
		 * Internal method to compute the covering.
		 */
		private function getCoveringInternal (S2Region $region, S2CellUnion $covering): void {
			// Start with the root cell
			$root = S2CellId::fromFacePosLevel(0, 0, 0);
			$this->getCoveringRecursive($region, $root, $covering);
		}

    /**
		 * Recursive method to compute the covering.
     */
		private function getCoveringRecursive (S2Region $region, S2CellId $cellId, S2CellUnion $covering): void {
			if (count($covering->cellIds()) >= $this->maxCells) {
				return;
        }

			$cell = new S2Cell($cellId);
			$level = $cellId->level();

			// Check if we should use this cell
            if ($this->interiorCovering) {
				if (!$region->contains($cell->getCenter())) {
					return;
                }
			}
			else {
				if (!$region->mayIntersect($cell)) {
					return;
                }
            }

			// If we've reached the minimum level, add the cell
			if ($level >= $this->minLevel) {
				$covering->add($cellId);
				return;
    }

			// If we've reached the maximum level, add the cell
			if ($level >= $this->maxLevel) {
				$covering->add($cellId);
            return;
        }

			// Recursively check children
			for ($i = 0; $i < 4; $i++) {
				$child = $cellId->child($i);
				$this->getCoveringRecursive($region, $child, $covering);
				if (count($covering->cellIds()) >= $this->maxCells) {
            return;
        }
        }
    }

    /**
		 * Returns the minimum cell level.
		 */
		public function getMinLevel (): int {
			return $this->minLevel;
                }

		/**
		 * Sets the minimum cell level.
		 */
		public function setMinLevel (int $minLevel): self {
			$this->minLevel = max(0, min($minLevel, S2::MAX_LEVEL));

			return $this;
            }

		/**
		 * Returns the maximum cell level.
		 */
		public function getMaxLevel (): int {
			return $this->maxLevel;
		}

		/**
		 * Sets the maximum cell level.
		 */
		public function setMaxLevel (int $maxLevel): self {
			$this->maxLevel = max($this->minLevel, min($maxLevel, S2::MAX_LEVEL));

			return $this;
		}

		/**
		 * Returns the maximum number of cells.
		 */
		public function getMaxCells (): int {
			return $this->maxCells;
		}

		/**
		 * Sets the maximum number of cells.
		 */
		public function setMaxCells (int $maxCells): self {
			$this->maxCells = max(1, $maxCells);

			return $this;
		}

		/**
		 * Returns whether interior covering is enabled.
		 */
		public function isInteriorCovering (): bool {
			return $this->interiorCovering;
    }

    /**
		 * Sets whether interior covering is enabled.
		 */
		public function setInteriorCovering (bool $interiorCovering): self {
			$this->interiorCovering = $interiorCovering;

			return $this;
    }
}