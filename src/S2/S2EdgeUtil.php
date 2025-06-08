<?php

	namespace Sjoller\S2Geometry\S2;

	/**
	 * S2EdgeUtil provides utility functions for working with edges.
	 */
	class S2EdgeUtil {
		/**
		 * Returns true if the edge AB intersects the edge CD.
		 */
		public static function intersects (S2Point $a, S2Point $b, S2Point $c, S2Point $d): bool {
			// Check if any of the endpoints are equal
			if ($a->equals($c) || $a->equals($d) || $b->equals($c) || $b->equals($d)) {
				return false;
			}

			// Check if the edges cross
			$cross1 = $a->cross($b);
			$cross2 = $c->cross($d);
			$dot1 = $cross1->dot($c);
			$dot2 = $cross1->dot($d);
			$dot3 = $cross2->dot($a);
			$dot4 = $cross2->dot($b);

			// If the edges cross, the signs of the cross products will be different
			return ($dot1 * $dot2 < 0) && ($dot3 * $dot4 < 0);
		}

		/**
		 * Returns true if the edge AB contains the point X.
		 */
		public static function contains (S2Point $a, S2Point $b, S2Point $x): bool {
			// Check if X is equal to either endpoint
			if ($x->equals($a) || $x->equals($b)) {
				return true;
			}

			// Check if X is on the great circle through A and B
			$cross = $a->cross($b)->dot($x);
			if (abs($cross) > 1e-14) {
				return false;
			}

			// Check if X is between A and B
			$dot = $a->dot($x);
			$dotAB = $a->dot($b);

			return $dot >= $dotAB && $dot <= 1;
		}

		/**
		 * Returns the closest point on the edge AB to the point X.
		 */
		public static function getClosestPoint (S2Point $a, S2Point $b, S2Point $x): S2Point {
			// If X is equal to either endpoint, return that endpoint
			if ($x->equals($a)) {
				return $a;
			}
			if ($x->equals($b)) {
				return $b;
			}

			// Project X onto the great circle through A and B
			$normal = $a->cross($b)->normalize();
			$xProjected = $x->add($normal->mul($normal->dot($x))->neg());

			// If the projection is on the edge, return it
			if (self::contains($a, $b, $xProjected)) {
				return $xProjected->normalize();
			}

			// Otherwise, return the closest endpoint
			$distA = $x->distance($a);
			$distB = $x->distance($b);

			return $distA <= $distB ? $a : $b;
		}

		/**
		 * Returns the distance from the point X to the edge AB.
		 */
		public static function distance (S2Point $a, S2Point $b, S2Point $x): S1Angle {
			$closest = self::getClosestPoint($a, $b, $x);

			return S1Angle::fromRadians($x->distance($closest));
		}

		/**
		 * Returns true if the edges AB and CD are approximately parallel.
		 */
		public static function isParallel (S2Point $a, S2Point $b, S2Point $c, S2Point $d, float $tolerance = 1e-14): bool {
			$normal1 = $a->cross($b)->normalize();
			$normal2 = $c->cross($d)->normalize();

			return abs($normal1->dot($normal2)) > 1 - $tolerance;
		}

		/**
		 * Returns the intersection point of the edges AB and CD, or null if they don't intersect.
		 */
		public static function getIntersection (S2Point $a, S2Point $b, S2Point $c, S2Point $d): ?S2Point {
			if (!self::intersects($a, $b, $c, $d)) {
				return null;
			}

			// Compute the intersection point
			$normal1 = $a->cross($b)->normalize();
			$normal2 = $c->cross($d)->normalize();
			$intersection = $normal1->cross($normal2)->normalize();

			// Ensure the intersection point is on the correct side of the sphere
			if ($intersection->dot($a) < 0) {
				$intersection = $intersection->neg();
			}

			return $intersection;
		}

		/**
		 * Returns true if the edges AB and CD are approximately collinear.
		 */
		public static function isCollinear (S2Point $a, S2Point $b, S2Point $c, S2Point $d, float $tolerance = 1e-14): bool {
			$normal1 = $a->cross($b)->normalize();
			$normal2 = $c->cross($d)->normalize();

			return abs($normal1->dot($normal2)) < $tolerance;
		}
	}