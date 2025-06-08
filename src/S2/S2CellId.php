<?php

	namespace Sjoller\S2Geometry\S2;

	use InvalidArgumentException;

	/**
	 * S2CellId represents a cell in the S2 cell hierarchy.
	 */
	class S2CellId {
		private int $id;

		/**
		 * Creates a new S2CellId from a 64-bit integer.
		 */
		public function __construct (int $id = 0) {
			$this->id = $id;
		}

		/**
		 * Returns the cell ID as a 64-bit integer.
		 */
		public function id (): int {
			return $this->id;
		}

		/**
		 * Returns the face of the cell (0-5).
		 */
		public function face (): int {
			return $this->id >> 60;
		}

		/**
		 * Returns the level of the cell (0-30).
		 */
		public function level (): int {
			$level = 0;
			$id = $this->id;
			for ($i = 0; $i < 30; $i++) {
				if (($id & (1 << (2 * (29 - $i)))) != 0) {
					$level = $i + 1;
					break;
				}
			}

        return $level;
    }

    /**
		 * Returns the orientation of the cell (0-3).
		 */
		public function orientation (): int {
			return ($this->id >> 2) & 3;
		}

		/**
		 * Returns the position of the cell within its face.
		 */
		public function pos (): int {
			return $this->id & ((1 << (2 * (30 - $this->level()))) - 1);
		}

		/**
		 * Returns true if this is a leaf cell.
		 */
		public function isLeaf (): bool {
			return $this->level() == S2::MAX_LEVEL;
		}

		/**
		 * Returns the parent cell ID at the specified level.
		 * If no level is specified, returns the immediate parent.
		 */
		public function parent (?int $level = null): self {
        if ($level === null) {
				$level = $this->level() - 1;
			}
			if ($level < 0 || $level > $this->level()) {
				throw new InvalidArgumentException("Invalid level: " . $level);
			}
			$newId = $this->id;
			while ($this->level() > $level) {
				$newId = $newId >> 2;
			}
			return new self($newId);
		}

		/**
		 * Returns the child cell at the given position (0-3).
		 */
		public function child (int $position): self {
			$level = $this->level();
			if ($level >= S2::MAX_LEVEL) {
				return new self();
			}
			$newId = $this->id | (1 << (2 * (29 - $level))) | ($position << (2 * (29 - $level - 1)));

			return new self($newId);
		}

		/**
		 * Returns the next cell at the same level.
		 */
		public function next (): self {
			return new self($this->id + (1 << (2 * (30 - $this->level()))));
		}

		/**
		 * Returns the previous cell at the same level.
		 */
		public function prev (): self {
			return new self($this->id - (1 << (2 * (30 - $this->level()))));
		}

		/**
		 * Returns true if this cell contains the given cell.
		 */
		public function contains (S2CellId $other): bool {
			$level = $this->level();
			$otherLevel = $other->level();
			if ($level > $otherLevel) {
				return false;
			}

			return ($this->id & ~((1 << (2 * (30 - $level))) - 1)) ==
				($other->id & ~((1 << (2 * (30 - $level))) - 1));
		}

		/**
		 * Returns true if this cell intersects with the given cell.
		 */
		public function intersects (S2CellId $other): bool {
			return $this->contains($other) || $other->contains($this);
		}

		/**
		 * Returns the cell ID for the given point.
		 */
		public static function fromPoint (S2Point $p): self {
			$face = S2::getFace($p);
			$uv = S2::faceXYZtoUV($face, $p);
			$i = self::stToIJ(self::uvToST($uv[0]));
			$j = self::stToIJ(self::uvToST($uv[1]));

			return self::fromFaceIJ($face, $i, $j);
		}

		/**
		 * Returns the cell ID for the given face and IJ coordinates.
		 */
		public static function fromFaceIJ (int $face, int $i, int $j): self {
			$id = $face << 60;
			$bits = $face & 3;
			for ($k = 7; $k >= 0; $k--) {
				$bits = (($bits << 1) | (($i >> $k) & 1)) << 1 | (($j >> $k) & 1);
				$id |= $bits << (4 * $k);
			}

			return new self($id);
		}

		/**
		 * Returns the cell ID for the given face, position, and level.
		 */
		public static function fromFacePosLevel (int $face, int $pos, int $level): self {
			$id = $face << 60;
			if ($level > 0) {
				$id |= (1 << (2 * (30 - $level))) | $pos;
			}

			return new self($id);
		}

		/**
		 * Returns the IJ coordinates for this cell.
		 */
		public function toIJ (): array {
			$i = 0;
			$j = 0;
			$bits = $this->id >> 2;
			for ($k = 0; $k < 30; $k++) {
				$i = ($i << 1) | (($bits >> (2 * $k + 1)) & 1);
				$j = ($j << 1) | (($bits >> (2 * $k)) & 1);
			}

			return [$i, $j];
		}

		/**
		 * Converts ST coordinates to IJ coordinates.
		 */
		private static function stToIJ (float $s): int {
			return max(0, min(S2::MAX_CELL_SIZE - 1, (int)round($s * S2::MAX_CELL_SIZE)));
		}

		/**
		 * Converts UV coordinates to ST coordinates.
		 */
		private static function uvToST (float $u): float {
			return ($u + 1) * 0.5;
		}

		/**
		 * Returns a string representation of this cell ID.
		 */
		public function __toString (): string {
			return sprintf("Face: %d, Level: %d, ID: %d", $this->face(), $this->level(), $this->id);
		}

		/**
		 * Returns the center point of this cell.
		 */
		public function toPoint (): S2Point {
			$ij = $this->toIJ();
			$u = S2::ijToUV($ij[0], $this->level());
			$v = S2::ijToUV($ij[1], $this->level());
			return S2::faceUVtoXYZ($this->face(), $u, $v);
		}
	}