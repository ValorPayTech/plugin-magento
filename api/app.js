const path = require("path");
const cron = require("node-cron");
const express = require("express");
const cors = require("cors");
const fileUpload = require('express-fileupload');


const app = express();
app.use(fileUpload({
  createParentPath: true
}));

app.use(cors());

const bodyParser = require('body-parser');
const routes = require("./Routes/routes");

const errorHandler = require("./Middleware/errorHandler");
const requestLogger = require("./Middleware/requestLogger");

app.use(express.json({limit: "3000mb"}));
app.use(express.urlencoded({ limit: "3000mb", extended: true }));
app.use("/v1", routes);
app.use(express.static(path.resolve(__dirname, "../build")));

// cron.schedule("*/2 * * * * *", function() {
//   let timeStamp = new Date();
//   console.log("running a task every 10 second manoj "+timeStamp);
// });

app.get("*", (req, res) => {
  res.sendFile(path.join(__dirname, "../build/index.html"));
});

app.use((error, req, res, next) => {
  res.setHeader("Access-Control-Allow-Origin", "*");
  res.setHeader("Access-Control-Allow-Methods", "OPTIONS, GET, POST, PUT, PATCH, DELETE");
  res.setHeader("Access-Control-Allow-Headers", "Content-Type, Authorization");
  res.status(404).json({
    status: false,
    message: "Please check your request",
    error: "Bed request",
  });
});

// Request logger
app.use(requestLogger);

// Error Handler
app.use(errorHandler.notFoundErrorHandler);
app.use(errorHandler.genericErrorHandler);
app.use(errorHandler.methodNotAllowed);

module.exports = app;