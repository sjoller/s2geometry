<?php

namespace Sjoller\S2Geometry\S2;

use InvalidArgumentException;

/**
 * S2CellId represents a cell in the S2 cell hierarchy.
 */
class S2CellId {
	private $id;

	/**
	 * Create a new S2CellId from a 64-bit integer.
	 */
	public function __construct(int $id = 0) {
		$this->id = $id;
	}

	/**
	 * Get the cell ID as a 64-bit integer.
	 */
	public function getId(): int {
		return $this->id;
	}

	/**
	 * Create a cell ID from a point on the sphere.
	 */
	public static function fromPoint(S2Point $p): self {
		$p = $p->normalize();
		$face = S2::getFace($p);
		$uv = S2::faceXYZtoUV($face, $p);
		
		// Convert UV to ST coordinates
		$s = ($uv[0] + 1) * 0.5;
		$t = ($uv[1] + 1) * 0.5;
		
		// Convert ST to IJ coordinates
		$i = max(0, min(S2::MAX_CELL_SIZE - 1, (int)round($s * S2::MAX_CELL_SIZE)));
		$j = max(0, min(S2::MAX_CELL_SIZE - 1, (int)round($t * S2::MAX_CELL_SIZE)));
		
		// Calculate position within face
		$pos = 0;
		for ($k = 0; $k < S2::MAX_LEVEL; $k++) {
			$pos = ($pos << 2) | ((($i >> (29 - $k)) & 1) << 1) | (($j >> (29 - $k)) & 1);
		}
		
		return new self(($face << S2::POS_BITS) | $pos);
	}

	/**
	 * Create a cell ID from face, position, and level.
	 */
	public static function fromFacePosLevel(int $face, int $pos, int $level): self {
		if ($face < 0 || $face >= S2::NUM_FACES) {
			throw new InvalidArgumentException("Invalid face: $face");
		}
		if ($level < S2::MIN_LEVEL || $level > S2::MAX_LEVEL) {
			throw new InvalidArgumentException("Invalid level: $level");
		}
		
		// Clear unused bits
		$pos &= ~((1 << (2 * (S2::MAX_LEVEL - $level))) - 1);
		
		return new self(($face << S2::POS_BITS) | $pos);
	}

	/**
	 * Get the face of this cell.
	 */
	public function face(): int {
		return $this->id >> S2::POS_BITS;
	}

	/**
	 * Get the position of this cell within its face.
	 */
	public function pos(): int {
		return $this->id & ((1 << S2::POS_BITS) - 1);
	}

	/**
	 * Get the level of this cell.
	 */
	public function level(): int {
		$id = $this->id;
		$level = S2::MAX_LEVEL;
		
		// Find the highest set bit
		while (($id & 1) == 0) {
			$id >>= 1;
			$level--;
		}
		
		return $level;
	}

	/**
	 * Get the parent cell at the specified level.
	 */
	public function parent(int $level): self {
		if ($level < S2::MIN_LEVEL || $level > S2::MAX_LEVEL) {
			throw new InvalidArgumentException("Invalid level: $level");
		}
		
		$currentLevel = $this->level();
		if ($level >= $currentLevel) {
			return $this;
		}
		
		// Clear unused bits
		$pos = $this->pos() & ~((1 << (2 * (S2::MAX_LEVEL - $level))) - 1);
		
		return new self(($this->face() << S2::POS_BITS) | $pos);
	}

	/**
	 * Get the child cell at the specified position.
	 */
	public function child(int $pos): self {
		if ($pos < 0 || $pos > 3) {
			throw new InvalidArgumentException("Invalid child position: $pos");
		}
		
		$level = $this->level();
		if ($level >= S2::MAX_LEVEL) {
			throw new InvalidArgumentException("Cannot get child of leaf cell");
		}
		
		$newPos = ($this->pos() << 2) | $pos;
		
		return new self(($this->face() << S2::POS_BITS) | $newPos);
	}

	/**
	 * Get the center point of this cell.
	 */
	public function toPoint(): S2Point {
		$face = $this->face();
		$pos = $this->pos();
		$level = $this->level();
		
		// Convert position to ST coordinates
		$s = self::ijToST($pos >> 1, level);
		$t = self::ijToST($pos & 1, level);
		
		// Convert ST to UV coordinates
		$u = 2 * $s - 1;
		$v = 2 * $t - 1;
		
		return S2::faceUVtoXYZ($face, $u, $v);
	}

	/**
	 * Convert IJ coordinates to ST coordinates.
	 */
	private static function ijToST(int $ij, int $level): float {
		$cellSize = 1 << (S2::MAX_LEVEL - $level);
		return (2 * $ij + 1) / (2 * $cellSize);
	}

	/**
	 * Returns the face of the cell (0-5).
	 */
	public function getFace(): int {
		return $this->id >> 60;
	}

	/**
	 * Returns the orientation of the cell (0-3).
	 */
	public function orientation(): int {
		return ($this->id >> 2) & 3;
	}

	/**
	 * Returns true if this is a leaf cell.
	 */
	public function isLeaf(): bool {
		return $this->level() == S2::MAX_LEVEL;
	}

	/**
	 * Returns the next cell at the same level.
	 */
	public function next(): self {
		return new self($this->id + (1 << (2 * (30 - $this->level()))));
	}

	/**
	 * Returns the previous cell at the same level.
	 */
	public function prev(): self {
		return new self($this->id - (1 << (2 * (30 - $this->level()))));
	}

	/**
	 * Returns true if this cell contains the given cell.
	 */
	public function contains(S2CellId $other): bool {
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
	public function intersects(S2CellId $other): bool {
		return $this->contains($other) || $other->contains($this);
	}

	/**
	 * Returns the IJ coordinates for this cell.
	 */
	public function toIJ(): array {
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
	private static function stToIJ(float $s): int {
		return max(0, min(S2::MAX_CELL_SIZE - 1, (int)round($s * S2::MAX_CELL_SIZE)));
	}

	/**
	 * Converts UV coordinates to ST coordinates.
	 */
	private static function uvToST(float $u): float {
		return ($u + 1) * 0.5;
	}

	/**
	 * Returns a string representation of this cell ID.
	 */
	public function __toString(): string {
		return sprintf("Face: %d, Level: %d, ID: %d", $this->getFace(), $this->level(), $this->id);
	}
}