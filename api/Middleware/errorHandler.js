const HttpStatus = require("http-status-codes");
const logger = require("../Config/winston");

class errorHandler{
    /**
     * NOT_FOUND(404) middleware to catch error response
     *
     * @param  {Object}   req
     * @param  {Object}   res
     * @param  {Function} next
     */
     static async notFoundErrorHandler(req, res, next) {
        res.status(HttpStatus.StatusCodes.NOT_FOUND)
            .json({
                error: {
                    code: HttpStatus.StatusCodes.NOT_FOUND,
                    message: HttpStatus.getStatusText(HttpStatus.StatusCodes.NOT_FOUND)
                }
            });
    }

    /**
     * METHOD_NOT_ALLOWED(405) middleware to catch error response.
     * It should be placed at the very bottom of the middleware stack.
     *
     * @param {Object} req
     * @param {Object} res
     */
     static async methodNotAllowed(req, res) {
        res.status(HttpStatus.StatusCodes.METHOD_NOT_ALLOWED).json({
            error: {
                code: HttpStatus.StatusCodes.METHOD_NOT_ALLOWED,
                message: HttpStatus.getStatusText(HttpStatus.StatusCodes.METHOD_NOT_ALLOWED)
            }
        });
    }

    /**
     * Generic error response middleware
     *
     * @param  {Object}   err
     * @param  {Object}   req
     * @param  {Object}   res
     * @param  {Function} next
     */
     static async genericErrorHandler(err, req, res, next) {
        logger.error(err);
        res.status(err.status || HttpStatus.StatusCodes.INTERNAL_SERVER_ERROR)
            .json({
                error: {
                    code: err.code || HttpStatus.StatusCodes.INTERNAL_SERVER_ERROR,
                    message: err.message || HttpStatus.getStatusText(HttpStatus.StatusCodes.INTERNAL_SERVER_ERROR)
                }
            });
    }
}

module.exports = errorHandler;