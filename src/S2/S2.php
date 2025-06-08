<?php

	namespace Sjoller\S2Geometry\S2;

	use InvalidArgumentException;

	/**
	 * S2 provides utility functions and constants for the S2 geometry library.
	 */
	class S2 {
		// Constants for the cube faces
		public const FACE_BITS = 3;
		public const NUM_FACES = 6;
		public const MAX_LEVEL = 30;
		public const POS_BITS = 2 * self::MAX_LEVEL + 1;
		public const MAX_CELL_ID = (1 << (self::FACE_BITS + self::POS_BITS)) - 1;

		// Constants for the cube projection
		public const SWAP_MASK = 0x01;
		public const INVERT_MASK = 0x02;
		public const FACE_MASK = 0x07;

		// Constants for the Hilbert curve
		public const LOOKUP_BITS = 4;
		public const LOOKUP_MASK = (1 << self::LOOKUP_BITS) - 1;
		public const LOOKUP_PS_MAX = 1 << (self::LOOKUP_BITS - 1);

		// Constants for the cell hierarchy
		public const MIN_LEVEL = 0;
		public const MAX_CELL_SIZE = 1 << self::MAX_LEVEL;

		// Constants for the sphere
		public const EARTH_RADIUS_METERS = 6371000.0;
		public const EARTH_RADIUS_KM = self::EARTH_RADIUS_METERS / 1000.0;
		public const EARTH_RADIUS_MILES = self::EARTH_RADIUS_METERS / 1609.344;

		/**
		 * Convert a point on the sphere to a point on the cube face.
		 */
		public static function faceXYZtoUV (int $face, S2Point $p): array {
			switch ($face) {
				case 0:
					return [$p->getY() / $p->getX(), $p->getZ() / $p->getX()];
				case 1:
					return [-$p->getX() / $p->getY(), $p->getZ() / $p->getY()];
				case 2:
					return [-$p->getX() / $p->getZ(), -$p->getY() / $p->getZ()];
				case 3:
					return [$p->getZ() / $p->getX(), $p->getY() / $p->getX()];
				case 4:
					return [$p->getZ() / $p->getY(), -$p->getX() / $p->getY()];
				case 5:
					return [-$p->getY() / $p->getZ(), -$p->getX() / $p->getZ()];
				default:
					throw new InvalidArgumentException("Invalid face: $face");
			}
		}

		/**
		 * Convert a point on the cube face to a point on the sphere.
		 */
		public static function faceUVtoXYZ (int $face, float $u, float $v): S2Point {
			$u = max(-1.0, min(1.0, $u));
			$v = max(-1.0, min(1.0, $v));

			switch ($face) {
				case 0:
					return new S2Point(1, $u, $v);
				case 1:
					return new S2Point(-$u, 1, $v);
				case 2:
					return new S2Point(-$u, -$v, 1);
				case 3:
					return new S2Point(-1, -$v, -$u);
				case 4:
					return new S2Point($v, -1, -$u);
				case 5:
					return new S2Point($v, $u, -1);
				default:
					throw new InvalidArgumentException("Invalid face: $face");
			}
		}

		/**
		 * Get the face containing the given point.
		 */
		public static function getFace (S2Point $p): int {
			$absX = abs($p->getX());
			$absY = abs($p->getY());
			$absZ = abs($p->getZ());

			if ($absX > $absY) {
				if ($absX > $absZ) {
					return $p->getX() > 0 ? 0 : 3;
				}
			}
			else {
				if ($absY > $absZ) {
					return $p->getY() > 0 ? 1 : 4;
				}
			}

			return $p->getZ() > 0 ? 2 : 5;
		}

		/**
		 * Get the face containing the given point and the UV coordinates.
		 */
		public static function getFaceUV (S2Point $p): array {
			$face = self::getFace($p);
			$uv = self::faceXYZtoUV($face, $p);

			return [$face, $uv[0], $uv[1]];
		}

		/**
		 * Get the face containing the given point and the UV coordinates, with the point normalized.
		 */
		public static function getFaceUVW (S2Point $p): array {
			$p = $p->normalize();
			$face = self::getFace($p);
			$uv = self::faceXYZtoUV($face, $p);

			return [$face, $uv[0], $uv[1], $p];
		}

		/**
		 * Get the face containing the given point and the UV coordinates, with the point normalized and the face rotated.
		 */
		public static function getFaceUVWRotated (S2Point $p): array {
			$p = $p->normalize();
			$face = self::getFace($p);
			$uv = self::faceXYZtoUV($face, $p);
			$rot = self::getFaceRotation($face);

			return [$face, $uv[0], $uv[1], $p, $rot];
		}

		/**
		 * Get the rotation for the given face.
		 */
		public static function getFaceRotation (int $face): int {
			return ($face & self::SWAP_MASK) | (($face & self::INVERT_MASK) >> 1);
		}

		/**
		 * Get the face containing the given point and the UV coordinates, with the point normalized and the face rotated.
		 */
		public static function getFaceUVWRotatedAndInverted (S2Point $p): array {
			$p = $p->normalize();
			$face = self::getFace($p);
			$uv = self::faceXYZtoUV($face, $p);
			$rot = self::getFaceRotation($face);
			$inverted = ($face & self::INVERT_MASK) != 0;

			return [$face, $uv[0], $uv[1], $p, $rot, $inverted];
		}

		/**
		 * Get the face containing the given point and the UV coordinates, with the point normalized and the face rotated.
		 */
		public static function getFaceUVWRotatedAndInvertedAndSwapped (S2Point $p): array {
			$p = $p->normalize();
			$face = self::getFace($p);
			$uv = self::faceXYZtoUV($face, $p);
			$rot = self::getFaceRotation($face);
			$inverted = ($face & self::INVERT_MASK) != 0;
			$swapped = ($face & self::SWAP_MASK) != 0;

			return [$face, $uv[0], $uv[1], $p, $rot, $inverted, $swapped];
		}

		/**
		 * Returns the area of a triangle on the unit sphere.
		 */
		public static function area (S2Point $a, S2Point $b, S2Point $c): float {
			$ab = $a->cross($b);
			$bc = $b->cross($c);
			$ca = $c->cross($a);

			$area = $ab->dot($c) + $bc->dot($a) + $ca->dot($b);
			$area = atan2($area, $ab->dot($bc));

			return abs($area);
		}

		/**
		 * Converts IJ coordinates to UV coordinates.
		 */
		public static function ijToUV (int $ij, int $level): float {
			$cellSize = 1 << (S2::MAX_LEVEL - $level);
			return (2 * $ij + 1) / (2 * $cellSize) - 1;
		}
	}