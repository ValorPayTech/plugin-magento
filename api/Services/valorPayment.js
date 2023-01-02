const fetch = require("node-fetch");
const request = require("request");
const { round } = require("lodash");
const valorPaymentUrl = process.env.SANDBOX_PAYMENT_URL;

const salesPayment = async (paymentData) => {
    const appid = paymentData.appid;
    const appkey = paymentData.appkey;
    const epi = paymentData.epi;
    const txn_type = paymentData.txn_type;
    const amount = paymentData.amount;
    const phone = paymentData.phone;
    const email = paymentData.email;
    const uid = paymentData.uid;
    const tax = paymentData.tax;
    const ip = paymentData.ip;
    const surchargeIndicator = paymentData.surchargeIndicator;
    const surchargeAmount = paymentData.surchargeAmount;
    const address1 = paymentData.address1;
    const address2 = paymentData.address2;
    const city = paymentData.city;
    const state = paymentData.state;    
    const zip = paymentData.zip;
    const billing_country = paymentData.billing_country;    
    const shipping_country = paymentData.shipping_country;    
    const cardnumber = paymentData.cardnumber;    
    const status = paymentData.status;
    const cvv = paymentData.cvv;
    const cardholdername = paymentData.cardholdername;
    const expirydate = paymentData.expirydate;    

    const paramenters =  {
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
    }

    const data = await fetch(valorPaymentUrl, {method: 'POST', body: JSON.stringify(paramenters)});
    return data.json();
}

module.exports = {
    salesPayment,
};