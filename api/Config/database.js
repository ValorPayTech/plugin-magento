const Sequelize = require("sequelize");
const fs = require("fs");
const path = require("path");
const _ = require("lodash");

const sequelize = new Sequelize({
  dialect: process.env.DB_DIALECT,
  host: process.env.DB_HOST,
  username: process.env.DB_USER,
  password: process.env.DB_PASSWORD,
  database: process.env.DB_NAME,
  multipleStatements: true,
  define: {
    timestamps: false,
  },
  pool: {
    max: 100, //maximum number of connections permissible in a pool
    min: 0, //minimum number of connections permissible in a pool
    acquire: 30000, //maximum time, in terms of milliseconds, that a connection can be held idly before being released
    idle: 10000, //maximum time, in terms of milliseconds, that the pool seeks to make the connection before an error message pops up on screen
  },
});

sequelize
  .authenticate()
  .then(() => {
    console.log("Connection has been established successfully.");
  })
  .catch((err) => {
    console.error("Unable to connect to the database:", err);
  });

const models = {};

const modelsDir = path.normalize(`${__dirname}/../Models`);

fs.readdirSync(modelsDir)
  .filter((file) => file.indexOf(".") !== 0 && file.indexOf(".map") === -1)
  .forEach((file) => {
    // console.info(`Loading model file ${file}`);
    // const model = sequelize.import(path.join(`${__dirname}/../Models`, file));
    const model = require(path.join(`${__dirname}/../Models`, file))(
      sequelize,
      Sequelize.DataTypes
    );
    models[model.name] = model;
  });

Object.keys(models).forEach((modelName) => {
  if (models[modelName].associate) {
    models[modelName].associate(models);
  }
});

sequelize
  .sync()
  .then(() => {
    // { force: true }
    console.info("Database synchronized");
  })
  .catch((err) => {
    console.error("An error occured %j", err);
  });

module.exports = _.extend(
  {
    sequelize,
    Sequelize,
  },
  models
);
