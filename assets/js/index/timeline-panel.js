/**
 * JS for Timeline panel on index page.
 */


class TimelinePanel {


    /**
     * @type {Index}
     */
    #Index;


    /**
     * @type {LibMaps}
     */
    #LibMaps;


    /**
     * @var {String} openTimelinePanelLinkId Open timeline panel link ID.
     */
    #openTimelinePanelLinkId = 'pmtl-open-timeline-panel';


    /**
     * @var {String} #timelineDateInputId Timeline date input ID.
     */
    #timelineDateInputId = 'pmtl-timeline-control-date-input';


    /**
     * @var {String} #timelineItemLinkClass The timeline item link (action) class name.
     */
    #timelineItemLinkClass = 'pmtl-timeline-data-match-map-link';


    /**
     * @var {String} #timelinePanelContentPlaceholderId Timeline content placeholder ID.
     */
    #timelinePanelContentPlaceholderId = 'pmtl-timeline-panel-content-placeholder';


    /**
     * @var {String} #timelinePanelId Timeline panel ID.
     */
    #timelinePanelId = 'pmtl-timeline-panel';


    /**
     * Timeline panel constructor.
     * 
     * @param {LibMaps} LibMaps The `LibMaps` class.
     * @param {Index} Index The `Index` class.
     */
    constructor(LibMaps, Index) {
        if (typeof(LibMaps) === 'object') {
            this.#LibMaps = LibMaps;
        }
        if (typeof(Index) === 'object') {
            this.#Index = Index;
        }
    }// constructor


    /**
     * AJAX get timeline data.
     * 
     * This method was called from `#listenEventsOnDateInput()`.
     * 
     * @param {String} selectedDate Selected date.
     */
    #ajaxGetTimelineData(selectedDate) {
        const timelineContentPlaceholder = document.getElementById(this.#timelinePanelContentPlaceholderId);
        timelineContentPlaceholder.innerHTML = '<p>Loading &hellip;</p>';

        return Ajax.fetchGet(appBasePath + '/HTTP/timeline-by-date.php?date=' + encodeURIComponent(selectedDate))
        .then((response) => {
            loadSelectedDate = selectedDate;
            this.#LibMaps.drawTimelineData(response);
            this.#displayTimelineData(response);
            return Promise.resolve(response);
        });
    }// #ajaxGetTimelineData


    /**
     * Display timeline data. This will not draw anything on the maps. To draw data on the maps, use `LibMaps.drawTimelineData()`.
     * 
     * This method was called from `#ajaxGetTimelineData()`.
     * 
     * @param {Object} response 
     */
    #displayTimelineData(response) {
        const thisClass = this;
        const timelineContentPlaceholder = document.getElementById(this.#timelinePanelContentPlaceholderId);
        let listResult = '';
        let hasResult = false;

        /**
         * Get start and end date/time.
         * 
         * @param {Object} item 
         * @returns {Array}
         */
        function getStartEndDateTime(item) {
            let startTime = '';
            let endTime = '';
            const inputDate = document.getElementById(thisClass.#timelineDateInputId);
            const selectedDate = new Date(inputDate?.value);

            if (item?.startTime) {
                const startDate = new Date(item.startTime);
                if (Utils.formatDate(selectedDate) == Utils.formatDate(startDate)) {
                    startTime = Utils.formatTimeHM(startDate);
                } else {
                    startTime = Utils.formatDate(startDate) + ' ' + Utils.formatTimeHM(startDate);
                }
            }// endif;
            if (item?.endTime) {
                const endDate = new Date(item.endTime);
                if (Utils.formatDate(selectedDate) == Utils.formatDate(endDate)) {
                    endTime = Utils.formatTimeHM(endDate);
                } else {
                    endTime = Utils.formatDate(endDate) + ' ' + Utils.formatTimeHM(endDate);
                }
            }// endif;

            return [startTime, endTime];
        }// getStartEndDateTime

        if (response?.result?.items) {
            listResult = '<ul class="segment-list">';
            response.result.items.forEach((item, index) => {
                if (item.visit) {
                    // if there is `visit` property.
                    hasResult = true;
                    let startTime, endTime;
                    [startTime, endTime] = getStartEndDateTime(item);

                    listResult += '<li id="segment-id-' + item.id + '-' + String(index) + '" class="is-visit">'
                        + '<h6 class="m-0"><a class="' + this.#timelineItemLinkClass + ' place-title-placement place-id-' + item?.visit?.topCandidate_placeId + '" data-segment-id="' + item.id + '-' + String(index) + '">' 
                            + (item?.visit?.place_name ?? item.visit.topCandidate_placeLocation_latLng) 
                        + '</a></h6>'
                        + (
                            (startTime !== '' || endTime !== '' ? '<div class="text-secondary">' : '')
                            + (startTime === '' && endTime !== '' ? '<i class="fa-solid fa-arrow-right" title="Continue from previous day"></i> ' : '')
                            + (startTime !== '' ? startTime : '')
                            + (startTime !== '' && endTime !== '' ? ' - ' : '')
                            + (endTime !== '' ? endTime : '')
                            + (startTime !== '' && endTime === '' ? ' <i class="fa-solid fa-arrow-right" title="Continue to next day"></i>' : '')
                            + (startTime !== '' || endTime !== '' ? '</div>' : '')
                        );
                    if (item.visit?.subVisits && Array.isArray(item.visit.subVisits) && item.visit.subVisits.length > 0) {
                        // if there is `subVisits`.
                        let subVisitResult = '<ul class="sub-visit-list">';
                        item.visit.subVisits.forEach((eachSubV) => {
                            subVisitResult += '<li id="segment-id-' + item.id + '-' + String(index) + '-' + eachSubV.visit_id + '" class="is-visit">'
                            subVisitResult += '<h6 class="m-0"><a class="' + this.#timelineItemLinkClass + ' place-title-placement place-id-' + eachSubV?.topCandidate_placeId + '" data-segment-id="' + item.id + '-' + String(index) + '">' 
                                + (eachSubV?.place_name ?? eachSubV.topCandidate_placeLocation_latLng) 
                            + '</a></h6>'
                            const latLngArray = MapsUtil.convertLatLngString(eachSubV.topCandidate_placeLocation_latLng);
                            const googleMapsURL = MapsUtil.buildGoogleMapsSearchURL(latLngArray.join(','), eachSubV.topCandidate_placeId);
                            const googleMapsURLNoPlaceId = MapsUtil.buildGoogleMapsSearchURL(latLngArray.join(','));
                            subVisitResult += '<small class="text-secondary">'
                                + '<a href="' + googleMapsURL + '" target="googlemaps">View on Google Maps</a>'
                                + ' <a href="' + googleMapsURLNoPlaceId + '" target="googlemaps" title="View by latitude, longitude only"><i class="fa-solid fa-map-pin"></i></a>'
                                + '</small>';
                            subVisitResult += '</li>';
                        });
                        subVisitResult += '</ul>';
                        listResult += subVisitResult;
                    }
                    listResult +=  '</li>';
                }// endif `visit` property.

                if (item.timelinepath && Array.isArray(item.timelinepath) && item.timelinepath.length > 0) {
                    // if there is `timelinepath` property.
                    const timelinePathsTimes = [];
                    let startTime, endTime;

                    // build timeline paths times to get min, max.
                    timelinePathsTimes.push.apply(timelinePathsTimes, item.timelinepath.map((tlp) => {
                        const tlpDate = new Date(tlp.time);
                        return parseInt(tlpDate.getTime());
                    }));
                    const tmpItem = {
                        startTime: new Date(Math.min(...timelinePathsTimes)),
                        endTime: new Date(Math.max(...timelinePathsTimes)),
                    };

                    [startTime, endTime] = getStartEndDateTime(tmpItem);
                    if (startTime !== '' || endTime !== '') {
                        // if there is min(start time) or max(end time) from timeline.
                        hasResult = true;

                        listResult += '<li id="segment-id-' + item.id + '-' + String(index) + '" class="is-travel">'
                            + '<h6 class="m-0"><a class="' + this.#timelineItemLinkClass + '" data-segment-id="' + item.id + '-' + String(index) + '">Travel</a></h6>'
                            + (
                                (startTime !== '' || endTime !== '' ? '<div class="text-secondary">' : '')
                                + (startTime === '' && endTime !== '' ? '<i class="fa-solid fa-arrow-right" title="Continue from previous day"></i> ' : '')
                                + (startTime !== '' ? startTime : '')
                                + (startTime !== '' && endTime !== '' ? ' - ' : '')
                                + (endTime !== '' ? endTime : '')
                                + (startTime !== '' && endTime === '' ? ' <i class="fa-solid fa-arrow-right" title="Continue to next day"></i>' : '')
                                + (startTime !== '' || endTime !== '' ? '</div>' : '')
                            )
                            + '</li>';
                    }// endif; there is start or end time from timeline.
                }// endif; `timelinepath` property.
            });// endForeach; end iteration response result.
            listResult += '</ul>';

            if (false === hasResult) {
                listResult = '';
            }
        }// endif; there is response result from AJAX.

        if ('' !== listResult) {
            timelineContentPlaceholder.innerHTML = listResult;
        } else {
            timelineContentPlaceholder.innerHTML = '<p><em>There is no timeline data for this date.</em></p>';
        }
    }// #displayTimelineData


    /**
     * Listen on click next/previous date and set date after calculated then trigger enter.
     * 
     * This method was called from `init()`.
     */
    #listenClickNextPrevDate() {
        document.addEventListener('click', (event) => {
            let thisTarget = event.target;
            if (thisTarget.closest('button')) {
                thisTarget = thisTarget.closest('button');
            }

            const dateInput = document.getElementById(this.#timelineDateInputId);
            const dateInputDateObj = new Date(dateInput.value);

            /**
             * Trigger enter event on the input date.
             * 
             * @returns {undefined}
             */
            function triggerEnterEvent() {
                const event = new KeyboardEvent('keydown', {
                    bubbles: true,
                    code: 'Enter',
                    key: 'Enter',
                });
                dateInput.dispatchEvent(event);
            }// triggerEnterEvent

            if (thisTarget.getAttribute('id') === 'pmtl-timeline-control-date-previous') {
                // if clicking on previous.
                event.preventDefault();
                dateInputDateObj.setDate(dateInputDateObj.getDate() - 1);
                dateInput.value = Utils.formatDate(dateInputDateObj);
                triggerEnterEvent();
            } else if (thisTarget.getAttribute('id') === 'pmtl-timeline-control-date-next') {
                // if clicking on next.
                event.preventDefault();
                dateInputDateObj.setDate(dateInputDateObj.getDate() + 1);
                dateInput.value = Utils.formatDate(dateInputDateObj);
                triggerEnterEvent();
            }
        });
    }// #listenClickNextPrevDate


    /**
     * Listen on click on select a date menu to show/hide timeline panel.
     * 
     * This method was called from `init()`.
     */
    #listenClickOpenTimelinePanel() {
        const selectDateMenuLink = document.getElementById(this.#openTimelinePanelLinkId);
        const timelinePanel = document.getElementById(this.#timelinePanelId);

        if (selectDateMenuLink) {
            selectDateMenuLink.addEventListener('click', (event) => {
                event.preventDefault();
                if (selectDateMenuLink.classList.contains('active')) {
                    // if timeline panal is already opened.
                    this.closeTimelinePanel();
                } else {
                    // if timeline panel is not opened.
                    // clear all actived items on navmenu.
                    this.#Index.clearAllActiveNavItems();

                    selectDateMenuLink.classList.add('active');
                    timelinePanel?.classList?.add('show');
                    this.#openPanelLoadTimelineData();
                }
            });
        }
    }// #listenClickOpenTimelinePanel


    /**
     * Listen on click timeline panel control buttons.
     * 
     * This method was called from `init()`.
     */
    #listenClickPanelControlButtons() {
        const timelinePanel = document.getElementById(this.#timelinePanelId);
        const timelinePanelCloseBtn = document.getElementById('pmtl-timeline-panel-close-btn');
        const timelinePanelMinMaxBtn = document.getElementById('pmtl-timeline-panel-maxmin-btn');

        if (timelinePanelCloseBtn) {
            timelinePanelCloseBtn.addEventListener('click', () => {
                this.closeTimelinePanel();
            });
        }

        if (timelinePanelMinMaxBtn) {
            timelinePanelMinMaxBtn.addEventListener('click', () => {
                if (timelinePanel.classList.contains('is-max')) {
                    // if is already maximize.
                    timelinePanel.classList.remove('is-max');
                    timelinePanel.style.height = '30px';
                } else {
                    // if minimize.
                    timelinePanel.classList.add('is-max');
                    timelinePanel.style.height = '100%';
                }
            });
        }
    }// #listenClickPanelControlButtons


    /**
     * Listen click on timeline item and trigger click on the map.
     * 
     * This method was called from `init()`.
     */
    #listenClickTimelineItem() {
        document.addEventListener('click', (event) => {
            let thisTarget = event?.target;
            if (thisTarget?.closest('.' + this.#timelineItemLinkClass)) {
                thisTarget = thisTarget?.closest('.' + this.#timelineItemLinkClass);
                event.preventDefault();
                const segment_id = thisTarget.dataset.segmentId;
                this.#LibMaps.openMapPopup(segment_id);
            }
        });
    }// #listenClickTimelineItem


    /**
     * Listen events on the date input and make ajax call to get timeline data for selected date.
     * 
     * This method was called from `init()`.
     */
    #listenEventsOnDateInput() {
        /**
         * Delay input.
         * 
         * @link https://stackoverflow.com/a/1909508/128761 Original source code.
         * @param {Callback} fn 
         * @param {Number} ms 
         * @returns 
         */
        function delay(fn, ms) {
            let timer = 0;
            return function (...args) {
                clearTimeout(timer);
                timer = setTimeout(fn.bind(this, ...args), ms || 0);
            }
        }// delay

        const timelineDateInput = document.getElementById(this.#timelineDateInputId);

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' && event?.target?.getAttribute('id') === this.#timelineDateInputId) {
                event.preventDefault();
                if (timelineDateInput.value !== loadSelectedDate) {
                    // if not yet loaded.
                    // make ajax call to get timeline data.
                    this.#ajaxGetTimelineData(timelineDateInput.value);
                }
            }
        }, false);

        document.addEventListener('keyup', delay(
            (event) => {
                if (event?.target?.getAttribute('id') === this.#timelineDateInputId) {
                    if (event.key === 'Enter' || event.key === 'Escape') {
                        return ;
                    }

                    const kEvent = new KeyboardEvent('keydown', {
                        bubbles: true,
                        code: 'Enter',
                        key: 'Enter',
                    });
                    timelineDateInput.dispatchEvent(kEvent);
                }
            }, 
            500
        ));

        document.addEventListener('change', delay(
            (event) => {
                if (event?.target?.getAttribute('id') === this.#timelineDateInputId) {
                    const event = new KeyboardEvent('keydown', {
                        bubbles: true,
                        code: 'Enter',
                        key: 'Enter',
                    });
                    timelineDateInput.dispatchEvent(event);
                }
            }, 
            500
        ));
    }// #listenEventsOnDateInput


    /**
     * Listen on resize timeline panel and resize it.
     * 
     * This method was called from `init()`.
     */
    #listenResizeTimelinePanel() {
        const resizeEl = document.getElementById('pmtl-timeline-panel-resize');
        const panel = document.getElementById(this.#timelinePanelId);
        let myPos = 0;
        myPos = parseFloat(myPos);

        /**
         * Do resize the timeline panel.
         * 
         * @param {Object} event The event object.
         * @returns {undefined}
         */
        function resizePanel(event){
            let dy;
            if (typeof(event.touches) === 'object') {
                const touch0 = event.touches[0];
                dy = myPos - touch0.clientY;
                myPos = touch0.clientY;
            } else {
                dy = myPos - event.clientY;
                myPos = event.clientY;
            }
            const currentPanelHeight = parseInt(getComputedStyle(panel, '').height);
            panel.style.height = parseInt(currentPanelHeight + dy) + "px";
            // set the `data-` for easy debugging.
            panel.dataset.myPos = myPos;
            panel.dataset.dy = dy;
            panel.dataset.currentPanelHeight = currentPanelHeight;
        }// resizePanel

        // listen on resize for mobile.
        resizeEl.addEventListener('touchstart', (event) => {
            document.body.click();// trigger click on body to make other listener such as [close navmenu when click outside] to work.
            const touch0 = event.touches[0];
            myPos = touch0.clientY;
            panel.classList.remove('is-max');
            document.addEventListener('touchmove', resizePanel);
        }, {
            passive: true,
        });
        document.addEventListener('touchend', () => {
            document.removeEventListener('touchmove', resizePanel);
        });

        // listen on resize for PC.
        resizeEl.addEventListener('mousedown', (event) => {
            document.body.click();
            myPos = event.clientY;
            panel.classList.remove('is-max');
            document.addEventListener('mousemove', resizePanel);
        });
        document.addEventListener('mouseup', () => {
            document.removeEventListener('mousemove', resizePanel);
        });
    }// #listenResizeTimelinePanel


    /**
     * Opened panel then load timeline data.
     * 
     * This method was called from `#listenClickOpenTimelinePanel()`.
     */
    #openPanelLoadTimelineData() {
        if (false !== loadSelectedDate) {
            return null;
        }

        const timelineDateInput = document.getElementById(this.#timelineDateInputId);
        if (timelineDateInput?.value) {
            const event = new KeyboardEvent('keydown', {
                bubbles: true,
                code: 'Enter',
                key: 'Enter',
            });
            timelineDateInput.dispatchEvent(event);
        }
    }// #openPanelLoadTimelineData


    /**
     * Close timeline panel.
     * 
     * This method must be able to call from outside this class.
     */
    closeTimelinePanel() {
        const selectDateMenuLink = document.getElementById(this.#openTimelinePanelLinkId);
        const timelinePanel = document.getElementById(this.#timelinePanelId);
        selectDateMenuLink.classList.remove('active');
        timelinePanel?.classList?.remove('show');
        timelinePanel.style = '';
        // clear loaded map layers.
        this.#LibMaps.clearMapLayers();
    }// closeTimelinePanel


    /**
     * Initialize the class.
     */
    async init() {
        this.#listenClickOpenTimelinePanel();
        this.#listenResizeTimelinePanel();
        this.#listenClickPanelControlButtons();
        this.#listenEventsOnDateInput();
        this.#listenClickNextPrevDate();
        this.#listenClickTimelineItem();
    }// init


}// TimelinePanel
