<?php

	namespace Sjoller\S2Geometry\S2;

	/**
	 * S2ShapeIndexCell represents the set of shapes that intersect a cell.
	 */
	class S2ShapeIndexCell {
		/** @var array<int, S2ClippedShape> */
		private array $clippedShapes = [];

		/**
		 * Adds a shape to this cell.
		 */
		public function addShape (int $shapeId, S2Shape $shape): void {
			$this->clippedShapes[$shapeId] = new S2ClippedShape($shapeId, $shape);
		}

		/**
		 * Returns all clipped shapes in this cell.
		 *
		 * @return S2ClippedShape[]
		 */
		public function clippedShapes (): array {
			return $this->clippedShapes;
		}

		/**
		 * Returns the number of clipped shapes in this cell.
		 */
		public function numClipped (): int {
			return count($this->clippedShapes);
		}
	}