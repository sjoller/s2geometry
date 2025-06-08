<?php

	namespace Sjoller\S2Geometry\S2;

	/**
	 * S2ClippedShape represents a shape that has been clipped to a cell.
	 */
	class S2ClippedShape {
		private int $shapeId;
		private S2Shape $shape;

		public function __construct (int $shapeId, S2Shape $shape) {
			$this->shapeId = $shapeId;
			$this->shape = $shape;
		}

		/**
		 * Returns the ID of the shape.
		 */
		public function shapeId (): int {
			return $this->shapeId;
		}

		/**
		 * Returns the shape.
		 */
		public function shape (): S2Shape {
			return $this->shape;
		}
	}