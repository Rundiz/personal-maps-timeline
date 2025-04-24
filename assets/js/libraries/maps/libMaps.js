/**
 * Maps driver.
 * 
 * This is for easier to change API like Leaflet to Google Maps or other.
 * 
 * This file class use Leaflet.
 */


class LibMaps {


    /**
     * @type {object} Leaflet map object.
     */
    #map = {};


    /**
     * @type {object} Timeline items such as marker, polyline path.
     */
    #timelineItems = {};


    /**
     * @type {object} Leaflet layer group object.
     */
    #timelineLayerGroup;


    /**
     * Leaflet as LibMaps class constructor.
     */
    constructor() {
        this.#listenClickYearVisited();
    }// constructor


    /**
     * Draw timeline paths.
     * 
     * @private This method was called from `drawTimelineData()`.
     * @param {array} timelinePaths 
     * @param {array} timelinePathsTimes 
     * @param {array} timelinePathsSegmentsMatchData 
     */
    #drawTimelinePaths(timelinePaths, timelinePathsTimes, timelinePathsSegmentsMatchData) {
        const defaultPathStyle = {
            color: '#3388ff',
            weight: 3,
        };
        const highlightPathStyle = {
            color: '#ff5555',
            weight: 5,
        }

        timelinePaths.forEach((eachPathSet, indexPathSet) => {
        const polyline = L.polyline(eachPathSet, defaultPathStyle);
        const oldestDate = new Date(Math.min(...timelinePathsTimes[indexPathSet]));
        const newestDate = new Date(Math.max(...timelinePathsTimes[indexPathSet]));
        polyline.bindPopup(
            '<p><strong>Travel</strong></p>'
            + 'On ' + Utils.formatDate(oldestDate)
            + ' '
            + Utils.formatTimeHM(oldestDate)
            + ' - '
            + Utils.formatDate(newestDate)
            + ' '
            + Utils.formatTimeHM(newestDate)
        );
        polyline.on('popupopen', () => {
            polyline.setStyle(highlightPathStyle);
        });
        polyline.on('popupclose', () => {
            polyline.setStyle(defaultPathStyle);
        });

        // set marker at the start of path.
        const pathSetMarkerStartDate = new Date(timelinePathsTimes[indexPathSet][0]);
        const pathSetMarkerStart = L.circleMarker(eachPathSet[0], {
            fillOpacity: 1,
            radius: 5,
        })
        .bindPopup(
            '<p><strong>Start travel</strong></p>'
            + 'On ' + Utils.formatDate(pathSetMarkerStartDate) + ' ' + Utils.formatTimeHM(pathSetMarkerStartDate)
        );

        const layer = this.#timelineLayerGroup.addLayer(polyline)
        .addLayer(pathSetMarkerStart);
        this.#timelineItems[timelinePathsSegmentsMatchData[indexPathSet]] = polyline;
    });// end iteration of paths.
    }// #drawTimelinePaths


    /**
     * Draw visit marker.
     * 
     * @private This method was called from `drawTimelineData()`.
     * @param {object} item 
     * @param {number} index 
     */
    #drawVisitMarker(item, index) {
        const markerIcon = L.divIcon({
            className: 'fa fa-solid fa-location-dot fa-2xl',
            iconSize: [18, 26],
            popupAnchor: [0, -21],
        });
        const markerIconHighlighted = L.divIcon({
            className: 'fa fa-solid fa-location-dot fa-2xl pmtl-marker-highlighted',
            iconSize: [18, 26],
            popupAnchor: [0, -21],
        });
        const defaultMarkerStyle = {
            icon: markerIcon,
        };

        const latLngArray = MapsUtil.convertLatLngString(item.visit.topCandidate_placeLocation_latLng);
        let startTime = '';
        let endTime = '';
        if (item?.startTime) {
            const startDate = new Date(item.startTime);
            startTime = Utils.formatDate(startDate) + ' ' + Utils.formatTimeHM(startDate);
        }
        if (item.endTime) {
            const endDate = new Date(item.endTime);
            endTime = Utils.formatDate(endDate) + ' ' + Utils.formatTimeHM(endDate);
        }
        const googleMapsURL = MapsUtil.buildGoogleMapsSearchURL(latLngArray.join(','), item?.visit?.topCandidate_placeId);
        const googleMapsURLNoPlaceId = MapsUtil.buildGoogleMapsSearchURL(latLngArray.join(','));
        const marker = L.marker(latLngArray, defaultMarkerStyle)
        .bindPopup(
            '<p data-segment-id="' + item.id + '-' + String(index) + '">'
            + '<strong class="place-title-placement place-id-' + item?.visit?.topCandidate_placeId + '">' + (item?.visit?.place_name ?? item.visit.topCandidate_placeLocation_latLng) + '</strong>'
            + this.#getEditPlaceNameHTML(item?.visit?.topCandidate_placeId)
            + (startTime !== '' ? 
                '<br>On ' + startTime 
                    + (endTime !== '' ? ' - ' + endTime : '')
                : 
                ''
            )
            + '</p>'
            + '<div>'
            + '<small><a href="' + googleMapsURL + '" target="googlemaps">View on Google Maps</a></small>'
            + ' <small><a href="' + googleMapsURLNoPlaceId + '" target="googlemaps" title="View by latitude, longitude only"><i class="fa-solid fa-map-pin"></i></a></small>'
            + '</div>'
        );
        marker.on('popupopen', () => {
            marker.setIcon(markerIconHighlighted);
            marker.setZIndexOffset(1);
        });
        marker.on('popupclose', () => {
            marker.setIcon(markerIcon);
            marker.setZIndexOffset(0);
        });

        this.#timelineLayerGroup.addLayer(marker);
        this.#timelineItems[item.id + '-' + String(index)] = marker;
    }// #drawVisitMarker


    /**
     * Fire default maps (including sattellite view) was loaded.
     * 
     * This will be fire once even user switch from map view to sattellite view.
     * 
     * @private This method was called from `setupDefaultMap()`.
     */
    #fireEventDefaultMapLoaded() {
        if (true === defaultMapsLoaded) {
            return null;
        }

        const pmtlMap = document.getElementById('pmtl-map');
        pmtlMap.classList.remove('pmtl-is-loading');
        // @link https://stackoverflow.com/a/56695852/128761 remove loading text.
        pmtlMap.childNodes.forEach(c => c.nodeType === Node.TEXT_NODE && c.remove());
        // mark default maps was loaded. so it will be ready to work on timeline detail for each day.
        defaultMapsLoaded = true;

        const event = new Event('pmtl.default.maps.loaded');
        window.dispatchEvent(event);
        document.dispatchEvent(event);
    }// #fireEventDefaultMapLoaded


    /**
     * Get edit place name HTML.
     * 
     * @param {String} placeId
     * @returns {String}
     */
    #getEditPlaceNameHTML(placeId) {
        if (typeof(placeId) !== 'string' || '' === placeId.trim()) {
            return '';
        }

        return ' <a class="pmtl-edit-placename" title="Edit place name" data-place-id="' + placeId + '" data-bs-toggle="modal" data-bs-target="#pmtl-bs-modal"><i class="fa-solid fa-pen"></i></a>';
    }// #getEditPlaceNameHTML


    /**
     * Listen click on year visited.
     * 
     * @private This method was called from `constructor()`.
     */
    #listenClickYearVisited() {
        document.addEventListener('click', (event) => {
            let thisTarget = event.target;
            if (thisTarget.closest('a')) {
                thisTarget = thisTarget.closest('a');
            }

            if (thisTarget.classList.contains('marker-popup-year-visited')) {
                event.preventDefault();
                const lastVisitDate = thisTarget.dataset.lastVisitDate;
                if (!lastVisitDate) {
                    return ;
                }
                const lvdDate = new Date(lastVisitDate);

                // set input date value.
                const inputDate = document.getElementById('pmtl-timeline-control-date-input');
                inputDate.value = Utils.formatDate(lvdDate);

                // trigger open timeline panel.
                const selectDateMenuLink = document.getElementById('pmtl-open-timeline-panel');
                if (!selectDateMenuLink.classList.contains('active')) {
                    // if not opened.
                    selectDateMenuLink.dispatchEvent(new Event('click'));
                } else {
                    // if already opened.
                    const kEvent = new KeyboardEvent('keydown', {
                        bubbles: true,
                        code: 'Enter',
                        key: 'Enter',
                    });
                    inputDate.dispatchEvent(kEvent);
                }
            }
        });
    }// #listenClickYearVisited


    /**
     * Listen map popup open.
     * 
     * @private This method was called from `setupDefaultMap()`.
     */
    #listenMapPopupOpen() {
        this.#map.addEventListener('popupopen', (event) => {
            const popupLatLng = event.popup?.getLatLng();
            if (popupLatLng && popupLatLng.lat && popupLatLng.lng) {
                const additionalContentPlaceholder = event.popup?.getElement()?.querySelector('.additional-content-placeholder');
                if (additionalContentPlaceholder) {
                    additionalContentPlaceholder.innerHTML = '';
                }

                Ajax.fetchGet('HTTP/summary-visit-details.php?lat=' + encodeURIComponent(popupLatLng.lat) + '&lng=' + encodeURIComponent(popupLatLng.lng))
                .then((response) => {
                    if (response.visitedPlace?.history?.items) {
                        let visitedPlaceHTML = '<h6 class="m-0">Years visited</h6>';
                        response.visitedPlace?.history?.items?.forEach((item) => {
                            if (item?.visitYear) {
                                visitedPlaceHTML += '<a class="marker-popup-year-visited" title="Latest date on this year: ' + item.startTime + '" data-last-visit-date="' + item.startTime + '">' + item.visitYear + '</a><br>';
                            }
                        });
                        additionalContentPlaceholder?.insertAdjacentHTML('beforeend', visitedPlaceHTML);
                    }
                });
            }
        });
    }// #listenMapPopupOpen


    /**
     * Clear map layers and mark load selected date to `false`.
     * 
     * @param {boolean} unmarkLoadSelectedDate Set to `true` to unmark `loadSelectedDate` variable. Set to `false` to untouch.
     */
    clearMapLayers(unmarkLoadSelectedDate = true) {
        for (const [key, item] of Object.entries(this.#timelineItems)) {
            item.closePopup();
            item.remove();
        }

        if (typeof(this.#timelineLayerGroup) === 'object' && this.#timelineLayerGroup !== null) {
            this.#timelineLayerGroup.clearLayers();
            this.#timelineLayerGroup = null;
        }

        this.#timelineItems = {};
        if (true === unmarkLoadSelectedDate) {
            loadSelectedDate = false;
        }
        this.#map.invalidateSize(true);
    }// clearMapLayers


    /**
     * Draw timeline data on the map.
     * 
     * @param {object} dbResult 
     */
    drawTimelineData(dbResult) {
        if (typeof(this.#timelineLayerGroup) === 'object' && this.#timelineLayerGroup !== null) {
            // if there is layergroup object.
            // just clear layers to remove previous loaded date.
            this.clearMapLayers(false);
        }

        if (dbResult?.result?.items) {
            this.#timelineLayerGroup = L.featureGroup([]);

            let drawn = 0;
            const timelinePaths = [];
            const timelinePathsTimes = [];
            const timelinePathsSegmentsMatchData = [];// for match timline panel item

            dbResult.result.items.forEach((item, index) => {
                if (item.activity) {
                    // if there is activity.
                    // do not prepend activity to timeline paths because it may cause of confustion 
                    // and can be duplicate paths on the same time.
                    // activity is covered by the timeline time between start to end.
                }

                // build timeline paths.
                if (item.timelinepath && Array.isArray(item.timelinepath) && item.timelinepath.length > 0) {
                    timelinePaths.push(item.timelinepath.map((tlp) => {
                        return MapsUtil.convertLatLngString(tlp.point);
                    }));
                    timelinePathsTimes.push(item.timelinepath.map((tlp) => {
                        const tlpDate = new Date(tlp.time);
                        return parseInt(tlpDate.getTime());
                    }));
                    timelinePathsSegmentsMatchData.push(item.id + '-' + String(index));
                }

                if (item.visit) {
                    this.#drawVisitMarker(item, index);
                    ++drawn;
                }// endif; visit property.
            });// end iteration result items (segments).

            if (timelinePaths.length > 0) {
                // if there is timeline paths.
                // draw lines.
                this.#drawTimelinePaths(timelinePaths, timelinePathsTimes, timelinePathsSegmentsMatchData);
                ++drawn;
            }// endif; there is timeline paths.

            // ending, draw them to the map.
            if (drawn > 0) {
                this.#timelineLayerGroup.addTo(this.#map);
                this.#map.invalidateSize(true);
                this.#map.fitBounds(this.#timelineLayerGroup.getBounds());
            }
        }
    }// drawTimelineData


    /**
     * Draw visited places summary by a selected year.
     * 
     * @param {object} visitedPlacesYear 
     */
    drawYearSummary(visitedPlacesYear) {
        if (visitedPlacesYear?.items) {
            this.#timelineLayerGroup = L.featureGroup([]);
            let drawn = 0;

            visitedPlacesYear.items.forEach((item, index) => {
                const latLngArray = MapsUtil.convertLatLngString(item.topCandidate_placeLocation_latLng);
                const googleMapsURL = MapsUtil.buildGoogleMapsSearchURL(latLngArray.join(','), item?.topCandidate_placeId);
                const googleMapsURLNoPlaceId = MapsUtil.buildGoogleMapsSearchURL(latLngArray.join(','));
                const circleMarker = L.circleMarker(latLngArray, {
                    color: '#f5f9ff',
                    fillColor: '#3388ff',
                    fillOpacity: 0.7,
                    radius: 6,
                    stroke: true,
                    weight: 2,
                })
                .bindPopup(
                    '<p>'
                    + '<strong class="place-title-placement place-id-' + item?.topCandidate_placeId + '">' + (item?.place_name ?? item.topCandidate_placeLocation_latLng) + '</strong>'
                    + this.#getEditPlaceNameHTML(item?.topCandidate_placeId)
                    + (item.startTime ? '<br>Latest on ' + item.startTime : '')
                    + '</p>'
                    + '<div class="additional-content-placeholder"></div>'
                    + '<div class="view-on-google-maps-links">'
                    + '<small><a href="' + googleMapsURL + '" target="googlemaps">View on Google Maps</a></small>'
                    + ' <small><a href="' + googleMapsURLNoPlaceId + '" target="googlemaps" title="View by latitude, longitude only"><i class="fa-solid fa-map-pin"></i></a></small>'
                    + '</div>',
                    {
                        className: 'map-marker-popup',
                    }
                );
                ++drawn;
                this.#timelineLayerGroup.addLayer(circleMarker);
                this.#timelineItems[item.id + '-' + String(index)] = circleMarker;
            });

            // ending, draw them to the map.
            if (drawn > 0) {
                this.#timelineLayerGroup.addTo(this.#map);
                this.#map.invalidateSize(true);
                this.#map.fitBounds(this.#timelineLayerGroup.getBounds());
            }
        }
    }// drawYearSummary


    /**
     * Open map popups.
     * 
     * @param {string} segment_id 
     */
    openMapPopup(segment_id) {
        if (typeof(segment_id) !== 'string') {
            throw new Error('The argument `segment_id` must be string.');
        }

        if (this.#timelineItems[segment_id]) {
            this.#timelineItems[segment_id].fire('click');
            this.#map.invalidateSize(true);
            const itemBounds = this.#timelineItems[segment_id].getBounds?.();

            if (itemBounds) {
                this.#map.flyToBounds(itemBounds);
            } else {
                // if it is possible this is marker (has no `getBounds()`).
                const thisMarker = this.#timelineItems[segment_id].getLatLng?.();
                if (thisMarker) {
                    this.#map.flyTo([thisMarker.lat, thisMarker.lng]);
                }
            }
        }
    }// openMapPopup


    /**
     * Setup default map.
     * 
     * @param {object} summaryVisitedPlaces The ajax result of summary visited places. This object must contain `items` property to display markers.
     * @returns null|void
     */
    setupDefaultMap(summaryVisitedPlaces = {}) {
        if (true === defaultMapsLoaded) {
            return null;
        }

        let mapZoom = 5;// default zoom for small screen.
        if (window.innerWidth >= 500 && window.innerHeight >= 600) {
            mapZoom = 6;
        }
        // 13.351245, 101.466092 is look like the center of Thailand in views.
        const defaultMapCenter = [13.351245, 101.466092];

        // set map layers. ----------------------------------------------
        const mapLayer = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="http://www.openstreetmap.org/copyright" target="osm">OpenStreetMap</a>'
        });
        mapLayer.on('load', () => {
            this.#fireEventDefaultMapLoaded();
        });

        const sattelliteLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            attribution: '&copy; <a href="https://www.esri.com/" target="esri">Esri</a>'
        });
        sattelliteLayer.on('load', () => {
            this.#fireEventDefaultMapLoaded();
        });

        const baseLayer = {
            'OpenStreetMap': mapLayer,
            'Sattellite view': sattelliteLayer,
        };
        const overlayLayer = {};
        // end set map layers. ------------------------------------------

        this.#map = L.map('pmtl-map', {
            preferCanvas: true,// @link https://stackoverflow.com/a/43019740/128761 Make a lot of marker load faster.
            layers: [mapLayer],
            zoomControl: false,// disable default zoom control (on top left)
        })
        .setView(defaultMapCenter, mapZoom);
        L.control.zoom({
            position: 'topright',
        }).addTo(this.#map);
        L.control.layers(baseLayer, overlayLayer, {
            sortLayers: true,
        }).addTo(this.#map);

        // display summary visited places. ------------------------------
        if (summaryVisitedPlaces?.items) {
            summaryVisitedPlaces.items.forEach((item) => {
                const latLngArray = MapsUtil.convertLatLngString(item.topCandidate_placeLocation_latLng);
                const googleMapsURL = MapsUtil.buildGoogleMapsSearchURL(latLngArray.join(','), item?.topCandidate_placeId);
                const googleMapsURLNoPlaceId = MapsUtil.buildGoogleMapsSearchURL(latLngArray.join(','));
                const circleMarker = L.circleMarker(latLngArray, {
                    color: '#f5f9ff',
                    fillColor: '#999999',
                    fillOpacity: 0.7,
                    radius: 4,
                    stroke: true,
                    weight: 2,
                })
                .bindPopup(
                    '<p>'
                    + '<strong class="place-title-placement place-id-' + item?.topCandidate_placeId + '">' + (item?.place_name ?? item.topCandidate_placeLocation_latLng) + '</strong>'
                    + this.#getEditPlaceNameHTML(item?.topCandidate_placeId)
                    + (item.startTime ? '<br>Latest on ' + item.startTime : '')
                    + '</p>'
                    + '<div class="additional-content-placeholder"></div>'
                    + '<div class="view-on-google-maps-links">'
                    + '<small><a href="' + googleMapsURL + '" target="googlemaps">View on Google Maps</a></small>'
                    + ' <small><a href="' + googleMapsURLNoPlaceId + '" target="googlemaps" title="View by latitude, longitude only"><i class="fa-solid fa-map-pin"></i></a></small>'
                    + '</div>',
                    {
                        className: 'map-marker-popup',
                    }
                )
                .addTo(this.#map);
            });
        }// endif;
        // end display summary visited places. --------------------------

        this.#listenMapPopupOpen();
    }// setupDefaultMap


}