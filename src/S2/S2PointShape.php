<?php

	namespace Sjoller\S2Geometry\S2;

	use InvalidArgumentException;

	/**
	 * S2PointShape is a concrete implementation of S2Shape for points.
	 * It represents a point as a degenerate edge (v0 == v1).
	 */
	class S2PointShape implements S2Shape {
		private S2Point $point;
		private array $edges = [];
		private array $chains = [];
		private int $numEdges = 1;

		public function __construct (S2Point $point) {
			$this->point = $point;
			$this->initEdges();
		}

		/**
		 * Initialize the edges and chains from the point.
		 * A point is represented as a degenerate edge where v0 == v1.
		 */
		private function initEdges (): void {
			$this->edges = [];
			$this->chains = [];

			// Create a degenerate edge for the point
			$this->edges[] = new S2ShapeTypes($this->point, $this->point);

			// Add a single chain for the point
			$this->chains[] = new S2ShapeChain(0, 1);
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

			// For points, there is only one edge in one chain
			return new S2ShapeChainPosition(0, 0);
		}

		public function contains (S2Point $p): bool {
			return $this->point->equals($p);
		}

		public function getCapBound (): S2Cap {
			return S2Cap::fromPoint($this->point);
		}

		public function getRectBound (): S2LatLngRect {
			$latLng = S2LatLng::fromPoint($this->point);

			return S2LatLngRect::fromPoint($latLng);
		}

		/**
		 * Returns the underlying point.
		 */
		public function getPoint (): S2Point {
			return $this->point;
		}
	}