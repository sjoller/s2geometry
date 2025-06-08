<?php

	namespace Sjoller\S2Geometry\S2;

	use InvalidArgumentException;

	/**
	 * S2PointIndex is a class that provides efficient indexing and lookup of points using S2 cells.
	 * It organizes points into a spatial index structure for fast spatial queries.
	 */
	class S2PointIndex {
		private array $points = [];
		private array $cellToPoints = [];
		private array $pointToCells = [];
		private int $minLevel;
		private int $maxLevel;

		public function __construct (int $minLevel = 0, int $maxLevel = S2::MAX_LEVEL) {
			$this->minLevel = max(0, min($minLevel, S2::MAX_LEVEL));
			$this->maxLevel = max($this->minLevel, min($maxLevel, S2::MAX_LEVEL));
		}

		/**
		 * Adds a point to the index.
		 */
		public function add (S2Point $point): self {
			$this->points[] = $point;
			$cellIds = $this->getCoveringCells($point);
			$this->pointToCells[count($this->points) - 1] = $cellIds;

			foreach ($cellIds as $cellId) {
				if (!isset($this->cellToPoints[$cellId->id()])) {
					$this->cellToPoints[$cellId->id()] = [];
				}
				$this->cellToPoints[$cellId->id()][] = count($this->points) - 1;
			}

			return $this;
		}

		/**
		 * Removes a point from the index.
		 */
		public function remove (int $index): self {
			if ($index < 0 || $index >= count($this->points)) {
				throw new InvalidArgumentException('Invalid point index');
			}

			// Remove from cell mappings
			foreach ($this->pointToCells[$index] as $cellId) {
				$cellPoints = &$this->cellToPoints[$cellId->id()];
				$pos = array_search($index, $cellPoints);
				if ($pos !== false) {
					array_splice($cellPoints, $pos, 1);
				}
				if (empty($cellPoints)) {
					unset($this->cellToPoints[$cellId->id()]);
				}
			}

			// Remove from points array and update indices
			array_splice($this->points, $index, 1);
			unset($this->pointToCells[$index]);

			// Update indices in cell mappings
			foreach ($this->cellToPoints as $cellId => $indices) {
				foreach ($indices as $i => $idx) {
					if ($idx > $index) {
						$this->cellToPoints[$cellId][$i]--;
					}
				}
			}

			return $this;
		}

		/**
		 * Returns all points in the index.
		 */
		public function getPoints (): array {
			return $this->points;
		}

		/**
		 * Returns the point at the given index.
		 */
		public function getPoint (int $index): S2Point {
			if ($index < 0 || $index >= count($this->points)) {
				throw new InvalidArgumentException('Invalid point index');
			}

			return $this->points[$index];
		}

		/**
		 * Returns all points in the given cell.
		 */
		public function getPointsForCell (S2CellId $cellId): array {
			if (!isset($this->cellToPoints[$cellId->id()])) {
				return [];
			}

			$points = [];
			foreach ($this->cellToPoints[$cellId->id()] as $index) {
				$points[] = $this->points[$index];
			}

			return $points;
		}

		/**
		 * Returns all cells containing the given point.
		 */
		public function getCellsForPoint (S2Point $point): array {
			return $this->getCoveringCells($point);
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
		 * Returns the number of points in the index.
		 */
		public function size (): int {
			return count($this->points);
		}

		/**
		 * Returns whether the index is empty.
		 */
		public function isEmpty (): bool {
			return empty($this->points);
		}

		/**
		 * Clears all points from the index.
		 */
		public function clear (): self {
			$this->points = [];
			$this->cellToPoints = [];
			$this->pointToCells = [];

			return $this;
		}

		/**
		 * Returns the cells that cover the given point.
		 */
		private function getCoveringCells (S2Point $point): array {
			$cellIds = [];
			$cellId = S2CellId::fromPoint($point);

			// Add cells at each level
			for ($level = $this->minLevel; $level <= $this->maxLevel; $level++) {
				$cellIds[] = $cellId->parent($level);
			}

			return $cellIds;
		}
	}