/**
 * JS for listing panel on index page.
 */


class ListingPanel {


    /**
     * @type {Index} The `Index` class.
     */
    #Index;


    /**
     * @type {LibMaps} The `LibMaps` class.
     */
    #LibMaps;


    /**
     * 
     * @type {String} Listing panel contents ID.
     */
    #listingPanelContentsId = 'pmtl-listing-panel-contents';


    /**
     * @type {String} Listing panel ID.
     */
    #listingPanelId = 'pmtl-listing-panel';


    /**
     * Listing panel constructor.
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
     * Listen on click listing panel control buttons.
     * 
     * This method was called from `init()`.
     */
    #listenClickPanelControlButtons() {
        const listingPanel = document.getElementById(this.#listingPanelId);
        const listingPanelMinMaxBtn = document.getElementById('pmtl-listing-panel-maxmin-btn');
        const listingPanelCloseBtn = document.getElementById('pmtl-listing-panel-close-btn');

        if (listingPanelCloseBtn) {
            listingPanelCloseBtn.addEventListener('click', (event) => {
                // dispatch event before close panel.
                const customEvent = new CustomEvent('pmtl.listingpanel.click.close', {
                    detail: {
                        parentEvent: event,
                    }
                });
                document.dispatchEvent(customEvent);
                // do close panel.
                this.closePanel();
            });
        }

        if (listingPanelMinMaxBtn) {
            listingPanelMinMaxBtn.addEventListener('click', () => {
                if (listingPanel.classList.contains('is-max')) {
                    // if is already maximize.
                    listingPanel.classList.remove('is-max');
                    listingPanel.style.height = '30px';
                } else {
                    // if minimize.
                    listingPanel.classList.add('is-max');
                    listingPanel.style.height = '100%';
                }
            });
        }
    }// #listenClickPanelControlButtons


    /**
     * Listen on resize listing panel and resize it.
     * 
     * This method was called from `init()`.
     */
    #listenResizeListingPanel() {
        const resizeEl = document.getElementById('pmtl-listing-panel-resize');
        const panel = document.getElementById(this.#listingPanelId);
        let myPos = 0;
        myPos = parseFloat(myPos);

        /**
         * Do resize the listing panel.
         * 
         * @param {Object} event The event object.
         * @returns {undefined}
         */
        function resizePanel(event){
            let dy;// different on Y axis.
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
            // set the `data-` attribute for easy debugging only.
            panel.dataset.pmtlMyPos = myPos;
            panel.dataset.pmtlDy = dy;
            panel.dataset.pmtlCurrentPanelHeight = currentPanelHeight;
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
    }// #listenResizeListingPanel


    /**
     * Update the maps.
     * 
     * This method was called from `closePanel()`, `openPanel()`.
     * 
     * @param {Int} delay The delay time before update maps. This delay must match CSS `transition`.
     * @returns {undefined}
     */
    #updateMaps(delay = 200) {
        if (isNaN(parseInt(delay)) === true) {
            delay = 200;
        }

        // slice the delay into pieces to prevent/reduce wobble effect (like a jelly shake) on the maps. this can be happened on the large screen.
        const minDelayEach = 50;
        let totalRound = 1;
        if (delay > minDelayEach) {
            totalRound = Math.floor(delay / minDelayEach);
        }

        let timeoutIDs = {};
        let round = 1;
        for (round = 1; round <= totalRound; ++round) {
            let eachDelay = minDelayEach * round;
            if (eachDelay > delay) {
                eachDelay = delay;
            }

            timeoutIDs[eachDelay] = setTimeout(() => {
                this.#LibMaps.updateMap();
                clearTimeout(timeoutIDs[eachDelay]);
            }, eachDelay);
        }// endfor;
        timeoutIDs = {};

        if ((minDelayEach * totalRound) < delay) {
            setTimeout(() => {
                this.#LibMaps.updateMap();
            }, delay);
        }
    }// #updateMaps


    /**
     * Close the listing panel.
     * 
     * This method must be able to call from outside this class.
     * 
     * @returns {undefined}
     */
    closePanel() {
        const listingPanelHTML = document.getElementById(this.#listingPanelId);
        const isOpened = listingPanelHTML.classList.contains('show');

        if (listingPanelHTML) {
            listingPanelHTML?.classList?.remove('show');
            listingPanelHTML.style = '';
        }// endif;

        if (true === isOpened) {
            // if it was opened. this is for prevent double call to this method and the map will be update twice.
            // on show or hide listing panel, maps container size changed. update them.
            this.#updateMaps();
        }// endif;
    }// closePanel


    /**
     * @type {String} Listing panel contents ID.
     */
    get listingPanelContentsId() {
        return this.#listingPanelContentsId;
    }// listingPanelContentsId


    /**
     * Initialize the class.
     * 
     * @returns {undefined}
     */
    init() {
        this.#listenClickPanelControlButtons();
        this.#listenResizeListingPanel();
    }// init


    /**
     * Open the listing panel.
     * 
     * This method must be able to call from outside this class.
     * 
     * @returns {undefined}
     */
    openPanel() {
        const listingPanelHTML = document.getElementById(this.#listingPanelId);
        const isOpened = listingPanelHTML.classList.contains('show');

        listingPanelHTML?.classList?.add('show');

        if (false === isOpened) {
            // if listing panel was NOT opened. this is for prevent double call to this method and the map will be update twice.
            // on show or hide listing panel, maps container size changed. update them.
            this.#updateMaps();
        }// endif;
    }// openPanel


    /**
     * Set panel contents.
     * 
     * This method must be able to call from outside this class.
     * 
     * @param {String|Object} contents The contents to set into listing panel contents placeholder.
     * @returns {undefined}
     */
    setPanelContents(contents) {
        if (typeof(contents) !== 'string' && typeof(contents) !== 'object') {
            throw new Error('The argument `contents` must be string or an object that supported by `appendChild()`.');
        }

        const listingPanelContents = document.getElementById(this.#listingPanelContentsId);

        listingPanelContents.innerHTML = '';

        if (typeof(contents) === 'string') {
            listingPanelContents.innerHTML = contents;
        } else if (typeof(contents) === 'object') {
            listingPanelContents.appendChild(contents);
        }
    }// setPanelContents


}// ListingPanel