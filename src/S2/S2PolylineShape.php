<?php

	namespace Sjoller\S2Geometry\S2;

	use InvalidArgumentException;

	/**
	 * S2PolylineShape is a concrete implementation of S2Shape for polylines.
	 * It represents a polyline as a collection of edges organized into a single chain.
	 */
	class S2PolylineShape implements S2Shape {
		private S2Polyline $polyline;
		private array $edges = [];
		private array $chains = [];
		private int $numEdges = 0;

		public function __construct (S2Polyline $polyline) {
			$this->polyline = $polyline;
			$this->initEdges();
		}

		/**
		 * Initialize the edges and chains from the polyline's vertices.
		 */
		private function initEdges (): void {
			$this->edges = [];
			$this->chains = [];
			$this->numEdges = 0;

			$numVertices = $this->polyline->numVertices();
			if ($numVertices < 2) {
				return; // Need at least 2 vertices for an edge
			}

			// Add edges for the polyline
			for ($i = 0; $i < $numVertices - 1; $i++) {
				$v0 = $this->polyline->vertex($i);
				$v1 = $this->polyline->vertex($i + 1);
				$this->edges[] = new S2ShapeTypes($v0, $v1);
			}

			// Add a single chain for the entire polyline
			$this->chains[] = new S2ShapeChain(0, $numVertices - 1);
			$this->numEdges = $numVertices - 1;
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
			return 1; // Polyline dimension
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

			// For polylines, all edges are in a single chain
			return new S2ShapeChainPosition(0, $edgeId);
		}

		public function contains (S2Point $p): bool {
			// For polylines, we check if the point is on any edge
			for ($i = 0; $i < $this->numEdges; $i++) {
				$edge = $this->edges[$i];
				if (S2EdgeUtil::contains($edge->v0, $edge->v1, $p)) {
					return true;
				}
			}

			return false;
		}

		public function getCapBound (): S2Cap {
			return $this->polyline->getCapBound();
		}

		public function getRectBound (): S2LatLngRect {
			return $this->polyline->getRectBound();
		}

		/**
		 * Returns the underlying polyline.
		 */
		public function getPolyline (): S2Polyline {
			return $this->polyline;
		}
	}