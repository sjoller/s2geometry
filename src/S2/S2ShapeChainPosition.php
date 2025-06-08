<?php

	namespace Sjoller\S2Geometry\S2;

	/**
	 * The position of an edge within a given edge chain,
	 * specified as a (chain_id, offset) pair.
	 */
	class S2ShapeChainPosition {
		public int $chainId;
		public int $offset;

		public function __construct (int $chainId, int $offset) {
			$this->chainId = $chainId;
			$this->offset = $offset;
		}
	}