<?php

	namespace Sjoller\S2Geometry\S2;

	/**
	 * R2Vector represents a 2D vector in the plane.
	 */
	class R2Vector {
		private float $x;
		private float $y;

		/**
		 * Creates a new R2Vector with the given x and y coordinates.
		 */
		public function __construct (float $x, float $y) {
            $this->x = $x;
            $this->y = $y;
		}

		/**
		 * Returns the x coordinate.
		 */
		public function getX (): float {
        return $this->x;
    }

		/**
		 * Returns the y coordinate.
		 */
		public function getY (): float {
        return $this->y;
    }

		/**
		 * Returns the dot product of this vector with another vector.
		 */
		public function dot (R2Vector $v): float {
			return $this->x * $v->x + $this->y * $v->y;
    }

		/**
		 * Returns the cross product of this vector with another vector.
		 */
		public function cross (R2Vector $v): float {
			return $this->x * $v->y - $this->y * $v->x;
		}

		/**
		 * Returns the squared norm of this vector.
		 */
		public function norm2 (): float {
			return $this->x * $this->x + $this->y * $this->y;
    }

		/**
		 * Returns the norm (length) of this vector.
		 */
		public function norm (): float {
			return sqrt($this->norm2());
		}

		/**
		 * Returns a normalized version of this vector.
		 */
		public function normalize (): R2Vector {
			$norm = $this->norm();
			if ($norm == 0) {
				return new R2Vector(0, 0);
			}

			return new R2Vector($this->x / $norm, $this->y / $norm);
    }

		/**
		 * Returns the sum of this vector with another vector.
		 */
		public function add (R2Vector $v): R2Vector {
			return new R2Vector($this->x + $v->x, $this->y + $v->y);
    }

		/**
		 * Returns the difference between this vector and another vector.
		 */
		public function sub (R2Vector $v): R2Vector {
			return new R2Vector($this->x - $v->x, $this->y - $v->y);
    }

		/**
		 * Returns this vector multiplied by a scalar.
		 */
		public function mul (float $m): R2Vector {
			return new R2Vector($this->x * $m, $this->y * $m);
    }

		/**
		 * Returns this vector divided by a scalar.
		 */
		public function div (float $m): R2Vector {
			return new R2Vector($this->x / $m, $this->y / $m);
		}

		/**
		 * Returns true if this vector equals another vector.
		 */
		public function equals (R2Vector $v): bool {
			return $this->x == $v->x && $this->y == $v->y;
		}

		/**
		 * Returns true if this vector is less than another vector in lexicographic order.
		 */
		public function lessThan (R2Vector $v): bool {
			if ($this->x < $v->x) {
            return true;
        }
			if ($this->x > $v->x) {
        return false;
    }

			return $this->y < $v->y;
		}

		/**
		 * Returns the angle between this vector and another vector.
		 */
		public function angle (R2Vector $v): float {
			$dot = $this->dot($v);
			$cross = $this->cross($v);

			return atan2($cross, $dot);
    }

    /**
		 * Returns the distance between this vector and another vector.
		 */
		public function distance (R2Vector $v): float {
			return $this->sub($v)->norm();
		}

		/**
		 * Returns the squared distance between this vector and another vector.
     */
		public function distance2 (R2Vector $v): float {
			return $this->sub($v)->norm2();
		}

		/**
		 * Returns a string representation of this vector.
		 */
		public function __toString (): string {
        return "(" . $this->x . ", " . $this->y . ")";
    }
}