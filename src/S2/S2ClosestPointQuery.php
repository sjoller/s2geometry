<?php

	namespace Sjoller\S2Geometry\S2;

	/**
	 * S2ClosestPointQuery is a class that provides functionality to find the closest points
	 * to a given target. It uses a spatial index to efficiently find nearby points.
	 */
	class S2ClosestPointQuery {
		private array $points;
		private array $index;
		private int $maxResults;
		private float $maxDistance;

		/**
		 * Creates a new S2ClosestPointQuery with the given points.
		 */
		public function __construct (array $points, int $maxResults = 1, float $maxDistance = INF) {
			$this->points = $points;
			$this->maxResults = max(1, $maxResults);
			$this->maxDistance = $maxDistance;
			$this->buildIndex();
		}

		/**
		 * Builds the spatial index for efficient point lookup.
		 */
		private function buildIndex (): void {
			$this->index = [];
			foreach ($this->points as $i => $point) {
				$cellId = S2CellId::fromPoint($point);
				$this->index[$cellId->id()] = $i;
			}
		}

		/**
		 * Finds the closest points to the given target point.
		 */
		public function findClosestPoints (S2Point $target): array {
			$results = [];
			$targetCell = S2CellId::fromPoint($target);
			$searchRadius = $this->maxDistance;

			// Search in the target cell and its neighbors
			$neighbors = $this->getNeighborCells($targetCell);
			foreach ($neighbors as $cellId) {
				if (isset($this->index[$cellId->id()])) {
					$pointIndex = $this->index[$cellId->id()];
					$point = $this->points[$pointIndex];
					$angle = $target->angle($point);
					$distance = $angle * S2::EARTH_RADIUS_METERS;

					if ($distance <= $searchRadius) {
						$results[] = [
							'point' => $point,
							'distance' => $distance,
							'index' => $pointIndex
						];
					}
				}
			}

			// Sort by distance and limit results
			usort($results, function ($a, $b) {
				return $a['distance'] <=> $b['distance'];
			});

			return array_slice($results, 0, $this->maxResults);
		}

		/**
		 * Gets the neighboring cells of the given cell.
		 */
		private function getNeighborCells (S2CellId $cellId): array {
			$neighbors = [$cellId];
			$level = $cellId->level();
			$face = $cellId->face();
			$pos = $cellId->pos();

			// Get the four neighbors at the same level
			$ij = $cellId->toIJ();
			$i = $ij[0];
			$j = $ij[1];

			// North neighbor
			if ($j < S2::MAX_CELL_SIZE - 1) {
				$neighbors[] = S2CellId::fromFaceIJ($face, $i, $j + 1);
			}

			// East neighbor
			if ($i < S2::MAX_CELL_SIZE - 1) {
				$neighbors[] = S2CellId::fromFaceIJ($face, $i + 1, $j);
			}

			// South neighbor
			if ($j > 0) {
				$neighbors[] = S2CellId::fromFaceIJ($face, $i, $j - 1);
			}

			// West neighbor
			if ($i > 0) {
				$neighbors[] = S2CellId::fromFaceIJ($face, $i - 1, $j);
			}

			// Add parent cell
			$parent = $cellId->parent();
			if ($parent !== null) {
				$neighbors[] = $parent;
			}

			// Add child cells if we're not at the maximum level
			if ($level < S2::MAX_LEVEL) {
				for ($i = 0; $i < 4; $i++) {
					$child = $cellId->child($i);
					if ($child !== null) {
						$neighbors[] = $child;
					}
				}
			}

			return $neighbors;
		}

		/**
		 * Sets the maximum number of results to return.
		 */
		public function setMaxResults (int $maxResults): self {
			$this->maxResults = max(1, $maxResults);

			return $this;
		}

		/**
		 * Sets the maximum distance to search.
		 */
		public function setMaxDistance (float $maxDistance): self {
			$this->maxDistance = $maxDistance;

			return $this;
		}

		/**
		 * Returns the maximum number of results.
		 */
		public function getMaxResults (): int {
			return $this->maxResults;
		}

		/**
		 * Returns the maximum distance.
		 */
		public function getMaxDistance (): float {
			return $this->maxDistance;
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
		 * Clears the index and all points.
		 */
		public function clear (): void {
			$this->points = [];
			$this->index = [];
		}

		/**
		 * Adds a point to the index.
		 */
		public function addPoint (S2Point $point): void {
			$this->points[] = $point;
			$cellId = S2CellId::fromPoint($point);
			$this->index[$cellId->id()] = count($this->points) - 1;
		}

		/**
		 * Removes a point from the index.
		 */
		public function removePoint (int $index): void {
			if (isset($this->points[$index])) {
				$cellId = S2CellId::fromPoint($this->points[$index]);
				unset($this->index[$cellId->id()]);
				array_splice($this->points, $index, 1);
				$this->rebuildIndex();
			}
		}

		/**
		 * Rebuilds the index after modifications.
		 */
		private function rebuildIndex (): void {
			$this->index = [];
			foreach ($this->points as $i => $point) {
				$cellId = S2CellId::fromPoint($point);
				$this->index[$cellId->id()] = $i;
			}
		}
	}