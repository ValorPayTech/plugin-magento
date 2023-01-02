const express = require("express");
require("express-group-routes");
const path = require("path");

const AuthMiddleware = require("../Middleware/AuthMiddleware");
const getPaymentData = require("../Controllers/paymentController");
const mainRouter = express.Router();
mainRouter.route("/").get((req, res) => {
  res.send("test api mysql side");
});

mainRouter.route("/payment").post(getPaymentData);
// mainRouter.use(AuthMiddleware);

module.exports = mainRouter;
