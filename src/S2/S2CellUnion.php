<?php

	namespace Sjoller\S2Geometry\S2;

	use InvalidArgumentException;

	/**
	 * S2CellUnion is a class that represents a union of S2 cells.
	 * It provides efficient operations for working with collections of cells,
	 * including containment checks, set operations, and normalization.
	 */
	class S2CellUnion {
		private array $cellIds;

		public function __construct (array $cellIds = []) {
			$this->cellIds = $cellIds;
			$this->normalize();
		}

		/**
		 * Returns the number of cells in the union.
		 */
		public function size (): int {
			return count($this->cellIds);
		}

		/**
		 * Returns whether the union is empty.
		 */
		public function isEmpty (): bool {
			return empty($this->cellIds);
		}

		/**
		 * Returns the cell ID at the given index.
		 */
		public function cellId (int $index): S2CellId {
			if ($index < 0 || $index >= count($this->cellIds)) {
				throw new InvalidArgumentException('Cell index out of range');
			}

			return $this->cellIds[$index];
		}

		/**
		 * Returns all cell IDs in the union.
		 */
		public function cellIds (): array {
			return $this->cellIds;
		}

		/**
		 * Checks if the union contains a specific cell ID.
		 */
		public function contains (S2Point $p): bool {
			$cellId = S2CellId::fromPoint($p);
			$index = $this->binarySearch($cellId);
			if ($index >= 0) {
				return true;
			}

			// Check if the cell is contained by any cell in the union
			$index = ~$index;
			if ($index > 0) {
				$prevCell = new S2Cell($this->cellIds[$index - 1]);
				if ($prevCell->contains($p)) {
					return true;
				}
			}
			if ($index < count($this->cellIds)) {
				$nextCell = new S2Cell($this->cellIds[$index]);
				if ($nextCell->contains($p)) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Checks if the union contains a specific point.
		 */
		public function containsPoint (S2Point $point): bool {
			return $this->contains($point);
		}

		/**
		 * Checks if the union intersects with a given cell.
		 */
		public function intersects (S2Cell $cell): bool {
			$cellId = $cell->id();
			$index = $this->binarySearch($cellId);
			if ($index >= 0) {
				return true;
			}

			// Check if any cell in the union contains the given cell's center
			$index = ~$index;
			$center = $cell->getCenter();
			if ($index > 0) {
				$prevCell = new S2Cell($this->cellIds[$index - 1]);
				if ($prevCell->contains($center)) {
					return true;
				}
			}
			if ($index < count($this->cellIds)) {
				$nextCell = new S2Cell($this->cellIds[$index]);
				if ($nextCell->contains($center)) {
					return true;
				}
			}

			// Check if the given cell contains any cell in the union
			$cellCap = $cell->getCapBound();
			$cellRect = $cell->getRectBound();
        foreach ($this->cellIds as $id) {
				$unionCell = new S2Cell($id);
				if ($cellCap->contains($unionCell->getCenter()) &&
					$cellRect->contains($unionCell->getCenter())) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Checks if the union contains any of the given cell IDs.
		 */
		public function containsAny (array $cellIds): bool {
			foreach ($cellIds as $cellId) {
				if (!$cellId instanceof S2CellId) {
					throw new InvalidArgumentException('All elements must be S2CellId instances');
				}
				$cell = new S2Cell($cellId);
				if ($this->contains($cell->getCenter())) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Checks if the union contains all of the given cell IDs.
		 */
		public function containsAll (array $cellIds): bool {
			foreach ($cellIds as $cellId) {
				if (!$cellId instanceof S2CellId) {
					throw new InvalidArgumentException('All elements must be S2CellId instances');
				}
				$cell = new S2Cell($cellId);
				if (!$this->contains($cell->getCenter())) {
					return false;
				}
			}

			return true;
		}

		/**
		 * Returns the union of this cell union with another.
		 */
		public function union (S2CellUnion $other): S2CellUnion {
			$cellIds = array_merge($this->cellIds, $other->cellIds);

			return new S2CellUnion($cellIds);
		}

		/**
		 * Returns the intersection of this cell union with another.
		 */
		public function intersection (S2CellUnion $other): S2CellUnion {
			$cellIds = [];
			$i = 0;
			$j = 0;

			while ($i < count($this->cellIds) && $j < count($other->cellIds)) {
				$a = $this->cellIds[$i];
				$b = $other->cellIds[$j];

				if ($a->equals($b)) {
					$cellIds[] = $a;
					$i++;
					$j++;
				}
				elseif ($a->lessThan($b)) {
					$i++;
				}
				else {
					$j++;
				}
			}

			return new S2CellUnion($cellIds);
		}

		/**
		 * Returns the difference of this cell union with another.
		 */
		public function difference (S2CellUnion $other): S2CellUnion {
			$cellIds = [];
			$i = 0;
			$j = 0;

			while ($i < count($this->cellIds)) {
				if ($j >= count($other->cellIds)) {
					$cellIds[] = $this->cellIds[$i++];
                continue;
            }

				$a = $this->cellIds[$i];
				$b = $other->cellIds[$j];

				if ($a->lessThan($b)) {
					$cellIds[] = $a;
					$i++;
				}
				elseif ($b->lessThan($a)) {
					$j++;
				}
				else {
					$i++;
					$j++;
				}
			}

			return new S2CellUnion($cellIds);
		}

		/**
		 * Adds a cell ID to the union.
		 */
		public function add (S2CellId $cellId): void {
			$this->cellIds[] = $cellId;
			$this->normalize();
		}

		/**
		 * Normalizes the cell union by sorting and removing redundant cells.
		 */
		private function normalize (): void {
			if (empty($this->cellIds)) {
				return;
			}

			// Sort the cell IDs
			usort($this->cellIds, function ($a, $b) {
				return $a->id() <=> $b->id();
			});

			// Remove redundant cells
			$output = [];
			$current = $this->cellIds[0];
			$output[] = $current;

			for ($i = 1; $i < count($this->cellIds); $i++) {
				$cell = $this->cellIds[$i];
				if (!$current->contains($cell)) {
					$current = $cell;
					$output[] = $current;
				}
			}

			$this->cellIds = $output;
		}

		/**
		 * Performs a binary search for a cell ID.
		 * Returns the index if found, or the bitwise complement of the insertion point if not found.
		 */
		private function binarySearch (S2CellId $cellId): int {
			$left = 0;
			$right = count($this->cellIds) - 1;

			while ($left <= $right) {
				$mid = (int)(($left + $right) / 2);
				$cmp = $this->cellIds[$mid]->id() <=> $cellId->id();

				if ($cmp < 0) {
					$left = $mid + 1;
				}
				elseif ($cmp > 0) {
					$right = $mid - 1;
				}
				else {
					return $mid;
				}
			}

			return ~$left;
		}
	}