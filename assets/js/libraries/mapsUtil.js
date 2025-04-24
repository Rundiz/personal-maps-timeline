/**
 * Maps utilities.
 */


class MapsUtil {


    /**
     * @type {string} Google Maps search URL based.
     */
    #googleMapsSearchURLBased = 'https://www.google.com/maps/search/?api=1';


    /**
     * Build Google Maps search URL.
     * 
     * @link https://developers.google.com/maps/documentation/urls/get-started Google Maps URL structure document.
     * @param {string} query The search query. It can be latitude, longitude.
     * @param {string} query_place_id The Google Maps's place ID. (optional.)
     */
    static buildGoogleMapsSearchURL(query, query_place_id) {
        const thisClass = new this();
        let URL = thisClass.#googleMapsSearchURLBased
        if (typeof(query) === 'string' && query !== '') {
            URL += '&query=' + encodeURIComponent(query);
        } else {
            throw new Error('The argument `query` must be string and not empty.');
        }

        if (typeof(query_place_id) === 'string' && query_place_id !== '') {
            URL += '&query_place_id=' + encodeURIComponent(query_place_id);
        }

        return URL;
    }// buildGoogleMapsSearchURL


    /**
     * Convert latitude, longitude string to array and strip non number.
     * 
     * @param {string} latLng Latitude and longitude string. Example: `13.351245°, 101.466092°`.
     * @param {string} separator Separator for separate lat, long. Default is a space.
     * @returns {Array} Return indexed array where first is latitude, second is longitude.
     */
    static convertLatLngString(latLng, separator = ' ') {
        if (typeof(separator) !== 'string') {
            separator = ' ';
        }

        let latLngArray = latLng.split(separator);
        // remove non number, dash, dot.
        latLngArray[0] = latLngArray[0].replace(/[^\d.-]/g,'');
        latLngArray[1] = latLngArray[1].replace(/[^\d.-]/g,'');
        return latLngArray;
    }// convertLatLngString


}// MapsUtil
