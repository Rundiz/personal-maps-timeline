/**
 * AJAX helper.
 */


class Ajax {


    /**
     * `fetch` using method GET.
     * 
     * @param {string} url 
     * @returns {Promise}
     */
    static fetchGet(url) {
        return fetch(url)
        .then((response) => {
            if (!response.ok) {
                // @link https://stackoverflow.com/a/44576265/128761 Reference.
                return response.text()
                .then((text) => {
                    throw new Error(text);
                });
            }
            return response.json();
        })
        .catch((error) => {
            let errorMsg = '';
            try {
                const JSO = JSON.parse(error.message);
                if (typeof(JSO.error?.messages) === 'undefined') {
                    errorMsg = error.message;
                } else {
                    JSO.error.messages.forEach((anError) => {
                        errorMsg += '* ' + anError + "\n";
                    });
                }
            } catch(JSOError) {
                // response error is not JSON.
                errorMsg = error.message;
            }

            alert(errorMsg);
            console.error('Response error: ' + errorMsg);
            return Promise.reject(errorMsg);
        });
    }// fetchGet


    /**
     * `fetch` using method POST.
     * 
     * @param {string} url
     * @param {object} options The `fetch` options.
     * @returns {Promise}
     */
    static fetchPost(url, options = {}) {
        if (typeof(options) !== 'object') {
            options = {};
        }

        const defaults = {
            'method': 'POST'
        };
        options = {...defaults, ...options};
        options.method = 'POST';

        return fetch(url, options)
        .then((response) => {
            if (!response.ok) {
                // @link https://stackoverflow.com/a/44576265/128761 Reference.
                return response.text()
                .then((text) => {
                    throw new Error(text);
                });
            }
            return response.json();
        })
        .catch((error) => {
            let errorMsg = '';
            try {
                const JSO = JSON.parse(error.message);
                if (typeof(JSO.error?.messages) === 'undefined') {
                    errorMsg = error.message;
                } else {
                    JSO.error.messages.forEach((anError) => {
                        errorMsg += '* ' + anError + "\n";
                    });
                }
            } catch(JSOError) {
                // response error is not JSON.
                errorMsg = error.message;
            }

            alert(errorMsg);
            console.error('Response error: ' + errorMsg);
            return Promise.reject(errorMsg);
        });
    }// fetchPost


}