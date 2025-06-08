<?php

	namespace Sjoller\S2Geometry\S2;

	/**
	 * S2EdgeIndex is a class for indexing edges for efficient edge-based operations.
	 */
	class S2EdgeIndex {
		private array $edges = [];
		private array $cells = [];
		private bool $indexComputed = false;

		/**
		 * Creates a new S2EdgeIndex.
		 */
		public function __construct () {
		}

		/**
		 * Adds an edge to the index.
		 */
		public function add (S2Point $v0, S2Point $v1): void {
			$this->edges[] = [$v0, $v1];
			$this->indexComputed = false;
		}

		/**
		 * Computes the index if it hasn't been computed yet.
		 */
		public function computeIndex (): void {
			if ($this->indexComputed) {
				return;
			}

			$this->cells = [];
			foreach ($this->edges as $edge) {
				$this->addEdgeToCells($edge[0], $edge[1]);
			}
			$this->indexComputed = true;
		}

		/**
		 * Returns all edges that may intersect with the given cell.
		 */
		public function getEdgesForCell (S2CellId $cellId): array {
			if (!$this->indexComputed) {
				$this->computeIndex();
			}

			$result = [];
			if (isset($this->cells[$cellId->id()])) {
				foreach ($this->cells[$cellId->id()] as $edgeIndex) {
					$result[] = $this->edges[$edgeIndex];
				}
			}

			return $result;
		}

		/**
		 * Returns all edges that may intersect with the given region.
		 */
		public function getEdgesForRegion (S2Region $region): array {
			if (!$this->indexComputed) {
				$this->computeIndex();
			}

			$result = [];
			$coverer = new S2RegionCoverer();
			$covering = new S2CellUnion();
			$coverer->getCovering($region, $covering);

			foreach ($covering->cellIds() as $cellId) {
				$result = array_merge($result, $this->getEdgesForCell($cellId));
			}

			return $result;
		}

		/**
		 * Returns all edges that may intersect with the given point.
		 */
		public function getEdgesForPoint (S2Point $point): array {
			$cellId = S2CellId::fromPoint($point);

			return $this->getEdgesForCell($cellId);
		}

		/**
		 * Returns all edges that may intersect with the given edge.
		 */
		public function getEdgesForEdge (S2Point $v0, S2Point $v1): array {
			$coverer = new S2RegionCoverer();
			$edge = new S2Edge($v0, $v1);
			$covering = new S2CellUnion();
			$coverer->getCovering($edge, $covering);

			$result = [];
			foreach ($covering->cellIds() as $cellId) {
				$result = array_merge($result, $this->getEdgesForCell($cellId));
			}

			return $result;
		}

		/**
		 * Returns the number of edges in the index.
		 */
		public function numEdges (): int {
			return count($this->edges);
		}

		/**
		 * Clears the index.
		 */
		public function clear (): void {
			$this->edges = [];
			$this->cells = [];
			$this->indexComputed = false;
		}

		/**
		 * Internal method to add an edge to the cells that contain it.
		 */
		private function addEdgeToCells (S2Point $v0, S2Point $v1): void {
			$coverer = new S2RegionCoverer();
			$edge = new S2Edge($v0, $v1);
			$covering = new S2CellUnion();
			$coverer->getCovering($edge, $covering);

			$edgeIndex = count($this->edges) - 1;
			foreach ($covering->cellIds() as $cellId) {
				if (!isset($this->cells[$cellId->id()])) {
					$this->cells[$cellId->id()] = [];
				}
				$this->cells[$cellId->id()][] = $edgeIndex;
			}
		}
	}