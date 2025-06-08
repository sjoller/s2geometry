<?php

	namespace Sjoller\S2Geometry\S2;

	/**
	 * R1Interval represents a closed interval on the real line.
	 */
	class R1Interval {
		private float $lo;
		private float $hi;

		/**
		 * Create a new R1Interval from the given bounds.
		 */
		public function __construct (float $lo, float $hi) {
			$this->lo = $lo;
			$this->hi = $hi;
		}

		/**
		 * Create an empty interval.
		 */
		public static function empty (): self {
			return new self(1, 0);
		}

		/**
		 * Create a full interval.
		 */
		public static function full (): self {
			return new self(-INF, INF);
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
			return $this->lo == -INF && $this->hi == INF;
		}

		/**
		 * Get the center of the interval.
		 */
		public function center (): float {
			return ($this->lo + $this->hi) * 0.5;
		}

		/**
		 * Get the length of the interval.
		 */
		public function length (): float {
			if ($this->isEmpty()) {
				return 0;
			}

			return $this->hi - $this->lo;
		}

		/**
		 * Check if this interval contains a given value.
		 */
		public function contains (float $value): bool {
			return $value >= $this->lo && $value <= $this->hi;
		}

		/**
		 * Check if this interval contains another interval.
		 */
		public function containsInterval (R1Interval $other): bool {
			if ($other->isEmpty()) {
				return true;
			}

			return $other->lo >= $this->lo && $other->hi <= $this->hi;
		}

		/**
		 * Check if this interval intersects with another interval.
		 */
		public function intersects (R1Interval $other): bool {
			if ($this->isEmpty() || $other->isEmpty()) {
				return false;
			}

			return $other->lo <= $this->hi && $other->hi >= $this->lo;
		}

		/**
		 * Get the intersection of this interval with another interval.
		 */
		public function intersection (R1Interval $other): self {
			if ($this->isEmpty() || $other->isEmpty()) {
				return self::empty();
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
		public function union (R1Interval $other): self {
			if ($this->isEmpty()) {
				return $other;
			}
			if ($other->isEmpty()) {
				return $this;
			}

			return new self(min($this->lo, $other->lo), max($this->hi, $other->hi));
		}

		/**
		 * Expand the interval by the given amount in both directions.
		 */
		public function expand (float $radius): self {
			if ($this->isEmpty()) {
				return $this;
			}

			return new self($this->lo - $radius, $this->hi + $radius);
		}

		/**
		 * Get the interval that contains both this interval and the given value.
		 */
		public function addPoint (float $value): self {
			if ($this->isEmpty()) {
				return new self($value, $value);
			}

			return new self(min($this->lo, $value), max($this->hi, $value));
		}

		/**
		 * Get the interval that contains both this interval and the given interval.
		 */
		public function addInterval (R1Interval $other): self {
			if ($this->isEmpty()) {
				return $other;
			}
			if ($other->isEmpty()) {
				return $this;
			}

			return new self(min($this->lo, $other->lo), max($this->hi, $other->hi));
		}

		/**
		 * Get the interval that contains all points in this interval that are also in the given interval.
		 */
		public function clamp (R1Interval $other): self {
			if ($this->isEmpty()) {
				return $this;
			}
			if ($other->isEmpty()) {
				return $other;
			}

			return new self(max($this->lo, $other->lo), min($this->hi, $other->hi));
		}
	}