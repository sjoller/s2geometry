<?php

	namespace Sjoller\S2Geometry\S2;

	use InvalidArgumentException;

	/**
	 * S2Projections provides various map projections for the S2 geometry system.
	 */
	class S2Projections {
		private const FACE_UV_TO_XYZ = [
			[1, 0, 0],  // face 0
			[-1, 0, 0], // face 1
			[0, 1, 0],  // face 2
			[0, -1, 0], // face 3
			[0, 0, 1],  // face 4
			[0, 0, -1]  // face 5
		];

		/**
		 * Projects a point on the sphere to a point on the cube face.
		 */
		public static function projectToFace (S2Point $p, int $face): R2Vector {
			$u = 0.0;
			$v = 0.0;

			switch ($face) {
				case 0: // +X
					$u = $p->getY() / $p->getX();
					$v = $p->getZ() / $p->getX();
					break;
				case 1: // +Y
					$u = -$p->getX() / $p->getY();
					$v = $p->getZ() / $p->getY();
					break;
				case 2: // +Z
					$u = -$p->getX() / $p->getZ();
					$v = -$p->getY() / $p->getZ();
					break;
				case 3: // -X
					$u = $p->getZ() / $p->getX();
					$v = $p->getY() / $p->getX();
					break;
				case 4: // -Y
					$u = $p->getZ() / $p->getY();
					$v = -$p->getX() / $p->getY();
					break;
				case 5: // -Z
					$u = -$p->getY() / $p->getZ();
					$v = -$p->getX() / $p->getZ();
					break;
			}

			return new R2Vector($u, $v);
    }

    /**
		 * Projects a point on a cube face back to a point on the sphere.
     */
		public static function unprojectFromFace (R2Vector $p, int $face): S2Point {
			$x = 0.0;
			$y = 0.0;
			$z = 0.0;
        
        switch ($face) {
				case 0: // +X
					$x = 1.0;
					$y = $p->getX();
					$z = $p->getY();
                break;
				case 1: // +Y
					$x = -$p->getX();
					$y = 1.0;
					$z = $p->getY();
                break;
				case 2: // +Z
					$x = -$p->getX();
					$y = -$p->getY();
					$z = 1.0;
                break;
				case 3: // -X
					$x = -1.0;
					$y = $p->getY();
					$z = $p->getX();
                break;
				case 4: // -Y
					$x = $p->getY();
					$y = -1.0;
					$z = $p->getX();
                break;
				case 5: // -Z
					$x = $p->getY();
					$y = $p->getX();
					$z = -1.0;
                break;
        }

			return (new S2Point($x, $y, $z))->normalize();
    }

		/**
		 * Projects a point on the sphere to a point on the cube face with rotation.
		 */
		public static function projectToFaceWithRotation (S2Point $p, int $face, int $rotation): R2Vector {
			$uv = self::projectToFace($p, $face);

			return self::rotateUV($uv, $rotation);
    }

		/**
		 * Projects a point on a cube face back to a point on the sphere with rotation.
		 */
		public static function unprojectFromFaceWithRotation (R2Vector $p, int $face, int $rotation): S2Point {
			$uv = self::unrotateUV($p, $rotation);

			return self::unprojectFromFace($uv, $face);
      }

		/**
		 * Rotates UV coordinates by the given rotation.
		 */
		private static function rotateUV (R2Vector $p, int $rotation): R2Vector {
			$u = $p->getX();
			$v = $p->getY();

			switch ($rotation) {
      case 0:
					return new R2Vector($u, $v);
      case 1:
					return new R2Vector($v, -$u);
      case 2:
					return new R2Vector(-$u, -$v);
      case 3:
					return new R2Vector(-$v, $u);
      default:
					throw new InvalidArgumentException("Invalid rotation: " . $rotation);
    }
  }

		/**
		 * Unrotates UV coordinates by the given rotation.
		 */
		private static function unrotateUV (R2Vector $p, int $rotation): R2Vector {
			$u = $p->getX();
			$v = $p->getY();

			switch ($rotation) {
      case 0:
					return new R2Vector($u, $v);
      case 1:
					return new R2Vector(-$v, $u);
      case 2:
					return new R2Vector(-$u, -$v);
      case 3:
					return new R2Vector($v, -$u);
      default:
					throw new InvalidArgumentException("Invalid rotation: " . $rotation);
			}
		}

		/**
		 * Returns the face containing the given point.
*/
		public static function getFace (S2Point $p): int {
			$absX = abs($p->getX());
			$absY = abs($p->getY());
			$absZ = abs($p->getZ());

			if ($absX > $absY && $absX > $absZ) {
				return $p->getX() > 0 ? 0 : 3;
			}
			else {
				if ($absY > $absZ) {
					return $p->getY() > 0 ? 1 : 4;
				}
				else {
					return $p->getZ() > 0 ? 2 : 5;
				}
			}
		}

		/**
		 * Returns the UV coordinates for the given point on the given face.
		 */
		public static function getFaceUV (S2Point $p, int $face): R2Vector {
			return self::projectToFace($p, $face);
		}

		/**
		 * Returns the point on the sphere for the given UV coordinates on the given face.
		 */
		public static function getFaceXYZ (int $face, float $u, float $v): S2Point {
			return self::unprojectFromFace(new R2Vector($u, $v), $face);
        }
    }