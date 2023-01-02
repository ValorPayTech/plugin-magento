class Util {
  constructor() {
    this.statusCode = null;
    this.type = null;
    this.data = null;
    this.message = null;
  }

  /**
   * Set success message and data to deliver to user
   *
   * @param {number} statusCode http status code
   * @param {string} message message to user
   * @param {mixed} data may be array, string, object
   * @return {void}
   */
  setSuccess(statusCode, message, data) {
    this.statusCode = statusCode;
    this.message = message;
    this.data = data;
    this.type = "success";
    return this;
  }

  /**
   * Set error message to deliver to user
   *
   * @param {number} statusCode http status code
   * @param {string} message message to user
   * @return {void}
   */
  setError(statusCode, message) {
    this.statusCode = statusCode;
    this.message = message;
    this.type = "error";
  }

  /**
   * Set success message and data to deliver to user
   *
   * @param {Object} http response object
   * @return {json}
   */
  send(res) {
    if (this.type === "success") {
      const result = {
        status: this.type,
        message: this.message,
        ...this,
      };

      if (Array.isArray(this.data)) {
        result.count = this.data.length;
      }
      result.data = this.data;

      try {
        return res.status(this.statusCode).json(result);
      } catch (err) {

      }
    }

    try {
      return res.status(this.statusCode).json({
        status: this.type,
        message: this.message,
      });
    } catch (err) {

    }
  }

  getCurrentDate() {
    var currentDate = new Date();

    return (
      currentDate.getFullYear() +
      "-" +
      (currentDate.getMonth() + 1) +
      "-" +
      currentDate.getDate()
    );
  }
}

module.exports = Util;
