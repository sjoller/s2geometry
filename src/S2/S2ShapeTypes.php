<?php

	namespace Sjoller\S2Geometry\S2;

	/**
	 * An edge, consisting of two vertices "v0" and "v1".
	 * Zero-length edges are allowed, and can be used to represent points.
	 */
	class S2ShapeTypes {
		public S2Point $v0;
		public S2Point $v1;

		public function __construct (S2Point $v0, S2Point $v1) {
			$this->v0 = $v0;
			$this->v1 = $v1;
		}

		public function equals (S2ShapeTypes $other): bool {
			return $this->v0->equals($other->v0) && $this->v1->equals($other->v1);
		}
	}

