/**
 * Approximate distance in kilometers between two lat/lng points.
 */
export function haversineDistanceKm(lat1, lng1, lat2, lng2) {
    const toRad = (deg) => (deg * Math.PI) / 180;
    const R = 6371;
    const dLat = toRad(lat2 - lat1);
    const dLng = toRad(lng2 - lng1);
    const a =
        Math.sin(dLat / 2) ** 2 +
        Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLng / 2) ** 2;
    return 2 * R * Math.asin(Math.sqrt(a));
}

/**
 * Given a coordinate and a { zoneName: [lat, lng] } map, returns the
 * name of the closest zone. Used to auto-pick a zone from a customer's
 * live location or a manual map click.
 */
export function nearestZone(lat, lng, zoneCenters) {
    let closest = null;
    let closestDistance = Infinity;

    for (const [zone, [zoneLat, zoneLng]] of Object.entries(zoneCenters || {})) {
        const distance = haversineDistanceKm(lat, lng, zoneLat, zoneLng);
        if (distance < closestDistance) {
            closestDistance = distance;
            closest = zone;
        }
    }

    return closest;
}

/**
 * Whether a coordinate falls within the utility's configured service
 * area bounds ({ min_lat, max_lat, min_lng, max_lng }).
 */
export function withinBounds(lat, lng, bounds) {
    return (
        lat >= bounds.min_lat &&
        lat <= bounds.max_lat &&
        lng >= bounds.min_lng &&
        lng <= bounds.max_lng
    );
}
