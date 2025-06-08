<?php

	namespace Sjoller\S2Geometry\S2;

	use InvalidArgumentException;

	/**
	 * S2RegionTermIndexer is a class that provides a way to convert spatial data into index terms
	 * for information retrieval systems. It can be used to generate terms for both indexing and
	 * querying spatial data.
	 */
	class S2RegionTermIndexer {
		private int $minLevel;
		private int $maxLevel;
		private int $maxCells;
		private bool $indexContainsPointsOnly;
		private bool $optimizeForSpace;

		public function __construct (
			int $minLevel = 0,
			int $maxLevel = S2::MAX_LEVEL,
			int $maxCells = 8,
			bool $indexContainsPointsOnly = false,
			bool $optimizeForSpace = false
		) {
			$this->minLevel = max(0, min($minLevel, S2::MAX_LEVEL));
			$this->maxLevel = max($this->minLevel, min($maxLevel, S2::MAX_LEVEL));
			$this->maxCells = max(1, $maxCells);
			$this->indexContainsPointsOnly = $indexContainsPointsOnly;
			$this->optimizeForSpace = $optimizeForSpace;
		}

		/**
		 * Returns index terms for the given region.
		 */
		public function getIndexTerms (S2Region $region, string $prefix = ''): array {
			$coverer = new S2RegionCoverer($this->minLevel, $this->maxLevel, $this->maxCells);
			$covering = new S2CellUnion();
			$coverer->getCovering($region, $covering);

			return $this->cellIdsToTerms($covering->cellIds(), $prefix);
		}

		/**
		 * Returns query terms for the given region.
		 */
		public function getQueryTerms (S2Region $region, string $prefix = ''): array {
			$coverer = new S2RegionCoverer($this->minLevel, $this->maxLevel, $this->maxCells);
			$covering = new S2CellUnion();
			$coverer->getCovering($region, $covering);

			return $this->cellIdsToTerms($covering->cellIds(), $prefix);
		}

		/**
		 * Converts a cell ID to an index term.
		 */
		public function cellIdToTerm (S2CellId $cellId, string $prefix = ''): string {
			if ($this->optimizeForSpace) {
				// Use a more compact representation
				$id = $cellId->id();
				$bytes = pack('J', $id);

				return $prefix . base64_encode($bytes);
			}
			else {
				// Use a more readable representation
				$level = $cellId->level();
				$face = $cellId->face();
				$pos = $cellId->pos();

				return sprintf('%s%d/%d/%d', $prefix, $face, $level, $pos);
			}
		}

		/**
		 * Converts an index term back to a cell ID.
		 */
		public function termToCellId (string $term, string $prefix = ''): S2CellId {
			if (empty($prefix) || strpos($term, $prefix) === 0) {
				$term = substr($term, strlen($prefix));
			}

			if ($this->optimizeForSpace) {
				// Decode from compact representation
				$bytes = base64_decode($term);
				$id = unpack('J', $bytes)[1];

				return new S2CellId($id);
			}
			else {
				// Decode from readable representation
				$parts = explode('/', $term);
				if (count($parts) !== 3) {
					throw new InvalidArgumentException('Invalid term format');
				}
				$face = (int)$parts[0];
				$level = (int)$parts[1];
				$pos = (int)$parts[2];

				return S2CellId::fromFacePosLevel($face, $pos, $level);
			}
		}

		/**
		 * Converts an array of cell IDs to terms.
		 */
		private function cellIdsToTerms (array $cellIds, string $prefix): array {
			$terms = [];
			foreach ($cellIds as $cellId) {
				$terms[] = $this->cellIdToTerm($cellId, $prefix);
			}

			return $terms;
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
		 * Returns whether the index contains only points.
		 */
		public function isIndexContainsPointsOnly (): bool {
			return $this->indexContainsPointsOnly;
		}

		/**
		 * Sets whether the index contains only points.
		 */
		public function setIndexContainsPointsOnly (bool $indexContainsPointsOnly): self {
			$this->indexContainsPointsOnly = $indexContainsPointsOnly;

			return $this;
		}

		/**
		 * Returns whether space optimization is enabled.
		 */
		public function isOptimizeForSpace (): bool {
			return $this->optimizeForSpace;
		}

		/**
		 * Sets whether space optimization is enabled.
		 */
		public function setOptimizeForSpace (bool $optimizeForSpace): self {
			$this->optimizeForSpace = $optimizeForSpace;

			return $this;
		}
	}