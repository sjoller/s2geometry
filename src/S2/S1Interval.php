<?php

	namespace Sjoller\S2Geometry\S2;

	/**
	 * S1Interval represents an interval of angles.
	 */
	class S1Interval {
		private float $lo;
		private float $hi;

		/**
		 * Create a new S1Interval from the given bounds.
		 * The bounds are in radians and should be normalized to [-π, π].
		 */
		public function __construct (float $lo, float $hi) {
			$this->lo = $lo;
			$this->hi = $hi;
		}

		/**
		 * Create an empty interval.
		 */
		public static function empty (): self {
			return new self(M_PI, -M_PI);
		}

		/**
		 * Create a full interval.
		 */
		public static function full (): self {
			return new self(-M_PI, M_PI);
		}

		/**
		 * Get the lower bound of the interval.
		 */
		public function lo (): float {
			return $this->lo;
		}

		/**
		 * Get the upper bound of the interval.
		 */
		public function hi (): float {
			return $this->hi;
		}

		/**
		 * Check if the interval is empty.
		 */
		public function isEmpty (): bool {
			return $this->lo > $this->hi;
		}

		/**
		 * Check if the interval is full.
		 */
		public function isFull (): bool {
			return $this->lo == -M_PI && $this->hi == M_PI;
		}

		/**
		 * Check if the interval is inverted (lo > hi).
		 */
		public function isInverted (): bool {
			return $this->lo > $this->hi;
		}

		/**
		 * Get the complement of this interval.
		 */
		public function complement (): self {
			if ($this->isEmpty()) {
				return self::full();
			}
			if ($this->isFull()) {
				return self::empty();
			}

			return new self($this->hi, $this->lo);
		}

		/**
		 * Check if this interval contains a given angle.
		 */
		public function contains (float $angle): bool {
			if ($this->isInverted()) {
				return $angle >= $this->lo || $angle <= $this->hi;
			}

			return $angle >= $this->lo && $angle <= $this->hi;
		}

		/**
		 * Check if this interval contains another interval.
		 */
		public function containsInterval (S1Interval $other): bool {
			if ($this->isInverted()) {
				if ($other->isInverted()) {
					return $other->lo >= $this->lo && $other->hi <= $this->hi;
				}

				return $other->lo >= $this->lo || $other->hi <= $this->hi;
			}
			if ($other->isInverted()) {
				return $this->isFull();
			}

			return $other->lo >= $this->lo && $other->hi <= $this->hi;
		}

		/**
		 * Check if this interval intersects with another interval.
		 */
		public function intersects (S1Interval $other): bool {
			if ($this->isEmpty() || $other->isEmpty()) {
				return false;
			}
			if ($this->isInverted()) {
				return $other->intersects(new self($this->hi, M_PI)) ||
					$other->intersects(new self(-M_PI, $this->lo));
			}
			if ($other->isInverted()) {
				return $this->intersects(new self($other->hi, M_PI)) ||
					$this->intersects(new self(-M_PI, $other->lo));
			}

			return $other->lo <= $this->hi && $other->hi >= $this->lo;
		}

		/**
		 * Get the intersection of this interval with another interval.
		 */
		public function intersection (S1Interval $other): self {
			if ($this->isEmpty() || $other->isEmpty()) {
				return self::empty();
			}
			if ($this->isInverted()) {
				$intersection1 = (new self($this->hi, M_PI))->intersection($other);
				$intersection2 = (new self(-M_PI, $this->lo))->intersection($other);
				if ($intersection1->isEmpty()) {
					return $intersection2;
				}
				if ($intersection2->isEmpty()) {
					return $intersection1;
				}

				return new self($intersection2->lo, $intersection1->hi);
			}
			if ($other->isInverted()) {
				return $other->intersection($this);
			}
			$lo = max($this->lo, $other->lo);
			$hi = min($this->hi, $other->hi);
			if ($lo > $hi) {
				return self::empty();
			}

			return new self($lo, $hi);
		}

		/**
		 * Get the union of this interval with another interval.
		 */
		public function union (S1Interval $other): self {
			if ($this->isEmpty()) {
				return $other;
			}
			if ($other->isEmpty()) {
				return $this;
			}
			if ($this->isInverted()) {
				$union1 = (new self($this->hi, M_PI))->union($other);
				$union2 = (new self(-M_PI, $this->lo))->union($other);
				if ($union1->isFull() || $union2->isFull()) {
					return self::full();
				}

				return new self($union2->lo, $union1->hi);
			}
			if ($other->isInverted()) {
				return $other->union($this);
			}
			$lo = min($this->lo, $other->lo);
			$hi = max($this->hi, $other->hi);
			if ($hi - $lo >= 2 * M_PI) {
				return self::full();
			}

			return new self($lo, $hi);
		}

		/**
		 * Get the length of this interval.
		 */
		public function length (): float {
			if ($this->isEmpty()) {
				return 0;
			}
			if ($this->isInverted()) {
				return (2 * M_PI - $this->lo + $this->hi);
			}

			return $this->hi - $this->lo;
		}

		/**
		 * Get the center of this interval.
		 */
		public function center (): float {
			if ($this->isInverted()) {
				$center = ($this->lo + $this->hi + 2 * M_PI) / 2;
				if ($center > M_PI) {
					$center -= 2 * M_PI;
				}

				return $center;
			}

			return ($this->lo + $this->hi) / 2;
		}
	}