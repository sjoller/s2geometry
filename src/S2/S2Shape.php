<?php

	namespace Sjoller\S2Geometry\S2;

	use InvalidArgumentException;

	/**
	 * S2Shape is an interface for shapes that can be indexed by S2ShapeIndex.
	 * It represents a collection of edges that optionally defines an interior.
	 */
	interface S2Shape {
		/**
		 * Returns the number of edges in this shape.
		 * Edges have ids ranging from 0 to num_edges() - 1.
		 */
		public function numEdges (): int;

		/**
		 * Returns the endpoints of the given edge id.
		 *
		 * @throws InvalidArgumentException if edge_id is out of range
		 */
		public function edge (int $edgeId): S2ShapeTypes;

		/**
		 * Returns the dimension of the geometry represented by this shape:
		 *   0 - Point geometry. Each point is represented as a degenerate edge.
		 *   1 - Polyline geometry. Polyline edges may be degenerate.
		 *   2 - Polygon geometry. Edges should be oriented such that the polygon
		 *       interior is always on the left.
		 */
		public function dimension (): int;

		/**
		 * Returns the number of contiguous edge chains in the shape.
		 * For example, a shape whose edges are [AB, BC, CD, AE, EF] would consist
		 * of two chains (AB,BC,CD and AE,EF).
		 */
		public function numChains (): int;

		/**
		 * Returns the range of edge ids corresponding to the given chain.
		 *
		 * @throws InvalidArgumentException if chain_id is out of range
		 */
		public function chain (int $chainId): S2ShapeChain;

		/**
		 * Returns the position of the given edge within its chain.
		 *
		 * @throws InvalidArgumentException if edge_id is out of range
		 */
		public function chainPosition (int $edgeId): S2ShapeChainPosition;

		/**
		 * Returns true if the shape contains the given point.
		 */
		public function contains (S2Point $p): bool;

		/**
		 * Returns a bounding cap for the shape.
		 */
		public function getCapBound (): S2Cap;

		/**
		 * Returns a bounding rectangle for the shape.
		 */
		public function getRectBound (): S2LatLngRect;
	}