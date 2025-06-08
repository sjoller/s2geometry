<?php

	namespace Sjoller\S2Geometry\S2;

	/**
	 * S2LatLng represents a point on the sphere using latitude and longitude coordinates.
	 */
class S2LatLng {
		private S1Angle $lat;
		private S1Angle $lng;

    /**
		 * Creates a new S2LatLng with the given latitude and longitude.
     */
		public function __construct (S1Angle $lat, S1Angle $lng) {
			$this->lat = $lat;
			$this->lng = $lng;
		}

		/**
		 * Creates a new S2LatLng from radians.
		 */
		public static function fromRadians (float $latRadians, float $lngRadians): self {
			return new self(
				S1Angle::fromRadians($latRadians),
				S1Angle::fromRadians($lngRadians)
			);
    }

		/**
		 * Creates a new S2LatLng from degrees.
		 */
		public static function fromDegrees (float $latDegrees, float $lngDegrees): self {
			return new self(
				S1Angle::fromDegrees($latDegrees),
				S1Angle::fromDegrees($lngDegrees)
			);
    }

    /**
		 * Creates a new S2LatLng from an S2Point.
     */
		public static function fromPoint (S2Point $p): self {
			$lat = asin(max(-1.0, min(1.0, $p->getZ())));
			$lng = atan2($p->getY(), $p->getX());

			return self::fromRadians($lat, $lng);
    }

		/**
		 * Returns the latitude.
		 */
		public function lat (): S1Angle {
			return $this->lat;
    }

		/**
		 * Returns the longitude.
		 */
		public function lng (): S1Angle {
			return $this->lng;
    }

		/**
		 * Returns the latitude in radians.
		 */
		public function latRadians (): float {
			return $this->lat->radians();
    }

		/**
		 * Returns the longitude in radians.
		 */
		public function lngRadians (): float {
			return $this->lng->radians();
    }

		/**
		 * Returns the latitude in degrees.
		 */
		public function latDegrees (): float {
			return $this->lat->degrees();
    }

		/**
		 * Returns the longitude in degrees.
		 */
		public function lngDegrees (): float {
			return $this->lng->degrees();
    }

    /**
		 * Returns true if this point is valid.
		 */
		public function isValid (): bool {
			return abs($this->latRadians()) <= M_PI_2 && abs($this->lngRadians()) <= M_PI;
		}

		/**
		 * Returns true if this point is normalized.
		 */
		public function isNormalized (): bool {
			return $this->latRadians() >= -M_PI_2 && $this->latRadians() <= M_PI_2
				&& $this->lngRadians() >= -M_PI && $this->lngRadians() <= M_PI;
		}

		/**
		 * Returns a normalized version of this point.
		 */
		public function normalize (): self {
			$lat = $this->latRadians();
			$lng = $this->lngRadians();

			// Normalize latitude
			if ($lat < -M_PI_2) {
				$lat = -M_PI_2;
    }
			else {
				if ($lat > M_PI_2) {
					$lat = M_PI_2;
				}
			}

			// Normalize longitude
			$lng = fmod($lng + M_PI, 2 * M_PI) - M_PI;

			return self::fromRadians($lat, $lng);
		}

		/**
		 * Returns the distance between this point and another point.
		 */
		public function distance (S2LatLng $other): S1Angle {
			$p1 = $this->toPoint();
			$p2 = $other->toPoint();
			return S1Angle::fromRadians($p1->distance($p2));
		}

    /**
		 * Returns the S2Point corresponding to this latitude/longitude.
		 */
		public function toPoint (): S2Point {
			$phi = $this->latRadians();
			$theta = $this->lngRadians();
			$cosphi = cos($phi);

			return new S2Point(
				cos($theta) * $cosphi,
				sin($theta) * $cosphi,
				sin($phi)
			);
    }

		/**
		 * Returns a string representation of this point.
    */
		public function __toString (): string {
        return "(" . $this->latDegrees() . ", " . $this->lngDegrees() . ")";
    }
	}