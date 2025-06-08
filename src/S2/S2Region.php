<?php

	namespace Sjoller\S2Geometry\S2;

	/**
	 * S2Region defines an interface for all regions on the sphere.
	 */
	interface S2Region {
		/**
		 * Returns a bounding cap that contains the region.
		 */
		public function getCapBound (): S2Cap;

		/**
		 * Returns a bounding rectangle that contains the region.
		 */
		public function getRectBound (): S2LatLngRect;

		/**
		 * Returns true if the region contains the given point.
		 */
		public function contains (S2Point $p): bool;

		/**
		 * Returns true if the region may intersect the given cell.
		 */
		public function mayIntersect (S2Cell $cell): bool;
	}