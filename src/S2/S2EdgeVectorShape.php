<?php

	namespace Sjoller\S2Geometry\S2;

	use InvalidArgumentException;

	/**
	 * S2EdgeVectorShape is a concrete implementation of S2Shape for collections of edges.
	 * It represents a set of edges, each as a pair of vertices (v0, v1).
	 */
	class S2EdgeVectorShape implements S2Shape {
		private array $edges = [];
		private array $chains = [];
		private int $numEdges = 0;

		public function __construct (array $edges) {
			$this->edges = $edges;
			$this->numEdges = count($edges);
			$this->initChains();
		}

		/**
		 * Initialize the chains from the edges.
		 * Each edge gets its own chain for maximum flexibility.
		 */
		private function initChains (): void {
			$this->chains = [];
			for ($i = 0; $i < $this->numEdges; $i++) {
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
			return 1; // Edge dimension
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
			foreach ($this->edges as $edge) {
				if (S2EdgeUtil::contains($edge->v0, $edge->v1, $p)) {
					return true;
				}
			}

			return false;
		}

		public function getCapBound (): S2Cap {
			if (empty($this->edges)) {
				return S2Cap::empty();
			}

			// Start with a cap containing the first edge
			$cap = S2Cap::fromPoint($this->edges[0]->v0);
			$cap = $cap->union(S2Cap::fromPoint($this->edges[0]->v1));

			// Expand the cap to include all other edges
			for ($i = 1; $i < $this->numEdges; $i++) {
				$cap = $cap->union(S2Cap::fromPoint($this->edges[$i]->v0));
				$cap = $cap->union(S2Cap::fromPoint($this->edges[$i]->v1));
			}

			return $cap;
		}

		public function getRectBound (): S2LatLngRect {
			if (empty($this->edges)) {
				return S2LatLngRect::empty();
			}

			// Start with a rectangle containing the first edge
			$rect = S2LatLngRect::fromPoint($this->edges[0]->v0->toLatLng());
			$rect = $rect->union(S2LatLngRect::fromPoint($this->edges[0]->v1->toLatLng()));

			// Expand the rectangle to include all other edges
			for ($i = 1; $i < $this->numEdges; $i++) {
				$rect = $rect->union(S2LatLngRect::fromPoint($this->edges[$i]->v0->toLatLng()));
				$rect = $rect->union(S2LatLngRect::fromPoint($this->edges[$i]->v1->toLatLng()));
			}

			return $rect;
		}

		/**
		 * Returns the underlying edges.
		 */
		public function getEdges (): array {
			return $this->edges;
		}
	}