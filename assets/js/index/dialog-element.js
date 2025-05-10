/**
 * JS for the dialog element on index page.
 */


class DialogElement {


    /**
     * @type {String} Bootstrap dialog ID.
     */
    #bootstrapDialogId = 'pmtl-bs-modal';


    /**
     * @type {Index} The `Index` class.
     */
    #Index;


    /**
     * @type {Boolean} Mark that dialog is opened or not. If value is `true` means it is currently opened, `false` for otherwise.
     */
    #isOpened = false;


    /**
     * @type {String} ID of loading message in the dialog content.
     */
    #loadingMessageId = 'pmtl-bs-modal-loading';


    /**
     * Dialog element constructor.
     * 
     * @param {Index} Index The `Index` class.
     */
    constructor(Index) {
        if (typeof(Index) === 'object') {
            this.#Index = Index;
        }
    }// constructor


    /**
     * Listen dialog on show and hide and mark `#isOpened` property.
     * 
     * This method was called from `init()`.
     * 
     * @returns {undefined}
     */
    #listenShowHideDialog() {
        const bsModal = document.getElementById(this.#bootstrapDialogId);

        bsModal.addEventListener('shown.bs.modal', (event) => {
            this.#isOpened = true;
        });
        bsModal.addEventListener('hidden.bs.modal', (event) => {
            this.#isOpened = false;
        });
    }// #listenShowHideDialog


    /**
     * Close the dialog.
     * 
     * This method must be able to call from outside this class.
     * 
     * @param {Boolean} removeContents Set to `true` to remove content after dialog is closed, or set to `false` to not remove them. Default is `false`. 
     * @returns {undefined}
     */
    closeDialog(removeContents = false) {
        const bsModal = document.getElementById(this.#bootstrapDialogId);
        if (true === removeContents) {
            // event listener must listen on HTML element not Bootstrap object.
            bsModal.addEventListener('hidden.bs.modal', (event) => {
                this.removeDialogContents();
            });
        }

        const bsModalObj = bootstrap.Modal.getInstance(bsModal);

        bsModalObj?.hide();
    }// closeDialog


    /**
     * @type {String} Get Bootstrap dialog ID.
     */
    get bootstrapDialogId() {
        return this.#bootstrapDialogId;
    }// bootstrapDialogId


    /**
     * @type {Boolean} Get status of dialog that it is currently opened or not.
     */
    get isOpened() {
        return this.#isOpened;
    }// isOpened


    /**
     * Initialize the class.
     * 
     * @returns {undefined}
     */
    init() {
        this.#listenShowHideDialog();
    }// init


    /**
     * Remove dialog contents including title and set loading message to displayed.
     * 
     * This method was called from `closeDialog()`.
     * This method must be able to call from outside this class.
     * 
     * @returns {undefined}
     */
    removeDialogContents() {
        const bsModal = document.getElementById(this.#bootstrapDialogId);
        const modalBody = bsModal.querySelector('.modal-body');
        const modalTitle = bsModal.querySelector('.modal-title');
        const loadingP = document.getElementById(this.#loadingMessageId);

        modalTitle.textContent = '';

        const nodes = modalBody.childNodes;
        // remove everything except loading element. ----------------------
        // remove element nodes first.
        nodes.forEach((elm) => {
            if (elm.nodeType === Node.ELEMENT_NODE && elm.id !== this.#loadingMessageId) {
                elm.remove();
            }
        });
        // then remove non-element nodes.
        nodes.forEach((elm) => {
            if (elm.nodeType !== Node.ELEMENT_NODE) {
                elm.parentNode.removeChild(elm);
            }
        });
        // end remove everything except loading element. ------------------

        loadingP.classList.remove('d-none');
    }// removeDialogContents


    /**
     * Set dialog contents.
     * 
     * This method must be able to call from outside this class.
     * 
     * @param {String|null} title The dialog title. Set to `null` to skip this.
     * @param {String|null} body The dialog body. Set to `null` to skip this.
     * @returns {undefined}
     */
    setDialogContents(title = null, body = null) {
        if (typeof(title) !== 'string' && title !== null) {
            throw new Error('The argument `title` must be string or `null`.');
        }
        if (typeof(body) !== 'string' && body !== null) {
            throw new Error('The argument `body` must be string or `null`.');
        }

        const bsModal = document.getElementById(this.#bootstrapDialogId);

        if (title !== null) {
            // if title argument was set.
            // set dialog's title.
            const modalTitle = bsModal.querySelector('.modal-title');
            modalTitle.textContent = title;
        }

        if (body !== null) {
            // if body argument was set.
            // hide loading text.
            const loadingP = document.getElementById(this.#loadingMessageId);
            if (loadingP) {
                loadingP.classList.add('d-none');
            }

            // set dialog's body.
            const modalBody = bsModal.querySelector('.modal-body');
            modalBody.insertAdjacentHTML('beforeend', body);
        }// endif; body argument was set.
    }// setDialogContents


}// DialogElement