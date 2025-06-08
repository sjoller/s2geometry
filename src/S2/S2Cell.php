<?php

	namespace Sjoller\S2Geometry\S2;

	/**
	 * S2Cell represents a cell in the S2 cell hierarchy.
	 */
	class S2Cell implements S2Region {
		private S2CellId $cellId;
		private int $face;
		private int $level;
		private int $orientation;
		private array $uv;

		/**
		 * Creates a new S2Cell from a cell ID.
		 */
		public function __construct (S2CellId $cellId) {
			$this->cellId = $cellId;
			$this->face = $cellId->getFace();
			$this->level = $cellId->level();
			$this->orientation = $cellId->orientation();
			$this->uv = $this->getUV();
		}

		/**
		 * Create a cell from a point on the unit sphere.
		 */
		public static function fromPoint (S2Point $p): self {
			return new self(S2CellId::fromPoint($p));
		}

		/**
		 * Returns the cell ID.
		 */
		public function id (): S2CellId {
        return $this->cellId;
    }

		/**
		 * Returns the face of the cell.
		 */
		public function face (): int {
        return $this->face;
    }

		/**
		 * Returns the level of the cell.
		 */
		public function level (): int {
        return $this->level;
		}

		/**
		 * Returns the orientation of the cell.
		 */
		public function orientation (): int {
			return $this->orientation;
		}

		/**
		 * Returns true if the cell is a leaf cell.
		 */
		public function isLeaf (): bool {
			return $this->level == S2::MAX_LEVEL;
		}

		/**
		 * Returns the center of the cell.
		 */
		public function getCenter (): S2Point {
			return $this->cellId->toPoint();
		}

		/**
		 * Returns the average area of cells at the given level.
		 */
		public static function averageArea (int $level): float {
			return 4 * M_PI / (6 * (1 << (2 * $level)));
		}

		/**
		 * Returns the exact area of this cell.
		 */
		public function exactArea (): float {
			$v0 = $this->getVertex(0);
			$v1 = $this->getVertex(1);
			$v2 = $this->getVertex(2);
			$v3 = $this->getVertex(3);

			return S2::area($v0, $v1, $v2) + S2::area($v0, $v2, $v3);
		}

		/**
		 * Returns the vertices of the cell.
		 */
		public function getVertices (): array {
			$vertices = [];
			for ($i = 0; $i < 4; $i++) {
				$vertices[] = $this->getVertex($i);
			}

			return $vertices;
		}

		/**
		 * Returns the vertex at the given index (0-3).
		 */
		public function getVertex (int $k): S2Point {
			$u = $this->uv[0][$k & 1];
			$v = $this->uv[1][($k >> 1) & 1];

			return S2::faceUVtoXYZ($this->face, $u, $v);
		}

		/**
		 * Returns the edge at the given index (0-3).
		 */
		public function getEdge (int $k): S2Edge {
			return new S2Edge(
				$this->getVertex($k),
				$this->getVertex(($k + 1) & 3)
			);
		}

		/**
		 * Returns true if the cell contains the given point.
		 */
		public function contains (S2Point $p): bool {
			$uv = S2::faceXYZtoUV($this->face, $p);
			if ($uv === null) {
                return false;
            }

			return $uv[0] >= $this->uv[0][0] && $uv[0] <= $this->uv[0][1] &&
				$uv[1] >= $this->uv[1][0] && $uv[1] <= $this->uv[1][1];
		}

		/**
		 * Returns true if the cell may intersect with the given cell.
		 */
		public function mayIntersect (S2Cell $cell): bool {
			return $this->cellId->intersects($cell->id());
		}

		/**
		 * Returns a bounding cap that contains the cell.
		 */
		public function getCapBound (): S2Cap {
			$center = $this->getCenter();
			$height = 0;
			for ($i = 0; $i < 4; $i++) {
				$height = max($height, $center->dot($this->getVertex($i)));
			}

			return S2Cap::fromAxisHeight($center, 1 - $height);
		}

		/**
		 * Returns the bounding rectangle of this cell.
		 */
		public function getRectBound (): S2LatLngRect {
			$lo = $this->getVertex(0)->toLatLng();
			$hi = $lo;

			for ($i = 1; $i < 4; $i++) {
				$ll = $this->getVertex($i)->toLatLng();
				$lo = S2LatLng::fromRadians(
					(float)min($lo->latRadians(), $ll->latRadians()),
					(float)min($lo->lngRadians(), $ll->lngRadians())
				);
				$hi = S2LatLng::fromRadians(
					(float)max($hi->latRadians(), $ll->latRadians()),
					(float)max($hi->lngRadians(), $ll->lngRadians())
				);
			}

			return new S2LatLngRect(
				new R1Interval($lo->latRadians(), $hi->latRadians()),
				new S1Interval($lo->lngRadians(), $hi->lngRadians())
			);
		}

		/**
		 * Returns the UV coordinates of the cell.
		 */
		private function getUV (): array {
			$uv = [[0, 0], [0, 0]];
			$ij = $this->cellId->toIJ();
			$uv[0][0] = S2::ijToUV($ij[0], $this->level);
			$uv[0][1] = S2::ijToUV($ij[0] + 1, $this->level);
			$uv[1][0] = S2::ijToUV($ij[1], $this->level);
			$uv[1][1] = S2::ijToUV($ij[1] + 1, $this->level);

			return $uv;
		}
	}