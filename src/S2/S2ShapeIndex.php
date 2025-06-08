<?php

	namespace Sjoller\S2Geometry\S2;

	/**
	 * S2ShapeIndex provides efficient spatial indexing for shapes.
	 * It allows fast queries for finding shapes that intersect with cells or points.
	 */
	class S2ShapeIndex implements S2Region {
		/** @var array<int, S2Shape> */
		private array $shapes = [];

		/** @var array<int, S2ShapeIndexCell> */
		private array $cells = [];

		/**
		 * Creates a new shape index.
		 */
		public function __construct () {
		}

		/**
		 * Adds a shape to the index.
		 * Returns the shape ID assigned to the shape.
		 */
		public function add (S2Shape $shape): int {
			$shapeId = count($this->shapes);
			$this->shapes[$shapeId] = $shape;
			$this->indexShape($shape, $shapeId);

			return $shapeId;
		}

		/**
		 * Returns the number of shapes in the index.
		 */
		public function numShapes (): int {
			return count($this->shapes);
		}

		/**
		 * Returns the shape with the given ID, or null if not found.
		 */
		public function shape (int $shapeId): ?S2Shape {
			return $this->shapes[$shapeId] ?? null;
		}

		/**
		 * Returns the cell for the given cell ID, or null if not found.
		 */
		public function cell (S2CellId $cellId): ?S2ShapeIndexCell {
			return $this->cells[$cellId->id()] ?? null;
		}

		/**
		 * Returns all shapes that intersect with the given cell.
		 *
		 * @return S2Shape[]
		 */
		public function getShapesForCell (S2CellId $cellId): array {
			$cell = $this->cell($cellId);
			if ($cell === null) {
				return [];
			}

			$shapes = [];
			foreach ($cell->clippedShapes() as $clippedShape) {
				$shape = $this->shape($clippedShape->shapeId());
				if ($shape !== null) {
					$shapes[] = $shape;
				}
			}

			return $shapes;
		}

		/**
		 * Returns all shapes that contain the given point.
		 *
		 * @return S2Shape[]
		 */
		public function getShapesForPoint (S2Point $point): array {
			$cellId = S2CellId::fromPoint($point);
			$shapes = $this->getShapesForCell($cellId);

			return array_filter($shapes, function ($shape) use ($point) {
				return $shape->contains($point);
			});
		}

		/**
		 * Clears all shapes from the index.
		 */
		public function clear (): void {
			$this->shapes = [];
			$this->cells = [];
		}

		/**
		 * Indexes a shape by adding it to all cells that it intersects.
		 */
		private function indexShape (S2Shape $shape, int $shapeId): void {
			// Get the bounding cap of the shape
			$cap = $shape->getCapBound();

			// Find all cells that intersect with the cap
			$coverer = new S2RegionCoverer();
			$coverer->setMaxCells(100); // Limit the number of cells for performance
			$covering = new S2CellUnion();
			$coverer->getCovering($cap, $covering);

			// Add the shape to each cell
			foreach ($covering->cellIds() as $cellId) {
				if (!isset($this->cells[$cellId->id()])) {
					$this->cells[$cellId->id()] = new S2ShapeIndexCell();
				}
				$this->cells[$cellId->id()]->addShape($shapeId, $shape);
			}
		}

		/**
		 * Returns true if the index contains the given point.
		 */
		public function contains (S2Point $p): bool {
			$shapes = $this->getShapesForPoint($p);

			return !empty($shapes);
		}

		/**
		 * Returns true if the index may intersect the given cell.
		 */
		public function mayIntersect (S2Cell $cell): bool {
			$shapes = $this->getShapesForCell($cell->id());

			return !empty($shapes);
		}

		/**
		 * Returns a bounding cap for the index.
		 */
		public function getCapBound (): S2Cap {
			if (empty($this->shapes)) {
				return S2Cap::empty();
			}

			// Start with a cap containing the first shape
			$cap = $this->shapes[0]->getCapBound();

			// Expand the cap to include all other shapes
			for ($i = 1; $i < count($this->shapes); $i++) {
				$cap = $cap->union($this->shapes[$i]->getCapBound());
			}

			return $cap;
		}

		/**
		 * Returns a bounding rectangle for the index.
		 */
		public function getRectBound (): S2LatLngRect {
			if (empty($this->shapes)) {
				return S2LatLngRect::empty();
			}

			// Start with a rectangle containing the first shape
			$rect = $this->shapes[0]->getRectBound();

			// Expand the rectangle to include all other shapes
			for ($i = 1; $i < count($this->shapes); $i++) {
				$rect = $rect->union($this->shapes[$i]->getRectBound());
			}

			return $rect;
		}
	}

