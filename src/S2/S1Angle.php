<?php

	namespace Sjoller\S2Geometry\S2;

	/**
	 * S1Angle represents an angle in radians and provides various angle-related operations.
	 */
	class S1Angle {
		private float $radians;

		/**
		 * Create a new S1Angle with the given angle in radians.
		 */
		public function __construct (float $radians) {
			$this->radians = $radians;
		}

    /**
		 * Create a new S1Angle from degrees.
		 */
		public static function fromDegrees (float $degrees): self {
			return new self(deg2rad($degrees));
		}

		/**
		 * Create a new S1Angle from radians.
		 */
		public static function fromRadians (float $radians): self {
			return new self($radians);
		}

		/**
		 * Get the angle in radians.
     */
		public function radians (): float {
        return $this->radians;
    }

		/**
		 * Get the angle in degrees.
     */
		public function degrees (): float {
			return rad2deg($this->radians);
    }

    /**
		 * Get the absolute value of the angle.
     */
		public function abs (): self {
			return new self(abs($this->radians));
    }

		/**
		 * Get the normalized angle in the range [-π, π].
		 */
		public function normalize (): self {
			$radians = fmod($this->radians, 2 * M_PI);
			if ($radians <= -M_PI) {
				$radians += 2 * M_PI;
    }
			elseif ($radians > M_PI) {
				$radians -= 2 * M_PI;
			}

			return new self($radians);
		}

		/**
		 * Add another angle to this angle.
		 */
		public function add (S1Angle $other): self {
			return new self($this->radians + $other->radians);
		}

		/**
		 * Subtract another angle from this angle.
     */
		public function sub (S1Angle $other): self {
			return new self($this->radians - $other->radians);
		}

		/**
		 * Multiply this angle by a scalar.
		 */
		public function mul (float $scalar): self {
			return new self($this->radians * $scalar);
        }

		/**
		 * Divide this angle by a scalar.
		 */
		public function div (float $scalar): self {
			return new self($this->radians / $scalar);
		}

		/**
		 * Check if this angle is less than another angle.
		 */
		public function lessThan (S1Angle $other): bool {
			return $this->radians < $other->radians;
    }

		/**
		 * Check if this angle is greater than another angle.
		 */
		public function greaterThan (S1Angle $other): bool {
			return $this->radians > $other->radians;
    }

		/**
		 * Check if this angle is equal to another angle.
		 */
		public function equals (S1Angle $other): bool {
			return $this->radians === $other->radians;
    }

		/**
		 * Get the sine of this angle.
		 */
		public function sin (): float {
			return sin($this->radians);
		}

		/**
		 * Get the cosine of this angle.
		 */
		public function cos (): float {
			return cos($this->radians);
		}

		/**
		 * Get the tangent of this angle.
		 */
		public function tan (): float {
			return tan($this->radians);
		}

		/**
		 * Get the arc sine of this angle.
		 */
		public function asin (): float {
			return asin($this->radians);
		}

		/**
		 * Get the arc cosine of this angle.
		 */
		public function acos (): float {
			return acos($this->radians);
		}

		/**
		 * Get the arc tangent of this angle.
		 */
		public function atan (): float {
			return atan($this->radians);
    }

    /**
		 * Get the arc tangent of y/x.
		 */
		public static function atan2 (float $y, float $x): float {
			return atan2($y, $x);
		}

		/**
		 * Get the angle between two points on the sphere.
     */
		public static function betweenPoints (S2Point $a, S2Point $b): self {
			return new self($a->angle($b));
    }

		/**
		 * Get the angle between two vectors.
		 */
		public static function betweenVectors (S2Point $a, S2Point $b): self {
			return new self($a->angle($b));
        }
	}