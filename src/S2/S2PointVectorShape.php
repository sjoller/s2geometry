<?php

	namespace Sjoller\S2Geometry\S2;

	use InvalidArgumentException;

	/**
	 * S2PointVectorShape is a concrete implementation of S2Shape for collections of points.
	 * It represents a set of points, each as a degenerate edge (v0 == v1).
	 */
	class S2PointVectorShape implements S2Shape {
		private array $points;
		private array $edges = [];
		private array $chains = [];
		private int $numEdges = 0;

		public function __construct (array $points) {
			$this->points = $points;
			$this->initEdges();
		}

		/**
		 * Initialize the edges and chains from the points.
		 * Each point is represented as a degenerate edge where v0 == v1.
		 */
		private function initEdges (): void {
			$this->edges = [];
			$this->chains = [];
			$this->numEdges = count($this->points);

			// Create degenerate edges for each point
			foreach ($this->points as $i => $point) {
				$this->edges[] = new S2ShapeTypes($point, $point);
				// Each point gets its own chain
				$this->chains[] = new S2ShapeChain($i, 1);
			}
		}

		public function numEdges (): int {
			return $this->numEdges;
		}

		public function edge (int $edgeId): S2ShapeTypes {
			if ($edgeId < 0 || $edgeId >= $this->numEdges) {
				throw new InvalidArgumentException("Edge ID out of range");
			}

			return $this->edges[$edgeId];
		}

		public function dimension (): int {
			return 0; // Point dimension
		}

		public function numChains (): int {
			return count($this->chains);
		}

		public function chain (int $chainId): S2ShapeChain {
			if ($chainId < 0 || $chainId >= count($this->chains)) {
				throw new InvalidArgumentException("Chain ID out of range");
			}

			return $this->chains[$chainId];
		}

		public function chainPosition (int $edgeId): S2ShapeChainPosition {
			if ($edgeId < 0 || $edgeId >= $this->numEdges) {
				throw new InvalidArgumentException("Edge ID out of range");
			}

			// Each edge is in its own chain with offset 0
			return new S2ShapeChainPosition($edgeId, 0);
		}

		public function contains (S2Point $p): bool {
			foreach ($this->points as $point) {
				if ($point->equals($p)) {
					return true;
				}
			}

			return false;
		}

		public function getCapBound (): S2Cap {
			if (empty($this->points)) {
				return S2Cap::empty();
			}

			// Start with a cap centered at the first point
			$cap = S2Cap::fromPoint($this->points[0]);

			// Expand the cap to include all other points
			for ($i = 1; $i < count($this->points); $i++) {
				$cap = $cap->addPoint($this->points[$i]);
			}

			return $cap;
		}

		public function getRectBound (): S2LatLngRect {
			if (empty($this->points)) {
				return S2LatLngRect::empty();
			}

			// Start with a rectangle containing the first point
			$rect = S2LatLngRect::fromPoint(S2LatLng::fromPoint($this->points[0]));

			// Expand the rectangle to include all other points
			for ($i = 1; $i < count($this->points); $i++) {
				$rect = $rect->addPoint(S2LatLng::fromPoint($this->points[$i]));
			}

			return $rect;
		}

		/**
		 * Returns the underlying points.
		 */
		public function getPoints (): array {
			return $this->points;
		}

		/**
		 * Returns the number of points.
		 */
		public function numPoints (): int {
			return count($this->points);
		}

		/**
		 * Returns the point at the given index.
		 */
		public function point (int $index): S2Point {
			if ($index < 0 || $index >= count($this->points)) {
				throw new InvalidArgumentException("Point index out of range");
			}

			return $this->points[$index];
		}
	}