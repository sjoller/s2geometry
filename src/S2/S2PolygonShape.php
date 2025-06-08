<?php

	namespace Sjoller\S2Geometry\S2;

	use InvalidArgumentException;
	use RuntimeException;

	/**
	 * S2PolygonShape is a concrete implementation of S2Shape for polygons.
	 * It represents a polygon as a collection of edges organized into chains.
	 */
	class S2PolygonShape implements S2Shape {
		private S2Polygon $polygon;
		private array $edges = [];
		private array $chains = [];
		private int $numEdges = 0;

		public function __construct (S2Polygon $polygon) {
			$this->polygon = $polygon;
			$this->initEdges();
		}

		/**
		 * Initialize the edges and chains from the polygon's loops.
		 */
		private function initEdges (): void {
			$this->edges = [];
			$this->chains = [];
			$this->numEdges = 0;

			// Add edges from each loop
			for ($i = 0; $i < $this->polygon->numLoops(); $i++) {
				$loop = $this->polygon->loop($i);
				$start = $this->numEdges;
				$length = $loop->numVertices();

				// Add edges for this loop
				for ($j = 0; $j < $length; $j++) {
					$v0 = $loop->vertex($j);
					$v1 = $loop->vertex(($j + 1) % $length);
					$this->edges[] = new S2ShapeTypes($v0, $v1);
				}

				// Add chain for this loop
				$this->chains[] = new S2ShapeChain($start, $length);
				$this->numEdges += $length;
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
			return 2; // Polygon dimension
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

			// Find the chain containing this edge
			foreach ($this->chains as $chainId => $chain) {
				if ($edgeId >= $chain->start && $edgeId < $chain->start + $chain->length) {
					return new S2ShapeChainPosition($chainId, $edgeId - $chain->start);
				}
			}

			throw new RuntimeException("Edge not found in any chain");
		}

		public function contains (S2Point $p): bool {
			return $this->polygon->contains($p);
		}

		public function getCapBound (): S2Cap {
			return $this->polygon->getCapBound();
		}

		public function getRectBound (): S2LatLngRect {
			return $this->polygon->getRectBound();
		}

		/**
		 * Returns the underlying polygon.
		 */
		public function getPolygon (): S2Polygon {
			return $this->polygon;
		}
	}