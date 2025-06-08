<?php

	namespace Sjoller\S2Geometry\S2;

	/**
	 * A range of edge ids corresponding to a chain of zero or more connected edges,
	 * specified as a (start, length) pair.
	 */
	class S2ShapeChain {
		public int $start;
		public int $length;

		public function __construct (int $start, int $length) {
			$this->start = $start;
			$this->length = $length;
		}
	}