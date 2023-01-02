const btoa = require("btoa");
const atob = require("atob");
const { v4: uuidv4 } = require("uuid");
const Guid = uuidv4();
const moment = require("moment");
const Util = require("../Utils/Utils");
const util = new Util();
const { salesPayment } = require("../Services/valorPayment");

/**
     * valor sale Payment
     *
     * @param {Object} request
     * @param {Object} response
     * @return {json} json response
*/

const getPaymentData = async (req, res) => {
    const { appid, appkey, epi, txn_type, amount, phone, email, uid, tax, ip, surchargeIndicator, surchargeAmount, address1, address2, city, state, zip, billing_country, shipping_country, cardnumber, status, cvv, cardholdername, expirydate } = req.body;

    const paramentersData = {
        appid: appid,
        appkey: appkey,
        epi: epi,
        txn_type: txn_type,
        amount: amount,
        phone: phone,
        email: email,
        uid: uid,
        tax: tax,
        ip: ip,
        surchargeIndicator: surchargeIndicator,
        surchargeAmount: surchargeAmount,
        address1: address1,
        address2: address2,
        city: city,
        state: state,
        zip: zip,
        billing_country: billing_country,
        shipping_country: shipping_country,
        cardnumber: cardnumber,
        status: status,
        cvv: cvv,
        cardholdername: cardholdername,
        expirydate: expirydate
    };

    const salesPaymentResult = await salesPayment(paramentersData);
    
    if (salesPaymentResult.error_no == "S00") {
        util.setSuccess(200, 'Success payment', salesPaymentResult);
    } else {
        util.setError(404, salesPaymentResult, undefined);
    }
    return util.send(res);
};

module.exports = getPaymentData;