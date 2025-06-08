<?php

	namespace Sjoller\S2Geometry\S2;

	use InvalidArgumentException;

	/**
	 * S2PolygonBuilder helps construct polygons from various inputs and handles loop normalization.
	 * It ensures that loops are properly oriented and handles the creation of holes.
	 */
	class S2PolygonBuilder {
		/** @var S2Loop[] */
		private array $loops = [];

		/**
		 * Creates a new polygon builder.
		 */
		public function __construct () {
		}

		/**
		 * Adds a loop to the polygon.
		 * The loop must be valid (unit length vertices, closed loop).
		 */
		public function addLoop (S2Loop $loop): self {
			if (!$loop->isValid()) {
				throw new InvalidArgumentException('Loop must be valid');
			}

			$this->loops[] = $loop;

			return $this;
		}

		/**
		 * Adds multiple loops to the polygon.
		 * All loops must be valid.
		 *
		 * @param S2Loop[] $loops
		 */
		public function addLoops (array $loops): self {
			foreach ($loops as $loop) {
				$this->addLoop($loop);
			}

			return $this;
		}

		/**
		 * Builds a polygon from the added loops.
		 * The first loop is treated as the outer shell, and subsequent loops are treated as holes.
		 */
		public function build (): S2Polygon {
			if (empty($this->loops)) {
				return new S2Polygon([]);
			}

			// Ensure the first loop is normalized (area <= 2π)
			$firstLoop = $this->loops[0];
			if (!$firstLoop->isNormalized()) {
				$firstLoop = $firstLoop->normalize();
			}

			// Process remaining loops
			$processedLoops = [$firstLoop];
			for ($i = 1; $i < count($this->loops); $i++) {
				$loop = $this->loops[$i];

				// Ensure hole loops are normalized in the opposite direction
				if ($loop->isNormalized()) {
					$loop = $loop->normalize()->invert();
				}

				$processedLoops[] = $loop;
			}

			return new S2Polygon($processedLoops);
		}

		/**
		 * Clears all loops from the builder.
		 */
		public function clear (): self {
			$this->loops = [];

			return $this;
		}

		/**
		 * Returns the number of loops currently in the builder.
		 */
		public function numLoops (): int {
			return count($this->loops);
		}

		/**
		 * Returns all loops currently in the builder.
		 *
		 * @return S2Loop[]
		 */
		public function loops (): array {
			return $this->loops;
		}

		/**
		 * Returns true if the builder has no loops.
		 */
		public function isEmpty (): bool {
			return empty($this->loops);
		}
	}