/**
 * Maps driver.
 * 
 * This is for easier to change API like Leaflet to Google Maps or other.
 * 
 * This file class use Leaflet.
 */


class LibMaps {


    /**
     * @type {Index}
     */
    #Index;


    /**
     * @type {L.control.layers} Leaflet layer control from `L.control.layers`.
     */
    #layerControl = {};


    /**
     * @type {L.map} Leaflet map object.
     */
    #map = {};


    /**
     * @type {L.layerGroup} Leaflet layer group for paths traveled from `L.layerGroup()`.
     */
    #pathsTraveledLayerGroup = {};


    /**
     * @type {Object} Summary visited places (that was first load at the beginning of page loaded) as markers.
     */
    #summaryVisitedPlacesItems = {};


    /**
     * @type {Object} Timeline items such as marker, polyline path.
     */
    #timelineItems = {};


    /**
     * @type {Object} Leaflet layer group object.
     */
    #timelineLayerGroup;


    /**
     * Leaflet as LibMaps class constructor.
     * 
     * @param {Index} Index The `Index` class.
     */
    constructor(Index) {
        if (typeof(Index) === 'object') {
            this.#Index = Index;
        }

        this.#listenClickYearVisited();
    }// constructor


    /**
     * @type {String} The "Paths traveled" layer name.
     */
    get #pathsTraveledLayerName() {
        return 'Paths traveled';
    }// #pathsTraveledLayerName


    /**
     * Close "Paths traveled" layer group.
     * 
     * This method was called from `drawTimelineData()`, `#listenMapOverlayPathsTraveledSelected()`.
     * 
     * @link https://stackoverflow.com/a/78865335/128761 Original source code.
     * @returns {undefined}
     */
    #closePathsTraveledLayerGroup() {
        const labels = document.querySelectorAll('.leaflet-control-layers-overlays label');

        for (const label of labels) {
            if (label.textContent.toLowerCase().trim() !== this.#pathsTraveledLayerName.toLowerCase()) {
                continue;
            }

            const input = label.querySelector('input');
            if (input.checked) {
                input.click();
                break;
            }// endif;
        }// endfor;

        this.#pathsTraveledLayerGroup?.clearLayers();
    }// #closePathsTraveledLayerGroup


    /**
     * Draw activity for debugging only.
     * 
     * Do not prepend activity to timeline paths because it may cause of confusion 
     * and can be duplicate paths on the same time.  
     * Activity is covered by the timeline time between start to end.
     * 
     * This code shows that activity is already have paths in the `timelinepath` and `timelinepath` have a lot more details on points.  
     * To make this method work, set `pmtl_debug_activity = true;` in your browser console and open timeline by date.
     * 
     * This method was called from `drawTimelineData()`.
     * 
     * @param {Object} item A single DB result row data that should contains properties such as `activity`.
     * @returns {undefined}
     */
    #drawActivityDebug(item) {
        if (typeof(pmtl_debug_activity) === 'boolean' && pmtl_debug_activity === true) {
            const activityPathStyle = {
                color: 'orange',
                weight: 6,
            };
            const activityHighlightPathStyle = {
                color: '#fc7703',
                weight: 8,
            };
            const activityStartLatLng = MapsUtil.convertLatLngString(item.activity.start_latLng);
            const activityEndLatLng = MapsUtil.convertLatLngString(item.activity.end_latLng);
            console.debug('Drawing activity ' + item.activity.activity_id + '; lat-lng: ' + activityStartLatLng + '-' + activityEndLatLng);

            const polyline = L.polyline([activityStartLatLng, activityEndLatLng], activityPathStyle);
            polyline.bindPopup('activity_id: ' + item.activity.activity_id 
                + '; topCandidate_type: ' + item.activity.topCandidate_type
                + '; lat-lng: ' + item.activity.start_latLng + '-' + item.activity.end_latLng + ' (' + item.activity.distanceMeters + 'M)'
            );
            polyline.on('popupopen', () => {
                polyline.setStyle(activityHighlightPathStyle);
            });
            polyline.on('popupclose', () => {
                polyline.setStyle(activityPathStyle);
            });

            const pathSetMarkerStyle = {
                color: 'orange',
                fillColor: 'orange',
                fillOpacity: 1,
                radius: 8,
            };
            const pathSetMarkerStyleActive = {
                color: '#fc7703',
                fillColor: '#fc7703',
                fillOpacity: 1,
                radius: 10,
            };
            const pathSetMarkerStart = L.circleMarker(activityStartLatLng, pathSetMarkerStyle)
            .bindPopup(
                'activity_id: ' + item.activity.activity_id
            );
            pathSetMarkerStart.on('popupopen', () => {
                pathSetMarkerStart.setStyle(pathSetMarkerStyleActive);
            });
            pathSetMarkerStart.on('popupclose', () => {
                pathSetMarkerStart.setStyle(pathSetMarkerStyle)
            });

            this.#timelineLayerGroup.addLayer(polyline).addLayer(pathSetMarkerStart);
        }// endif;
    }// #drawActivityDebug


    /**
     * Draw paths traveled.
     * 
     * This method was called from `drawPathsTraveled()`.
     * 
     * @param {Object} response AJAX response.
     * @returns {undefined}
     */
    #drawPathsTraveled(response) {
        if (response?.result?.items) {
            // if there are response items from AJAX.
            const defaultPathStyle = {
                color: '#8f9aa8',
                opacity: 0.5,
                weight: 4,
            };
            const highlightPathStyle = {
                color: '#7055c9',
                opacity: 0.6,
                weight: 5,
            }

            for (const eachSegment of response.result.items) {
                if (eachSegment?.timelinepath?.length <= 1) {
                    continue;
                }
                let timelinePaths = [];
                let timelinePathsTimes = [];
                timelinePaths = eachSegment.timelinepath.map((tlp) => {
                    return MapsUtil.convertLatLngString(tlp.point);
                });
                timelinePathsTimes = eachSegment.timelinepath.map((tlp) => {
                    const tlpDate = new Date(tlp.time);
                    return parseInt(tlpDate.getTime());
                });

                const polyline = L.polyline(timelinePaths, defaultPathStyle);
                const oldestDate = new Date(Math.min(...timelinePathsTimes));
                const newestDate = new Date(Math.max(...timelinePathsTimes));
                polyline.bindPopup(
                    '<p><strong>Travel</strong></p>'
                    + 'On <a class="marker-popup-year-visited" data-pmtl-last-visit-date="' + Utils.formatDate(oldestDate) + '">' + Utils.formatDate(oldestDate)
                    + ' '
                    + Utils.formatTimeHM(oldestDate) + '</a>'
                    + ' - '
                    + '<a class="marker-popup-year-visited" data-pmtl-last-visit-date="' + Utils.formatDate(newestDate) + '">' + Utils.formatDate(newestDate)
                    + ' '
                    + Utils.formatTimeHM(newestDate) + '</a>',
                    {
                        className: 'map-marker-popup',
                    }
                );
                polyline.on('popupopen', () => {
                    polyline.setStyle(highlightPathStyle);
                });
                polyline.on('popupclose', () => {
                    polyline.setStyle(defaultPathStyle);
                });

                this.#pathsTraveledLayerGroup.addLayer(polyline);
            }// endfor; loop each semantic segment row.
        }// endif; there is response items from AJAX.
    }// #drawPathsTraveled


    /**
     * Draw summary visited places (all places from the beginning of page load).
     * 
     * This method was called from `setupDefaultMap()`.
     * 
     * @param {Object} summaryVisitedPlaces The AJAX result that must contain `.items` property inside. This property contains the visted places result as array.
     * @returns {undefined}
     */
    #drawSummaryVisitedPlaces(summaryVisitedPlaces) {
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
                    '<p data-pmtl-segment-id="' + item.id + '" data-pmtl-visit_id="' + item.visit_id + '" data-pmtl-place-id="' + item.topCandidate_placeId + '" data-pmtl-latlng="' + item.topCandidate_placeLocation_latLng + '">'
                    + '<strong class="place-title-placement place-id-' + item?.topCandidate_placeId + '">' + (item?.place_name ?? item.topCandidate_placeLocation_latLng) + '</strong>'
                    + this.#getEditPlaceNameHTML(item?.topCandidate_placeId)
                    + (item.startTime ? '<br>Latest on ' + item.startTime : '')
                    + '</p>'
                    + '<div class="additional-content-placeholder"></div>'
                    + this.#getViewOnGoogleMapsLinks(item?.topCandidate_placeLocation_latLng, item?.topCandidate_placeId),
                    {
                        className: 'map-marker-popup',
                    }
                )
                .addTo(this.#map);
                this.#summaryVisitedPlacesItems['summaryVisitedPlace-' + item.topCandidate_placeLocation_latLng] = circleMarker;
            });
        }
    }// #drawSummaryVisitedPlaces


    /**
     * Draw timeline paths.
     * 
     * This method was called from `drawTimelineData()`.
     * 
     * @param {Array} timelinePaths 
     * @param {Array} timelinePathsTimes 
     * @param {Array} timelinePathsSegmentsMatchData 
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
     * Draw visit marker. This marker will be show when user view timeline data base on selected date.
     * 
     * This method was called from `drawTimelineData()`.
     * 
     * @param {Object} item A single DB result row data that should contains properties such as `visit`, `startTime`, etc.
     * @param {Number} index The index number of DB results.
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
            '<p data-pmtl-segment-id="' + item.id + '-' + String(index) + '" data-pmtl-place-id="' + item?.visit?.topCandidate_placeId + '">'
            + '<strong class="place-title-placement place-id-' + item?.visit?.topCandidate_placeId + '">' + (item?.visit?.place_name ?? item.visit.topCandidate_placeLocation_latLng) + '</strong>'
            + this.#getEditPlaceNameHTML(item?.visit?.topCandidate_placeId)
            + (startTime !== '' ? 
                '<br>On ' + startTime 
                    + (endTime !== '' ? ' - ' + endTime : '')
                : 
                ''
            )
            + '</p>'
            + '<div class="additional-content-placeholder"></div>'
            + this.#getViewOnGoogleMapsLinks(item?.visit?.topCandidate_placeLocation_latLng, item?.visit?.topCandidate_placeId),
            {
                className: 'map-marker-popup',
            }
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
     * This method was called from `setupDefaultMap()`.
     */
    #fireEventDefaultMapLoaded() {
        if (true === IndexJSObject.defaultMapsLoaded) {
            return null;
        }

        const pmtlMap = document.getElementById('pmtl-map');
        pmtlMap.classList.remove('pmtl-is-loading');
        // @link https://stackoverflow.com/a/56695852/128761 remove loading text.
        pmtlMap.childNodes.forEach(c => c.nodeType === Node.TEXT_NODE && c.remove());
        // mark default maps was loaded. so it will be ready to work on timeline detail for each day.
        IndexJSObject.defaultMapsLoaded = true;

        const event = new Event('pmtl.default.maps.loaded');
        window.dispatchEvent(event);
        document.dispatchEvent(event);
    }// #fireEventDefaultMapLoaded


    /**
     * Get edit place name HTML link (link with an icon).
     * 
     * This method was called from `drawYearSummary()`, `setupDefaultMap()`, `#drawVisitMarker()`.
     * 
     * @param {String} placeId The place ID from DB.
     * @returns {String} Return HTML link with an icon.
     */
    #getEditPlaceNameHTML(placeId) {
        if (typeof(placeId) !== 'string' || '' === placeId.trim()) {
            return '';
        }

        return ' <a class="pmtl-edit-placename" title="Edit place name" data-pmtl-place-id="' + placeId + '" data-bs-toggle="modal" data-bs-target="#pmtl-bs-modal"><i class="fa-solid fa-pen"></i></a>';
    }// #getEditPlaceNameHTML


    /**
     * Get "View on Google Maps" links where the link is with and without place ID.
     * 
     * This method was called from `drawYearSummary()`, `setupDefaultMap()`, `#drawVisitMarker()`.
     * 
     * @param {String} topCandidate_placeLocation_latLng The data from column `topCandidate_placeLocation_latLng` on DB.
     * @param {String} topCandidate_placeId The data from column `topCandidate_placeId` on DB.
     * @returns {String} Return HTML of view on Google Maps.
     */
    #getViewOnGoogleMapsLinks(topCandidate_placeLocation_latLng, topCandidate_placeId) {
        const latLngArray = MapsUtil.convertLatLngString(topCandidate_placeLocation_latLng);
        const googleMapsURL = MapsUtil.buildGoogleMapsSearchURL(latLngArray.join(','), topCandidate_placeId);
        const googleMapsURLNoPlaceId = MapsUtil.buildGoogleMapsSearchURL(latLngArray.join(','));

        return '<div class="view-on-google-maps-links">'
            + '<small><a href="' + googleMapsURL + '" target="googlemaps">View on Google Maps</a></small>'
            + ' <small><a href="' + googleMapsURLNoPlaceId + '" target="googlemaps" title="View by latitude, longitude only"><i class="fa-solid fa-map-pin"></i></a></small>'
            + '</div>';
    }// #getViewOnGoogleMapsLinks


    /**
     * Listen click on year visited.
     * 
     * This method was called from `constructor()`.
     */
    #listenClickYearVisited() {
        document.addEventListener('click', (event) => {
            let thisTarget = event.target;
            if (thisTarget.closest('a')) {
                thisTarget = thisTarget.closest('a');
            }

            if (thisTarget.classList.contains('marker-popup-year-visited')) {
                event.preventDefault();
                const lastVisitDate = thisTarget.dataset.pmtlLastVisitDate;
                if (!lastVisitDate) {
                    console.warn('There is no last visit date dataset specified.');
                    return ;
                }
                const lvdDate = new Date(lastVisitDate);

                // open timeline panel or change the date based on clicked year visited.
                const selectDateMenuLink = document.getElementById(this.#Index.TimelinePanel.openTimelinePanelLinkId);
                if (!selectDateMenuLink.classList.contains('active')) {
                    // if not opened.
                    this.#Index.TimelinePanel.openTimelinePanel(Utils.formatDate(lvdDate));
                } else {
                    // if already opened.
                    this.#Index.TimelinePanel.changeDateOnTimeline(Utils.formatDate(lvdDate));
                }
            }
        });
    }// #listenClickYearVisited


    /**
     * Listen map overlay "Paths traveled" selected.
     * 
     * This method was called from `setupDefaultMap()`.
     * 
     * @returns {undefined}
     */
    #listenMapOverlayPathsTraveledSelected() {
        const thisClass = this;
        this.#map.addEventListener('overlayadd', (event) => {
            const layerNameLcase = event.name?.toLowerCase();
            if (layerNameLcase.includes(thisClass.#pathsTraveledLayerName.toLowerCase())) {
                // if overlay layer paths traveled in checked.
                if (IndexJSObject.loadSelectedDate !== false) {
                    // if it is currently opening timeline data.
                    // do not work on load paths traveled layer otherwise user PC may crash due to overload.
                    alert('You are currently opening timeline data. Couldn\'t load this layer to prevent slow or crash.');
                    console.log('Opening timeline data. Couldn\'t load this layer to prevent slow or crash. Closing layer.');
                    thisClass.#closePathsTraveledLayerGroup();
                    return ;
                }

                // AJAX get data and display it.
                thisClass.#Index.ajaxGetSummaryPathsTraveled();
            }// endif; overlay layer paths traveled in checked.
        });// end addEventListener 'overlayadd'
    }// #listenMapOverlayPathsTraveledSelected


    /**
     * Listen map popup opened and display view all visited history button (with summary of first and last visited years).
     * 
     * This method was called from `setupDefaultMap()`.
     * 
     * @returns {undefined}
     */
    #listenMapPopupOpenDisplayViewAllVisitedHistoryLink() {
        this.#map.addEventListener('popupopen', (event) => {
            const additionalContentPlaceholder = event.popup?.getElement()?.querySelector('.additional-content-placeholder');
            if (!additionalContentPlaceholder) {
                // if not found any additional content placeholder that will be use for display view all visited history link.
                // no need to work here to reduce AJAX call.
                return ;
            }

            const popupLatLng = event.popup?.getLatLng();
            if (popupLatLng && popupLatLng.lat && popupLatLng.lng) {
                // if there is popup latitude & langitude.
                Ajax.fetchGet('HTTP/summary-visited.php?lat=' + encodeURIComponent(popupLatLng.lat) + '&lng=' + encodeURIComponent(popupLatLng.lng))
                .then((response) => {
                    if (response?.visitedPlace?.minMaxYears) {
                        let visitedHistoryLinkHTML = '<a class="pmtl-view-all-visited-history btn btn-sm btn-outline-secondary" \n\
                            data-pmtl-visit-id="' + response.visitedPlace.minMaxYears.visit_id + '" \n\
                            data-pmtl-place-id="' + response.visitedPlace.minMaxYears.topCandidate_placeId + '" \n\
                            data-pmtl-place-location-latlng="' + response.visitedPlace.minMaxYears.topCandidate_placeLocation_latLng	 + '" \n\
                            data-bs-toggle="modal" data-bs-target="#pmtl-bs-modal">';
                        visitedHistoryLinkHTML += 'View all visited history';
                        if (response.visitedPlace.minMaxYears.startYear === response.visitedPlace.minMaxYears.endYear) {
                            visitedHistoryLinkHTML += ' (' + response.visitedPlace.minMaxYears.startYear + ')';
                        } else {
                            visitedHistoryLinkHTML += ' (' + response.visitedPlace.minMaxYears.startYear + ' - ' + response.visitedPlace.minMaxYears.endYear + ')';
                        }
                        visitedHistoryLinkHTML += '</a>';
                        additionalContentPlaceholder.insertAdjacentHTML('beforeend', visitedHistoryLinkHTML);
                    }
                });
            }
        });
    }// #listenMapPopupOpenDisplayViewAllVisitedHistoryLink


    /**
     * Clear map layers and mark load selected date to `false`.
     * 
     * This method was called from `drawTimelineData()`, `TimelinePanel.closeTimelinePanel()`.
     * 
     * @param {Boolean} unmarkLoadSelectedDate Set to `true` to unmark `IndexJSObject.loadSelectedDate` variable. Set to `false` to untouch.
     */
    clearMapTimelineLayerGroup(unmarkLoadSelectedDate = true) {
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
            IndexJSObject.loadSelectedDate = false;
        }
        this.#map.invalidateSize(true);
    }// clearMapTimelineLayerGroup


    /**
     * Draw paths traveled.
     * 
     * This method was called from `Index.ajaxGetSummaryPathsTraveled()`.
     * 
     * @see LibMaps.#drawPathsTraveled()
     * @param {Object} response AJAX response.
     * @returns {undefined}
     */
    drawPathsTraveled(response) {
        // clear previously rendered before display new one.
        this.#pathsTraveledLayerGroup?.clearLayers();

        return this.#drawPathsTraveled(response);
    }// drawPathsTraveled


    /**
     * Draw timeline data on the map.
     * 
     * This method was called from `TimelinePanel.#ajaxGetTimelineData()`.
     * 
     * @param {Object} dbResult The AJAX response data that should contain `.result.items` properties.
     */
    drawTimelineData(dbResult) {
        if (typeof(this.#timelineLayerGroup) === 'object' && this.#timelineLayerGroup !== null) {
            // if there is layergroup object.
            // just clear layers to remove previous loaded date.
            this.clearMapTimelineLayerGroup(false);
        }
        this.#closePathsTraveledLayerGroup();

        if (dbResult?.result?.items) {
            this.#timelineLayerGroup = L.featureGroup([]);

            let drawn = 0;
            const timelinePaths = [];
            const timelinePathsTimes = [];
            const timelinePathsSegmentsMatchData = [];// for match timline panel item

            dbResult.result.items.forEach((item, index) => {
                if (item.activity) {
                    // if there is activity.
                    // read more at method `#drawActivityDebug()`.
                    this.#drawActivityDebug(item);
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
     * This method was called from `Index.#ajaxGetSummaryByYear()`.
     * 
     * @param {Object} visitedPlacesYear AJAX response within `.visitedPlacesYear` property.
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
                    '<p data-pmtl-segment-id="' + item.id + '" data-pmtl-visit_id="' + item.visit_id + '" data-pmtl-place-id="' + item.topCandidate_placeId + '">'
                    + '<strong class="place-title-placement place-id-' + item?.topCandidate_placeId + '">' + (item?.place_name ?? item.topCandidate_placeLocation_latLng) + '</strong>'
                    + this.#getEditPlaceNameHTML(item?.topCandidate_placeId)
                    + (item.startTime ? '<br>Latest on ' + item.startTime : '')
                    + '</p>'
                    + '<div class="additional-content-placeholder"></div>'
                    + this.#getViewOnGoogleMapsLinks(item?.topCandidate_placeLocation_latLng, item?.topCandidate_placeId),
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
     * Check if paths traveled layer group is already actived or checked.
     * 
     * This method was called from `Index.#listenClickNavSummaryDateDropdown()`.
     * 
     * @returns {Boolean} Return `true` if yes, `false` for otherwise.
     */
    isPathsTraveledLayerGroupActived() {
        return this.#map.hasLayer(this.#pathsTraveledLayerGroup);
    }// isPathsTraveledLayerGroupActived


    /**
     * Open map popups.
     * 
     * This method was called from `TimelinePanel.#listenClickTimelineItem()`, `SearchPanel.#listenClickSearchResultItem()`.
     * 
     * @param {string} segment_id The `segment_id` from DB.
     * @param {Object} options The options.
     * @param {string} options.summaryVisitedPlaces The latitude, longitude of summary visited places.
     */
    openMapPopup(segment_id, options = {}) {
        if (typeof(segment_id) !== 'string') {
            throw new Error('The argument `segment_id` must be string.');
        }
        if (typeof(options) !== 'object') {
            throw new Error('The argument `options` must be an object.');
        }

        let itemBounds, thisMarker = null;

        if ('' !== segment_id && this.#timelineItems[segment_id]) {
            // if there is argument `segment_id` which will be use for normal timeline items.
            this.#timelineItems[segment_id].fire('click');
            itemBounds = this.#timelineItems[segment_id].getBounds?.();
            thisMarker = this.#timelineItems[segment_id].getLatLng?.();
        } else if (
            typeof(options?.summaryVisitedPlaces) === 'string' && 
            '' !== options?.summaryVisitedPlaces &&
            this.#summaryVisitedPlacesItems[options.summaryVisitedPlaces]
        ) {
            // if there is an option to use with summary visited places.
            this.#summaryVisitedPlacesItems[options.summaryVisitedPlaces].fire('click');
            itemBounds = this.#summaryVisitedPlacesItems[options.summaryVisitedPlaces].getBounds?.();
            thisMarker = this.#summaryVisitedPlacesItems[options.summaryVisitedPlaces].getLatLng?.();
        }// endif;

        // trying to bound to marker.
        this.#map.invalidateSize(true);
        if (itemBounds) {
            this.#map.flyToBounds(itemBounds);
        } else if (typeof(thisMarker) === 'object') {
            // if it is possible this is marker (has no `getBounds()`).
            this.#map.flyTo([thisMarker.lat, thisMarker.lng]);
        }
    }// openMapPopup


    /**
     * Setup default map.
     * 
     * This method was called from `Index.#setupDefaultMap()`.
     * 
     * @param {Object} summaryVisitedPlaces The AJAX result of summary visited places. This object must contain `items` property to display markers.
     * @returns {null|undefined}
     */
    setupDefaultMap(summaryVisitedPlaces = {}) {
        if (true === IndexJSObject.defaultMapsLoaded) {
            return null;
        }

        let mapZoom = 5;// default zoom for small screen.
        if (window.innerWidth >= 500 && window.innerHeight >= 600) {
            mapZoom = 6;
        }
        const defaultMapCenter = IndexJSObject.defaultMapCenter;

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

        this.#pathsTraveledLayerGroup = L.layerGroup([]);

        const baseLayer = {
            'OpenStreetMap': mapLayer,
            'Sattellite view': sattelliteLayer,
        };
        let overlayLayer = {};
        overlayLayer[this.#pathsTraveledLayerName] = this.#pathsTraveledLayerGroup;
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
        this.#layerControl = L.control.layers(baseLayer, overlayLayer, {
            sortLayers: true,
        }).addTo(this.#map);

        // display summary visited places. ------------------------------
        this.#drawSummaryVisitedPlaces(summaryVisitedPlaces);
        // end display summary visited places. --------------------------

        this.#listenMapOverlayPathsTraveledSelected();
        this.#listenMapPopupOpenDisplayViewAllVisitedHistoryLink();
    }// setupDefaultMap


    /**
     * Update the map. This is recommend to do after container size changed.
     * 
     * @link https://leafletjs.com/reference.html#map-invalidatesize Document.
     * @returns {undefined}
     */
    updateMap() {
        this.#map.invalidateSize(true);
    }// updateMap


}// LibMaps
