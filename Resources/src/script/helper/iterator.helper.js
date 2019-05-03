export default class Iterator {

    /**
     * This callback is displayed as a global member.
     * @callback ObjectIterateCallback
     * @param {value} value
     * @param {key} key
     */

    /**
     * Iterates over an object
     *
     * @param {Array|Object} source
     * @param {ObjectIterateCallback} callback
     *
     * @returns {*}
     */
    static iterate(source, callback) {
        if (source instanceof Map) {
            return source.forEach(callback);
        }

        if (Array.isArray(source)) {
            return source.forEach(callback);
        }

        if (source instanceof FormData) {
            for(var entry of source.entries()) {
                callback(entry[1], entry[0]);
            }
            return;
        }

        if (source instanceof Object) {
            return Object.entries(source).forEach(entry => callback(entry[1], entry[0]));
        }

        throw new Error(`The element type ${typeof source} is not iterable!`);
    }
}
