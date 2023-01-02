const http = require('http');
console.log(`Max HTTP Header size is ${http.maxHeaderSize}`);
const dotenv = require('dotenv');
dotenv.config();

const app = require('./app');

const server = http.createServer(app);

const port = process.env.PORT || 7000;


server.listen(port, console.log('app is running'));

console.log(server.address());